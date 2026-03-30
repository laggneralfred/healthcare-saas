<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportSession extends Model
{
    use BelongsToPractice, SoftDeletes;

    protected $fillable = [
        'practice_id',
        'status',
        'file_path',
        'original_filename',
        'detected_headers',
        'column_mappings',
        'total_rows',
        'valid_rows',
        'duplicate_rows',
        'error_rows',
        'imported_rows',
        'dry_run_results',
        'error_report_path',
    ];

    protected function casts(): array
    {
        return [
            'detected_headers' => 'array',
            'column_mappings'  => 'array',
            'dry_run_results'  => 'array',
            'total_rows'       => 'integer',
            'valid_rows'       => 'integer',
            'duplicate_rows'   => 'integer',
            'error_rows'       => 'integer',
            'imported_rows'    => 'integer',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function isAnalyzing(): bool
    {
        return $this->status === 'analyzing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
