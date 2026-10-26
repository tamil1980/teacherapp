@extends('layouts.app')

@section('title', 'Contact us')
@section('meta_description', 'Get in touch with the EduBook support team by phone, email or a support ticket. Office hours, contact details and complaint escalation.')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

        <div class="max-w-3xl">
            <h1 class="page-title">Contact us</h1>
            <p class="page-subtitle">
                Questions about a booking, a payment or a teacher? We are happy to help. Choose the fastest way to
                reach us below.
            </p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            {{-- Contact channels --}}
            <div class="space-y-4 lg:col-span-2">
                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Reach the support team</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Support phone</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">
                                <a href="tel:{{ $supportPhone }}" class="hover:text-brand-600">{{ $supportPhone }}</a>
                            </p>
                            <p class="hint">Toll-free voice support during office hours. Standard call charges apply.</p>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Support email</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">
                                <a href="mailto:{{ $supportEmail }}" class="break-all hover:text-brand-600">{{ $supportEmail }}</a>
                            </p>
                            <p class="hint">We reply to email within one working day.</p>
                        </div>
                    </div>
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Support tickets &mdash; the fastest route</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Support tickets are available to logged-in users, and they are the best way to get a tracked,
                        written answer. A ticket keeps the full conversation and the related booking reference in one
                        place, so you never have to repeat yourself on a phone call.
                    </p>

                    <ul class="mt-4 space-y-2 text-sm leading-relaxed text-slate-600">
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                            <span>Attach a booking reference so we can pull the exact session record straight away.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                            <span>Choose the right category &mdash; booking, payment, teacher, technical, complaint &mdash; so it reaches the correct team.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                            <span>You will get a written response on the ticket, with a notification when it is updated.</span>
                        </li>
                    </ul>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('support.create') }}" class="btn-primary">Raise a ticket</a>
                        <a href="{{ route('support.index') }}" class="btn-secondary">My tickets</a>
                    </div>

                    <div class="mt-5 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <p class="font-semibold">Not signed in yet?</p>
                        <p class="mt-1">
                            Support tickets need an account so we can link them to your bookings and reply safely.
                            <a href="{{ route('login') }}" class="font-semibold underline">Log in</a> or
                            <a href="{{ route('register.parent') }}" class="font-semibold underline">create a parent account</a>
                            to continue.
                        </p>
                    </div>
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Before you write to us</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        These pages answer most day-to-day questions in full:
                    </p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <a href="{{ route('faq') }}" class="rounded-lg border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                            <span class="block text-sm font-semibold text-slate-900">Frequently asked questions</span>
                            <span class="mt-1 block text-xs text-slate-500">Booking, pricing, refunds and payments</span>
                        </a>
                        <a href="{{ route('cancellation-policy') }}" class="rounded-lg border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                            <span class="block text-sm font-semibold text-slate-900">Cancellation &amp; refunds</span>
                            <span class="mt-1 block text-xs text-slate-500">Windows, charges and settlement rules</span>
                        </a>
                        <a href="{{ route('privacy') }}" class="rounded-lg border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                            <span class="block text-sm font-semibold text-slate-900">Privacy policy</span>
                            <span class="mt-1 block text-xs text-slate-500">How we handle your data</span>
                        </a>
                    </div>
                </section>
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-4">
                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Office hours</h2>
                    <p class="mt-1 text-xs text-slate-500">All times are India Standard Time (IST, Asia/Kolkata).</p>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600">Monday &ndash; Friday</dt>
                            <dd class="font-semibold text-slate-900">9:00 AM &ndash; 8:00 PM</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600">Saturday</dt>
                            <dd class="font-semibold text-slate-900">9:00 AM &ndash; 6:00 PM</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600">Sunday &amp; public holidays</dt>
                            <dd class="font-semibold text-slate-900">Closed</dd>
                        </div>
                    </dl>

                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">During an ongoing class</p>
                        <p class="mt-1">
                            For anything urgent happening inside a live session, raise a ticket with the
                            <strong>complaint</strong> category and priority <strong>high</strong> so it is escalated
                            to the on-call reviewer.
                        </p>
                    </div>
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Company details</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Platform</dt>
                            <dd class="mt-0.5 font-medium text-slate-900">{{ $platformName ?? 'EduBook' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marketplace</dt>
                            <dd class="mt-0.5 text-slate-600">India &mdash; State Board, Matriculation, CBSE and ICSE</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Languages</dt>
                            <dd class="mt-0.5 text-slate-600">English and Hindi</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
@endsection
