<?php

namespace App\Services;

class EncounterNoteDocument
{
    public const CHIEF_HEADING = 'Chief Complaint';

    public const NOTES_HEADING = 'Visit Notes';

    public const PLAN_HEADING = 'Plan / Follow-up';

    public static function template(?string $discipline = null): string
    {
        return EncounterDisciplineTemplate::template($discipline);
    }

    public static function fromFields(?string $chiefComplaint, ?string $visitNotes, ?string $plan, ?string $discipline = null): string
    {
        $headings = EncounterDisciplineTemplate::headings($discipline);
        $lastHeading = array_key_last($headings);

        return collect($headings)
            ->map(function (string $heading, int $index) use ($chiefComplaint, $visitNotes, $plan, $lastHeading): string {
                $value = match (true) {
                    in_array($heading, EncounterDisciplineTemplate::chiefHeadings(), true) => trim((string) $chiefComplaint),
                    $index === $lastHeading || in_array($heading, EncounterDisciplineTemplate::planHeadings(), true) => trim((string) $plan),
                    $index === 1 => trim((string) $visitNotes),
                    default => '',
                };

                return "{$heading}:\n{$value}";
            })
            ->implode("\n\n");
    }

    public static function applyToEncounterData(array $data, bool $parseDocument = true): array
    {
        if (! array_key_exists('visit_note_document', $data)) {
            return $data;
        }

        $document = trim((string) $data['visit_note_document']);
        unset($data['visit_note_document']);

        if (! $parseDocument) {
            return $data;
        }

        if ($document === '') {
            $data['chief_complaint'] = null;
            $data['visit_notes'] = null;
            $data['plan'] = null;

            return $data;
        }

        $parsed = self::parse($document, $data['discipline'] ?? null);

        $data['chief_complaint'] = $parsed['chief_complaint'];
        $data['visit_notes'] = $parsed['visit_notes'];
        $data['plan'] = $parsed['plan'];

        return $data;
    }

    /**
     * Parse the document by known headings. If headings are incomplete or out of
     * order, keep the full text as the treatment note so no clinician text is lost.
     *
     * @return array{chief_complaint: ?string, visit_notes: ?string, plan: ?string}
     */
    public static function parse(string $document, ?string $discipline = null): array
    {
        $headings = collect(EncounterDisciplineTemplate::allHeadings())
            ->map(fn (string $heading): string => preg_quote($heading, '/'))
            ->implode('|');
        $pattern = '/^\s*('.$headings.')\s*:\s*$/mi';

        if (! preg_match_all($pattern, $document, $matches, PREG_OFFSET_CAPTURE)) {
            return self::fallback($document);
        }

        $sections = [];
        $count = count($matches[1]);

        for ($index = 0; $index < $count; $index++) {
            $heading = $matches[1][$index][0];
            $headingStart = $matches[0][$index][1];
            $contentStart = $headingStart + strlen($matches[0][$index][0]);
            $contentEnd = $matches[0][$index + 1][1] ?? strlen($document);

            $sections[$heading] = trim(substr($document, $contentStart, $contentEnd - $contentStart));
        }

        $chiefComplaint = null;
        $visitNotes = [];
        $plan = null;

        foreach ($sections as $heading => $content) {
            if (in_array($heading, EncounterDisciplineTemplate::chiefHeadings(), true)) {
                $chiefComplaint = self::nullable($content);

                continue;
            }

            if (in_array($heading, EncounterDisciplineTemplate::planHeadings(), true)) {
                $plan = self::nullable($content);

                continue;
            }

            if (filled($content)) {
                $visitNotes[] = "{$heading}:\n{$content}";
            }
        }

        $visitNotesText = count($visitNotes) === 1
            ? preg_replace('/^[^\n]+:\n/u', '', $visitNotes[0])
            : implode("\n\n", $visitNotes);

        return [
            'chief_complaint' => $chiefComplaint,
            'visit_notes' => self::nullable((string) $visitNotesText),
            'plan' => $plan,
        ];
    }

    /**
     * @return array{chief_complaint: null, visit_notes: string, plan: null}
     */
    private static function fallback(string $document): array
    {
        return [
            'chief_complaint' => null,
            'visit_notes' => trim($document),
            'plan' => null,
        ];
    }

    private static function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
