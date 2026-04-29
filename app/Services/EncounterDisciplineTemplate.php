<?php

namespace App\Services;

class EncounterDisciplineTemplate
{
    public const GENERAL = 'general';

    private const TEMPLATES = [
        'general_wellness' => [
            'Reason for Visit',
            'Visit Note',
            'Care Provided',
            'Response',
            'Plan / Follow-up',
        ],
        'tcm_acupuncture' => [
            'Reason for Visit',
            'History / Presentation',
            'TCM Assessment',
            'Pattern Impression',
            'Tongue / Pulse',
            'Treatment Principle',
            'Points / Techniques',
            'Response',
            'Plan / Follow-up',
        ],
        'five_element_acupuncture' => [
            'Reason for Visit',
            'Patient Presentation',
            'Constitutional / Elemental Impression',
            'Color / Sound / Odor / Emotion Observations',
            'Officials / Element Considerations',
            'Treatment Intention',
            'Points Used',
            'Response',
            'Plan / Follow-up',
        ],
        'chiropractic' => [
            'Reason for Visit',
            'History / Presentation',
            'Exam / Findings',
            'Assessment',
            'Treatment Performed',
            'Response',
            'Plan / Follow-up',
        ],
        'massage_therapy' => [
            'Reason for Visit',
            'Client Presentation',
            'Areas Treated',
            'Techniques Used',
            'Tissue Response',
            'Self-Care / Plan',
        ],
        'physiotherapy' => [
            'Reason for Visit',
            'Subjective',
            'Objective / Findings',
            'Assessment',
            'Treatment / Exercises',
            'Response',
            'Plan / Follow-up',
        ],
        self::GENERAL => [
            'Chief Complaint',
            'Chief Complaint / Reason for Visit',
            'Treatment Notes',
            'Points / Techniques',
            'Spinal / Musculoskeletal Findings',
            'Adjustment / Treatment',
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
            'general_wellness' => 'general_wellness',
            'tcm_acupuncture', 'acupuncture' => 'tcm_acupuncture',
            'five_element_acupuncture' => 'five_element_acupuncture',
            'chiropractic' => 'chiropractic',
            'massage', 'massage_therapy' => 'massage_therapy',
            'physiotherapy', 'physical_therapy' => 'physiotherapy',
            default => 'general_wellness',
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
            'Chief Complaint / Reason for Visit',
            'Reason for Visit',
            'Client Report',
            'Client Presentation',
            'Subjective',
        ];
    }

    public static function planHeadings(): array
    {
        return [
            'Plan / Follow-up',
            'Home Care / Plan',
            'Self-Care / Plan',
            'Plan / Goals',
        ];
    }

    private static function normalizeDocument(string $document): string
    {
        return trim(preg_replace('/\R/u', "\n", $document));
    }
}
