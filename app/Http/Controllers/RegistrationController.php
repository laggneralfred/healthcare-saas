<?php

namespace App\Http\Controllers;

use App\Mail\TrialWelcomeMail;
use App\Mail\TrialSignupNotificationMail;
use App\Models\Practice;
use App\Models\TrialSignup;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LegalAcceptanceService;
use App\Services\Onboarding\PracticeStarterDefaultsService;
use App\Support\PracticeAccessRoles;
use App\Support\PracticeType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'practice_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
            'practice_type' => 'nullable|required_without:discipline|in:general_wellness,tcm_acupuncture,five_element_acupuncture,chiropractic,massage_therapy,physiotherapy',
            'discipline' => 'nullable|required_without:practice_type|in:Acupuncture,Massage Therapy,Chiropractic,Physiotherapy',
            'phone' => 'nullable|string|max:20',
            'referral_source' => 'nullable|in:Google,Facebook,Colleague,Conference,Other',
            'terms_accepted' => 'required|accepted',
        ]);

        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser && $existingUser->practice_id) {
            return back()->withErrors(['email' => 'An account with this email already exists. Please log in instead.'])->withInput();
        }

        if ($existingUser && ! $existingUser->practice_id) {
            Auth::login($existingUser);
            return redirect('/onboarding');
        }

        $validated = $request->only([
            'practice_name', 'first_name', 'last_name', 'email',
            'password', 'practice_type', 'discipline', 'phone', 'referral_source',
        ]);
        $practiceType = $validated['practice_type'] ?? PracticeType::fromDiscipline($validated['discipline'] ?? null);
        $discipline = $validated['discipline'] ?? PracticeType::disciplineFallback($practiceType);

        // Generate unique slug
        $slug = Str::slug($validated['practice_name']);
        $originalSlug = $slug;
        $counter = 2;
        while (Practice::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        // Create practice with 30-day trial
        $practice = Practice::create([
            'name' => $validated['practice_name'],
            'slug' => $slug,
            'timezone' => 'UTC',
            'is_active' => true,
            'discipline' => $discipline,
            'practice_type' => $practiceType,
            'referral_source' => $validated['referral_source'] ?? null,
            'trial_ends_at' => now()->addDays(30),
        ]);

        // Create user linked to practice
        $user = User::create([
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'practice_id' => $practice->id,
        ]);
        PracticeAccessRoles::assignOwner($user);

        $legalAcceptanceService = app(LegalAcceptanceService::class);

        foreach (['terms_of_service', 'privacy_policy'] as $documentKey) {
            $legalAcceptanceService->acceptCurrent($practice, $user, $documentKey, $request, 'register');
        }

        app(PracticeStarterDefaultsService::class)->seed($practice, $user);

        $trialSignup = TrialSignup::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $validated['phone'] ?? null,
            'practice_name' => $practice->name,
            'profession' => PracticeType::label($practiceType),
            'practice_type' => $practiceType,
            'heard_about_us' => $validated['referral_source'] ?? null,
            'source' => 'register',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signed_up_at' => now(),
        ]);

        // Audit logging
        AuditLogger::created($practice);
        AuditLogger::created($user);

        // Send welcome email
        Mail::to($user->email)->send(new TrialWelcomeMail($practice, $user));
        Mail::to(config('mail.trial_signup_notification_email'))
            ->send(new TrialSignupNotificationMail($trialSignup));

        // Log the user in
        Auth::login($user);

        // Flash welcome message
        session()->flash('welcome_message', 'Welcome to Practiq! Your 30-day free trial has started.');

        return redirect('/onboarding');
    }
}
