<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sending_identity' => $this->whenLoaded('sendingIdentity', fn () => [
                'id' => $this->sendingIdentity->id,
                'from_email' => $this->sendingIdentity->from_email,
            ]),
            'recipients_count' => $this->whenCounted('recipients'),
            // Only computed when this campaign was loaded with recipients (the "show"
            // endpoint) - kept off the list endpoint so listing campaigns stays a cheap query.
            'stats' => $this->when($this->relationLoaded('recipients'), function () {
                $statuses = $this->recipients->countBy('status');

                return [
                    'sent' => $statuses->get('sent', 0),
                    'failed' => $statuses->get('failed', 0),
                    'bounced' => $statuses->get('bounced', 0),
                    'complained' => $statuses->get('complained', 0),
                    'suppressed' => $statuses->get('suppressed', 0),
                    'skipped' => $statuses->get('skipped', 0),
                    'pending' => $statuses->get('pending', 0),
                    'opened' => $this->recipients->whereNotNull('opened_at')->count(),
                    'clicked' => $this->recipients->whereNotNull('clicked_at')->count(),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
