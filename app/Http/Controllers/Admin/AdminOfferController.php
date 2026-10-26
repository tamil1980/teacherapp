<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\PromotionalOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Promotional offers / coupon codes.
 */
class AdminOfferController extends Controller
{
    use AuditsActions;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $offers = PromotionalOffer::query()
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.offers.index', [
            'offers' => $offers,
            'filters' => ['q' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $offer = PromotionalOffer::create([
            'code' => Str::upper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'max_discount' => isset($validated['max_discount']) && $validated['max_discount'] !== ''
                ? (float) $validated['max_discount']
                : null,
            'min_order_amount' => (float) ($validated['min_order_amount'] ?? 0),
            'applies_to_first_booking' => (bool) ($validated['applies_to_first_booking'] ?? false),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => isset($validated['usage_limit']) && $validated['usage_limit'] !== ''
                ? (int) $validated['usage_limit']
                : null,
            'used_count' => 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->audit('offer.create', PromotionalOffer::class, $offer->id, [
            'code' => $offer->code,
            'name' => $offer->name,
        ]);

        return back()->with('success', 'The offer '.$offer->code.' has been created.');
    }

    public function update(Request $request, PromotionalOffer $offer): RedirectResponse
    {
        $validated = $request->validate($this->rules($offer));

        $offer->update([
            'code' => Str::upper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'max_discount' => isset($validated['max_discount']) && $validated['max_discount'] !== ''
                ? (float) $validated['max_discount']
                : null,
            'min_order_amount' => (float) ($validated['min_order_amount'] ?? 0),
            'applies_to_first_booking' => (bool) ($validated['applies_to_first_booking'] ?? false),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => isset($validated['usage_limit']) && $validated['usage_limit'] !== ''
                ? (int) $validated['usage_limit']
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $this->audit('offer.update', PromotionalOffer::class, $offer->id, [
            'code' => $offer->code,
            'name' => $offer->name,
            'is_active' => $offer->is_active,
        ]);

        return back()->with('success', 'The offer '.$offer->code.' has been updated.');
    }

    public function destroy(PromotionalOffer $offer): RedirectResponse
    {
        $code = $offer->code;
        $used = (int) $offer->used_count;

        $offer->delete();

        $this->audit('offer.delete', PromotionalOffer::class, $offer->id, [
            'code' => $code,
            'used_count' => $used,
        ]);

        return back()->with('success', 'The offer '.$code.' has been deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?PromotionalOffer $offer = null): array
    {
        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('promotional_offers', 'code')->ignore($offer?->id)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percent', 'flat'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to_first_booking' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
