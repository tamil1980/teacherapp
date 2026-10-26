<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Platform;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['commission_percent', '15.00', 'commission', 'float', false],
            ['commission_fixed_fee', '0.00', 'commission', 'float', false],
            ['commission_base', 'gross', 'commission', 'string', false],
            ['tax_percent', '0.00', 'commission', 'float', false],
            ['payment_gateway_fee_percent', '2.00', 'commission', 'float', false],
            ['payment_gateway_fee_fixed', '0.00', 'commission', 'float', false],
            ['gateway_fee_bearer', 'platform', 'commission', 'string', false],

            ['suggested_hourly_rate', '500.00', 'fees', 'float', false],

            ['slot_granularity_minutes', '30', 'availability', 'integer', false],
            ['default_session_minutes', '60', 'availability', 'integer', false],
            ['max_session_minutes', '240', 'availability', 'integer', false],
            ['session_duration_options', '[30,60,90,120,180]', 'availability', 'json', false],
            ['hold_timeout_minutes', '15', 'availability', 'integer', false],
            ['min_booking_notice_hours', '2', 'availability', 'integer', false],
            ['default_travel_charge', '0.00', 'availability', 'float', false],
            ['booking_requires_teacher_approval', '0', 'availability', 'boolean', false],

            ['cancellation_window_hours', '24', 'policy', 'integer', false],
            ['cancellation_fee_percent', '0.00', 'policy', 'float', false],
            ['reschedule_window_hours', '12', 'policy', 'integer', false],
            ['refund_full_within_hours', '24', 'policy', 'integer', false],
            ['refund_policy_text', 'Cancellations made more than 24 hours before the scheduled class are refunded in full. Cancellations within 24 hours are subject to a cancellation charge as displayed at checkout.', 'policy', 'string', true],

            ['teacher_settlement_delay_days', '2', 'settlement', 'integer', false],
            ['payout_minimum_amount', '500.00', 'settlement', 'float', false],
            ['payout_processing_days', '3', 'settlement', 'integer', false],

            ['support_phone', '18001234567', 'general', 'string', true],
            ['support_email', 'support@edubook.test', 'general', 'string', true],
            ['currency', 'INR', 'general', 'string', true],
            ['platform_name', 'EduBook', 'general', 'string', true],
            ['google_maps_api_key', '', 'general', 'string', false],
            ['meetings_enabled', '1', 'general', 'boolean', true],
            ['allow_offline_classes', '1', 'general', 'boolean', true],
            ['allow_online_classes', '1', 'general', 'boolean', true],
        ];

        foreach ($settings as [$key, $value, $group, $type, $isPublic]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'type' => $type, 'is_public' => $isPublic]
            );
        }

        Platform::flush();
    }
}
