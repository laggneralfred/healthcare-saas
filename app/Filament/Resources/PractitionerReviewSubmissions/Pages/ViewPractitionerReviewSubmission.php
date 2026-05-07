<?php

namespace App\Filament\Resources\PractitionerReviewSubmissions\Pages;

use App\Filament\Resources\PractitionerReviewSubmissions\PractitionerReviewSubmissionResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewPractitionerReviewSubmission extends ViewRecord
{
    protected static string $resource = PractitionerReviewSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('practice.name')->label('Practice'),
                TextEntry::make('user.name')->label('Submitted by')->placeholder('—'),
                TextEntry::make('submitted_at')->label('Submitted')->dateTime()->placeholder('—'),
                TextEntry::make('practice_type')->label('Practice type')->placeholder('—'),
                TextEntry::make('clinic_size')->label('Clinic size')->placeholder('—'),
                TextEntry::make('current_systems')
                    ->label('Current systems')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->placeholder('—'),
                TextEntry::make('first_impression')->label('First impression')->placeholder('—')->columnSpanFull(),
                TextEntry::make('setup_clarity_rating')->label('Setup clarity rating')->placeholder('—'),
                TextEntry::make('setup_checklist_helpfulness')->label('Setup checklist helpfulness')->placeholder('—'),
                TextEntry::make('confusing_setup_step')->label('Confusing setup step')->placeholder('—'),
                TextEntry::make('website_links_feedback')->label('Website links feedback')->placeholder('—'),
                TextEntry::make('scheduling_preference')->label('Scheduling preference')->placeholder('—'),
                TextEntry::make('online_forms_feedback')->label('Online forms feedback')->placeholder('—'),
                TextEntry::make('notes_workflow')->label('Notes workflow')->placeholder('—'),
                TextEntry::make('ai_feedback')->label('AI feedback')->placeholder('—'),
                TextEntry::make('follow_up_feedback')->label('Follow-up feedback')->placeholder('—'),
                TextEntry::make('pricing_feedback')->label('Pricing feedback')->placeholder('—'),
                TextEntry::make('subscription_blockers')->label('Subscription blockers')->placeholder('—')->columnSpanFull(),
                TextEntry::make('most_useful')->label('Most useful')->placeholder('—')->columnSpanFull(),
                TextEntry::make('most_confusing')->label('Most confusing')->placeholder('—')->columnSpanFull(),
                TextEntry::make('one_change')->label('One change')->placeholder('—')->columnSpanFull(),
                IconEntry::make('may_contact')->label('May contact')->boolean(),
                TextEntry::make('contact_info')->label('Contact info')->placeholder('—'),
                IconEntry::make('discount_acknowledged')->label('Discount acknowledged')->boolean(),
            ]);
    }
}
