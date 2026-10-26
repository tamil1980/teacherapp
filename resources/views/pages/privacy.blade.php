@extends('layouts.app')

@section('title', 'Privacy — EduBook')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">Privacy policy</h1>
            <p class="page-subtitle">Last updated {{ now()->format('d M Y') }}</p>
        </div>

        <div class="mt-10 space-y-8 text-slate-700">
            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">1. Information we collect</h2>
                <ul class="mt-3 list-disc space-y-1 pl-6 text-sm leading-relaxed">
                    <li>Account details such as name, email, mobile number and role.</li>
                    <li>Teacher profiles including identity documents and qualifications submitted for verification.</li>
                    <li>Student profiles linked to a parent, including class and board context.</li>
                    <li>Booking, scheduling, payment and notification records needed to operate the service.</li>
                </ul>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">2. How we use it</h2>
                <ul class="mt-3 list-disc space-y-1 pl-6 text-sm leading-relaxed">
                    <li>To provide, schedule and bill the tutoring service.</li>
                    <li>To verify teachers and mediate booking disputes.</li>
                    <li>To communicate with users and send notifications.</li>
                </ul>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">3. Sharing</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Parent contact details for a teacher are shared only once a booking is confirmed. Booking and payment records are shared with the participating teacher and the parent as needed to deliver the service, and with the platform for administration and payouts.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">4. Retention and security</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Records are retained for the period required to operate the tutoring service and resolve disputes, and access is restricted to authorised roles. Payment gateway data is handled by the provider.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">5. Your choices</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    You may request deletion or correction of your personal data by contacting support. Deleting a teacher account removes it from public listings; historical booking records may be retained for accounting and dispute purposes.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">6. Changes</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    This policy may be updated. Continued use of the platform after publication constitutes acceptance of the revised policy.
                </p>
            </section>
        </div>
    </div>
@endsection
