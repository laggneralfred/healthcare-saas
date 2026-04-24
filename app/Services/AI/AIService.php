<?php

namespace App\Services\AI;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AIService
{
    private const IMPROVE_NOTE_SYSTEM_PROMPT = 'You are a clinical documentation assistant for an acupuncture/wellness practice. Improve the wording of the provided note. Do not add facts that are not present. Do not diagnose. Do not assign billing codes. If important details are missing, mention them as "not documented". Return only the improved note text.';

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

    private function improveNoteWithOpenAI(string $note, array $context): string
    {
        $apiKey = config('services.ai.openai.api_key');
        $model = config('services.ai.openai.model', 'gpt-4.1-mini');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new AIUnavailableException('AI is not configured. Set OPENAI_API_KEY to enable Improve Note.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'instructions' => self::IMPROVE_NOTE_SYSTEM_PROMPT,
                    'input' => $this->buildUserPrompt($note, $context),
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

    private function buildUserPrompt(string $note, array $context): string
    {
        $lines = [];

        if (! empty($context['discipline'])) {
            $lines[] = 'Discipline: ' . $context['discipline'];
        }

        if (! empty($context['chief_complaint'])) {
            $lines[] = 'Chief complaint: ' . $context['chief_complaint'];
        }

        $lines[] = 'Note to improve:';
        $lines[] = $note;

        return implode("\n", $lines);
    }
}
