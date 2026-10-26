@php
    $adminNav = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.teachers.index', 'label' => 'Teachers'],
        ['route' => 'admin.parents.index', 'label' => 'Parents'],
        ['route' => 'admin.students.index', 'label' => 'Students'],
        ['route' => 'admin.bookings.index', 'label' => 'Bookings'],
        ['route' => 'admin.payments.index', 'label' => 'Payments'],
        ['route' => 'admin.refunds.index', 'label' => 'Refunds'],
        ['route' => 'admin.payouts.index', 'label' => 'Payouts'],
        ['route' => 'admin.reviews.index', 'label' => 'Reviews'],
        ['route' => 'admin.disputes.index', 'label' => 'Disputes'],
        ['route' => 'admin.offers.index', 'label' => 'Offers'],
        ['route' => 'admin.catalog.index', 'label' => 'Catalog'],
        ['route' => 'admin.settings.index', 'label' => 'Settings'],
        ['route' => 'admin.audit-logs.index', 'label' => 'Audit log'],
    ];
@endphp

<nav class="mb-6 overflow-x-auto">
    <div class="flex min-w-max gap-1 rounded-xl border border-slate-200 bg-white p-1">
        @foreach ($adminNav as $link)
            <a href="{{ route($link['route']) }}"
               class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($link['route'].'*') || request()->routeIs($link['route']) ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>
