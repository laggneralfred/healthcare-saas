<?php

namespace App\Filament\Resources\CommunicationRules\Pages;

use App\Filament\Resources\CommunicationRules\CommunicationRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunicationRule extends CreateRecord
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['practice_id'] = auth()->user()->practice_id;
        return $this->computeOffset($data);
    }

    private function computeOffset(array $data): array
    {
        $direction   = $data['timing_direction'] ?? 'before';
        $amount      = (int) ($data['timing_amount'] ?? 0);
        $unit        = $data['timing_unit'] ?? 'hours';
        $multipliers = ['minutes' => 1, 'hours' => 60, 'days' => 1440, 'weeks' => 10080];
        $minutes     = $amount * ($multipliers[$unit] ?? 60);

        $data['send_at_offset_minutes'] = match ($direction) {
            'before'     => -$minutes,
            'after'      => $minutes,
            default      => 0,
        };

        unset($data['timing_direction'], $data['timing_amount'], $data['timing_unit'], $data['timing_preview']);
        return $data;
    }
}
