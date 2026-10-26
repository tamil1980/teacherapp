<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay gateway (primary gateway for the Indian market).
 *
 * Supports UPI, cards, net banking and wallets through Razorpay Checkout.
 * Card numbers, CVVs and UPI PINs are handled entirely by Razorpay — the
 * EduBook servers never receive or store them.
 */
class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
        private readonly string $webhookSecret = '',
        private readonly string $mode = 'sandbox',
    ) {}

    public function name(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return $this->keyId !== '' && $this->keySecret !== '';
    }

    public function baseUrl(): string
    {
        return $this->mode === 'live'
            ? 'https://api.razorpay.com/v1'
            : 'https://api.razorpay.com/v1';
    }

    public function createOrder(float $amount, string $receipt, array $notes = []): GatewayOrder
    {
        if (! $this->isConfigured()) {
            throw new PaymentException('The Razorpay gateway is not configured. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET to your .env file.');
        }

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post($this->baseUrl().'/orders', [
                'amount' => $this->toMinorUnit($amount),
                'currency' => 'INR',
                'receipt' => $receipt,
                'notes' => array_merge(['platform' => 'edubook'], $notes),
                'payment_capture' => 1,
            ]);

        if (! $response->successful()) {
            Log::error('Razorpay order creation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new PaymentException('We could not start the payment. Please try again.');
        }

        $data = $response->json();

        return new GatewayOrder(
            id: (string) $data['id'],
            amount: $this->fromMinorUnit((int) $data['amount']),
            currency: $data['currency'] ?? 'INR',
            key: $this->keyId,
            receipt: $data['receipt'] ?? $receipt,
            raw: $data,
        );
    }

    public function verifySignature(array $payload): bool
    {
        $orderId = (string) ($payload['razorpay_order_id'] ?? $payload['order_id'] ?? '');
        $paymentId = (string) ($payload['razorpay_payment_id'] ?? $payload['payment_id'] ?? '');
        $signature = (string) ($payload['razorpay_signature'] ?? $payload['signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret);

        return hash_equals($expected, $signature);
    }

    public function fetchPayment(string $gatewayPaymentId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->get($this->baseUrl().'/payments/'.$gatewayPaymentId);

        if (! $response->successful()) {
            Log::error('Razorpay payment fetch failed', [
                'payment_id' => $gatewayPaymentId,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function refund(string $gatewayPaymentId, float $amount, array $notes = []): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentException('The Razorpay gateway is not configured.');
        }

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post($this->baseUrl().'/payments/'.$gatewayPaymentId.'/refund', [
                'amount' => $this->toMinorUnit($amount),
                'notes' => array_merge(['platform' => 'edubook'], $notes),
            ]);

        if (! $response->successful()) {
            Log::error('Razorpay refund failed', [
                'payment_id' => $gatewayPaymentId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new PaymentException('The refund could not be processed by the gateway.');
        }

        return $response->json();
    }

    public function verifyWebhook(string $rawBody, string $signature): bool
    {
        if ($this->webhookSecret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function checkoutConfig(GatewayOrder $order): array
    {
        return [
            'provider' => 'razorpay',
            'key' => $this->keyId,
            'order_id' => $order->id,
            'amount' => $this->toMinorUnit($order->amount),
            'currency' => $order->currency,
            'name' => config('app.name'),
            'description' => 'EduBook tuition booking',
            'prefill' => [],
            'theme' => ['color' => '#4f46e5'],
            'modal' => ['ondismiss' => true, 'confirm_close' => true],
            'notes' => ['platform' => 'edubook'],
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
