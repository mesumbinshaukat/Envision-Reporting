<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: light;
        }

        .hero-gradient {
            background: radial-gradient(circle at top left, rgba(17, 45, 78, 0.15), transparent 40%),
                        radial-gradient(circle at 80% -10%, rgba(3, 105, 161, 0.18), transparent 45%),
                        linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .nav-blur {
            backdrop-filter: blur(16px);
            background-color: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .section-spacing {
            padding-top: clamp(3rem, 6vw, 6rem);
            padding-bottom: clamp(3rem, 6vw, 6rem);
        }

        .glass-card {
            backdrop-filter: blur(18px);
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.85), rgba(241, 245, 249, 0.65));
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 18px 35px -20px rgba(15, 23, 42, 0.35);
        }

        .feature-card {
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 45px -30px rgba(15, 23, 42, 0.45);
        }

        .timeline::before {
            content: "";
            position: absolute;
            inset: 0;
            margin: auto;
            width: 2px;
            height: 100%;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.1), rgba(30, 64, 175, 0.25));
        }

        .fade-in-up {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .fade-in-scale {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .animate-in {
            opacity: 1 !important;
            transform: none !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in-up,
            .fade-in-scale {
                transition: none;
            }
        }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased">
    <header class="sticky top-0 z-50 nav-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center space-x-3">
                    <!-- <img src="{{ asset('assets/logo.png') }}" alt="{{ config('app.name') }} logo" class="h-12 w-auto"> -->
                    <div class="sm:block">
                        <p class="text-xs uppercase tracking-[0.3em] text-navy-900">BIZENTIFY</p>
                        <p class="text-base font-semibold text-slate-700">{{ config('app.name') }}</p>
                    </div>
                </a>
                <nav class="flex items-center space-x-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2 rounded-full bg-navy-900 text-white font-semibold hover:bg-navy-800 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2 rounded-full border border-navy-900 text-navy-900 font-medium hover:bg-navy-900 hover:text-white transition">Login</a>
                        <a href="{{ route('register') }}" class="px-5 py-2 rounded-full bg-navy-900 text-white font-semibold hover:bg-navy-800 transition">Register</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="hero-gradient">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 section-spacing">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6" data-animate="fade-in-up">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900/5 text-xs font-semibold text-slate-700 uppercase tracking-wide">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Unified Workforce Intelligence</span>
                        </span>
                        <h1 class="text-4xl sm:text-5xl font-extrabold text-navy-900 leading-tight">
                            Operational clarity for every client, employee and pay-cycle.
                        </h1>
                        <p class="text-lg text-slate-600 max-w-xl">
                            {{ config('app.name') }} brings geolocation attendance, adaptive office scheduling, financial reporting and real-time insights together under a single, elegant dashboard that scales with every team.
                        </p>
                        <div class="flex flex-wrap items-center gap-4">
                            @guest
                                <a href="{{ route('register') }}" class="px-8 py-3 rounded-full bg-navy-900 text-white font-semibold shadow-lg shadow-navy-900/20 hover:-translate-y-0.5 transition">
                                    Start free trial
                                </a>
                                <a href="{{ route('login') }}" class="px-8 py-3 rounded-full border border-navy-900 text-navy-900 font-semibold hover:bg-navy-900 hover:text-white transition">
                                    Login
                                </a>
                            @else
                                <a href="{{ url('/dashboard') }}" class="px-8 py-3 rounded-full bg-navy-900 text-white font-semibold shadow-lg shadow-navy-900/20 hover:-translate-y-0.5 transition">
                                    Continue to dashboard
                                </a>
                            @endguest
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6">
                            <div class="glass-card rounded-2xl p-4 shadow-sm" data-animate="fade-in-scale">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Average rollout</p>
                                <p class="mt-2 text-3xl font-bold text-navy-900">7 days</p>
                                <p class="text-xs text-slate-500 mt-1">to onboard an entire company</p>
                            </div>
                            <div class="glass-card rounded-2xl p-4 shadow-sm" data-animate="fade-in-scale">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Trusted records</p>
                                <p class="mt-2 text-3xl font-bold text-navy-900">1.2M+</p>
                                <p class="text-xs text-slate-500 mt-1">attendance events secured</p>
                            </div>
                            <div class="glass-card rounded-2xl p-4 shadow-sm" data-animate="fade-in-scale">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Finance clarity</p>
                                <p class="mt-2 text-3xl font-bold text-navy-900">18%</p>
                                <p class="text-xs text-slate-500 mt-1">faster close cycles</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative" data-animate="fade-in-scale">
                        <div class="absolute inset-0 blur-3xl bg-gradient-to-br from-sky-300/40 to-navy-900/20 rounded-3xl"></div>
                        <div class="relative rounded-3xl border border-white/40 shadow-xl overflow-hidden bg-white">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-6">
                                <div class="col-span-1 sm:col-span-2 p-5 rounded-2xl bg-slate-900 text-white">
                                    <p class="text-xs uppercase tracking-wide text-white/70">Live attendance</p>
                                    <p class="mt-2 text-2xl font-semibold">Geo-verified check-ins</p>
                                    <p class="mt-3 text-sm text-white/80">Hybrid GPS + IP safeguards synced to your office radius.</p>
                                </div>
                                <div class="p-5 rounded-2xl border border-slate-200">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Scheduling</p>
                                    <p class="mt-2 text-xl font-semibold text-navy-900">Smart shifts</p>
                                    <p class="mt-2 text-xs text-slate-500">Alternate weekend logic & cross-midnight coverage.</p>
                                </div>
                                <div class="p-5 rounded-2xl border border-slate-200">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Finance</p>
                                    <p class="mt-2 text-xl font-semibold text-navy-900">Invoices & payroll</p>
                                    <p class="mt-2 text-xs text-slate-500">Granular approvals, currency control & audit-ready exports.</p>
                                </div>
                                <div class="col-span-2 p-5 rounded-2xl border border-slate-200">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Analytics</p>
                                    <p class="mt-2 text-xl font-semibold text-navy-900">Engagement pulse</p>
                                    <p class="mt-3 text-xs text-slate-500">Track overtime, late trends and closure impact in real time.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Pillars -->
        <section class="bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 section-spacing space-y-12">
                <div class="text-center space-y-4" data-animate="fade-in-up">
                    <h2 class="text-3xl sm:text-4xl font-bold text-navy-900">Everything your operations team needs to stay ahead</h2>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto">From admin policy controls to employee self-service, every workflow is intentionally designed to feel familiar, fast and future-ready.</p>
                </div>

                <div class="grid gap-8 lg:grid-cols-3" data-animate="fade-in-up">
                    <article class="feature-card rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l3 3m6-3.978a8.5 8.5 0 11-8.5-8.5 8.5 8.5 0 018.5 8.5z"/></svg>
                            </div>
                            <h3 class="text-xl font-semibold text-navy-900">Adaptive Attendance Engine</h3>
                        </div>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">Hybrid geolocation sampling, IP allowlists and calibration tooling ensure every check-in is trusted, whether employees are on-site, remote or travelling.</p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-600">
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>Dynamic office radius with cross-midnight protection.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>Instant anomaly logging for overrides, failures and compliance audits.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>Employee self-check dashboards with GPS diagnostics.</span></li>
                        </ul>
                    </article>

                    <article class="feature-card rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-sky-100 flex items-center justify-center text-sky-600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h7l5 5v9a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-xl font-semibold text-navy-900">Policy-aware Scheduling</h3>
                        </div>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">Set office timings, alternating weekends and exceptional closures once—attendance, leave balances and analytics honour those rules everywhere.</p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-600">
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-sky-500"></span><span>Office calendar designer with holiday overlays and admin approvals.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-sky-500"></span><span>Real-time schedule clash detection across teams and locations.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-sky-500"></span><span>Automated leave exclusions for non-working or closed days.</span></li>
                        </ul>
                    </article>

                    <article class="feature-card rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h11m-11 4h7m3 8l4-4-4-4m4 4H10a7 7 0 010-14h1"/></svg>
                            </div>
                            <h3 class="text-xl font-semibold text-navy-900">Finance & Compliance Intelligence</h3>
                        </div>
                        <p class="mt-4 text-sm text-slate-600 leading-relaxed">Invoices, salary releases, bonuses and multi-currency ledgers live in one secure workspace—complete with approval trails and export-ready reports.</p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-600">
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span><span>One-click payroll releases tied to attendance eligibility.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span><span>Tiered access policies for finance, HR and leadership cohorts.</span></li>
                            <li class="flex items-start space-x-2"><span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span><span>Comprehensive audit pack with exchange-rate history and attachments.</span></li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <!-- Workflow Timeline -->
        <section class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 section-spacing">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6" data-animate="fade-in-up">
                        <h2 class="text-3xl sm:text-4xl font-bold">Your entire employee lifecycle, orchestrated</h2>
                        <p class="text-base text-slate-200">Every step is automated yet reviewable—from onboarding to payout. No more spreadsheets, manual reconciliations or lost approvals.</p>
                        <div class="flex flex-wrap gap-4">
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">Guided onboarding flows</div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">Attendance → Payroll sync</div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold">Realtime compliance alerts</div>
                        </div>
                    </div>
                    <div class="relative" data-animate="fade-in-scale">
                        <div class="timeline absolute left-1/2 -translate-x-1/2 inset-y-0"></div>
                        <div class="relative space-y-10">
                            @php
                                $steps = [
                                    ['title' => 'Centralised employee onboarding', 'description' => 'Capture documentation, assign currencies and roll out digital contracts in minutes.'],
                                    ['title' => 'Smart scheduling & closures', 'description' => 'Define working calendars, IP whitelists and closure overrides that sync everywhere.'],
                                    ['title' => 'Precision attendance tracking', 'description' => 'Geolocation, device fingerprinting and calibration logs maintain accuracy and trust.'],
                                    ['title' => 'Actionable dashboards & reports', 'description' => 'Visualise productivity, overtime, payable liabilities and forecast workload instantly.'],
                                    ['title' => 'Smooth payroll execution', 'description' => 'Issue salary releases, bonuses and reimbursements aligned with verified attendance.'],
                                ];
                            @endphp
                            @foreach ($steps as $index => $step)
                                <div class="relative flex gap-4 lg:gap-6">
                                    <div class="mt-1">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-white/40 bg-white/10 text-sm font-semibold">{{ $index + 1 }}</span>
                                    </div>
                                    <div class="bg-white/10 rounded-2xl p-5 shadow-lg shadow-black/5">
                                        <h4 class="text-lg font-semibold">{{ $step['title'] }}</h4>
                                        <p class="mt-2 text-sm text-slate-200 leading-relaxed">{{ $step['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Insight Highlights -->
        <section class="bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 section-spacing grid gap-10 lg:grid-cols-2">
                <div class="space-y-6" data-animate="fade-in-up">
                    <h2 class="text-3xl sm:text-4xl font-bold text-navy-900">A dashboard that feels tailored to your team</h2>
                    <p class="text-base text-slate-600">Interactive widgets, filters and drill-downs let operators answer questions in seconds. Every module is crafted to echo the app experience you already have on the inside.</p>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-navy-900">Real-time attendance intelligence</h3>
                                <p class="text-sm text-slate-600">Spot late check-ins, unresolved fix requests and IP overrides the moment they happen.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-navy-900">Instant financial snapshots</h3>
                                <p class="text-sm text-slate-600">Understand receivables, releases and currency impacts without opening spreadsheets.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-navy-900">Automations that stay under control</h3>
                                <p class="text-sm text-slate-600">Every automation logs context for auditability and can be overridden with manager approval.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative" data-animate="fade-in-scale">
                    <div class="absolute -inset-4 bg-gradient-to-br from-navy-900/10 to-sky-400/10 rounded-3xl blur-2xl"></div>
                    <div class="relative rounded-3xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                        <div class="p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Live insights</p>
                                <span class="inline-flex items-center space-x-1 text-xs text-emerald-600">
                                    <span class="animate-ping h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Up to date</span>
                                </span>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500 uppercase">Attendance health</p>
                                    <p class="mt-2 text-2xl font-bold text-navy-900">97%</p>
                                    <p class="text-xs text-emerald-600 mt-1">+3.1% week over week</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-500 uppercase">Payroll ready</p>
                                    <p class="mt-2 text-2xl font-bold text-navy-900">82%</p>
                                    <p class="text-xs text-yellow-500 mt-1">18 pending fix requests</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                    <p class="text-xs text-slate-500 uppercase">Upcoming closures</p>
                                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                        <li>• Independence Day (Company-wide)</li>
                                        <li>• Alternate Saturday – Tech & Ops</li>
                                        <li>• HQ maintenance downtime</li>
                                    </ul>
                                </div>
                                <div class="rounded-xl bg-slate-900 text-white p-4 sm:col-span-2">
                                    <p class="text-xs text-white/70 uppercase">Instant actions</p>
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">Generate audit report</span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">Review late arrivals</span>
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">Approve salary release</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Voices section -->
        <section class="bg-white">
            <div class="max-w-7xl mx-able px-4 sm:px-6 lg:px-8 section-spacing">
                <div class="bg-slate-900 rounded-3xl px-8 py-12 sm:px-12 lg:px-16 text-white space-y-8" data-animate="fade-in-up">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl space-y-4">
                            <p class="text-sm uppercase tracking-[0.4em] text-white/60">What teams experience</p>
                            <h2 class="text-3xl sm:text-4xl font-bold leading-snug">“We finally see attendance, finance and policy data in one story. It makes the leadership huddles feel proactive instead of reactive.”</h2>
                        </div>
                        <div class="text-sm text-white/70">
                            <p class="font-semibold text-white">Ops Director, Venture-backed SaaS</p>
                            <p>Raised accuracy of payroll-ready attendance to 99.2% within first quarter.</p>
                        </div>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 text-sm text-white/70">
                        <div>
                            <p class="text-2xl font-semibold text-white">65%</p>
                            <p class="mt-1">reduction in time spent reconciling leave and office closures.</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-white">40hrs</p>
                            <p class="mt-1">saved monthly on manual payroll adjustments.</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-white">3x</p>
                            <p class="mt-1">increase in fix-request resolution speed.</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-white">100%</p>
                            <p class="mt-1">visibility into IP overrides and geolocation anomalies.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 section-spacing text-center text-white space-y-6" data-animate="fade-in-up">
                <p class="uppercase tracking-[0.4em] text-white/60 text-xs">Launch ready</p>
                <h2 class="text-3xl sm:text-4xl font-bold">Transform the way you operate by Monday</h2>
                <p class="max-w-2xl mx-auto text-base text-white/80">We migrate your legacy spreadsheets, import historic attendance and configure policies so teams can start executing with confidence.</p>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="px-8 py-3 rounded-full bg-white text-navy-900 font-semibold hover:bg-slate-100 transition">Create account</a>
                        <a href="{{ route('login') }}" class="px-8 py-3 rounded-full border border-white text-white font-semibold hover:bg-white hover:text-navy-900 transition">Login</a>
                    @else
                        <a href="{{ url('/dashboard') }}" class="px-8 py-3 rounded-full bg-white text-navy-900 font-semibold hover:bg-slate-100 transition">Open dashboard</a>
                    @endguest
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid gap-8 md:grid-cols-3">
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('assets/logo.png') }}" alt="{{ config('app.name') }} logo" class="h-10 w-auto">
                        <span class="text-lg font-semibold text-navy-900">{{ config('app.name') }}</span>
                    </div>
                    <p class="text-sm text-slate-600">The unified platform for attendance precision, finance transparency and employee clarity.</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-slate-600">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-navy-900">Modules</p>
                        <p>Attendance & IP controls</p>
                        <p>Office schedules & closures</p>
                        <p>Payroll & bonuses</p>
                        <p>Invoice lifecycle</p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-navy-900">Resources</p>
                        <p>Security architecture</p>
                        <p>Role-based access</p>
                        <p>Hybrid workforce playbook</p>
                        <p>Change management kit</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <p class="text-sm font-semibold text-navy-900">Need a guided tour?</p>
                    <p class="text-sm text-slate-600">We’ll configure a sandbox that mirrors your structure and walk through live dashboards.</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="mailto:hello@example.com" class="px-4 py-2 rounded-full border border-navy-900 text-navy-900 text-sm font-semibold hover:bg-navy-900 hover:text-white transition">Book a demo</a>
                        <a href="tel:+1234567890" class="px-4 py-2 rounded-full bg-navy-900 text-white text-sm font-semibold hover:bg-navy-800 transition">Call sales</a>
                    </div>
                </div>
            </div>
            <div class="mt-10 border-t border-slate-200 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <div class="flex items-center space-x-4 mt-3 sm:mt-0">
                    <a href="#" class="hover:text-navy-900">Privacy</a>
                    <span>&bull;</span>
                    <a href="#" class="hover:text-navy-900">Terms</a>
                    <span>&bull;</span>
                    <a href="#" class="hover:text-navy-900">Support</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15
            });

            document.querySelectorAll('[data-animate]').forEach(element => {
                const animation = element.dataset.animate;
                element.classList.add(animation === 'fade-in-scale' ? 'fade-in-scale' : 'fade-in-up');
                observer.observe(element);
            });
        });
    </script>
</body>
</html>
