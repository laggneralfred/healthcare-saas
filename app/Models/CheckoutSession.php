<?php

namespace App\Models;

use App\Models\States\CheckoutSession\CheckoutSessionState;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

class CheckoutSession extends Model
{
    use HasFactory, HasStates;

    protected $fillable = [
        'practice_id',
        'appointment_id',
        'patient_id',
        'practitioner_id',
        'state',
        'charge_label',
        'amount_total',
        'amount_paid',
        'tender_type',
        'started_on',
        'paid_on',
        'payment_note',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'state'        => CheckoutSessionState::class,
            'amount_total' => 'decimal:2',
            'amount_paid'  => 'decimal:2',
            'started_on'   => 'datetime',
            'paid_on'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CheckoutSession $session) {
            if (! $session->started_on) {
                $session->started_on = now();
            }
        });

        static::saving(function (CheckoutSession $session) {
            // Ensure amount_paid is never greater than amount_total
            if ($session->amount_paid > $session->amount_total) {
                $session->amount_paid = $session->amount_total;
            }

            // Ensure paid_on is only set when in paid state
            // Only apply this logic if state is loaded and not null
            if ($session->state && $session->state->name !== 'paid' && $session->paid_on) {
                $session->paid_on = null;
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function checkoutLines(): HasMany
    {
        return $this->hasMany(CheckoutLine::class)->orderBy('sequence');
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->state && $this->state->equals(Open::class);
    }

    // ── State transitions ──────────────────────────────────────────────────────

    public function transitionToOpen(): void
    {
        if ($this->state) {
            $this->state->transitionTo(Open::class);
        }
    }

    public function markPaid(string $tenderType): void
    {
        if ($this->state) {
            $this->state->transitionTo(Paid::class);
        }
        $this->update([
            'tender_type'  => $tenderType,
            'amount_paid'  => $this->amount_total,
            'paid_on'      => now(),
        ]);
    }

    public function markPaymentDue(): void
    {
        if ($this->state) {
            $this->state->transitionTo(PaymentDue::class);
        }
        $this->update([
            'tender_type' => null,
            'amount_paid' => 0,
            'paid_on'     => null,
        ]);
    }

    public function voidSession(): void
    {
        if ($this->state) {
            $this->state->transitionTo(Voided::class);
        }
    }

    // ── Totals ─────────────────────────────────────────────────────────────────

    public function syncTotalFromLines(): void
    {
        $total = $this->checkoutLines()->sum('amount');
        $this->updateQuietly(['amount_total' => $total]);
    }

    // ── Query Scopes ────────────────────────────────────────────────────────────────

    public function scopeByPractice($query, $practiceId)
    {
        return $query->where('practice_id', $practiceId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('state', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('state', 'paid');
    }

    public function scopePending($query)
    {
        return $query->whereIn('state', ['open', 'payment_due']);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    // ── Calculated Properties ───────────────────────────────────────────────────────

    public function getAmountDueAttribute(): float|int
    {
        return $this->amount_total - $this->amount_paid;
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->amount_paid >= $this->amount_total && $this->amount_total > 0;
    }

    public function getIsPartiallyPaidAttribute(): bool
    {
        return $this->amount_paid > 0 && !$this->is_fully_paid;
    }
}
