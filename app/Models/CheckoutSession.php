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
        return $this->state->equals(Open::class);
    }

    // ── State transitions ──────────────────────────────────────────────────────

    public function transitionToOpen(): void
    {
        $this->state->transitionTo(Open::class);
    }

    public function markPaid(string $tenderType): void
    {
        $this->state->transitionTo(Paid::class);
        $this->update([
            'tender_type'  => $tenderType,
            'amount_paid'  => $this->amount_total,
            'paid_on'      => now(),
        ]);
    }

    public function markPaymentDue(): void
    {
        $this->state->transitionTo(PaymentDue::class);
        $this->update([
            'tender_type' => null,
            'amount_paid' => 0,
            'paid_on'     => null,
        ]);
    }

    public function voidSession(): void
    {
        $this->state->transitionTo(Voided::class);
    }

    // ── Totals ─────────────────────────────────────────────────────────────────

    public function syncTotalFromLines(): void
    {
        $total = $this->checkoutLines()->sum('amount');
        $this->updateQuietly(['amount_total' => $total]);
    }
}
