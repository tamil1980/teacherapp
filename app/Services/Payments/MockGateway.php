<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use Illuminate\Support\Str;

/**
 * Development / sandbox gateway used when no Razorpay keys are configured.
 *
 * It behaves like a real gateway from the application's point of view:
 * orders are created server-side, a signed callback payload is produced and
 * then validated through the very same signature-verification path that is
 * used for Razorpay. Nothing here contacts an external service, so it is
 * only ever used for local testing — the UI labels it clearly.
 */
class MockGateway implements PaymentGateway
{
    public function __construct(private readonly string $secret) {}

    public function name(): string
    {
        return 'mock';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function createOrder(float $amount, string $receipt, array $notes = []): GatewayOrder
    {
        $id = 'order_'.Str::lower(Str::random(12));

        return new GatewayOrder(
            id: $id,
            amount: $amount,
            currency: 'INR',
            key: 'mock_public_key',
            receipt: $receipt,
            raw: [
                'id' => $id,
                'amount' => $this->toMinorUnit($amount),
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => $notes,
                'status' => 'created',
            ],
        );
    }

    /**
     * Produce the signed callback payload a real gateway would send back.
     *
     * @return array<string, mixed>
     */
    public function signedCallback(GatewayOrder $order, bool $successful = true, string $method = 'upi'): array
    {
        $paymentId = 'pay_'.Str::lower(Str::random(14));
        $signature = hash_hmac('sha256', $order->id.'|'.$paymentId, $this->secret);

        // Register the payment so that the server-side verification step can
        // fetch it back, exactly like it would from a live gateway.
        cache()->put('mock_gateway_payment.'.$paymentId, [
            'id' => $paymentId,
            'order_id' => $order->id,
            'status' => $successful ? 'captured' : 'failed',
            'method' => $method,
            'amount' => $this->toMinorUnit($order->amount),
            'currency' => $order->currency,
            'captured' => $successful,
        ], now()->addHours(24));

        return [
            'razorpay_order_id' => $order->id,
            'razorpay_payment_id' => $successful ? $paymentId : '',
            'razorpay_signature' => $successful ? $signature : '',
            'razorpay_method' => $method,
            'status' => $successful ? 'captured' : 'failed',
        ];
    }

    public function verifySignature(array $payload): bool
    {
        $orderId = (string) ($payload['razorpay_order_id'] ?? $payload['order_id'] ?? '');
        $paymentId = (string) ($payload['razorpay_payment_id'] ?? $payload['payment_id'] ?? '');
        $signature = (string) ($payload['razorpay_signature'] ?? $payload['signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function fetchPayment(string $gatewayPaymentId): ?array
    {
        if ($gatewayPaymentId === '') {
            return null;
        }

        return cache()->get('mock_gateway_payment.'.$gatewayPaymentId);
    }

    public function refund(string $gatewayPaymentId, float $amount, array $notes = []): array
    {
        $refundId = 'rfnd_'.Str::lower(Str::random(12));

        cache()->put('mock_gateway_refund.'.$refundId, [
            'id' => $refundId,
            'payment_id' => $gatewayPaymentId,
            'amount' => $this->toMinorUnit($amount),
            'status' => 'processed',
            'notes' => $notes,
        ], now()->addHours(24));

        return cache()->get('mock_gateway_refund.'.$refundId);
    }

    public function verifyWebhook(string $rawBody, string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function checkoutConfig(GatewayOrder $order): array
    {
        return [
            'provider' => 'mock',
            'key' => 'mock_public_key',
            'order_id' => $order->id,
            'amount' => $this->toMinorUnit($order->amount),
            'currency' => $order->currency,
            'name' => config('app.name'),
            'description' => 'EduBook tuition booking (test gateway)',
            'methods' => ['upi', 'card', 'netbanking', 'wallet'],
            'theme' => ['color' => '#4f46e5'],
        ];
    }

    public function toMinorUnit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function fromMinorUnit(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
