<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    public const TRIGGERS = [
        'contact_added' => 'A new contact is added',
        'tag_added' => 'A tag is added to a contact',
    ];

    protected $fillable = [
        'organization_id',
        'sending_identity_id',
        'name',
        'status',
        'trigger_type',
        'trigger_config',
    ];

    protected $casts = [
        'trigger_config' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sendingIdentity(): BelongsTo
    {
        return $this->belongsTo(SendingIdentity::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(AutomationNode::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function triggerTag(): string
    {
        return (string) ($this->trigger_config['tag'] ?? '');
    }

    public function triggerLabel(): string
    {
        if ($this->trigger_type === 'tag_added') {
            return 'Tag "'.$this->triggerTag().'" is added to a contact';
        }

        return self::TRIGGERS[$this->trigger_type] ?? $this->trigger_type;
    }

    /**
     * The steps as nested arrays, the shape the journey editor works with:
     * a list of steps, where a condition step carries its own "yes" and "no" lists.
     *
     * @return list<array<string, mixed>>
     */
    public function stepTree(): array
    {
        $byParent = $this->nodes()->orderBy('id')->get()->groupBy(fn ($node) => $node->parent_id ?? 0);

        $build = function (int $parentId, ?string $branch) use (&$build, $byParent): array {
            $steps = [];
            $node = ($byParent[$parentId] ?? collect())->first(fn ($n) => $n->branch === $branch);

            while ($node) {
                $step = ['type' => $node->type] + (array) $node->config;

                if ($node->type === 'condition') {
                    $step['yes'] = $build($node->id, 'yes');
                    $step['no'] = $build($node->id, 'no');
                }

                $steps[] = $step;
                $node = ($byParent[$node->id] ?? collect())->first(fn ($n) => $n->branch === null);
            }

            return $steps;
        };

        return $build(0, null);
    }
}
