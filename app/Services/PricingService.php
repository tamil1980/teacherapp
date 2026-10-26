<?php

namespace App\Services;

use App\Models\Board;
use App\Models\ClassPackage;
use App\Models\ClassStandard;
use App\Models\PromotionalOffer;
use App\Models\Subject;
use App\Models\TeacherFee;
use App\Models\TeacherProfile;
use App\Support\Platform;

/**
 * Resolves the agreed price for a booking and builds the full, itemised
 * payment breakdown. All money is calculated here on the server — the
 * website and the Android app only ever send identifiers and quantities.
 */
class PricingService
{
    /**
     * Find the most specific active fee row for the requested combination.
     *
     * @return array{fee: ?TeacherFee, rate: float, session_minutes: int, travel_charge: float}
     */
    public function resolveFee(
        TeacherProfile $teacher,
        ?Subject $subject,
        ?ClassStandard $classStandard,
        ?Board $board,
        string $mode
    ): array {
        $fees = TeacherFee::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('is_active', true)
            ->where(function ($query) use ($mode) {
                $query->where('mode', $mode)->orWhere('mode', 'both');
            })
            ->where(function ($query) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->get()
            ->filter(function (TeacherFee $fee) use ($subject, $classStandard, $board) {
                if ($fee->subject_id && (! $subject || $fee->subject_id !== $subject->id)) {
                    return false;
                }
                if ($fee->class_standard_id && (! $classStandard || $fee->class_standard_id !== $classStandard->id)) {
                    return false;
                }
                if ($fee->board_id && (! $board || $fee->board_id !== $board->id)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (TeacherFee $fee) => $fee->specificity())
            ->values();

        $fee = $fees->first();

        $rate = $fee ? (float) $fee->hourly_rate : 0.0;
        $sessionMinutes = $fee ? (int) $fee->session_minutes : (int) $teacher->default_session_minutes;
        $travelCharge = $fee ? (float) $fee->travel_charge : 0.0;

        return [
            'fee' => $fee,
            'rate' => $rate,
            'session_minutes' => $sessionMinutes,
            'travel_charge' => $travelCharge,
        ];
    }

    /**
     * Build the complete price breakdown for a booking.
     *
     * @return array<string, mixed>
     */
    public function quote(
        TeacherProfile $teacher,
        int $durationMinutes,
        string $mode,
        ?Subject $subject = null,
        ?ClassStandard $classStandard = null,
        ?Board $board = null,
        ?string $discountCode = null,
        float $travelChargeOverride = null,
        ?ClassPackage $package = null
    ): array {
        $resolved = $this->resolveFee($teacher, $subject, $classStandard, $board, $mode);

        $rate = $resolved['rate'];
        $sessionMinutes = $resolved['session_minutes'];

        if ($package) {
            // Package pricing: the package defines its own per-session price.
            $rate = (float) $package->price_per_session;
            $sessionMinutes = (int) $package->session_minutes;
        }

        if ($rate <= 0) {
            $rate = (float) Platform::get('default_travel_charge') * 0; // keep rate 0 -> error surfaced by caller
        }

        $hours = $durationMinutes / 60;
        $subtotal = round($rate * $hours, 2);

        $travelCharge = 0.0;
        if ($mode === 'offline') {
            $travelCharge = $travelChargeOverride ?? $resolved['travel_charge'];
            if ($travelChargeOverride === null && $travelCharge <= 0) {
                $travelCharge = (float) Platform::get('default_travel_charge');
            }
        }

        $discount = 0.0;
        $appliedOffer = null;
        if ($discountCode) {
            $appliedOffer = PromotionalOffer::where('code', strtoupper($discountCode))->first();
            if ($appliedOffer) {
                $discount = $appliedOffer->discountFor($subtotal + $travelCharge);
            }
        }

        $taxPercent = (float) Platform::get('tax_percent');
        $taxable = max(0, $subtotal + $travelCharge - $discount);
        $tax = round($taxable * ($taxPercent / 100), 2);

        $total = round($taxable + $tax, 2);

        return [
            'hourly_rate' => $rate,
            'session_minutes' => $sessionMinutes,
            'duration_minutes' => $durationMinutes,
            'hours' => round($hours, 2),
            'subtotal' => $subtotal,
            'travel_charge' => $travelCharge,
            'discount_amount' => $discount,
            'discount_code' => $appliedOffer?->code,
            'tax_amount' => $tax,
            'tax_percent' => $taxPercent,
            'total_amount' => $total,
            'currency' => Platform::get('currency', 'INR'),
            'offer' => $appliedOffer,
        ];
    }

    /**
     * Packages offered by the teacher that match the requested filters.
     */
    public function packagesFor(
        TeacherProfile $teacher,
        ?Subject $subject = null,
        ?ClassStandard $classStandard = null,
        ?Board $board = null,
        ?string $mode = null
    ) {
        return ClassPackage::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('is_active', true)
            ->when($subject, fn ($q) => $q->where(fn ($w) => $w->whereNull('subject_id')->orWhere('subject_id', $subject->id)))
            ->when($classStandard, fn ($q) => $q->where(fn ($w) => $w->whereNull('class_standard_id')->orWhere('class_standard_id', $classStandard->id)))
            ->when($board, fn ($q) => $q->where(fn ($w) => $w->whereNull('board_id')->orWhere('board_id', $board->id)))
            ->when($mode, fn ($q) => $q->where(fn ($w) => $w->where('mode', $mode)->orWhere('mode', 'both')))
            ->orderBy('total_price')
            ->get();
    }
}
