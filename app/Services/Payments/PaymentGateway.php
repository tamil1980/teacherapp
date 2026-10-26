<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Is the gateway ready to process real transactions?
     */
    public function isConfigured(): bool;

    /**
     * Create a payment order with the gateway.
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): GatewayOrder;

    /**
     * Verify the signature returned by the checkout callback.
     *
     * @param  array<string, mixed>  $payload  typically order_id + payment_id + signature
     */
    public function verifySignature(array $payload): bool;

    /**
     * Fetch the authoritative payment object from the gateway (used to
     * confirm a transaction server-side rather than trusting the browser).
     */
    public function fetchPayment(string $gatewayPaymentId): ?array;

    /**
     * Refund a captured payment.
     */
    public function refund(string $gatewayPaymentId, float $amount, array $notes = []): array;

    /**
     * Verify an incoming webhook signature.
     */
    public function verifyWebhook(string $rawBody, string $signature): bool;

    /**
     * Configuration payload consumed by the browser checkout component.
     */
    public function checkoutConfig(GatewayOrder $order): array;

    /**
     * Convert the gateway's decimal amount into the smallest currency unit.
     */
    public function toMinorUnit(float $amount): int;

    /**
     * Convert the gateway's smallest currency unit back to a decimal amount.
     */
    public function fromMinorUnit(int $amount): float;
}
