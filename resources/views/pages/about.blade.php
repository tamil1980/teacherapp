@extends('layouts.app')

@section('title', 'About us')
@section('meta_description', 'EduBook is an Indian tuition marketplace connecting parents with verified teachers across State Board, Matriculation, CBSE and ICSE. Learn how bookings, payments and safety work.')

@section('content')
    {{-- Hero --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <span class="badge bg-brand-100 text-brand-700">About {{ $platformName ?? 'EduBook' }}</span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    Tuition, without the guesswork.
                </h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    {{ $platformName ?? 'EduBook' }} is an online marketplace that connects Indian families with verified,
                    independent home and online tutors. Parents can search by board, class and subject, compare real
                    prices, book a slot and pay securely &mdash; while teachers keep control of their own fees,
                    availability and earnings.
                </p>
                <p class="mt-3 text-base leading-relaxed text-slate-600">
                    We are built for the way Indian education actually works: State Board, Matriculation, CBSE and
                    ICSE, one-on-one tuition, small batches and home visits &mdash; with modern payment rails (UPI,
                    cards, net banking and wallets) and a complete record of every class.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('teachers.index') }}" class="btn-primary">Find a teacher</a>
                    <a href="{{ route('register.teacher') }}" class="btn-secondary">Teach with us</a>
                </div>
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">What {{ $platformName ?? 'EduBook' }} does</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="card p-6">
                <h3 class="font-semibold text-slate-900">A teacher directory that speaks your board</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Filter by board, class standard and subject. Every listed teacher has been reviewed by our
                    verification team before their profile becomes visible in search.
                </p>
            </div>
            <div class="card p-6">
                <h3 class="font-semibold text-slate-900">Booking that respects a real calendar</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Teachers publish the slots they are actually free for. You pick one, we hold it for you while you
                    pay, and the teacher is notified instantly.
                </p>
            </div>
            <div class="card p-6">
                <h3 class="font-semibold text-slate-900">Transparent pricing and payouts</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Teachers set their own hourly fee &mdash; you always see the exact amount before you pay. Earnings
                    are tracked per session and released to teachers on a predictable payout cycle.
                </p>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-2">
                {{-- Parents --}}
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">How it works for parents</h2>
                    <p class="mt-2 text-sm text-slate-600">Five steps from &ldquo;we need help&rdquo; to &ldquo;the marks have moved&rdquo;.</p>

                    <ol class="mt-6 space-y-5">
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">1</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Search</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Browse verified teachers and filter by board, class standard, subject, mode
                                    (online or home tuition) and locality. Read reviews and compare hourly fees.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">2</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Book</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Choose an available slot, add your child&rsquo;s details, and confirm the booking.
                                    The slot is reserved for you while you complete payment.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">3</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Pay</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Pay by UPI, card, net banking or wallet through a secure payment gateway. The
                                    exact fee, any travel charge and the total are always shown before you pay.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">4</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Attend</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Join the online session through a private meeting link, or welcome the teacher for
                                    home tuition. Attendance is recorded so both sides agree on what happened.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">5</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Rate</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    After a completed class, rate your experience. Honest reviews help other families
                                    and give good teachers the recognition they deserve.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>

                {{-- Teachers --}}
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">How it works for teachers</h2>
                    <p class="mt-2 text-sm text-slate-600">Six steps from sign-up to a paid, well-managed teaching week.</p>

                    <ol class="mt-6 space-y-5">
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">1</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Register</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Create a teacher account with your name, mobile number, email and the subjects,
                                    boards and class standards you teach.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">2</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Get verified</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Upload identity and qualification documents. Our team reviews them and approves
                                    your profile &mdash; only approved teachers appear in public search.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">3</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Set your fees</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    You decide your hourly rate. The platform may suggest a benchmark, but it is only
                                    a suggestion &mdash; the final price is always yours.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">4</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Publish your availability</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Set weekly rules or one-off slots for online and home-tuition sessions, including
                                    travel areas and visiting charges.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">5</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Teach</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Accept or decline incoming requests, share a private meeting link for online
                                    classes, and mark the session complete. Attendance and notes stay on record.
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">6</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Get paid</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                    Earnings are credited after each completed session, less the platform commission
                                    configured by the administrator, and are paid out on request once the minimum
                                    payout amount is reached.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- Child safety --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Why child safety matters here</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">
                A tutoring platform introduces a stranger into a family&rsquo;s routine. That is a serious
                responsibility, so we treat it as the core product requirement rather than a policy footnote.
                Our rules are simple: guardians decide who teaches their child, guardians control the money,
                children&rsquo;s details are never advertised, and every action is recorded so problems can be
                investigated properly.
            </p>
            <ul class="mt-5 space-y-3 text-sm leading-relaxed text-slate-600">
                <li class="flex gap-3">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                    <span><strong class="font-semibold text-slate-900">A guardian is always in charge.</strong> Bookings
                        are raised from a parent or guardian account, and no child account can spend money or book a
                        class on its own.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                    <span><strong class="font-semibold text-slate-900">Minimum information, maximum care.</strong> We
                        ask only what a teacher genuinely needs to prepare and teach &mdash; display name, class,
                        board and learning requirements &mdash; and we keep it out of public view.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                    <span><strong class="font-semibold text-slate-900">A way to raise a voice.</strong> If something is
                        not right, a parent can raise a complaint from the booking itself and our team reviews the
                        record.</span>
                </li>
            </ul>
        </div>
    </section>

    {{-- Trust & safety --}}
    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Our trust &amp; safety measures</h2>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
                Five safeguards run through every booking on the platform.
            </p>

            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="card p-6">
                    <h3 class="font-semibold text-slate-900">Identity &amp; qualification verification</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Teachers upload identity proof and academic or professional qualifications. An administrator
                        reviews every document, and unverified or rejected profiles are never shown in search.
                    </p>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold text-slate-900">Guardian-controlled bookings and payments</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Only the parent or guardian account attached to a child can create bookings, authorise
                        payments and cancel or reschedule classes on that child&rsquo;s behalf.
                    </p>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold text-slate-900">Private meeting links</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Online classes run through a private link that is shared only with the confirmed teacher,
                        parent and student. No public class links, no open classrooms, no visitor lists.
                    </p>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold text-slate-900">Auditable records</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Bookings, status changes, attendance, payments and refunds are all time-stamped and kept on
                        record. Administrator actions are logged too, so any dispute can be reconstructed.
                    </p>
                </div>
                <div class="card p-6">
                    <h3 class="font-semibold text-slate-900">Complaint reporting</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        A parent can report a concern against a booking at any time. Complaints are reviewed by the
                        platform team, and serious issues can lead to suspension of a teacher&rsquo;s account.
                    </p>
                </div>
                <div class="card border-brand-200 bg-brand-50/60 p-6">
                    <h3 class="font-semibold text-slate-900">Need to report something?</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Raise a ticket with the details and our team will investigate. Please include the booking
                        reference so we can pull the exact session record.
                    </p>
                    <a href="{{ route('contact') }}" class="btn-secondary btn-sm mt-4">Contact support</a>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="card bg-white p-8 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Ready to get started?</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-slate-600">
                Create a free parent account to book your first class, or register as a teacher and start earning
                from your availability this week.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register.parent') }}" class="btn-primary">Create a parent account</a>
                <a href="{{ route('register.teacher') }}" class="btn-secondary">Register as a teacher</a>
                <a href="{{ route('faq') }}" class="btn-ghost">Read the FAQ</a>
            </div>
        </div>
    </section>
@endsection
