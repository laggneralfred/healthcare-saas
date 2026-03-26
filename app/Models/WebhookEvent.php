<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'type',
        'processed_at',
        'failed_at',
        'error',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
            'failed_at'    => 'datetime',
        ];
    }
}
