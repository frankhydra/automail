<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    protected $fillable = [
        'organization_id',
        'uploaded_by',
        'name',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Public address of the file. This is what gets pasted into an Image block,
     * so it must be reachable by whoever opens the email (see APP_URL).
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** "1.2 MB", "840 KB" ... */
    public function formattedSize(): string
    {
        if ($this->size >= 1048576) {
            return number_format($this->size / 1048576, 1).' MB';
        }

        return max(1, (int) round($this->size / 1024)).' KB';
    }

    /** "PNG", "JPG" ... for the little label under each thumbnail. */
    public function typeLabel(): string
    {
        return strtoupper(pathinfo($this->path, PATHINFO_EXTENSION));
    }
}
