<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'kind',
        'status',
        'input_tokens',
        'output_tokens',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
