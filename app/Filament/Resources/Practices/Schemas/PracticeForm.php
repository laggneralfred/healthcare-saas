<?php

namespace App\Filament\Resources\Practices\Schemas;

use App\Models\Practice;
use App\Support\PracticeType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PracticeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('view'),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('URL-safe identifier, e.g. "green-valley-acupuncture"')
                    ->disabledOn('view'),

                Html::make(fn (?Practice $record): HtmlString => self::publicLinksPanel($record))
                    ->visible(fn (?Practice $record): bool => (bool) $record?->exists),

                TextInput::make('timezone')
                    ->required()
                    ->default('UTC')
                    ->maxLength(50)
                    ->disabledOn('view'),

                Hidden::make('discipline')
                    ->default('general'),

                Select::make('practice_type')
                    ->label('Practice Type')
                    ->options(PracticeType::options())
                    ->default(PracticeType::GENERAL_WELLNESS)
                    ->required()
                    ->helperText('Used to customize visit note templates and AI suggestions.')
                    ->disabledOn('view'),

                Radio::make('insurance_billing_enabled')
                    ->label('Documentation & Billing Mode')
                    ->options([
                        0 => 'Simple Visit Note Mode',
                        1 => 'SOAP / Insurance Documentation Mode',
                    ])
                    ->descriptions([
                        0 => 'Best for cash-pay, wellness, and practices that do not need insurance-style SOAP documentation.',
                        1 => 'Shows structured SOAP fields and insurance-oriented documentation tools.',
                    ])
                    ->default(0)
                    ->afterStateHydrated(fn (Radio $component, $state) => $component->state((int) (bool) $state))
                    ->dehydrateStateUsing(fn ($state): bool => (bool) $state)
                    ->helperText('You can change this later. Existing saved notes are not automatically rewritten.')
                    ->disabledOn('view'),

                Toggle::make('is_active')
                    ->default(true)
                    ->disabledOn('view'),
            ]);
    }

    private static function publicLinksPanel(?Practice $practice): HtmlString
    {
        if (! $practice?->exists) {
            return new HtmlString('');
        }

        $links = [
            'New Patient Request link' => route('public.practice.new-patient', ['practiceSlug' => $practice->slug]),
            'Existing Patient Access link' => route('public.practice.existing-patient', ['practiceSlug' => $practice->slug]),
            'Appointment Request link' => route('public.practice.request-appointment', ['practiceSlug' => $practice->slug]),
        ];

        $rows = collect($links)
            ->map(function (string $url, string $label): string {
                $snippetText = match ($label) {
                    'New Patient Request link' => 'Request a New Patient Appointment',
                    'Existing Patient Access link' => 'Existing Patient Access',
                    default => 'Request an Appointment',
                };
                $snippet = '<a href="'.$url.'">'.$snippetText.'</a>';

                return '<div style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#ffffff;">'
                    .'<div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:6px;">'.e($label).'</div>'
                    .'<div style="font-size:13px;color:#0f766e;word-break:break-all;">'.e($url).'</div>'
                    .'<pre style="margin:10px 0 0;padding:10px;background:#f8fafc;border-radius:6px;color:#334155;font-size:12px;white-space:pre-wrap;word-break:break-all;">'.e($snippet).'</pre>'
                    .'</div>';
            })
            ->implode('');

        return new HtmlString(
            '<section style="border:1px solid #ccfbf1;background:#f0fdfa;border-radius:8px;padding:16px;margin:8px 0;">'
            .'<h3 style="margin:0;color:#134e4a;font-size:15px;font-weight:800;">Website links</h3>'
            .'<p style="margin:6px 0 14px;color:#475569;font-size:13px;line-height:1.5;">Use these stable public links on the practice website. Existing-patient access never reveals whether an email exists.</p>'
            .'<div style="display:grid;gap:12px;">'.$rows.'</div>'
            .'</section>'
        );
    }
}
