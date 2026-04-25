<?php

namespace App\Services;

class EncounterDisciplineTemplate
{
    public const GENERAL = 'general';

    private const TEMPLATES = [
        'acupuncture' => [
            'Chief Complaint',
            'Treatment Notes',
            'Points / Techniques',
            'Plan / Follow-up',
        ],
        'chiropractic' => [
            'Chief Complaint',
            'Spinal / Musculoskeletal Findings',
            'Adjustment / Treatment',
            'Response',
            'Plan / Follow-up',
        ],
        'massage' => [
            'Client Report',
            'Areas Treated',
            'Techniques Used',
            'Response',
            'Home Care / Plan',
        ],
        'physiotherapy' => [
            'Subjective',
            'Objective Measures',
            'Interventions',
            'Assessment',
            'Plan / Goals',
        ],
        self::GENERAL => [
            'Chief Complaint',
            'Visit Notes',
            'Plan / Follow-up',
        ],
    ];

    public static function headings(?string $discipline): array
    {
        return self::TEMPLATES[self::normalize($discipline)] ?? self::TEMPLATES[self::GENERAL];
    }

    public static function template(?string $discipline): string
    {
        return collect(self::headings($discipline))
            ->map(fn (string $heading): string => "{$heading}:\n")
            ->implode("\n");
    }

    public static function normalize(?string $discipline): string
    {
        return match ($discipline) {
            'acupuncture' => 'acupuncture',
            'chiropractic' => 'chiropractic',
            'massage', 'massage_therapy' => 'massage',
            'physiotherapy', 'physical_therapy' => 'physiotherapy',
            default => self::GENERAL,
        };
    }

    public static function allHeadings(): array
    {
        return collect(self::TEMPLATES)
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public static function isTemplate(string $document, ?string $discipline = null): bool
    {
        return self::normalizeDocument($document) === self::normalizeDocument(self::template($discipline));
    }

    public static function isBlankOrTemplate(?string $document): bool
    {
        $document = trim((string) $document);

        if ($document === '') {
            return true;
        }

        foreach (array_keys(self::TEMPLATES) as $discipline) {
            if (self::isTemplate($document, $discipline)) {
                return true;
            }
        }

        return false;
    }

    public static function chiefHeadings(): array
    {
        return [
            'Chief Complaint',
            'Client Report',
            'Subjective',
        ];
    }

    public static function planHeadings(): array
    {
        return [
            'Plan / Follow-up',
            'Home Care / Plan',
            'Plan / Goals',
        ];
    }

    private static function normalizeDocument(string $document): string
    {
        return trim(preg_replace('/\R/u', "\n", $document));
    }
}
