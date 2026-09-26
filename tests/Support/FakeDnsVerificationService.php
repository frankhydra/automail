<?php

namespace Tests\Support;

use App\Services\DnsVerificationService;

/**
 * Serves DNS answers from arrays instead of the network.
 */
class FakeDnsVerificationService extends DnsVerificationService
{
    /**
     * @param array<string, list<string>> $txt   host => TXT values
     * @param array<string, list<string>> $cname host => CNAME targets
     */
    public function __construct(public array $txt = [], public array $cname = [])
    {
    }

    public function txtRecords(string $host): array
    {
        return $this->txt[$host] ?? [];
    }

    public function cnameTargets(string $host): array
    {
        return $this->cname[$host] ?? [];
    }
}
