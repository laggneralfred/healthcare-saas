<?php

namespace App\Filament\Pages;

use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BillingPage extends Page
{
    protected static ?string $slug = 'billing';

    protected static ?string $title = 'Billing & Subscription';

    protected static ?string $navigationLabel = 'Subscription';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?int $navigationGroupSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected string $view = 'filament.pages.billing';

    // ── Livewire reactive properties ───────────────────────────────────────────
    // These are tracked by Livewire and trigger a re-render when mutated,
    // which is why subscription state (active/inactive, current plan) must
    // live here rather than only inside getViewData().

    public bool $hasActiveSubscription = false;

    public bool $hasPastDueSubscription = false;

    public bool $isOnTrial = false;

    public ?string $trialEndsAt = null;

    public ?string $trialRemainingLabel = null;

    public ?string $activePriceId = null;

    public ?string $currentPlanName = null;

    public ?string $subscriptionEndsAt = null;

    // ── Lifecycle ──────────────────────────────────────────────────────────────

    public function mount(): void
    {
        if (request()->query('success')) {
            $practice = $this->getPractice();

            // Force sync from Stripe so the local subscriptions table is up to date
            // before we load reactive state below. Uses the same logic as
            // `php artisan stripe:sync` so there is a single source of truth.
            if ($practice && $practice->stripe_id) {
                app(\App\Console\Commands\StripeSyncCommand::class)->syncPractice($practice);
            }

            Notification::make()
                ->title('Subscription activated successfully!')
                ->body('Your subscription is now active.')
                ->success()
                ->persistent()
                ->send();
        }

        // Always populate reactive properties on mount so the view has correct
        // initial state regardless of the success redirect flow.
        $this->loadSubscriptionState();
    }

    // ── Reactive state loader ──────────────────────────────────────────────────

    /**
     * Populate the public Livewire properties from the current subscription state.
     * Called in mount() and can be called again after subscribe/swap to refresh the UI.
     */
    protected function loadSubscriptionState(): void
    {
        $practice = $this->getPractice();

        if (! $practice) {
            return;
        }

        $priceId = $this->resolveActivePriceId($practice);

        $this->activePriceId          = $priceId;
        $this->hasActiveSubscription  = $priceId !== null;
        $this->currentPlanName        = $priceId
            ? SubscriptionPlan::where('stripe_price_id', $priceId)->value('name')
            : null;

        $sub = $practice->subscription('default');
        $this->hasPastDueSubscription = $sub && $sub->stripe_status === 'past_due';
        $this->subscriptionEndsAt     = $sub?->ends_at?->format('M d, Y');

        $this->isOnTrial           = ! $this->hasActiveSubscription
            && $practice->trial_ends_at
            && $practice->trial_ends_at->isFuture();
        $this->trialEndsAt         = $this->isOnTrial ? $practice->trial_ends_at->format('M d, Y') : null;
        $this->trialRemainingLabel = $this->isOnTrial
            ? 'Trial — ' . $practice->trial_ends_at->diffForHumans(null, true) . ' remaining'
            : null;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getPractice(): ?\App\Models\Practice
    {
        return auth()->user()?->practice()->first();
    }

    public function getCurrentPlan(): ?SubscriptionPlan
    {
        return $this->activePriceId
            ? SubscriptionPlan::where('stripe_price_id', $this->activePriceId)->first()
            : null;
    }

    /**
     * Returns the active Stripe price ID.
     * Checks the local subscription record first (fast path). If nothing is found
     * but the practice has a stripe_id, calls syncPractice() once to pull the latest
     * data from Stripe (handles cases where the webhook missed or local dev has no
     * webhook listener), then re-reads the local DB.
     */
    protected function resolveActivePriceId(\App\Models\Practice $practice): ?string
    {
        $sub = $practice->subscription('default');

        if ($sub && in_array($sub->stripe_status, ['active', 'trialing'])) {
            return $sub->stripe_price ?? null;
        }

        // No local record — sync from Stripe and try once more
        if ($practice->stripe_id) {
            app(\App\Console\Commands\StripeSyncCommand::class)->syncPractice($practice);

            $sub = $practice->subscriptions()
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->latest('created_at')
                ->first();

            return $sub?->stripe_price ?? null;
        }

        return null;
    }

    protected function getViewData(): array
    {
        $currentPlan = $this->getCurrentPlan();
        $allPlans    = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        // $hasActiveSubscription, $hasPastDueSubscription, $activePriceId,
        // $currentPlanName, and $subscriptionEndsAt are public Livewire properties —
        // they are available in the view automatically without being listed here.
        return compact('currentPlan', 'allPlans');
    }

    // ── Subscribe action (called from blade via wire:click) ───────────────────

    public function subscribeToPlan(string $planKey): mixed
    {
        $practice = auth()->user()?->practice()->first();

        if (! $practice) {
            Notification::make()
                ->title('No practice associated with your account')
                ->body('Your user account is not linked to a practice. Please contact support.')
                ->danger()
                ->send();

            return null;
        }

        $plan = SubscriptionPlan::where('key', $planKey)->first();

        if (! $plan) {
            Notification::make()->title('Plan not found')->danger()->send();

            return null;
        }

        if (! $plan->stripe_price_id) {
            Notification::make()
                ->title('Plan not available')
                ->body('This plan has not been configured in Stripe yet. Please add the stripe_price_id.')
                ->warning()
                ->send();

            return null;
        }

        try {
            return $this->attemptSubscription($practice, $plan);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if (str_contains($e->getMessage(), 'No such customer')) {
                $practice->stripe_id     = null;
                $practice->pm_type       = null;
                $practice->pm_last_four  = null;
                $practice->trial_ends_at = null;
                $practice->save();
                $practice->subscriptions()->delete();

                return $this->attemptSubscription($practice, $plan);
            }

            Notification::make()->title('Payment error')->body($e->getMessage())->danger()->send();

            return null;
        }
    }

    private function attemptSubscription(\App\Models\Practice $practice, SubscriptionPlan $plan): mixed
    {
        $subscription = $practice->subscription('default');

        if ($subscription && in_array($subscription->stripe_status, ['active', 'trialing'])) {
            // Existing subscriber — send to Stripe Customer Portal to change plan
            // without re-entering payment details
            return $this->redirect($practice->billingPortalUrl(route('filament.admin.pages.billing')));
        }

        $checkout = $practice->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => route('filament.admin.pages.billing') . '?success=true',
                'cancel_url'  => route('filament.admin.pages.billing'),
            ]);

        return $this->redirect($checkout->url);
    }

    // ── Header actions ─────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageBilling')
                ->label('Manage Billing')
                ->icon('heroicon-o-credit-card')
                ->color('gray')
                ->visible(fn () => $this->hasActiveSubscription && (bool) $this->getPractice()?->stripe_id)
                ->url(function (): ?string {
                    try {
                        return $this->getPractice()
                            ?->billingPortalUrl(route('filament.admin.pages.billing'));
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->openUrlInNewTab(),
        ];
    }
}
