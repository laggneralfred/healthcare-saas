<?php

namespace App\Filament\Pages;

use App\Models\Practice;
use App\Services\PracticeContext;
use App\Services\PracticeSetupChecklistService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PracticeSetupChecklistPage extends Page
{
    protected static ?string $slug = 'setup-checklist';

    protected static ?string $title = 'Setup Checklist';

    protected static ?string $navigationLabel = 'Setup Checklist';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.practice-setup-checklist';

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageOperations() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function getViewData(): array
    {
        $practiceId = PracticeContext::currentPracticeId();
        $practice = $practiceId ? Practice::query()->find($practiceId) : null;

        return [
            'practice' => $practice,
            'setupChecklist' => $practice
                ? app(PracticeSetupChecklistService::class)->forPractice($practice)
                : null,
        ];
    }
}
