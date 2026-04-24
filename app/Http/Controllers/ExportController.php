<?php

namespace App\Http\Controllers;

use App\Jobs\ExportPracticeDataJob;
use App\Models\ExportToken;
use App\Models\Practice;
use App\Services\PracticeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function request(Request $request): RedirectResponse
    {
        $request->validate(['format' => 'required|in:csv,json']);

        $practice = $this->resolvePractice();
        if (!$practice) {
            return back()->with('error', 'No practice associated with your account.');
        }

        // Check authorization: subscribed, active trial, or within 30-day grace
        $this->authorizeExportAccess($practice);

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => $request->input('format'),
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        ExportPracticeDataJob::dispatch($practice->id, $token->id, $request->input('format'));

        return back()->with('message', 'Your export is being prepared. You will receive an email when it is ready.');
    }

    public function download(Request $request, string $tokenId): StreamedResponse|RedirectResponse
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (!$practiceId) {
            abort(404);
        }

        $token = ExportToken::withoutPracticeScope()
            ->where('id', $tokenId)
            ->where('practice_id', $practiceId)
            ->firstOrFail();

        if ($token->isExpired()) {
            abort(410, 'Export link has expired.');
        }

        if (!$token->file_path || !Storage::exists($token->file_path)) {
            abort(404, 'Export file not found.');
        }

        $token->update([
            'downloaded_at' => now(),
            'status' => 'downloaded',
        ]);

        $ext = $token->format === 'csv' ? 'zip' : 'json';
        $filename = "practiq-export.{$ext}";
        $response = Storage::download($token->file_path, $filename);

        // Delete file after download (using callback to ensure it happens after sending)
        Storage::delete($token->file_path);

        return $response;
    }

    private function authorizeExportAccess(\App\Models\Practice $practice): void
    {
        // Allow if subscribed
        if ($practice->subscribed('default')) {
            return;
        }

        // Allow if trial is active
        if ($practice->trial_ends_at && $practice->trial_ends_at->isFuture()) {
            return;
        }

        // Allow if within 30-day grace period after trial expiry
        if ($practice->trial_ends_at && now()->lessThanOrEqualTo($practice->trial_ends_at->addDays(30))) {
            return;
        }

        abort(403, 'Export access not available for your account.');
    }

    private function resolvePractice(): ?Practice
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (!$practiceId) {
            return null;
        }

        return Practice::find($practiceId);
    }
}
