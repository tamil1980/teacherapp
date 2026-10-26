<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Support\Platform;

/**
 * Calculates the platform commission on a completed payment.
 *
 * The percentage, the fixed fee and the gateway-fee treatment are all
 * administrator-configurable (see the `settings` table and the admin
 * "Platform Settings" screen) — nothing is hard-coded here.
 */
class CommissionService
{
    /**
     * @return array{gross: float, percent: float, fixed: float, commission: float, teacher: float, platform: float, gateway_fee: float}
     */
    public function compute(Booking $booking, ?Payment $payment = null): array
    {
        $percent = (float) Platform::get('commission_percent', 15);
        $fixed = (float) Platform::get('commission_fixed_fee', 0);

        $subtotal = (float) $booking->subtotal;
        $travel = (float) $booking->travel_charge;
        $discount = (float) $booking->discount_amount;

        $base = Platform::get('commission_base', 'gross') === 'teaching'
            ? $subtotal
            : round($subtotal + $travel, 2);

        $commission = round($base * ($percent / 100), 2) + $fixed;
        $commission = max(0, $commission);

        $gross = round($subtotal + $travel, 2);
        $teacher = max(0, round($gross - $commission, 2));

        $gatewayFee = 0.0;
        if ($payment) {
            $gatewayFee = round(
                ($payment->amount * ((float) Platform::get('payment_gateway_fee_percent', 0) / 100))
                + (float) Platform::get('payment_gateway_fee_fixed', 0),
                2
            );

            if (Platform::get('gateway_fee_bearer', 'platform') === 'teacher') {
                $teacher = max(0, round($teacher - $gatewayFee, 2));
            }
        }

        $platform = round($commission - $discount, 2);

        return [
            'gross' => $gross,
            'percent' => $percent,
            'fixed' => $fixed,
            'commission' => $commission,
            'teacher' => $teacher,
            'platform' => $platform,
            'gateway_fee' => $gatewayFee,
        ];
    }

    /**
     * Persist the commission row for a booking.
     */
    public function record(Booking $booking, Payment $payment): PlatformCommission
    {
        $figures = $this->compute($booking, $payment);

        return PlatformCommission::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'payment_id' => $payment->id,
                'gross_amount' => $figures['gross'],
                'commission_percent' => $figures['percent'],
                'commission_amount' => $figures['commission'],
                'fixed_fee' => $figures['fixed'],
                'teacher_amount' => $figures['teacher'],
                'platform_amount' => $figures['platform'],
                'currency' => $booking->currency,
            ]
        );
    }
}
