<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialSignup extends Model
{
    use BelongsToPractice;
    use HasFactory;

    protected $fillable = [
        'practice_id',
        'user_id',
        'name',
        'email',
        'phone',
        'practice_name',
        'profession',
        'practice_type',
        'heard_about_us',
        'source',
        'ip_address',
        'user_agent',
        'signed_up_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_up_at' => 'datetime',
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
