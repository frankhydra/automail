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
        'segment_id',
        'name',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'tag_filter',
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
     * Get the saved segment (if any) whose rules produced this campaign's
     * audience snapshot. Nullable: a campaign may target a list, a segment, or
     * everyone.
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    /**
     * Get all target contact recipient entries for this campaign.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /**
     * Close the campaign once no recipient is pending any more.
     *
     * The conditional UPDATE ("where status = sending") is atomic, so when several
     * workers finish at the same moment only one of them performs the transition.
     * Ends as "failed" only when nothing was sent and at least one delivery failed;
     * otherwise "sent" (recipients skipped or suppressed are expected outcomes).
     */
    public function markFinishedIfComplete(): void
    {
        if ($this->recipients()->where('status', 'pending')->exists()) {
            return;
        }

        $anySent = $this->recipients()->where('status', 'sent')->exists();
        $anyFailed = $this->recipients()->where('status', 'failed')->exists();
        $failedOnly = !$anySent && $anyFailed;

        static::where('id', $this->id)
            ->where('status', 'sending')
            ->update([
                'status' => $failedOnly ? 'failed' : 'sent',
                'sent_at' => $failedOnly ? null : now(),
            ]);
    }
}
