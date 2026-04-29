<?php

namespace App\Providers;

use App\Models\Practitioner;
use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\PracticePaymentMethod;
use App\Models\User;
use App\Observers\PractitionerObserver;
use App\Policies\AppointmentPolicy;
use App\Policies\CheckoutSessionPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\MedicalHistoryPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PracticePolicy;
use App\Policies\PracticePaymentMethodPolicy;
use App\Policies\PractitionerPolicy;
use App\Policies\UserPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(CheckoutSession::class, CheckoutSessionPolicy::class);
        Gate::policy(Encounter::class, EncounterPolicy::class);
        Gate::policy(MedicalHistory::class, MedicalHistoryPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Practice::class, PracticePolicy::class);
        Gate::policy(PracticePaymentMethod::class, PracticePaymentMethodPolicy::class);
        Gate::policy(Practitioner::class, PractitionerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
            Request::setTrustedProxies(
                ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        Practitioner::observe(PractitionerObserver::class);
    }
}
