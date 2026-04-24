<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsageLog extends Model
{
    use BelongsToPractice;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'practice_id',
        'user_id',
        'feature',
        'input_tokens',
        'output_tokens',
        'cost_estimate',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'cost_estimate' => 'decimal:6',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
