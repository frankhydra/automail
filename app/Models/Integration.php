<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'enabled',
        'settings',
        'secret_hash',
        'uses',
        'last_used_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function forOrganization(int $organizationId, string $type): ?self
    {
        return static::where('organization_id', $organizationId)->where('type', $type)->first();
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
