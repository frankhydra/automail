<?php

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent through Laravel's own mailer (MAIL_MAILER in .env) - the same channel
 * as password-reset and email-verification mail. This is a transactional
 * system email to a person, not a marketing send to a Contact, so it does NOT
 * go through EmailProviderInterface/EmailDeliveryService; mixing the two would
 * blur an important architectural line (see the milestone notes).
 */
class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrganizationInvitation $invitation, public string $acceptUrl)
    {
    }

    public function build(): self
    {
        return $this->subject("You've been invited to join {$this->invitation->organization->name} on AutoMail")
            ->view('emails.team-invitation');
    }
}
