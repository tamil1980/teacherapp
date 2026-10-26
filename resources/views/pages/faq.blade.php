@extends('layouts.app')

@section('title', 'FAQ — EduBook')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">Frequently asked questions</h1>
            <p class="page-subtitle">Everything parents and teachers usually ask before booking a class.</p>
        </div>

        <div class="mt-10 space-y-8">
            @foreach ([
                ['q' => 'How do I find a teacher for my child?', 'a' => 'Browse the subject, board and class catalogues, then open the teacher directory. Every teacher profile lists the subjects, boards and class standards they teach, their hourly rates, teaching modes and parent reviews. Only teachers whose identity and qualifications have been verified by the platform appear in the public results.'],
                ['q' => 'How does the booking process work?', 'a' => 'Pick a teacher, choose a subject, mode (online or offline) and an available slot. The parent books an instant, transparent quote based on the teacher\'s agreed hourly fee; the slot is held while you complete payment. Once the payment is confirmed the booking is final.'],
                ['q' => 'Is payment required to book?', 'a' => 'Yes — the checkout step collects the agreed amount before the class is confirmed. The platform supports online payments (Razorpay/UPI) plus an offline mode for teachers who instruct at the student home or a tuition centre.'],
                ['q' => 'What happens if the teacher cancels or is unavailable?', 'a' => 'The slot is released and the parent is refunded, or offered an equivalent available slot. Teachers who repeatedly miss slots risk suspension.'],
                ['q' => 'Can I reschedule or cancel a booking?', 'a' => 'Parents can reschedule within the platform\'s reschedule window (12 hours by default) or cancel according to the cancellation policy. Teachers can cancel confirmed bookings; the refund process is triggered automatically.'],
                ['q' => 'How are refunds handled?', 'a' => 'Cancellations inside the cancellation window are refunded according to the platform policy; cancellations inside a short grace period may incur a partial fee. See the cancellation policy page for the exact numbers.'],
                ['q' => 'How does online class setup work?', 'a' => 'Online classes are created as soon as the teacher shares a meeting join URL after the booking is confirmed. The join URL, meeting identifier and passcode are delivered to the parent through the booking record and the platform messaging.'],
                ['q' => 'How is teacher verification handled?', 'a' => 'Teachers must upload identity and qualification documents. An administrator reviews those documents before the teacher can be approved, and only approved teachers appear publicly.'],
                ['q' => 'What subjects and boards are supported?', 'a' => 'The catalogue covers CBSE, State Board, Matriculation and ICSE curricula from early primary through Class 12 plus competitive exam preparation.'],
                ['q' => 'How do I contact support?', 'a' => 'Use the support page to raise a ticket with your booking reference. Our team replies by email and updates the ticket status.'],
            ] as $item)
        @endforeach
    </div>
@endsection
