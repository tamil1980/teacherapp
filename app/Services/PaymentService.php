<?php

namespace App\Services;

use App\Exceptions\PaymentException;
use App\Models\Booking;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Owns the payment lifecycle: order creation, server-side verification,
 * webhook handling, refunds and reconciliation.
 */
class PaymentService
{
    public function __construct(
        private readonly Payments\PaymentGateway $gateway,
        private readonly BookingService $bookings,
        private readonly NotificationService $notifications,
        private readonly EarningService $earnings,
    ) {}

    public function gateway(): Payments\PaymentGateway
    {
        return $this->gateway;
    }

    /**
     * Create a gateway order for a booking.
     */
    public function createOrder(Booking $booking, ParentProfile $parent, ?User $user = null): Payment
    {
        $existing = $booking->payments()->whereIn('status', [Payment::STATUS_CREATED, Payment::STATUS_PAID])->latest()->first();

        if ($existing && $existing->status === Payment::STATUS_CREATED && $existing->gateway_order_id) {
            return $existing;
        }

        $payment = Payment::create([
            'reference_no' => $this->referenceNumber(),
            'booking_id' => $booking->id,
            'parent_profile_id' => $parent->id,
            'user_id' => $user?->id ?? $parent->user_id,
            'gateway' => $this->gateway->name(),
            'order_id' => $this->referenceNumber(),
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'status' => Payment::STATUS_CREATED,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $order = $this->gateway->createOrder(
            (float) $booking->total_amount,
            $payment->order_id,
            [
                'booking_id' => (string) $booking->id,
                'booking_reference' => $booking->reference_no,
            ]
        );

        $payment->update([
            'gateway_order_id' => $order->id,
            'gateway_payload' => $order->raw,
        ]);

        return $payment->fresh();
    }

    /**
     * Verify and capture a payment, then confirm the booking.
     *
     * The browser callback alone is never trusted: the signature is checked
     * and the payment is fetched back from the gateway before the booking is
     * confirmed.
     */
    public function capture(Payment $payment, array $payload): Payment
    {
        if ($payment->status === Payment::STATUS_PAID) {
            return $payment;
        }

        if (! $this->gateway->verifySignature($payload)) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => 'Signature verification failed.',
            ]);

            throw new PaymentException('The payment signature could not be verified. The booking was not confirmed.');
        }

        $gatewayPaymentId = (string) ($payload['razorpay_payment_id'] ?? $payload['payment_id'] ?? '');

        $remote = $this->gateway->fetchPayment($gatewayPaymentId);

        if (! $remote) {
            throw new PaymentException('The payment could not be confirmed with the gateway. Please try again.');
        }

