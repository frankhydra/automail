<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendingIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'from_name',
        'from_email',
        'reply_to',
        'type',
        'verification_status',
        'verification_token',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this sending identity.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Helper to check if identity is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}