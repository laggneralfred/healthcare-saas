<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\CommunicationRule;
use App\Models\MessageTemplate;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')->sortable()->searchable()->toggleable(),
                TextColumn::make('patient.name')->label('Patient')->sortable()->searchable(),
                TextColumn::make('practitioner.user.name')->label('Practitioner')->sortable()->searchable(),
                TextColumn::make('appointmentType.name')->label('Type')->sortable()->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Scheduled::$name  => 'info',
                        InProgress::$name => 'warning',
                        Completed::$name  => 'success',
                        Closed::$name     => 'gray',
                        Checkout::$name   => 'primary',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Scheduled::$name  => 'Scheduled',
                        InProgress::$name => 'In Progress',
                        Completed::$name  => 'Completed',
                        Closed::$name     => 'Closed',
                        Checkout::$name   => 'Checkout',
                        default           => $state,
                    }),
                TextColumn::make('start_datetime')->dateTime()->sortable(),
                TextColumn::make('end_datetime')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('needs_follow_up')->boolean()->label('Follow-up'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('send_message')
                    ->label('Send Message')
                    ->icon('heroicon-m-envelope')
                    ->color('info')
                    ->hidden(fn () => auth()->user()?->isDemo())
                    ->form(fn ($record) => [
                        Select::make('message_template_id')
                            ->label('Template')
                            ->options(
                                MessageTemplate::withoutPracticeScope()
                                    ->where('practice_id', $record->practice_id)
                                    ->active()
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->live(),

                        Placeholder::make('preview')
                            ->label('Preview')
                            ->content(function ($get) use ($record) {
                                $templateId = $get('message_template_id');
                                if (! $templateId) {
                                    return 'Select a template to preview.';
                                }
                                $template = MessageTemplate::find($templateId);
                                if (! $template) {
                                    return '';
                                }
                                $record->load(['patient', 'practitioner.user', 'appointmentType', 'practice']);
                                $vars = [
                                    'patient_name'      => $record->patient?->name ?? '',
                                    'appointment_date'  => $record->start_datetime->format('l, F j, Y'),
                                    'appointment_time'  => $record->start_datetime->format('g:i A'),
                                    'practitioner_name' => $record->practitioner?->user?->name ?? '',
                                    'practice_name'     => $record->practice?->name ?? '',
                                    'appointment_type'  => $record->appointmentType?->name ?? '',
                                ];
                                return $template->renderBody($vars);
                            }),
                    ])
                    ->action(function ($record, array $data) {
                        $template = MessageTemplate::withoutPracticeScope()->find($data['message_template_id']);
                        if (! $template) {
                            return;
                        }

                        $rule = CommunicationRule::withoutPracticeScope()->firstOrCreate(
                            [
                                'practice_id'         => $record->practice_id,
                                'message_template_id' => $template->id,
                                'practitioner_id'     => null,
                                'appointment_type_id' => null,
                            ],
                            [
                                'is_active'              => true,
                                'send_at_offset_minutes' => 0,
                            ]
                        );

                        SendAppointmentReminderJob::dispatch($record, $rule);
                    })
                    ->successNotificationTitle('Message queued for delivery'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
