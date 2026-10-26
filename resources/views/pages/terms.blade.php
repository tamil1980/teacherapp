@extends('layouts.app')

@section('title', 'Terms — EduBook')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">Terms of service</h1>
            <p class="page-subtitle">Last updated {{ now()->format('d M Y') }}</p>
        </div>

        <div class="mt-10 space-y-8 text-slate-700">
            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">1. The service</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    EduBook is a marketplace connecting parents and verified teachers for tuition, test preparation and academic coaching. The platform schedules classes, collects agreed tuition fees and moderates teacher profiles; it is not the provider of the education services themselves, which are delivered by the teachers listed on the platform.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">2. Accounts</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Parent, teacher and administrator accounts are personal. You are responsible for keeping your credentials confidential and for all activity under your account. You must be 18 years or older, or have parental consent, to register.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">3. Bookings and payments</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    A booking becomes confirmed when the parent completes the payment step. Tuition fees are set by the teacher and agreed with the parent before or at the start of a booking. Refunds follow the cancellation policy published on this platform.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">4. Acceptable use</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    You agree not to misuse the platform, attempt to access another user's account or data, scrape listings or interfere with bookings of others.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">5. Changes and termination</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    The platform may suspend or terminate accounts that violate these terms, misrepresent themselves or repeatedly miss scheduled sessions. Cancellation of access may be immediate in such cases.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">6. Contact</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Questions about these terms can be sent through the support ticket flow. These terms are provided for information and do not constitute legal advice.
                </p>
            </section>
        </div>
    </div>
@endsection
