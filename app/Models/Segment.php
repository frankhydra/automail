<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'rules',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Every field/operator combination a segment rule can use. Used both to
     * render the rule-builder UI and to validate submitted rules server-side.
     *
     * @return array<string, list<string>>
     */
    public static function availableOperators(): array
    {
        return [
            'status' => ['equals', 'not_equals'],
            'tag' => ['has', 'not_has'],
            'email_domain' => ['equals', 'not_equals'],
            'created_at' => ['before', 'after'],
        ];
    }

    /**
     * The contacts in $organization that match every rule (AND). SQL handles
     * status/email_domain/created_at directly; tag rules are applied in PHP
     * afterwards via Contact::hasAnyTag(), since tags are a comma-separated
     * column and exact-tag matching (not substring) matters - see Contact model.
     */
    public function matchingContacts(Organization $organization): Collection
    {
        $query = $organization->contacts();
        $tagRules = [];

        foreach ($this->rules ?? [] as $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;
            $value = $rule['value'] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($field === 'status' && $operator === 'equals') {
                $query->where('status', $value);
            } elseif ($field === 'status' && $operator === 'not_equals') {
                $query->where('status', '!=', $value);
            } elseif ($field === 'email_domain' && $operator === 'equals') {
                $query->where('email', 'like', '%@'.ltrim($value, '@'));
            } elseif ($field === 'email_domain' && $operator === 'not_equals') {
                $query->where('email', 'not like', '%@'.ltrim($value, '@'));
            } elseif ($field === 'created_at' && $operator === 'before') {
                $query->whereDate('created_at', '<', $value);
            } elseif ($field === 'created_at' && $operator === 'after') {
                $query->whereDate('created_at', '>', $value);
            } elseif ($field === 'tag') {
                // Tags are a comma-separated column, so exact-tag matching happens
                // in PHP below via Contact::hasAnyTag(), not in SQL.
                $tagRules[] = ['operator' => $operator, 'value' => $value];
            }
        }

        $contacts = $query->get();

        foreach ($tagRules as $rule) {
            $contacts = $contacts->filter(function (Contact $contact) use ($rule) {
                $has = $contact->hasAnyTag($rule['value']);

                return $rule['operator'] === 'has' ? $has : !$has;
            })->values();
        }

        return $contacts;
    }
}
