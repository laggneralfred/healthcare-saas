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

    // ── Data for the view ──────────────────────────────────────────────────────

    public function getPractice(): ?\App\Models\Practice
    {
        return auth()->user()?->practice;
    }

    public function getCurrentPlan(): ?SubscriptionPlan
    {
        $practice = $this->getPractice();

        if (! $practice || ! $practice->subscribed('default')) {
            return null;
        }

        $stripePrice = $practice->subscription('default')?->stripe_price;

        return SubscriptionPlan::where('stripe_price_id', $stripePrice)->first();
    }

    public function getSubscription(): ?\Laravel\Cashier\Subscription
    {
        return $this->getPractice()?->subscription('default');
    }

    protected function getViewData(): array
    {
        $practice     = $this->getPractice();
        $subscription = $this->getSubscription();
        $currentPlan  = $this->getCurrentPlan();
        $allPlans     = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        return compact('practice', 'subscription', 'currentPlan', 'allPlans');
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
                            ->body('This plan has not been configured in Stripe yet. Please add the stripe_price_id.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $subscription = $practice->subscription('default');

                    if ($subscription) {
                        $subscription->swap($plan->stripe_price_id);
                        Notification::make()
                            ->title('Plan updated')
                            ->body("Switched to {$plan->name}.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No active subscription')
                            ->body('Subscribe via the Stripe billing portal.')
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
