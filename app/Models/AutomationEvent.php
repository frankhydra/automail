<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'automation_run_id',
        'automation_node_id',
        'event',
        'detail',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
