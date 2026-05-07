<?php

namespace App\Filament\Pages;

use App\Models\Practice;
use App\Models\PractitionerReviewSubmission;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PractitionerReviewPage extends Page
{
    protected static ?string $slug = 'practitioner-review';

    protected static ?string $title = 'Practitioner Review';

    protected static ?string $navigationLabel = 'Practitioner Review';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.practitioner-review';

    public ?string $practice_type = null;
    public ?string $clinic_size = null;
    public ?string $current_systems = null;
    public ?string $first_impression = null;
    public ?int $setup_clarity_rating = null;
    public ?string $setup_checklist_helpfulness = null;
    public ?string $confusing_setup_step = null;
    public ?string $website_links_feedback = null;
    public ?string $scheduling_preference = null;
    public ?string $online_forms_feedback = null;
    public ?string $notes_workflow = null;
    public ?string $ai_feedback = null;
    public ?string $follow_up_feedback = null;
    public ?string $pricing_feedback = null;
    public ?string $subscription_blockers = null;
    public ?string $most_useful = null;
    public ?string $most_confusing = null;
    public ?string $one_change = null;
    public bool $may_contact = false;
    public ?string $contact_info = null;
    public bool $discount_acknowledged = false;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->practice_id !== null;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $practice = $this->practice();
        $latest = $practice ? $this->latestSubmission($practice) : null;

        if (! $latest) {
            $this->practice_type = $practice?->practice_type;

            return;
        }

        $this->practice_type = $latest->practice_type;
        $this->clinic_size = $latest->clinic_size;
        $this->current_systems = implode("\n", $latest->current_systems ?? []);
        $this->first_impression = $latest->first_impression;
        $this->setup_clarity_rating = $latest->setup_clarity_rating;
        $this->setup_checklist_helpfulness = $latest->setup_checklist_helpfulness;
        $this->confusing_setup_step = $latest->confusing_setup_step;
        $this->website_links_feedback = $latest->website_links_feedback;
        $this->scheduling_preference = $latest->scheduling_preference;
        $this->online_forms_feedback = $latest->online_forms_feedback;
        $this->notes_workflow = $latest->notes_workflow;
        $this->ai_feedback = $latest->ai_feedback;
        $this->follow_up_feedback = $latest->follow_up_feedback;
        $this->pricing_feedback = $latest->pricing_feedback;
        $this->subscription_blockers = $latest->subscription_blockers;
        $this->most_useful = $latest->most_useful;
        $this->most_confusing = $latest->most_confusing;
        $this->one_change = $latest->one_change;
        $this->may_contact = (bool) $latest->may_contact;
        $this->contact_info = $latest->contact_info;
        $this->discount_acknowledged = (bool) $latest->discount_acknowledged;
    }

    public function submit(): void
    {
        $practice = $this->practice();
        abort_unless($practice, 403);

        $data = $this->validate();

        PractitionerReviewSubmission::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'user_id' => auth()->id(),
            'practice_type' => $data['practice_type'] ?? null,
            'clinic_size' => $data['clinic_size'] ?? null,
            'current_systems' => $this->systemsList($data['current_systems'] ?? null),
            'first_impression' => $data['first_impression'] ?? null,
            'setup_clarity_rating' => $data['setup_clarity_rating'] ?? null,
            'setup_checklist_helpfulness' => $data['setup_checklist_helpfulness'] ?? null,
            'confusing_setup_step' => $data['confusing_setup_step'] ?? null,
            'website_links_feedback' => $data['website_links_feedback'] ?? null,
            'scheduling_preference' => $data['scheduling_preference'] ?? null,
            'online_forms_feedback' => $data['online_forms_feedback'] ?? null,
            'notes_workflow' => $data['notes_workflow'] ?? null,
            'ai_feedback' => $data['ai_feedback'] ?? null,
            'follow_up_feedback' => $data['follow_up_feedback'] ?? null,
            'pricing_feedback' => $data['pricing_feedback'] ?? null,
            'subscription_blockers' => $data['subscription_blockers'] ?? null,
            'most_useful' => $data['most_useful'] ?? null,
            'most_confusing' => $data['most_confusing'] ?? null,
            'one_change' => $data['one_change'] ?? null,
            'may_contact' => (bool) ($data['may_contact'] ?? false),
            'contact_info' => $data['contact_info'] ?? null,
            'discount_acknowledged' => (bool) ($data['discount_acknowledged'] ?? false),
            'submitted_at' => now(),
        ]);

        Notification::make()
            ->title('Practitioner review submitted.')
            ->success()
            ->send();
    }

    public function latestSubmission(?Practice $practice = null): ?PractitionerReviewSubmission
    {
        $practice ??= $this->practice();

        return $practice
            ? PractitionerReviewSubmission::withoutPracticeScope()
                ->with('user')
                ->where('practice_id', $practice->id)
                ->latest('submitted_at')
                ->latest('id')
                ->first()
            : null;
    }

    public function getViewData(): array
    {
        $practice = $this->practice();

        return [
            'practice' => $practice,
            'latestSubmission' => $this->latestSubmission($practice),
        ];
    }

    protected function rules(): array
    {
        return [
            'practice_type' => ['nullable', 'string', 'max:255'],
            'clinic_size' => ['nullable', 'string', 'max:255'],
            'current_systems' => ['nullable', 'string', 'max:2000'],
            'first_impression' => ['nullable', 'string', 'max:5000'],
            'setup_clarity_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'setup_checklist_helpfulness' => ['nullable', 'string', 'max:255'],
            'confusing_setup_step' => ['nullable', 'string', 'max:255'],
            'website_links_feedback' => ['nullable', 'string', 'max:255'],
            'scheduling_preference' => ['nullable', 'string', 'max:255'],
            'online_forms_feedback' => ['nullable', 'string', 'max:255'],
            'notes_workflow' => ['nullable', 'string', 'max:255'],
            'ai_feedback' => ['nullable', 'string', 'max:255'],
            'follow_up_feedback' => ['nullable', 'string', 'max:255'],
            'pricing_feedback' => ['nullable', 'string', 'max:255'],
            'subscription_blockers' => ['nullable', 'string', 'max:5000'],
            'most_useful' => ['nullable', 'string', 'max:5000'],
            'most_confusing' => ['nullable', 'string', 'max:5000'],
            'one_change' => ['nullable', 'string', 'max:5000'],
            'may_contact' => ['boolean'],
            'contact_info' => ['nullable', 'string', 'max:255'],
            'discount_acknowledged' => ['accepted'],
        ];
    }

    private function practice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        return $practiceId ? Practice::query()->find($practiceId) : null;
    }

    private function systemsList(?string $value): array
    {
        if (! filled($value)) {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
