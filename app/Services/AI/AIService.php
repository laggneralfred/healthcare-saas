<?php

namespace App\Services\AI;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AIService
{
    private const IMPROVE_NOTE_SYSTEM_PROMPT = 'You are a clinical documentation assistant for an acupuncture/wellness practice. Improve the wording of the provided note. Do not add facts that are not present. Do not diagnose. Do not assign billing codes. If important details are missing, mention them as "not documented". Return only the improved note text.';

    private const DOCUMENTATION_CHECK_SYSTEM_PROMPT = 'You are a clinical documentation completeness assistant for an acupuncture/wellness practice. Review the provided encounter note and identify missing or unclear documentation elements. Do not diagnose. Do not assign billing codes. Do not invent facts. Return a concise checklist. If the note is adequate, say so briefly. Focus on items such as chief complaint, onset/duration, severity, treatment performed, points/techniques if mentioned, treatment time, patient response, follow-up plan, and missing objective findings. Use "not documented" where appropriate.';

    private const IMPORT_MAPPING_SYSTEM_PROMPT = 'You are a CSV import mapping assistant for a healthcare practice management system. Map the uploaded CSV headers to supported patient fields. Only use supported fields. Do not invent fields. Return concise JSON with header-to-field mappings and confidence where possible.';

    private const REMINDER_DRAFT_SYSTEM_PROMPT = 'You are a patient communication assistant for a small healthcare/acupuncture practice. Draft a short, warm, professional reminder message. Do not include diagnosis or sensitive clinical details. Do not promise outcomes. Do not mention AI. Keep it concise and suitable for SMS or email. Return only the message text.';

    public function improveNote(string $note, array $context = []): string
    {
        $note = trim($note);

        if ($note === '') {
            throw new AIUnavailableException('A note is required before AI can improve it.');
        }

        return match (config('services.ai.provider', 'openai')) {
            'openai' => $this->improveNoteWithOpenAI($note, $context),
            default => throw new AIUnavailableException('The configured AI provider is not supported.'),
        };
    }

    public function checkMissingDocumentation(string $note, array $context = []): string
    {
        $note = trim($note);

        if ($note === '') {
            throw new AIUnavailableException('A note is required before AI can check documentation.');
        }

        return match (config('services.ai.provider', 'openai')) {
            'openai' => $this->checkMissingDocumentationWithOpenAI($note, $context),
            default => throw new AIUnavailableException('The configured AI provider is not supported.'),
        };
    }

    public function suggestImportMapping(array $headers, array $supportedFields): array|string
    {
        $headers = array_values(array_filter(array_map(
            fn ($header) => trim((string) $header),
            $headers
        )));

        $supportedFields = array_values(array_filter(array_map(
            fn ($field) => trim((string) $field),
            $supportedFields
        )));

        if ($headers === []) {
            throw new AIUnavailableException('CSV headers are required before AI can suggest import mappings.');
        }

        if ($supportedFields === []) {
            throw new AIUnavailableException('Supported import fields are required before AI can suggest mappings.');
        }

        return match (config('services.ai.provider', 'openai')) {
            'openai' => $this->suggestImportMappingWithOpenAI($headers, $supportedFields),
            default => throw new AIUnavailableException('The configured AI provider is not supported.'),
        };
    }

    public function draftReminderMessage(array $context): string
    {
        $context = array_filter($context, fn ($value) => filled($value));

        if ($context === []) {
            throw new AIUnavailableException('Reminder context is required before AI can draft a message.');
        }

        return match (config('services.ai.provider', 'openai')) {
            'openai' => $this->draftReminderMessageWithOpenAI($context),
            default => throw new AIUnavailableException('The configured AI provider is not supported.'),
        };
    }

    private function improveNoteWithOpenAI(string $note, array $context): string
    {
        return $this->sendOpenAIRequest(
            self::IMPROVE_NOTE_SYSTEM_PROMPT,
            $this->buildUserPrompt($note, $context, 'Note to improve:')
        );
    }

    private function suggestImportMappingWithOpenAI(array $headers, array $supportedFields): array|string
    {
        $text = $this->sendOpenAIRequest(
            self::IMPORT_MAPPING_SYSTEM_PROMPT,
            $this->buildImportMappingPrompt($headers, $supportedFields)
        );

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : $text;
    }

    private function draftReminderMessageWithOpenAI(array $context): string
    {
        return $this->sendOpenAIRequest(
            self::REMINDER_DRAFT_SYSTEM_PROMPT,
            $this->buildReminderDraftPrompt($context)
        );
    }

    private function checkMissingDocumentationWithOpenAI(string $note, array $context): string
    {
        return $this->sendOpenAIRequest(
            self::DOCUMENTATION_CHECK_SYSTEM_PROMPT,
            $this->buildUserPrompt($note, $context, 'Encounter note to review:')
        );
    }

    private function sendOpenAIRequest(string $instructions, string $input): string
    {
        $apiKey = config('services.ai.openai.api_key');
        $model = config('services.ai.openai.model', 'gpt-4.1-mini');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new AIUnavailableException('AI is not configured. Set OPENAI_API_KEY to enable AI features.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => $instructions,
                    'input' => $input,
                    'temperature' => 0.2,
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new AIUnavailableException('AI request failed. Please try again later.', previous: $exception);
        }

        $text = trim((string) data_get($response, 'output_text', ''));

        if ($text === '') {
            throw new AIUnavailableException('AI returned an empty suggestion.');
        }

        return $text;
    }

    private function buildUserPrompt(string $note, array $context, string $noteLabel): string
    {
        $lines = [];

        if (! empty($context['discipline'])) {
            $lines[] = 'Discipline: ' . $context['discipline'];
        }

        if (! empty($context['chief_complaint'])) {
            $lines[] = 'Chief complaint: ' . $context['chief_complaint'];
        }

        $lines[] = $noteLabel;
        $lines[] = $note;

        return implode("\n", $lines);
    }

    private function buildImportMappingPrompt(array $headers, array $supportedFields): string
    {
        return json_encode([
            'csv_headers' => $headers,
            'supported_patient_fields' => $supportedFields,
            'output_shape' => [
                'mappings' => [
                    [
                        'header' => 'CSV header name',
                        'field' => 'supported_field_or_null',
                        'confidence' => 'high|medium|low',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT);
    }

    private function buildReminderDraftPrompt(array $context): string
    {
        return json_encode([
            'patient_first_name' => $context['patient_first_name'] ?? null,
            'practice_name' => $context['practice_name'] ?? null,
            'appointment_datetime' => $context['appointment_datetime'] ?? null,
            'reminder_reason' => $context['reminder_reason'] ?? null,
        ], JSON_PRETTY_PRINT);
    }
}
