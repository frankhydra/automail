<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Contact;
use Throwable;

/**
 * Decides which automations a contact change should start. Called from the
 * Contact model's events. It must never break saving a contact, so any problem
 * is reported and swallowed here.
 */
class AutomationTrigger
{
    public static function contactCreated(Contact $contact): void
    {
        $tags = $contact->tagList();

        self::fire($contact, function (Automation $automation) use ($tags) {
            return $automation->trigger_type === 'contact_added'
                || ($automation->trigger_type === 'tag_added' && in_array($automation->triggerTag(), $tags, true));
        });
    }

    public static function tagsChanged(Contact $contact, string $oldTags): void
    {
        $added = array_diff(Contact::splitTags((string) $contact->tags), Contact::splitTags($oldTags));

        if ($added === []) {
            return;
        }

        self::fire($contact, function (Automation $automation) use ($added) {
            return $automation->trigger_type === 'tag_added'
                && in_array($automation->triggerTag(), $added, true);
        });
    }

    protected static function fire(Contact $contact, callable $matches): void
    {
        // Only people who can actually receive marketing mail enter a journey.
        if ($contact->status !== 'subscribed') {
            return;
        }

        try {
            $engine = app(AutomationEngine::class);

            Automation::where('organization_id', $contact->organization_id)
                ->where('status', 'active')
                ->get()
                ->filter($matches)
                ->each(fn (Automation $automation) => $engine->enroll($automation, $contact));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
