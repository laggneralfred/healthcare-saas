<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq — Modern Practice Management for Solo Practitioners</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <!-- Navigation -->
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-teal-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xl">P</span>
            </div>
            <span class="font-bold text-xl tracking-tight text-slate-800">Practiq</span>
        </div>
        <div class="flex items-center gap-6 text-sm font-medium">
            <a href="/admin/login" class="text-slate-600 hover:text-teal-600 transition-colors">Log in</a>
            <a href="/register" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg hover:bg-teal-700 transition-all shadow-sm">Start Free Trial</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="max-w-5xl mx-auto px-4 pt-16 pb-24 text-center">
        <h1 class="text-5xl sm:text-6xl font-extrabold text-slate-900 tracking-tight mb-6">
            Practice management <br>
            <span class="text-teal-600">simplified.</span>
        </h1>
        <p class="text-xl text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed">
            The all-in-one platform for solo health practitioners. Online booking, patient intake, 
            clinical notes, and billing — all in one place.
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-12">
            <a href="/register" class="w-full sm:w-auto bg-teal-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-teal-700 transition-all shadow-lg shadow-teal-200">
                Get Started for Free
            </a>
            <a href="https://demo.practiqapp.com/demo-login" class="w-full sm:w-auto bg-white text-slate-700 border border-slate-200 px-8 py-4 rounded-xl font-bold text-lg hover:bg-slate-50 transition-all">
                Watch Demo
            </a>
        </div>

        <!-- Demo Access Box -->
        <div class="max-w-md mx-auto bg-slate-100 border border-slate-200 rounded-xl p-5 text-left shadow-sm">
            <a href="https://demo.practiqapp.com/demo-login" class="flex items-center justify-between group">
                <div>
                    <p class="font-bold text-slate-800 group-hover:text-teal-600 transition-colors">Try the live demo instantly →</p>
                    <p class="text-sm text-slate-600 mt-1">No signup needed. Explore a real acupuncture practice.</p>
                </div>
            </a>
            <div class="mt-4 pt-4 border-t border-slate-200 flex flex-wrap gap-x-4 gap-y-1 text-xs font-mono text-slate-500">
                <span>demo.practiqapp.com</span>
                <span>demo@practiqapp.com</span>
                <span>demo1234</span>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="max-w-7xl mx-auto px-4 py-24 border-t border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div>
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3">Online Booking</h3>
                <p class="text-slate-600 leading-relaxed">Let patients book online 24/7 with a beautiful, mobile-friendly interface that matches your brand.</p>
            </div>
            <div>
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3">Intake & Consent</h3>
                <p class="text-slate-600 leading-relaxed">Paperless forms sent automatically. Patients can sign electronically before they even arrive.</p>
            </div>
            <div>
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3">Clinical Charts</h3>
                <p class="text-slate-600 leading-relaxed">Fast, compliant charting. Specialized templates for acupuncture, massage, and more.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 px-4 text-center text-sm">
        <p>© 2026 Practiq. Built by Alfred, for practitioners.</p>
    </footer>
</body>
</html>
