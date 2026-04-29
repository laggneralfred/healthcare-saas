<?php

namespace App\Filament\Resources\CheckoutSessions\Schemas;

use App\Models\Appointment;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\InventoryProduct;
use App\Models\Practice;
use App\Models\ServiceFee;
use App\Services\PracticeContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CheckoutSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Session Details')->schema([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Hidden::make('patient_id'),

                Placeholder::make('visit_checkout_context')
                    ->label('Checkout For')
                    ->content(fn (?CheckoutSession $record): string => self::checkoutContext($record))
                    ->visible(fn (?CheckoutSession $record): bool => self::isVisitCheckout($record)),

                Placeholder::make('visit_patient_context')
                    ->label('Patient')
                    ->content(fn (?CheckoutSession $record): string => $record?->patient?->name ?? 'Patient')
                    ->visible(fn (?CheckoutSession $record): bool => self::isVisitCheckout($record)),

                Placeholder::make('visit_practitioner_context')
                    ->label('Practitioner')
                    ->content(fn (?CheckoutSession $record): string => $record?->practitioner?->user?->name ?? 'Unassigned')
                    ->visible(fn (?CheckoutSession $record): bool => self::isVisitCheckout($record)),

                Select::make('appointment_id')
                    ->label('Appointment')
                    ->options(fn (Get $get): array => self::appointmentOptions($get('patient_id')))
                    ->searchable()
                    ->preload()
                    ->required(fn ($record): bool => $record === null || $record->appointment_id !== null)
                    ->visible(fn (?CheckoutSession $record): bool => ! self::isVisitCheckout($record))
                    ->disabledOn('edit')
                    ->disabledOn('view'),

                TextInput::make('charge_label')
                    ->required()
                    ->maxLength(255)
                    ->default('Visit Charges')
                    ->disabledOn('view'),

                Textarea::make('notes')
                    ->rows(2)
                    ->nullable()
                    ->disabledOn('view'),

                Textarea::make('diagnosis_codes')
                    ->label('Diagnosis Codes')
                    ->rows(2)
                    ->helperText('Optional superbill codes. Enter one or more diagnosis codes for patient reimbursement paperwork.')
                    ->nullable()
                    ->disabledOn('view'),

                Textarea::make('procedure_codes')
                    ->label('Procedure / CPT Codes')
                    ->rows(2)
                    ->helperText('Optional superbill codes. Enter one or more procedure or CPT codes.')
                    ->nullable()
                    ->disabledOn('view'),
            ])->columns(2),

            Section::make('Line Items')->schema([
                Repeater::make('checkoutLines')
                    ->relationship()
                    ->disabledOn('view')
                    ->schema([
                        Select::make('line_type')
                            ->label('Line Type')
                            ->options(fn ($record): array => self::lineTypeOptions($record?->practice_id))
                            ->default(CheckoutLine::TYPE_CUSTOM)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === CheckoutLine::TYPE_CUSTOM) {
                                    $set('service_fee_id', null);
                                    $set('inventory_product_id', null);
                                    $set('quantity', null);
                                    $set('unit_price', null);
                                }

                                if ($state === CheckoutLine::TYPE_SERVICE) {
                                    $set('inventory_product_id', null);
                                    $set('quantity', null);
                                }

                                if ($state === CheckoutLine::TYPE_INVENTORY) {
                                    $set('service_fee_id', null);
                                    $set('quantity', 1);
                                }
                            }),

                        Select::make('service_fee_id')
                            ->label('Service')
                            ->options(fn ($record): array => self::serviceFeeOptions($record?->practice_id))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_SERVICE)
                            ->visible(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_SERVICE)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, $record): void {
                                $serviceFee = self::serviceFeeForSelection($state, $record?->practice_id);

                                if (! $serviceFee) {
                                    return;
                                }

                                $set('description', $serviceFee->name);
                                $set('unit_price', number_format((float) $serviceFee->default_price, 2, '.', ''));
                                $set('amount', number_format((float) $serviceFee->default_price, 2, '.', ''));
                            })
                            ->columnSpan(2),

                        Select::make('inventory_product_id')
                            ->label('Product')
                            ->options(fn ($record): array => self::inventoryProductOptions($record?->practice_id))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_INVENTORY)
                            ->visible(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_INVENTORY)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, Set $set, $record): void {
                                self::applyInventoryProductSelection($state, (int) ($get('quantity') ?: 1), $set, $record?->practice_id);
                            })
                            ->columnSpan(2),

                        TextInput::make('description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->required(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_INVENTORY)
                            ->visible(fn (Get $get): bool => $get('line_type') === CheckoutLine::TYPE_INVENTORY)
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, Set $set, $record): void {
                                self::applyInventoryProductSelection($get('inventory_product_id'), (int) ($state ?: 1), $set, $record?->practice_id);
                            }),

                        TextInput::make('unit_price')
                            ->label('Unit Price ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->nullable()
                            ->visible(fn (Get $get): bool => in_array($get('line_type'), [CheckoutLine::TYPE_SERVICE, CheckoutLine::TYPE_INVENTORY], true))
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                if ($get('line_type') === CheckoutLine::TYPE_INVENTORY) {
                                    $quantity = max(1, (int) ($get('quantity') ?: 1));
                                    $set('amount', number_format((float) $state * $quantity, 2, '.', ''));
                                }
                            }),

                        TextInput::make('amount')
                            ->label('Amount ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->default(0),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addable(fn ($record) => $record === null || $record->isEditable())
                    ->deletable(fn ($record) => $record === null || $record->isEditable())
                    ->reorderable(false)
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                        return self::normalizeLineData($data, $record->practice_id);
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record): array {
                        return self::normalizeLineData($data, $record->practice_id);
                    }),

                Placeholder::make('amount_total_display')
                    ->label('Session Total')
                    ->content(fn ($record) => $record
                        ? '$' . number_format((float) $record->amount_total, 2)
                        : '$0.00'
                    ),
            ]),

            Section::make('Payments')->schema([
                Placeholder::make('payments_recorded')
                    ->label('Payments Recorded')
                    ->content(fn (?CheckoutSession $record): string => self::paymentsSummary($record)),

                Placeholder::make('amount_paid_display')
                    ->label('Amount Paid')
                    ->content(fn (?CheckoutSession $record): string => '$' . number_format((float) ($record?->amount_paid ?? 0), 2)),

                Placeholder::make('balance_due_display')
                    ->label('Balance Due')
                    ->content(fn (?CheckoutSession $record): string => '$' . number_format((float) ($record?->amount_due ?? 0), 2)),
            ])->columns(3),

        ]);
    }

    private static function isVisitCheckout(?CheckoutSession $record): bool
    {
        return $record !== null && ($record->appointment_id !== null || $record->encounter_id !== null);
    }

    private static function checkoutContext(?CheckoutSession $record): string
    {
        if (! $record) {
            return 'Checkout';
        }

        if ($record->appointment) {
            $time = $record->appointment->start_datetime?->format('M j, Y g:i A') ?? 'scheduled appointment';

            return "Appointment Visit - {$time}";
        }

        if ($record->encounter) {
            $date = $record->encounter->visit_date?->format('M j, Y') ?? 'visit date not set';

            return "Direct Visit - {$date}";
        }

        return 'Visit checkout';
    }

    private static function appointmentOptions(null|int|string $patientId): array
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return [];
        }

        return Appointment::withoutPracticeScope()
            ->with('patient')
            ->where('practice_id', $practiceId)
            ->when($patientId, fn ($query) => $query->where('patient_id', $patientId))
            ->orderByDesc('start_datetime')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Appointment $appointment): array => [
                $appointment->id => sprintf(
                    '%s - %s',
                    $appointment->patient?->name ?? 'Patient',
                    $appointment->start_datetime?->format('M d, Y g:i A') ?? 'No time'
                ),
            ])
            ->all();
    }

    private static function paymentsSummary(?CheckoutSession $record): string
    {
        if (! $record) {
            return 'No payments recorded.';
        }

        $payments = $record->checkoutPayments()->latest('paid_at')->get();

        if ($payments->isEmpty()) {
            return 'No payments recorded.';
        }

        return $payments
            ->map(fn (CheckoutPayment $payment): string => sprintf(
                '$%s %s on %s',
                number_format((float) $payment->amount, 2),
                CheckoutPayment::METHODS[$payment->payment_method] ?? $payment->payment_method,
                $payment->paid_at?->format('M j, Y g:i A') ?? 'date not set',
            ))
            ->implode("\n");
    }

    private static function serviceFeeOptions(?int $practiceId): array
    {
        $practiceId ??= PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return [];
        }

        return ServiceFee::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function lineTypeOptions(?int $practiceId): array
    {
        $practiceId ??= PracticeContext::currentPracticeId();
        $options = CheckoutLine::TYPES;

        if (! $practiceId || ! Practice::query()->find($practiceId)?->hasInventoryAddon()) {
            unset($options[CheckoutLine::TYPE_INVENTORY]);
        }

        return $options;
    }

    private static function inventoryProductOptions(?int $practiceId): array
    {
        $practiceId ??= PracticeContext::currentPracticeId();

        if (! $practiceId || ! Practice::query()->find($practiceId)?->hasInventoryAddon()) {
            return [];
        }

        return InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function serviceFeeForSelection(null|int|string $serviceFeeId, ?int $practiceId): ?ServiceFee
    {
        if (! $serviceFeeId) {
            return null;
        }

        $practiceId ??= PracticeContext::currentPracticeId();

        return ServiceFee::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('is_active', true)
            ->find($serviceFeeId);
    }

    private static function inventoryProductForSelection(null|int|string $productId, ?int $practiceId): ?InventoryProduct
    {
        if (! $productId) {
            return null;
        }

        $practiceId ??= PracticeContext::currentPracticeId();

        return InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('is_active', true)
            ->find($productId);
    }

    private static function applyInventoryProductSelection(null|int|string $productId, int $quantity, Set $set, ?int $practiceId): void
    {
        $product = self::inventoryProductForSelection($productId, $practiceId);

        if (! $product) {
            return;
        }

        $quantity = max(1, $quantity);
        $unitPrice = (float) $product->selling_price;

        $set('description', "{$product->name} (x{$quantity})");
        $set('unit_price', number_format($unitPrice, 2, '.', ''));
        $set('amount', number_format($unitPrice * $quantity, 2, '.', ''));
    }

    private static function normalizeLineData(array $data, int $practiceId): array
    {
        $lineType = $data['line_type'] ?? CheckoutLine::TYPE_CUSTOM;
        $data['practice_id'] = $practiceId;

        if ($lineType === CheckoutLine::TYPE_SERVICE) {
            $serviceFee = self::serviceFeeForSelection($data['service_fee_id'] ?? null, $practiceId);

            if ($serviceFee) {
                $data['description'] = $data['description'] ?: $serviceFee->name;
                $data['unit_price'] = $data['unit_price'] ?? $serviceFee->default_price;
                $data['amount'] = $data['amount'] ?? $serviceFee->default_price;
            }

            $data['inventory_product_id'] = null;
            $data['quantity'] = null;
        }

        if ($lineType === CheckoutLine::TYPE_INVENTORY) {
            $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $product = self::inventoryProductForSelection($data['inventory_product_id'] ?? null, $practiceId);

            if ($product) {
                $unitPrice = (float) ($data['unit_price'] ?? $product->selling_price);
                $data['description'] = $data['description'] ?: "{$product->name} (x{$quantity})";
                $data['unit_price'] = $unitPrice;
                $data['amount'] = $unitPrice * $quantity;
            }

            $data['quantity'] = $quantity;
            $data['service_fee_id'] = null;
        }

        if ($lineType === CheckoutLine::TYPE_CUSTOM) {
            $data['service_fee_id'] = null;
            $data['inventory_product_id'] = null;
            $data['quantity'] = null;
            $data['unit_price'] = $data['unit_price'] ?? null;
        }

        return $data;
    }
}
