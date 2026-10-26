<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform settings.
 *
 * Every value lives in the `settings` table and is read through
 * {@see Platform}, so changing a value here immediately changes the way the
 * marketplace behaves.
 */
class AdminSettingController extends Controller
{
    use AuditsActions;

    /**
     * The settings screen, grouped by the area of the platform they affect.
     *
     * @return array<string, array{label: string, description: string, fields: array<string, array<string, mixed>>}>
     */
    public static function schema(): array
    {
        return [
            'commission' => [
                'label' => 'Commission, gateway fees & tax',
                'description' => 'How the money paid by a parent is split between EduBook and the teacher.',
                'fields' => [
                    'commission_percent' => [
                        'label' => 'Commission percentage',
                        'type' => 'float',
                        'step' => '0.01',
                        'hint' => 'The platform\'s share of the teaching fee, in percent.',
                    ],
                    'commission_fixed_fee' => [
                        'label' => 'Fixed commission per booking (₹)',
                        'type' => 'float',
                        'step' => '0.01',
                        'hint' => 'Added on top of the percentage for every booking.',
                    ],
                    'commission_base' => [
                        'label' => 'Commission is calculated on',
                        'type' => 'select',
                        'options' => [
                            'gross' => 'The gross amount (teaching fee + travel charge)',
                            'teaching' => 'The teaching fee only',
                        ],
                    ],
                    'tax_percent' => [
                        'label' => 'Tax percentage',
                        'type' => 'float',
                        'step' => '0.01',
                        'hint' => 'Added on top of the booking total and shown separately on the receipt.',
                    ],
                    'payment_gateway_fee_percent' => [
                        'label' => 'Payment gateway fee (%)',
                        'type' => 'float',
                        'step' => '0.01',
                    ],
                    'payment_gateway_fee_fixed' => [
                        'label' => 'Payment gateway fixed fee (₹)',
                        'type' => 'float',
                        'step' => '0.01',
                    ],
                    'gateway_fee_bearer' => [
                        'label' => 'The gateway fee is borne by',
                        'type' => 'select',
                        'options' => [
                            'platform' => 'EduBook (the platform)',
                            'teacher' => 'The teacher',
                        ],
                    ],
                ],
            ],
            'availability' => [
                'label' => 'Slots, sessions & booking',
                'description' => 'How much a session may cost, how long it may run and how early it must be booked.',
                'fields' => [
                    'slot_granularity_minutes' => [
                        'label' => 'Slot granularity (minutes)',
                        'type' => 'integer',
                        'hint' => 'The smallest bookable step in the timetable.',
                    ],
                    'default_session_minutes' => [
                        'label' => 'Default session length (minutes)',
                        'type' => 'integer',
                    ],
                    'max_session_minutes' => [
                        'label' => 'Maximum session length (minutes)',
                        'type' => 'integer',
                    ],
                    'session_duration_options' => [
                        'label' => 'Session length options (minutes)',
                        'type' => 'list',
                        'hint' => 'Comma separated, e.g. 30, 60, 90, 120.',
                    ],
                    'hold_timeout_minutes' => [
                        'label' => 'Slot hold timeout (minutes)',
                        'type' => 'integer',
                        'hint' => 'How long a slot is reserved while a parent completes checkout.',
                    ],
                    'min_booking_notice_hours' => [
                        'label' => 'Minimum booking notice (hours)',
                        'type' => 'integer',
                    ],
                    'default_travel_charge' => [
                        'label' => 'Default travel / visiting charge (₹)',
                        'type' => 'float',
                        'step' => '0.01',
                    ],
                    'booking_requires_teacher_approval' => [
                        'label' => 'Every booking must be accepted by the teacher',
                        'type' => 'boolean',
                    ],
                ],
            ],
            'policy' => [
                'label' => 'Cancellation, reschedule & refunds',
                'description' => 'The rules that are applied when a parent or a teacher changes a booking.',
                'fields' => [
                    'cancellation_window_hours' => [
                        'label' => 'Free cancellation window (hours)',
                        'type' => 'integer',
                    ],
                    'cancellation_fee_percent' => [
                        'label' => 'Late cancellation fee (%)',
                        'type' => 'float',
                        'step' => '0.01',
                    ],
                    'reschedule_window_hours' => [
                        'label' => 'Reschedule window (hours)',
                        'type' => 'integer',
                    ],
                    'refund_policy_text' => [
                        'label' => 'Refund policy shown to parents',
                        'type' => 'text',
                        'hint' => 'This text is shown on the public cancellation and refund policy pages.',
                    ],
                ],
            ],
            'settlement' => [
                'label' => 'Teacher settlement & payouts',
                'description' => 'When a teacher earns the money and how much must accumulate before a payout.',
                'fields' => [
                    'teacher_settlement_delay_days' => [
                        'label' => 'Settlement delay (days)',
                        'type' => 'integer',
                        'hint' => 'Days between a completed session and the money becoming payable.',
                    ],
                    'payout_minimum_amount' => [
                        'label' => 'Minimum payout amount (₹)',
                        'type' => 'float',
                        'step' => '0.01',
                    ],
                ],
            ],
            'general' => [
                'label' => 'Support & contact',
                'description' => 'How parents and teachers reach the EduBook support team.',
                'fields' => [
                    'support_phone' => [
                        'label' => 'Support phone number',
                        'type' => 'string',
                    ],
                    'support_email' => [
                        'label' => 'Support email address',
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    public function index(Request $request): View
    {
        $schema = self::schema();
        $values = [];

        foreach ($schema as $group) {
            foreach ($group['fields'] as $key => $field) {
                $values[$key] = Platform::get($key, Platform::DEFAULTS[$key] ?? null);
            }
        }

        return view('admin.settings.index', [
            'schema' => $schema,
            'values' => $values,
            'example' => $this->example(
                (float) $request->query('example_fee', 500),
                (float) $request->query('example_hours', 1),
                (float) $request->query('example_travel', 0),
                (float) $values['commission_percent'],
                (float) $values['commission_fixed_fee']
            ),
        ]);
    }

    /**
     * Persist every setting and clear the platform cache.
     */
    public function update(Request $request): RedirectResponse
    {
        $schema = self::schema();

        $rules = [];
        foreach ($schema as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $rules[$key] = $this->rulesFor($field);
            }
        }

        $validated = $request->validate($rules);

        $changed = [];

        foreach ($schema as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $value = $this->normalise($field, $validated[$key] ?? null);

                $isPublic = (bool) (Setting::query()->where('key', $key)->value('is_public') ?? false);

                Platform::put($key, $value, $group, $isPublic);

                $changed[$key] = $value;
            }
        }

        Platform::flush();

        $this->audit('settings.update', Setting::class, null, [
            'changed' => $changed,
        ]);

        return back()->with('success', 'Platform settings have been saved.');
    }

    /**
     * Build the worked example that explains the money split.
     *
     * @return array<string, float>
     */
    private function example(float $fee, float $hours, float $travel, float $percent, float $fixed): array
    {
        $fee = $fee > 0 ? $fee : 500.0;
        $hours = $hours > 0 ? $hours : 1.0;
        $travel = max(0.0, $travel);

        $teaching = round($fee * $hours, 2);
        $base = round($teaching + $travel, 2);
        $commission = round($base * ($percent / 100) + $fixed, 2);
        $teacher = round($base - $commission, 2);

        return [
            'fee' => $fee,
            'hours' => $hours,
            'travel' => $travel,
            'teaching' => $teaching,
            'base' => $base,
            'percent' => $percent,
            'fixed' => $fixed,
            'commission' => $commission,
            'teacher' => $teacher,
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function rulesFor(array $field): array
    {
        return match ($field['type']) {
            'float' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'integer' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'boolean' => ['nullable', 'boolean'],
            'list' => ['nullable', 'string', 'max:500'],
            'text' => ['nullable', 'string', 'max:5000'],
            'select' => ['nullable', 'string', Rule::in(array_keys($field['options'] ?? []))],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function normalise(array $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return match ($field['type']) {
                'boolean' => false,
                'float' => 0.0,
                'integer' => 0,
                'list' => [],
                'select' => (string) array_key_first($field['options'] ?? []),
                default => null,
            };
        }

        return match ($field['type']) {
            'float' => (float) $value,
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'list' => collect(explode(',', (string) $value))
                ->map(fn ($part) => (int) trim($part))
                ->filter(fn ($part) => $part > 0)
                ->unique()
                ->values()
                ->all(),
            'select' => (string) $value,
            default => (string) $value,
        };
    }
}
