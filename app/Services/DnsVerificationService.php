<?php

namespace App\Services;

use App\Models\SendingIdentity;

class DnsVerificationService
{
    /**
     * TXT record values published at a host.
     *
     * @return list<string>
     */
    public function txtRecords(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);

        if (!is_array($records)) {
            return [];
        }

        $values = [];
        foreach ($records as $record) {
            if (isset($record['txt'])) {
                $values[] = (string) $record['txt'];
            } elseif (isset($record['entries']) && is_array($record['entries'])) {
                $values[] = implode('', $record['entries']);
            }
        }

        return $values;
    }

    /**
     * CNAME targets published at a host.
     *
     * @return list<string>
     */
    public function cnameTargets(string $host): array
    {
        $records = @dns_get_record($host, DNS_CNAME);

        if (!is_array($records)) {
            return [];
        }

        $targets = [];
        foreach ($records as $record) {
            if (isset($record['target'])) {
                $targets[] = (string) $record['target'];
            }
        }

        return $targets;
    }

    /**
     * The SPF-include host and DKIM selector for whichever provider is
     * currently active (EMAIL_PROVIDER). Falls back to the "log"/generic
     * preset for a provider with no dedicated entry (e.g. SES - see the
     * comment in config/automail.php).
     *
     * @return array{spf_include: string, dkim_selector: string}
     */
    protected function activeProviderPreset(): array
    {
        $provider = (string) config('automail.active_provider', 'log');
        $presets = config('automail.provider_presets', []);

        return $presets[$provider] ?? $presets['log'] ?? [
            'spf_include' => 'spf.example-provider.com',
            'dkim_selector' => 'automail',
        ];
    }

    /**
     * Records a customer must publish, plus the current status of each.
     *
     * @return list<array{key: string, label: string, type: string, host: string, value: string, status: string}>
     */
    public function expectedRecords(SendingIdentity $identity): array
    {
        $preset = $this->activeProviderPreset();

        // A sending identity can store its own dkim_selector (set when it was
        // created); that always wins over the provider default.
        $selector = $identity->dkim_selector ?: $preset['dkim_selector'];
        $dkimValue = config('automail.dkim_value');

        return [
            [
                'key' => 'ownership',
                'label' => 'Domain ownership',
                'type' => 'TXT',
                'host' => config('automail.ownership_host'),
                'value' => 'automail-verification='.$identity->verification_token,
                'status' => $identity->verification_status === 'verified' ? 'verified' : 'unverified',
            ],
            [
                'key' => 'spf',
                'label' => 'SPF',
                'type' => 'TXT',
                'host' => '@',
                'value' => 'v=spf1 include:'.$preset['spf_include'].' ~all',
                'status' => (string) $identity->spf_status,
            ],
            [
                'key' => 'dkim',
                'label' => 'DKIM',
                'type' => 'TXT or CNAME (as issued by your provider)',
                'host' => $selector.'._domainkey',
                'value' => $dkimValue ?: 'Use the DKIM value shown in your '.ucfirst((string) config('automail.active_provider')).' dashboard for this domain',
                'status' => (string) $identity->dkim_status,
            ],
            [
                'key' => 'dmarc',
                'label' => 'DMARC',
                'type' => 'TXT',
                'host' => '_dmarc',
                'value' => config('automail.dmarc_record'),
                'status' => (string) $identity->dmarc_status,
            ],
        ];
    }

    /**
     * Look up the live DNS for a custom-domain identity.
     *
     * @return array{ownership: bool, spf: bool, dkim: bool, dmarc: bool}
     */
    public function check(SendingIdentity $identity): array
    {
        $domain = strtolower((string) $identity->domain);

        if ($domain === '') {
            return ['ownership' => false, 'spf' => false, 'dkim' => false, 'dmarc' => false];
        }

        $preset = $this->activeProviderPreset();
        $selector = $identity->dkim_selector ?: $preset['dkim_selector'];

        return [
            'ownership' => $this->hasOwnership($domain, (string) $identity->verification_token),
            'spf' => $this->hasSpf($domain, $preset['spf_include']),
            'dkim' => $this->hasDkim($domain, $selector),
            'dmarc' => $this->hasDmarc($domain),
        ];
    }

    protected function hasOwnership(string $domain, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $expected = 'automail-verification='.$token;

        foreach ($this->txtRecords(config('automail.ownership_host').'.'.$domain) as $value) {
            if (trim($value) === $expected) {
                return true;
            }
        }

        return false;
    }

    protected function hasSpf(string $domain, string $spfInclude): bool
    {
        $include = strtolower('include:'.$spfInclude);

        foreach ($this->txtRecords($domain) as $value) {
            $value = strtolower(trim($value));
            if (str_starts_with($value, 'v=spf1') && str_contains($value, $include)) {
                return true;
            }
        }

        return false;
    }

    protected function hasDkim(string $domain, string $selector): bool
    {
        $host = $selector.'._domainkey.'.$domain;
        $values = array_merge($this->txtRecords($host), $this->cnameTargets($host));

        if ($values === []) {
            return false;
        }

        $expected = config('automail.dkim_value');

        if (empty($expected)) {
            return true; // any DKIM record at the selector
        }

        foreach ($values as $value) {
            if (str_contains(strtolower($value), strtolower((string) $expected))) {
                return true;
            }
        }

        return false;
    }

    protected function hasDmarc(string $domain): bool
    {
        foreach ($this->txtRecords('_dmarc.'.$domain) as $value) {
            if (str_starts_with(strtolower(trim($value)), 'v=dmarc1')) {
                return true;
            }
        }

        return false;
    }
}
