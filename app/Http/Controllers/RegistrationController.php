<?php

namespace App\Http\Controllers;

use App\Mail\TrialWelcomeMail;
use App\Models\Practice;
use App\Models\User;
use App\Services\AuditLogger;
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
        $validated = $request->validate([
            'practice_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'discipline' => 'required|in:Acupuncture,Massage Therapy,Chiropractic,Physiotherapy',
            'phone' => 'nullable|string|max:20',
            'referral_source' => 'nullable|in:Google,Facebook,Colleague,Conference,Other',
            'terms_accepted' => 'required|accepted',
        ]);

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
            'discipline' => $validated['discipline'],
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

        // Audit logging
        AuditLogger::created($practice);
        AuditLogger::created($user);

        // Send welcome email
        Mail::to($user->email)->send(new TrialWelcomeMail($practice, $user));

        // Log the user in
        Auth::login($user);

        // Flash welcome message
        session()->flash('welcome_message', 'Welcome to Practiq! Your 30-day free trial has started.');

        return redirect('/admin');
    }
}
