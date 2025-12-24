<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intent extends Model
{
    protected $fillable = [
        'dialogflow_id',
        'display_name',
        'description',
        'priority',
        'is_fallback',
        'training_phrases',
        'events',
        'responses',
        'parameters',
        'input_contexts',
        'output_contexts',
        'webhook_enabled',
        'action',
        'synced',
        'last_synced_at',
    ];

    protected $casts = [
        'training_phrases' => 'array',
        'events' => 'array',
        'responses' => 'array',
        'parameters' => 'array',
        'input_contexts' => 'array',
        'output_contexts' => 'array',
        'is_fallback' => 'boolean',
        'webhook_enabled' => 'boolean',
        'synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
}
