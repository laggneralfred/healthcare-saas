<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Practiq</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-5xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            <!-- Left Column: Brand & Trust Signals -->
            <div class="flex flex-col justify-center space-y-8">
                <div>
                    <h1 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4">
                        Practiq
                    </h1>
                    <p class="text-lg text-slate-600">
                        Practice management software built by a practitioner, for practitioners
                    </p>
                </div>

                <!-- Trust Signals -->
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-[#0D7377] mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-slate-700 font-medium">30-day free trial</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-[#0D7377] mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-slate-700 font-medium">No credit card required</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-[#0D7377] mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-slate-700 font-medium">Cancel anytime</span>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-2 pt-4">
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">What you get</p>
                    <ul class="space-y-2 text-slate-700">
                        <li class="flex items-center gap-2">
                            <span class="text-[#0D7377] text-xl">→</span>
                            <span>Online booking for your patients</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-[#0D7377] text-xl">→</span>
                            <span>Intake & consent forms</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-[#0D7377] text-xl">→</span>
                            <span>Clinical visit documentation</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-[#0D7377] text-xl">→</span>
                            <span>Payment processing with Stripe</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Registration Form -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">Start your free trial</h2>

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <!-- Practice Name -->
                    <div>
                        <label for="practice_name" class="block text-sm font-medium text-slate-700 mb-1">
                            Practice Name
                        </label>
                        <input
                            type="text"
                            id="practice_name"
                            name="practice_name"
                            value="{{ old('practice_name') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('practice_name') border-red-500 @enderror"
                            required
                        >
                        @error('practice_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name: First & Last -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">
                                First Name
                            </label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('first_name') border-red-500 @enderror"
                                required
                            >
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">
                                Last Name
                            </label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('last_name') border-red-500 @enderror"
                                required
                            >
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('email') border-red-500 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                            Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('password') border-red-500 @enderror"
                            required
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                            Confirm Password
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent"
                            required
                        >
                    </div>

                    <!-- Practice Type -->
                    <div>
                        <label for="practice_type" class="block text-sm font-medium text-slate-700 mb-1">
                            Practice Type
                        </label>
                        <select
                            id="practice_type"
                            name="practice_type"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('practice_type') border-red-500 @enderror"
                            required
                        >
                            <option value="">Select your practice type</option>
                            <option value="general_wellness" @selected(old('practice_type') === 'general_wellness')>General Wellness</option>
                            <option value="tcm_acupuncture" @selected(old('practice_type') === 'tcm_acupuncture')>TCM Acupuncture</option>
                            <option value="five_element_acupuncture" @selected(old('practice_type') === 'five_element_acupuncture')>Five Element Acupuncture</option>
                            <option value="chiropractic" @selected(old('practice_type') === 'chiropractic')>Chiropractic</option>
                            <option value="massage_therapy" @selected(old('practice_type') === 'massage_therapy')>Massage Therapy</option>
                            <option value="physiotherapy" @selected(old('practice_type') === 'physiotherapy')>Physiotherapy</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Used to customize visit note templates and AI suggestions.</p>
                        @error('practice_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone (Optional) -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">
                            Phone (Optional)
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('phone') border-red-500 @enderror"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- How did you hear about us -->
                    <div>
                        <label for="referral_source" class="block text-sm font-medium text-slate-700 mb-1">
                            How did you hear about us? (Optional)
                        </label>
                        <select
                            id="referral_source"
                            name="referral_source"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0D7377] focus:border-transparent @error('referral_source') border-red-500 @enderror"
                        >
                            <option value="">Select one</option>
                            <option value="Google" @selected(old('referral_source') === 'Google')>Google</option>
                            <option value="Facebook" @selected(old('referral_source') === 'Facebook')>Facebook</option>
                            <option value="Colleague" @selected(old('referral_source') === 'Colleague')>Colleague</option>
                            <option value="Conference" @selected(old('referral_source') === 'Conference')>Conference</option>
                            <option value="Other" @selected(old('referral_source') === 'Other')>Other</option>
                        </select>
                        @error('referral_source')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Trial Notice -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mt-6">
                        <p class="text-sm text-amber-900">
                            <strong>Your Trial:</strong> 30 days of full access at no cost. After your trial expires, your data is retained for 30 days before permanent deletion. You can export your data at any time.
                        </p>
                    </div>

                    <!-- Terms & Privacy Acceptance -->
                    <div class="mt-6">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                id="terms_accepted"
                                name="terms_accepted"
                                value="1"
                                class="mt-1 h-4 w-4 text-[#0D7377] border-slate-300 rounded focus:ring-[#0D7377]"
                                @checked(old('terms_accepted'))
                                required
                            >
                            <span class="text-sm text-slate-700">
                                I have read and agree to the
                                <a href="{{ route('terms') }}" target="_blank" class="text-[#0D7377] hover:underline font-medium">Terms of Service</a>
                                and
                                <a href="{{ route('privacy') }}" target="_blank" class="text-[#0D7377] hover:underline font-medium">Privacy Policy</a>
                            </span>
                        </label>
                        @error('terms_accepted')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-[#0D7377] hover:bg-[#055c69] text-white font-semibold py-3 rounded-lg transition-colors mt-6"
                    >
                        Start Your Free Trial
                    </button>
                </form>

                <!-- Sign In Link -->
                <div class="mt-6 text-center text-sm text-slate-600">
                    Already have an account?
                    <a href="/admin/login" class="text-[#0D7377] hover:underline font-medium">
                        Sign in
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
