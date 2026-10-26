<?php

namespace App\Http\Controllers;

use App\Support\Platform;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'supportPhone' => Platform::get('support_phone'),
            'supportEmail' => Platform::get('support_email'),
        ]);
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function cancellationPolicy(): View
    {
        return view('pages.cancellation-policy', [
            'cancellationWindow' => (int) Platform::get('cancellation_window_hours', 24),
            'cancellationFeePercent' => (float) Platform::get('cancellation_fee_percent', 0),
            'rescheduleWindow' => (int) Platform::get('reschedule_window_hours', 12),
            'policyText' => Platform::get('refund_policy_text'),
            'settlementDelayDays' => (int) Platform::get('teacher_settlement_delay_days', 2),
        ]);
    }
}
