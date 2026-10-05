<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRun extends Model
{
    /** A worker that claimed a run and never finished (crash) is considered gone after this long. */
    public const STALE_CLAIM_MINUTES = 10;

    protected $fillable = [
        'automation_id',
        'contact_id',
        'status',
        'current_node_id',
        'last_recipient_id',
        'next_run_at',
        'claimed_at',
        'attempts',
        'completed_at',
    ];

    protected $casts = [
        'next_run_at' => 'datetime',
        'claimed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AutomationEvent::class);
    }

    /**
     * Runs that should be worked on now: active, with their time arrived and nobody
     * working on them - or claimed so long ago that the worker must have died.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'active')->where(function ($q) {
            $q->where(fn ($q) => $q->whereNull('claimed_at')->whereNotNull('next_run_at')->where('next_run_at', '<=', now()))
                ->orWhere('claimed_at', '<', now()->subMinutes(self::STALE_CLAIM_MINUTES));
        });
    }
}
