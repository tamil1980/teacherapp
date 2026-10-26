<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Central accessor for platform-wide configuration.
 *
 * Every value can be overridden by an administrator through the `settings`
 * table; the `.env` value acts as the fallback default. Nothing about fees,
 * commission or policy is hard-coded in the business logic.
 */
class Platform
{
    /**
     * Default values keyed by setting name. These mirror the `.env.example`
     * entries so a fresh installation works without any seeded settings.
     */
    public const DEFAULTS = [
        // Commission & fees
        'commission_percent' => 15.0,
        'commission_fixed_fee' => 0.0,
        'commission_base' => 'gross', // gross | teaching
        'tax_percent' => 0.0,
        // The rate we *suggest* to teachers when they set their fees. It is a
        // recommendation only — the teacher always chooses the final price.
        'suggested_hourly_rate' => 500.0,
        'payment_gateway_fee_percent' => 0.0,
        'payment_gateway_fee_fixed' => 0.0,
        'gateway_fee_bearer' => 'platform', // platform | teacher

        // Availability
        'slot_granularity_minutes' => 30,
        'default_session_minutes' => 60,
        'max_session_minutes' => 240,
        'session_duration_options' => [30, 60, 90, 120, 180],
        'hold_timeout_minutes' => 15,
        'min_booking_notice_hours' => 2,
        'default_travel_charge' => 0.0,
        'booking_requires_teacher_approval' => false,

        // Cancellation & refund
        'cancellation_window_hours' => 24,
        'cancellation_fee_percent' => 0.0,
        'reschedule_window_hours' => 12,
        'refund_full_within_hours' => 24,
        'refund_policy_text' => 'Cancellations made more than 24 hours before the scheduled class are refunded in full. '
            .'Cancellations within 24 hours are subject to a cancellation charge as displayed at checkout.',

        // Settlement
        'teacher_settlement_delay_days' => 2,
        'payout_minimum_amount' => 500.0,
        'payout_processing_days' => 3,

        // Misc
        'support_phone' => '18001234567',
        'support_email' => 'support@edubook.test',
        'currency' => 'INR',
        'platform_name' => 'EduBook',
        'google_maps_api_key' => '',
        'meetings_enabled' => true,
        'allow_offline_classes' => true,
        'allow_online_classes' => true,
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $fallback = $default ?? (self::DEFAULTS[$key] ?? null);

        $value = Cache::remember('platform.setting.'.$key, 300, function () use ($key, $fallback) {
            $setting = Setting::where('key', $key)->first();

            if (! $setting) {
                return $fallback;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'float' => (float) $setting->value,
                'json' => json_decode((string) $setting->value, true),
                default => $setting->value,
            };
        });

        return $value ?? $fallback;
    }

    public static function put(string $key, mixed $value, string $group = 'general', bool $isPublic = false): void
    {
        $type = match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };

        $stored = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'group' => $group, 'type' => $type, 'is_public' => $isPublic]
        );

        Cache::forget('platform.setting.'.$key);
    }

    public static function flush(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Cache::forget('platform.setting.'.$key);
        }
    }

    public static function money(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2);
    }
}
