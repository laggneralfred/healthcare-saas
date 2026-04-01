<?php

namespace App\Filament\Pages;

use App\Models\MessageLog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class CommunicationsDashboard extends Page
{
    protected string $view = 'filament.pages.communications-dashboard';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Overview';
    protected static string|\UnitEnum|null $navigationGroup = 'Communications';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Communications Overview';

    public int $sentThisMonth    = 0;
    public int $deliveredCount   = 0;
    public int $failedCount      = 0;
    public int $optedOutCount    = 0;
    public float $deliveryRate   = 0.0;

    public function mount(): void
    {
        $practiceId = Auth::user()?->practice_id;

        $this->sentThisMonth  = MessageLog::where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        $this->deliveredCount = MessageLog::where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'delivered')
            ->count();

        $this->failedCount = MessageLog::where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'failed')
            ->count();

        $this->optedOutCount = MessageLog::where('practice_id', $practiceId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'opted_out')
            ->count();

        $this->deliveryRate = $this->sentThisMonth > 0
            ? round(($this->deliveredCount / $this->sentThisMonth) * 100, 1)
            : 0.0;
    }

    public function getRecentLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return MessageLog::with(['patient', 'messageTemplate', 'practitioner.user'])
            ->latest()
            ->limit(20)
            ->get();
    }
}
