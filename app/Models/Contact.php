<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'status',
        'tags',
    ];

    /**
     * Get the organization that owns the contact.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Automation triggers listen to contact changes here, so every way a contact can
     * appear (manual add, CSV import, API) starts journeys without extra wiring.
     */
    protected static function booted(): void
    {
        static::created(function (Contact $contact) {
            \App\Services\AutomationTrigger::contactCreated($contact);
        });

        static::updated(function (Contact $contact) {
            if ($contact->wasChanged('tags')) {
                \App\Services\AutomationTrigger::tagsChanged($contact, (string) $contact->getOriginal('tags'));
            }
        });
    }

    /**
     * Every campaign email this contact was queued for (used for engagement stats).
     */
    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /**
     * The contact lists that this contact belongs to.
     */
    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class, 'contact_list_members')
            ->withTimestamps();
    }

    /**
     * The contact's tags as a normalized list (lowercase, trimmed, no blanks).
     * Tags are stored as a comma separated string, e.g. "VIP, newsletter".
     *
     * @return list<string>
     */
    public function tagList(): array
    {
        return self::splitTags((string) $this->tags);
    }

    /**
     * True if the contact has ANY of the tags in a comma separated filter.
     * Matching is exact per tag (so "vip" does not match "non-vip").
     */
    public function hasAnyTag(string $filter): bool
    {
        $wanted = self::splitTags($filter);

        if ($wanted === []) {
            return true; // empty filter matches everyone
        }

        return array_intersect($wanted, $this->tagList()) !== [];
    }

    /**
     * @return list<string>
     */
    public static function splitTags(string $value): array
    {
        $parts = array_map(
            fn (string $tag) => mb_strtolower(trim($tag)),
            explode(',', $value)
        );

        return array_values(array_filter($parts, fn (string $tag) => $tag !== ''));
    }
}
