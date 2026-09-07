<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'subject',
        'content',
    ];

    /**
     * Get the organization that owns this email template.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}