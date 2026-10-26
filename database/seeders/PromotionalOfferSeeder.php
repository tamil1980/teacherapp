<?php

namespace Database\Seeders;

use App\Models\PromotionalOffer;
use Illuminate\Database\Seeder;

class PromotionalOfferSeeder extends Seeder
{
    public function run(): void
    {
        PromotionalOffer::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'First session 10% off',
                'description' => 'Flat 10% off on the first booking for every new parent.',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'max_discount' => 150,
                'min_order_amount' => 300,
                'applies_to_first_booking' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'usage_limit' => 1000,
                'used_count' => 0,
                'is_active' => true,
            ]
        );

        PromotionalOffer::updateOrCreate(
            ['code' => 'EARLYBIRD'],
            [
                'name' => 'Early bird flat ₹100 off',
                'description' => 'Flat ₹100 off on bookings worth more than ₹800.',
                'discount_type' => 'flat',
                'discount_value' => 100,
                'max_discount' => 100,
                'min_order_amount' => 800,
                'applies_to_first_booking' => false,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(1),
                'usage_limit' => 500,
                'used_count' => 0,
                'is_active' => true,
            ]
        );
    }
}
