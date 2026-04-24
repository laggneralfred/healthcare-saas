<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Practice extends Model
{
    use Billable, HasFactory;

    protected $fillable = [
        'name', 'slug', 'timezone', 'is_active', 'is_demo',
        'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at',
        'discipline', 'referral_source', 'setup_completed_at',
        'dismissed_onboarding_banner',
        'default_appointment_duration', 'default_reminder_hours',
        'insurance_billing_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'dismissed_onboarding_banner' => 'boolean',
            'insurance_billing_enabled' => 'boolean',
            'trial_ends_at' => 'datetime',
            'setup_completed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function checkoutSessions(): HasMany
    {
        return $this->hasMany(CheckoutSession::class);
    }

    public function inventoryProducts(): HasMany
    {
        return $this->hasMany(InventoryProduct::class);
    }

    // ── Subscription Helpers ───────────────────────────────────────────────────────

    public function currentPlan(): ?SubscriptionPlan
    {
        if (!$this->subscribed('default')) {
            return null;
        }

        $stripePrice = $this->subscription('default')?->stripe_price;

        return SubscriptionPlan::where('stripe_price_id', $stripePrice)->first();
    }

    public function canAddPractitioner(): bool
    {
        $plan = $this->currentPlan();

        if (!$plan) {
            return false;
        }

        if ($plan->max_practitioners === -1) {
            return true;
        }

        return $this->practitioners()->count() < $plan->max_practitioners;
    }

    public function practitionerCountLimit(): int
    {
        return $this->currentPlan()?->max_practitioners ?? 0;
    }

    public function availablePractitionerSlots(): int
    {
        $limit = $this->practitionerCountLimit();

        if ($limit === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->practitioners()->count());
    }

    public function hasInventoryAddon(): bool
    {
        if (!$this->subscribed('default')) {
            return false;
        }

        $subscription = $this->subscription('default');
        $inventoryPriceId = config('services.stripe.addon_prices.inventory');

        if (!$inventoryPriceId) {
            return false;
        }

        return $subscription->items()
            ->where('stripe_price', $inventoryPriceId)
            ->exists();
    }
}