        $remoteStatus = (string) ($remote['status'] ?? '');
        if (! in_array($remoteStatus, ['captured', 'authorized', 'paid'], true)) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => 'Gateway status: '.$remoteStatus,
                'gateway_payment_id' => $gatewayPaymentId,
            ]);

            throw new PaymentException('The payment was not captured by the gateway.');
        }

        $remoteAmount = isset($remote['amount'])
            ? $this->gateway->fromMinorUnit((int) $remote['amount'])
            : (float) $payment->amount;

        if (abs($remoteAmount - (float) $payment->amount) > 0.01) {
            Log::error('Payment amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $payment->amount,
                'received' => $remoteAmount,
            ]);

            throw new PaymentException('The amount received does not match the booking amount.');
        }

        return DB::transaction(function () use ($payment, $payload, $remote) {
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'gateway_payment_id' => (string) ($payload['razorpay_payment_id'] ?? $payload['payment_id'] ?? ''),
                'gateway_signature' => (string) ($payload['razorpay_signature'] ?? $payload['signature'] ?? ''),
                'method' => (string) ($payload['razorpay_method'] ?? $remote['method'] ?? 'unknown'),
                'paid_at' => now(),
                'invoice_number' => 'INV'.now()->format('Ymd').strtoupper(Str::random(5)),
                'gateway_payload' => $remote,
            ]);

            $booking = $payment->booking;

            if ($booking) {
                $this->bookings->confirm($booking, $payment);
            }

            return $payment->fresh();
        });
    }

    /**
     * Handle an incoming gateway webhook. Processing is idempotent — the same
     * event id is only ever applied once.
     */
    public function handleWebhook(string $eventType, array $payload, string $rawBody, ?string $signature): PaymentWebhookEvent
    {
        $eventId = (string) ($payload['event']['id']
            ?? $payload['id']
            ?? $payload['event_id']
            ?? hash('sha256', $eventType.'|'.$rawBody));

        $existing = PaymentWebhookEvent::where('event_id', $eventId)->first();

        if ($existing && $existing->status === 'processed') {
            return $existing;
        }

        $event = $existing ?: PaymentWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'signature' => $signature,
            'payload' => $payload,
            'status' => 'received',
        ]);

        try {
            if ($signature !== null && ! $this->gateway->verifyWebhook($rawBody, $signature)) {
                $event->update(['status' => 'rejected', 'error_message' => 'Invalid webhook signature.']);

                throw new PaymentException('Invalid webhook signature.');
            }

            $orderId = (string) ($payload['payload']['payment']['entity']['order_id']
                ?? $payload['payload']['order_id']
                ?? $payload['payment']['entity']['order_id']
                ?? $payload['order_id']
                ?? '');

            $payment = Payment::where('gateway_order_id', $orderId)
                ->orWhere('order_id', $orderId)
                ->first();

            $event->update([
                'gateway_order_id' => $orderId ?: null,
                'gateway_payment_id' => (string) ($payload['payload']['payment']['entity']['id']
                    ?? $payload['payload']['payment_id']
                    ?? $payload['payment']['entity']['id']
                    ?? null),
            ]);

            if (! $payment) {
                $event->update(['status' => 'ignored', 'error_message' => 'No matching payment found.']);

                return $event;
            }

            if (str_contains($eventType, 'refund')) {
                $this->applyRefundWebhook($payment, $payload);
            } elseif (str_contains($eventType, 'captured') || str_contains($eventType, 'paid')) {
                if ($payment->status !== Payment::STATUS_PAID) {
                    $remote = $this->gateway->fetchPayment((string) $payment->gateway_payment_id);

                    if ($remote && in_array((string) ($remote['status'] ?? ''), ['captured', 'paid'], true)) {
                        $payment->update([
                            'status' => Payment::STATUS_PAID,
                            'paid_at' => $payment->paid_at ?? now(),
                            'invoice_number' => $payment->invoice_number ?: 'INV'.now()->format('Ymd').strtoupper(Str::random(5)),
                        ]);

                        if ($payment->booking) {
                            $this->bookings->confirm($payment->booking, $payment);
                        }
                    }
                }
            } elseif (str_contains($eventType, 'failed')) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'failure_reason' => 'Gateway reported a failed payment.',
                ]);
            }

            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $event->fresh();
    }

    /**
     * Issue a full or partial refund.
     */
    public function refund(
        Payment $payment,
        float $amount,
        string $reason,
        ?int $requestedBy = null,
        ?int $processedBy = null,
        ?string $notes = null
    ): Refund {
        $refundable = $payment->refundableAmount();

        if ($amount <= 0) {
            throw new PaymentException('The refund amount must be greater than zero.');
        }

        if ($amount - $refundable > 0.01) {
            throw new PaymentException('The refund amount exceeds the amount that can still be refunded.');
        }

        $refund = Refund::create([
            'reference_no' => $this->referenceNumber('RFN'),
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'amount' => $amount,
            'reason' => $reason,
            'notes' => $notes,
            'status' => 'pending',
            'requested_by' => $requestedBy,
            'processed_by' => $processedBy,
        ]);

        try {
            $response = $this->gateway->refund(
                (string) $payment->gateway_payment_id,
                $amount,
                ['booking_id' => (string) $payment->booking_id]
            );
        } catch (\Throwable $exception) {
            $refund->update(['status' => 'failed', 'notes' => $exception->getMessage()]);

            throw $exception;
        }

        $refund->update([
            'status' => 'processed',
            'gateway_refund_id' => (string) ($response['id'] ?? ''),
            'processed_at' => now(),
        ]);

        $payment->refunded_amount = round((float) $payment->refunded_amount + $amount, 2);

        $remaining = $payment->refundableAmount();
        $payment->status = $remaining <= 0.01 ? Payment::STATUS_REFUNDED : Payment::STATUS_PARTIALLY_REFUNDED;
        $payment->save();

        if ($payment->booking) {
            $booking = $payment->booking;
            $booking->payment_status = $payment->status;
            $booking->save();

            if ($payment->status === Payment::STATUS_REFUNDED) {
                $booking->update(['status' => Booking::STATUS_REFUNDED]);
                $this->bookings->history($booking, $booking->status, Booking::STATUS_REFUNDED, 'Full refund issued: '.$reason);
            }

            $this->earnings->reverse($booking, $amount, 'Refund '.$refund->reference_no.' — '.$reason);
        }

        $this->notifications->notify(
            $payment->parent?->user,
            'payment.refunded',
            'Refund processed',
            'A refund of ₹'.number_format($amount, 2).' has been processed for booking '.$payment->booking?->reference_no.'.',
            ['payment_id' => $payment->id, 'refund_id' => $refund->id]
        );

        return $refund->fresh();
    }

    /**
     * Apply a refund webhook payload to the matching refund record.
     */
    private function applyRefundWebhook(Payment $payment, array $payload): void
    {
        $refundId = (string) ($payload['payload']['refund']['entity']['id']
            ?? $payload['payload']['refund_id']
            ?? '');

        $refund = Refund::where('gateway_refund_id', $refundId)->first();

        if ($refund) {
            $refund->update(['status' => 'processed', 'processed_at' => now()]);
        }
    }

    /**
     * Generate a payment receipt payload.
     */
    public function receipt(Payment $payment): array
    {
        $booking = $payment->booking;

        return [
            'reference_no' => $payment->reference_no,
            'invoice_number' => $payment->invoice_number,
            'date' => optional($payment->paid_at)->format('d M Y, h:i A'),
            'booking_reference' => $booking?->reference_no,
            'amount' => (float) $payment->amount,
            'tax' => (float) $payment->tax_amount,
            'method' => $payment->method,
            'status' => $payment->status,
            'currency' => $payment->currency,
            'lines' => $booking ? [
                ['label' => 'Tuition fee ('.$booking->duration_minutes.' min @ ₹'.number_format((float) $booking->hourly_rate, 2).'/hr)', 'amount' => (float) $booking->subtotal],
                ['label' => 'Travel / visiting charge', 'amount' => (float) $booking->travel_charge],
                ['label' => 'Discount', 'amount' => -((float) $booking->discount_amount)],
                ['label' => 'Tax', 'amount' => (float) $booking->tax_amount],
            ] : [],
        ];
    }

    private function referenceNumber(string $prefix = 'PAY'): string
    {
        do {
            $reference = $prefix.now()->format('ymd').strtoupper(Str::random(6));
        } while (Payment::where('order_id', $reference)->exists() || Payment::where('reference_no', $reference)->exists());

        return $reference;
    }
}
