<?php

namespace App\Filament\Resources\CheckoutSessions\Pages;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;

class EditCheckoutSession extends EditRecord
{
    protected static string $resource = CheckoutSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // draft → open
            Action::make('openSession')
                ->label('Open Session')
                ->icon('heroicon-o-lock-open')
                ->color('info')
                ->visible(fn ($record) => $record->state instanceof Draft)
                ->requiresConfirmation()
                ->modalHeading('Open this checkout session?')
                ->modalDescription('Lines will become editable and the session will be ready for payment.')
                ->action(function ($record) {
                    $record->transitionToOpen();
                    $this->refreshFormData(['state']);
                }),

            // open / payment_due → paid (with tender type selection)
            Action::make('markPaid')
                ->label('Mark Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->state instanceof Open || $record->state instanceof PaymentDue)
                ->form([
                    Select::make('tender_type')
                        ->label('Payment Method')
                        ->options(['cash' => 'Cash', 'card' => 'Card'])
                        ->required(),
                ])
                ->modalHeading('Record payment')
                ->modalDescription('Select the payment method to mark this session as paid.')
                ->action(function (array $data, $record) {
                    $record->markPaid($data['tender_type']);
                    $this->refreshFormData(['state', 'amount_paid', 'tender_type', 'paid_on']);
                }),

            // open → payment_due
            Action::make('markPaymentDue')
                ->label('Mark Payment Due')
                ->icon('heroicon-o-clock')
                ->color('warning')
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
}
