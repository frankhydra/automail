<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvitation extends Model
{
    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'token',
        'invited_by',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * Roles that can be granted through an invite. "owner" is deliberately
     * excluded - ownership is not handed out through this flow.
     *
     * @return list<string>
     */
    public static function invitableRoles(): array
    {
        return ['admin', 'manager', 'editor', 'viewer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    public function isExpired(): bool
    {
        return $this->created_at->addDays(7)->isPast();
    }
}
