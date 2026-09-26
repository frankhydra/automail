<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\SuppressionList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Public unsubscribe endpoints.
 *
 * Links are per-recipient and signed (see the "signed" middleware in routes/web.php),
 * so a link can only unsubscribe the one contact it was generated for, and only
 * inside the organization that sent the campaign.
 */
class UnsubscribeController extends Controller
{
    /**
     * Confirmation page. Nothing changes on GET, so mail scanners that pre-fetch
     * links cannot unsubscribe people by accident.
     */
    public function show(CampaignRecipient $recipient): View
    {
        $recipient->load('contact');

        return view('unsubscribe.show', [
            'maskedEmail' => $this->maskEmail($recipient->contact?->email ?? ''),
            'actionUrl' => URL::signedRoute('unsubscribe.store', ['recipient' => $recipient->id]),
        ]);
    }

    /**
     * Process the opt-out: mark the contact unsubscribed and add the address to the
     * organization's suppression list.
     */
    public function store(CampaignRecipient $recipient): View
    {
        $recipient->load(['contact', 'campaign']);

        $contact = $recipient->contact;
        $campaign = $recipient->campaign;

        if ($contact && $campaign) {
            DB::transaction(function () use ($contact, $campaign) {
                $contact->update(['status' => 'unsubscribed']);

                SuppressionList::updateOrCreate(
                    [
                        'organization_id' => $campaign->organization_id,
                        'email' => strtolower($contact->email),
                    ],
                    ['reason' => 'unsubscribed']
                );
            });
        }

        return view('unsubscribe.done', [
            'maskedEmail' => $this->maskEmail($contact?->email ?? ''),
        ]);
    }

    protected function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
