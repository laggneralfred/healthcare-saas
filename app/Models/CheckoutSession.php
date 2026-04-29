<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\States\CheckoutSession\CheckoutSessionState;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use App\Models\Traits\HasAuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

class CheckoutSession extends Model
{
    use HasFactory, HasStates, BelongsToPractice, HasAuditLog;

    protected $fillable = [
        'practice_id',
        'appointment_id',
        'encounter_id',
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
        'diagnosis_codes',
        'procedure_codes',
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
            if ($session->state && ! ($session->state instanceof Paid) && $session->paid_on) {
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

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
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

    public function checkoutPayments(): HasMany
    {
        return $this->hasMany(CheckoutPayment::class)->orderBy('paid_at');
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->state && $this->state->equals(Open::class);
    }

    // ── State transitions ──────────────────────────────────────────────────────

    public function transitionToOpen(): void
    {
        $from = (string) $this->state;
        if ($this->state) {
            $this->state->transitionTo(Open::class);
        }
        AuditLogger::stateChanged($this, $from, Open::$name);
    }

    public function markPaid(string $tenderType): void
    {
        $method = $this->normalizePaymentMethod($tenderType);
        $paymentMethod = (float) $this->amount_total <= 0 ? CheckoutPayment::METHOD_COMPED : $method;
        $remaining = max(0, (float) $this->amount_due);

        if (! PracticePaymentMethod::isEnabledForPractice((int) $this->practice_id, $paymentMethod)) {
            throw new \InvalidArgumentException('This payment method is not enabled for this practice.');
        }

        if ($remaining > 0 || (float) $this->amount_total <= 0) {
            $this->recordPayment([
                'amount' => $remaining,
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
                'created_by_user_id' => auth()->id(),
            ]);
        }

        $from = (string) $this->state;
        if ($this->state) {
            $this->state->transitionTo(Paid::class);
        }
        $this->update([
            'tender_type'  => $tenderType,
            'amount_paid'  => $this->checkoutPayments()->sum('amount'),
            'paid_on'      => now(),
        ]);

        // Create inventory movements for products sold
        $this->createInventoryMovements();

        AuditLogger::stateChanged($this, $from, Paid::$name, ['tender_type' => $tenderType]);
    }

    public function markPaymentDue(): void
    {
        $from = (string) $this->state;
        if ($this->state) {
            $this->state->transitionTo(PaymentDue::class);
        }
        $this->update([
            'tender_type' => null,
            'paid_on'     => null,
        ]);
        AuditLogger::stateChanged($this, $from, PaymentDue::$name);
    }

    public function voidSession(): void
    {
        $from = (string) $this->state;
        if ($this->state) {
            $this->state->transitionTo(Voided::class);
        }
        AuditLogger::stateChanged($this, $from, Voided::$name);
    }

    // ── Totals ─────────────────────────────────────────────────────────────────

    public function syncTotalFromLines(): void
    {
        $total = $this->checkoutLines()->sum('amount');
        $this->updateQuietly(['amount_total' => $total]);
    }

    public function syncPayments(): void
    {
        $amountPaid = (float) $this->checkoutPayments()->sum('amount');
        $wasPaid = $this->state instanceof Paid;
        $updates = [
            'amount_paid' => min($amountPaid, (float) $this->amount_total),
        ];

        if ((float) $this->amount_total > 0 && $amountPaid >= (float) $this->amount_total) {
            $updates['state'] = Paid::$name;
            $updates['paid_on'] = $this->paid_on ?? now();
        } elseif ($this->state instanceof Paid) {
            $updates['state'] = Open::$name;
            $updates['paid_on'] = null;
        }

        $this->updateQuietly($updates);

        if (! $wasPaid && ($updates['state'] ?? null) === Paid::$name) {
            $this->createInventoryMovements();
        }
    }

    public function recordPayment(array $data): CheckoutPayment
    {
        if ($this->state instanceof Voided) {
            throw new \InvalidArgumentException('Voided checkout sessions cannot accept payments.');
        }

        $method = $this->normalizePaymentMethod($data['payment_method'] ?? null);
        $amount = (float) ($data['amount'] ?? 0);

        if ($method !== CheckoutPayment::METHOD_COMPED && $amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }

        if ($method === CheckoutPayment::METHOD_COMPED && $amount < 0) {
            throw new \InvalidArgumentException('Comped payment amount cannot be negative.');
        }

        if ($method === CheckoutPayment::METHOD_COMPED && $amount == 0.0 && (float) $this->amount_total > 0) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > (float) $this->amount_due) {
            throw new \InvalidArgumentException('Payment amount cannot exceed the balance due.');
        }

        if (! PracticePaymentMethod::isEnabledForPractice((int) $this->practice_id, $method)) {
            throw new \InvalidArgumentException('This payment method is not enabled for this practice.');
        }

        return $this->checkoutPayments()->create([
            'practice_id' => $this->practice_id,
            'amount' => $amount,
            'payment_method' => $method,
            'paid_at' => $data['paid_at'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? auth()->id(),
        ]);
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
        return max(0, (float) $this->amount_total - (float) $this->amount_paid);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->amount_paid >= $this->amount_total && $this->amount_total > 0;
    }

    public function getIsPartiallyPaidAttribute(): bool
    {
        return $this->amount_paid > 0 && !$this->is_fully_paid;
    }

    // ── Inventory Integration ──────────────────────────────────────────────────────

    public function createInventoryMovements(): void
    {
        if (InventoryMovement::query()
            ->where('practice_id', $this->practice_id)
            ->where('reference', "checkout-{$this->id}")
            ->exists()) {
            return;
        }

        // Create inventory movements for each product line item
        $this->checkoutLines()
            ->whereNotNull('inventory_product_id')
            ->each(function (CheckoutLine $line) {
                InventoryMovement::create([
                    'practice_id' => $this->practice_id,
                    'inventory_product_id' => $line->inventory_product_id,
                    'type' => 'sale',
                    'quantity' => -($line->quantity ?? 1),
                    'unit_price' => $line->amount / ($line->quantity ?? 1),
                    'reference' => "checkout-{$this->id}",
                    'created_by' => auth()->id(),
                ]);
            });
    }

    private function normalizePaymentMethod(?string $method): string
    {
        return match ($method) {
            'card' => CheckoutPayment::METHOD_CARD_EXTERNAL,
            CheckoutPayment::METHOD_CASH,
            CheckoutPayment::METHOD_CHECK,
            CheckoutPayment::METHOD_CARD_EXTERNAL,
            CheckoutPayment::METHOD_OTHER,
            CheckoutPayment::METHOD_COMPED => $method,
            default => throw new \InvalidArgumentException('Choose a valid payment method.'),
        };
    }
}
