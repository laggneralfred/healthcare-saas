<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExportToken extends Model
{
    use BelongsToPractice;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'practice_id',
        'format',
        'file_path',
        'status',
        'expires_at',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    // ── Lifecycle ──────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (ExportToken $token) {
            if (!$token->id) {
                $token->id = Str::uuid();
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->file_path;
    }

    public function isDownloaded(): bool
    {
        return $this->downloaded_at !== null;
    }
}
