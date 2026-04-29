<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Models\CheckoutPayment;
use App\Models\PracticePaymentMethod;
use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCheckoutSession extends EditRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['practice_id'],
            $data['patient_id'],
            $data['appointment_id'],
            $data['encounter_id'],
            $data['practitioner_id'],
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->hidden(fn () => auth()->user()?->isDemo())
                ->visible(fn ($record) => $record->state instanceof Open || $record->state instanceof PaymentDue)
                ->form([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(fn ($record) => number_format((float) $record->amount_due, 2, '.', '')),
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(fn ($record): array => PracticePaymentMethod::enabledOptionsForPractice($record->practice_id))
                        ->required(),
                    DateTimePicker::make('paid_at')
                        ->label('Paid At')
                        ->default(now())
                        ->required(),
                    TextInput::make('reference')
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->rows(2),
                ])
                ->modalHeading('Record payment')
                ->action(function (array $data, $record) {
                    if (! $this->hasEnabledPaymentMethods($record)) {
                        $this->notifyNoEnabledPaymentMethods();

                        return;
                    }

                    try {
                        $record->recordPayment($data + ['created_by_user_id' => auth()->id()]);
                    } catch (\InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->refreshFormData(['amount_paid', 'paid_on', 'state']);

                    Notification::make()
                        ->title('Payment recorded.')
                        ->success()
                        ->send();
                }),

            Action::make('viewSuperbill')
                ->label('View Superbill')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => CheckoutSessionResource::getUrl('superbill', ['record' => $record])),

            // draft → open
            Action::make('openSession')
                ->label('Open Session')
                ->icon('heroicon-o-lock-open')
                ->color('info')
                ->hidden(fn () => auth()->user()?->isDemo())
                ->visible(fn ($record) => $record->state instanceof Draft)
                ->requiresConfirmation()
                ->modalHeading('Open this checkout session?')
                ->modalDescription('Lines will become editable and the session will be ready for payment.')
                ->action(function ($record) {
                    $record->transitionToOpen();
                    $this->refreshFormData(['state']);
                }),

            // open / payment_due → paid (with payment record)
            Action::make('markPaid')
                ->label('Mark Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->hidden(fn () => auth()->user()?->isDemo())
                ->visible(fn ($record) => $record->state instanceof Open || $record->state instanceof PaymentDue)
                ->form([
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(fn ($record): array => PracticePaymentMethod::enabledOptionsForPractice($record->practice_id))
                        ->default(fn ($record): ?string => $this->defaultPaymentMethodFor($record))
                        ->required(),
                ])
                ->modalHeading('Record payment')
                ->modalDescription('This records a payment for the remaining balance and marks the session paid.')
                ->action(function (array $data, $record) {
                    if (! $this->hasEnabledPaymentMethods($record)) {
                        $this->notifyNoEnabledPaymentMethods();

                        return;
                    }

                    try {
                        $record->markPaid($data['payment_method']);
                    } catch (\InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->refreshFormData(['state', 'amount_paid', 'tender_type', 'paid_on']);
                }),

            // open → payment_due
            Action::make('markPaymentDue')
                ->label('Mark Payment Due')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->hidden(fn () => auth()->user()?->isDemo())
                ->visible(fn ($record) => $record->state instanceof Open)
                ->requiresConfirmation()
                ->modalHeading('Mark as Payment Due?')
                ->modalDescription('The session will be locked and the patient will be invoiced for later payment.')
                ->action(function ($record) {
                    $record->markPaymentDue();
                    $this->refreshFormData(['state']);
                }),

            // open / payment_due → void
            Action::make('voidSession')
                ->label('Void')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->hidden(fn () => auth()->user()?->isDemo())
                ->visible(fn ($record) => ! ($record->state instanceof Voided) && ! ($record->state instanceof Paid) && ! ($record->state instanceof Draft))
                ->requiresConfirmation()
                ->modalHeading('Void this checkout session?')
                ->modalDescription('This cannot be undone. The session will be permanently voided.')
                ->action(function ($record) {
                    $record->voidSession();
                    $this->refreshFormData(['state']);
                }),

            DeleteAction::make(),
        ];
    }

    private function defaultPaymentMethodFor($record): ?string
    {
        $options = PracticePaymentMethod::enabledOptionsForPractice($record->practice_id);

        if ($options === []) {
            return null;
        }

        if ((float) $record->amount_total <= 0 && array_key_exists(CheckoutPayment::METHOD_COMPED, $options)) {
            return CheckoutPayment::METHOD_COMPED;
        }

        if (array_key_exists(CheckoutPayment::METHOD_CARD_EXTERNAL, $options)) {
            return CheckoutPayment::METHOD_CARD_EXTERNAL;
        }

        return array_key_first($options);
    }

    private function hasEnabledPaymentMethods($record): bool
    {
        return PracticePaymentMethod::enabledOptionsForPractice($record->practice_id) !== [];
    }

    private function notifyNoEnabledPaymentMethods(): void
    {
        Notification::make()
            ->title('No payment methods are enabled for this practice.')
            ->danger()
            ->send();
    }
}
