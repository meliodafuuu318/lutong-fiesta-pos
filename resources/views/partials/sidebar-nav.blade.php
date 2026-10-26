@php
    // Menu tabs. Add one entry per page as it gets built.
    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Menu', 'route' => 'menu', 'active' => 'menu'],
        ['label' => 'Items', 'route' => 'items.index', 'active' => 'items.*'],
        ['label' => 'Transactions', 'route' => 'transactions.index', 'active' => 'transactions.*'],
    ];
@endphp

<nav aria-label="Main menu" class="p-3">
    <ul class="space-y-1">
        @foreach ($links as $link)
            @php($isActive = request()->routeIs($link['active']))
            <li>
                <a
                    href="{{ app('router')->has($link['route']) ? route($link['route']) : '#' }}"
                    @class([
                        'block rounded-lg px-4 py-2.5 font-bold',
                        'bg-teal-100 text-teal-900' => $isActive,
                        'text-ink hover:bg-cream-100' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
                >{{ $link['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
