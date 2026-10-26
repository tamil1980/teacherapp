<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\TeacherPayoutItem;
use App\Models\TeacherProfile;
use App\Support\Platform;
use Illuminate\Support\Facades\DB;

/**
 * The teacher earnings ledger.
 *
 * Earnings are only credited once the session has been completed and the
 * settlement delay configured by the administrator has elapsed. Refunds and
 * manual adjustments are recorded against the original earning row so the
 * full history remains auditable.
 */
class EarningService
{
    /**
     * Credit a completed session to the teacher's ledger.
     */
    public function credit(Booking $booking, Payment $payment): TeacherEarning
    {
        $commission = $booking->commissions()->first();

        $gross = $commission ? (float) $commission->gross_amount : (float) $booking->subtotal;
        $commissionAmount = $commission ? (float) $commission->commission_amount : 0.0;
        $net = $commission ? (float) $commission->teacher_amount : round($gross - $commissionAmount, 2);

        $delayDays = (int) Platform::get('teacher_settlement_delay_days', 2);

        return TeacherEarning::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'teacher_profile_id' => $booking->teacher_profile_id,
                'payment_id' => $payment->id,
                'gross_amount' => $gross,
                'commission_amount' => $commissionAmount,
                'refund_amount' => 0,
                'adjustment_amount' => 0,
                'net_amount' => $net,
                'currency' => $booking->currency,
                'status' => TeacherEarning::STATUS_PENDING,
                'available_at' => now()->addDays($delayDays),
            ]
        );
    }

    /**
     * Reverse (part of) an earning when a refund is issued.
     */
    public function reverse(Booking $booking, float $amount, ?string $note = null): ?TeacherEarning
    {
        $earning = TeacherEarning::where('booking_id', $booking->id)->first();

        if (! $earning) {
            return null;
        }

        $earning->refund_amount = round((float) $earning->refund_amount + $amount, 2);
        $earning->net_amount = round((float) $earning->net_amount - $amount, 2);

        if ((float) $earning->net_amount < 0) {
            $earning->net_amount = 0;
        }

        $earning->status = TeacherEarning::STATUS_REVERSED;
        $earning->notes = $note ?: $earning->notes;
        $earning->save();

        return $earning;
    }

    /**
     * Earnings that are ready to be paid out.
     */
    public function availableEarnings(TeacherProfile $teacher)
    {
        return TeacherEarning::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('status', TeacherEarning::STATUS_AVAILABLE)
            ->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('available_at')
            ->get();
    }

    /**
     * Totals for the teacher earnings dashboard.
     *
     * @return array<string, float|int>
     */
    public function summary(TeacherProfile $teacher): array
    {
        $rows = TeacherEarning::where('teacher_profile_id', $teacher->id)->get();

        return [
            'gross' => round($rows->sum(fn ($e) => (float) $e->gross_amount), 2),
            'commission' => round($rows->sum(fn ($e) => (float) $e->commission_amount), 2),
            'refunded' => round($rows->sum(fn ($e) => (float) $e->refund_amount), 2),
            'adjustments' => round($rows->sum(fn ($e) => (float) $e->adjustment_amount), 2),
            'net_earned' => round($rows->sum(fn ($e) => (float) $e->net_amount), 2),
            'pending' => round($rows->where('status', TeacherEarning::STATUS_PENDING)->sum(fn ($e) => (float) $e->net_amount), 2),
            'available' => round($rows->where('status', TeacherEarning::STATUS_AVAILABLE)->sum(fn ($e) => (float) $e->net_amount), 2),
            'paid' => round($rows->where('status', TeacherEarning::STATUS_PAID)->sum(fn ($e) => (float) $e->net_amount), 2),
            'reversed' => round($rows->where('status', TeacherEarning::STATUS_REVERSED)->sum(fn ($e) => (float) $e->net_amount), 2),
        ];
    }

    /**
     * Move earnings from "pending" to "available" once the settlement delay
     * has elapsed. Run by the `earnings:release` console command.
     */
    public function releaseDueEarnings(): int
    {
        return TeacherEarning::query()
            ->where('status', TeacherEarning::STATUS_PENDING)
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now())
            ->update(['status' => TeacherEarning::STATUS_AVAILABLE]);
    }

    /**
     * Create a payout record for a teacher.
     *
     * The payout is only a *record* — money is moved by the administrator (or
     * by the marketplace split product once the merchant account supports it).
     */
    public function createPayout(
        TeacherProfile $teacher,
        ?float $amount = null,
        string $method = 'bank_transfer',
        ?string $notes = null,
        ?int $processedBy = null
    ): TeacherPayout {
        return DB::transaction(function () use ($teacher, $amount, $method, $notes, $processedBy) {
            $earnings = $this->availableEarnings($teacher);

            if ($earnings->isEmpty()) {
                throw new \RuntimeException('There are no earnings available for payout.');
            }

            $selected = $earnings;

            if ($amount !== null) {
                $remaining = round($amount, 2);
                $selected = $earnings->takeWhile(function (TeacherEarning $earning) use (&$remaining) {
                    if ($remaining <= 0) {
                        return false;
                    }
                    $remaining -= (float) $earning->net_amount;

                    return true;
                });

                if ($selected->isEmpty()) {
                    throw new \RuntimeException('The requested payout amount exceeds the available balance.');
                }
            }

            $total = round($selected->sum(fn (TeacherEarning $e) => (float) $e->net_amount), 2);

            $payout = TeacherPayout::create([
                'teacher_profile_id' => $teacher->id,
                'reference_no' => 'PAY'.now()->format('Ymd').strtoupper(bin2hex(random_bytes(3))),
                'amount' => $total,
                'currency' => 'INR',
                'method' => $method,
                'status' => TeacherPayout::STATUS_REQUESTED,
                'notes' => $notes,
                'processed_by' => $processedBy,
            ]);

            foreach ($selected as $earning) {
                TeacherPayoutItem::create([
                    'teacher_payout_id' => $payout->id,
                    'teacher_earning_id' => $earning->id,
                    'amount' => $earning->net_amount,
                ]);

                $earning->update([
                    'status' => TeacherEarning::STATUS_PAID,
                    'payout_id' => $payout->id,
                ]);
            }

            return $payout;
        });
    }
}
