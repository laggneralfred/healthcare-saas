<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    use HasFactory, BelongsToPractice;

    protected $fillable = [
        'practice_id',
        'filename',
        'total_rows',
        'imported',
        'skipped',
        'failed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'imported' => 'integer',
            'skipped' => 'integer',
            'failed' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
