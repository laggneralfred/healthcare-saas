<?php

namespace App\Filament\Resources\PractitionerReviewSubmissions\Pages;

use App\Filament\Resources\PractitionerReviewSubmissions\PractitionerReviewSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListPractitionerReviewSubmissions extends ListRecords
{
    protected static string $resource = PractitionerReviewSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
