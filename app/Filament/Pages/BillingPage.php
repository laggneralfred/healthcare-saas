<?php

namespace App\Filament\Pages;

use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BillingPage extends Page
{
    protected static ?string $slug = 'billing';

    protected static ?string $title = 'Billing & Subscription';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected string $view = 'filament.pages.billing';

    // ── Livewire reactive properties ───────────────────────────────────────────
    // These are tracked by Livewire and trigger a re-render when mutated,
    // which is why subscription state (active/inactive, current plan) must
    // live here rather than only inside getViewData().

    public bool $hasActiveSubscription = false;

    public bool $hasPastDueSubscription = false;

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
            try {
                $subscription->swap($plan->stripe_price_id);

                // Refresh reactive state so the UI reflects the new plan immediately
                $this->loadSubscriptionState();

                Notification::make()
                    ->title('Plan updated')
                    ->body("Switched to {$plan->name}.")
                    ->success()
                    ->send();

                return null;
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $subscription->delete();
            }
        }

        $checkout = $practice->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => route('filament.admin.pages.billing') . '?success=true',
                'cancel_url'  => route('filament.admin.pages.billing'),
            ]);

        return redirect($checkout->url);
    }

    // ── Header actions ─────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changePlan')
                ->label('Change Plan')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => (bool) $this->getPractice())
                ->form(fn () => [
                    Radio::make('plan_key')
                        ->label('Select Plan')
                        ->options(
                            SubscriptionPlan::where('is_active', true)
                                ->orderBy('price_monthly')
                                ->pluck('name', 'key')
                                ->toArray()
                        )
                        ->descriptions(
                            SubscriptionPlan::where('is_active', true)
                                ->orderBy('price_monthly')
                                ->get()
                                ->mapWithKeys(fn ($plan) => [
                                    $plan->key => $plan->monthlyDollars() . '/mo · ' . $plan->practitionerLimit() . ' practitioners',
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->default(fn () => $this->getCurrentPlan()?->key),
                ])
                ->modalHeading('Change Subscription Plan')
                ->modalDescription('Select the plan that best fits your practice.')
                ->action(function (array $data): void {
                    $practice = $this->getPractice();
                    $plan     = SubscriptionPlan::where('key', $data['plan_key'])->firstOrFail();

                    if (! $plan->stripe_price_id) {
                        Notification::make()
                            ->title('Plan not available')
                            ->body('This plan has not been configured in Stripe yet.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $subscription = $practice->subscription('default');

                    if ($subscription) {
                        $subscription->swap($plan->stripe_price_id);
                        $this->loadSubscriptionState();
                        Notification::make()
                            ->title('Plan updated')
                            ->body("Switched to {$plan->name}.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No active subscription')
                            ->body('Subscribe via a plan below or the Stripe billing portal.')
                            ->warning()
                            ->send();
                    }
                }),

            Action::make('billingPortal')
                ->label('Stripe Billing Portal')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn () => (bool) $this->getPractice()?->stripe_id)
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
