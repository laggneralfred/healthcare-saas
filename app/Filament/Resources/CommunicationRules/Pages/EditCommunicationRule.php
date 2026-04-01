<?php

namespace App\Filament\Resources\CommunicationRules\Pages;

use App\Filament\Resources\CommunicationRules\CommunicationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunicationRule extends EditRecord
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $offset = $data['send_at_offset_minutes'] ?? 0;

        if ($offset === 0) {
            $data['timing_direction'] = 'at_booking';
            $data['timing_amount']    = null;
            $data['timing_unit']      = 'hours';
        } else {
            $data['timing_direction'] = $offset < 0 ? 'before' : 'after';
            $abs                      = abs($offset);

            if ($abs % 10080 === 0) {
                $data['timing_unit']   = 'weeks';
                $data['timing_amount'] = $abs / 10080;
            } elseif ($abs % 1440 === 0) {
                $data['timing_unit']   = 'days';
                $data['timing_amount'] = $abs / 1440;
            } elseif ($abs % 60 === 0) {
                $data['timing_unit']   = 'hours';
                $data['timing_amount'] = $abs / 60;
            } else {
                $data['timing_unit']   = 'minutes';
                $data['timing_amount'] = $abs;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
