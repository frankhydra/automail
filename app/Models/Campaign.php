<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'sending_identity_id',
        'template_id',
        'name',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this campaign.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the sending identity configured for this campaign.
     */
    public function sendingIdentity(): BelongsTo
    {
        return $this->belongsTo(SendingIdentity::class);
    }

    /**
     * Get the optional email template used as baseline for this campaign.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get all target contact recipient entries for this campaign.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}