@php
    // Menu tabs. Add one entry per page as it gets built.
    // Icons are 24x24 stroke paths (Lucide-style), drawn with currentColor.
    $links = [
        [
            'roles' => ['admin', 'cashier', 'rider'], 'label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard',
            'icon' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        ],
        [
            'roles' => ['admin'], 'label' => 'Menu', 'route' => 'menu', 'active' => 'menu',
            'icon' => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        ],
        [
            'roles' => ['admin'], 'label' => 'Items', 'route' => 'items.index', 'active' => 'items.*',
            'icon' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        ],
        [
            'roles' => ['cashier'], 'label' => 'Pending Payments', 'route' => 'pending-payments', 'active' => 'pending-payments',
            'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        ],
        [
            'roles' => ['admin', 'cashier'], 'label' => 'Transactions', 'route' => 'transactions.index', 'active' => 'transactions.*',
            'icon' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        ],
        [
            'roles' => ['admin'], 'label' => 'Expenses', 'route' => 'expenses.index', 'active' => 'expenses.*',
            'icon' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        ],
    ];

    // Each role only sees its own pages.
    $links = array_filter($links, fn ($link) => auth()->user()->hasRole(...$link['roles']));
@endphp

<nav aria-label="Main menu" class="p-3">
    <ul class="space-y-1">
        @foreach ($links as $link)
            @php($isActive = request()->routeIs($link['active']))
            <li>
                <a
                    href="{{ app('router')->has($link['route']) ? route($link['route']) : '#' }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-4 py-3 font-bold',
                        'bg-teal-100 text-teal-900' => $isActive,
                        'text-ink hover:bg-cream-100' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
                >
                    <svg
                        class="{{ $isActive ? 'text-teal-700' : 'text-ink-soft' }} size-6 shrink-0"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                    >{!! $link['icon'] !!}</svg>
                    <span>{{ $link['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>

{{-- Signed-in user: the header hides the name on small screens to make room --}}
<div class="border-t-2 border-cream-200 px-7 py-4 sm:hidden">
    <p class="text-xs font-bold uppercase tracking-wide text-ink-soft">Signed in as</p>
    <p class="mt-0.5 truncate font-bold text-ink">{{ auth()->user()->username }}</p>
</div>
