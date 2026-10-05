<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationNode extends Model
{
    protected $fillable = [
        'automation_id',
        'parent_id',
        'branch',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
