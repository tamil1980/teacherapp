<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;

/**
 * Result object returned by a gateway when an order is created.
 */
class GatewayOrder
{
    public function __construct(
        public string $id,
        public float $amount,
        public string $currency,
        public string $key,
        public string $receipt,
        public array $raw = [],
    ) {}

    public function toArray(): array
    {
        return [
            'order_id' => $this->id,
            'amount' => $this->amount,
            'amount_paise' => (int) round($this->amount * 100),
            'currency' => $this->currency,
            'key' => $this->key,
            'receipt' => $this->receipt,
        ];
    }
}
