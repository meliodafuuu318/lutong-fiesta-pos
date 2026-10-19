{{-- Menu tabs. Add one <li> per page as it gets built. --}}
<nav aria-label="Main menu" class="p-3">
    <ul class="space-y-1">
        <li>
            <a
                href="{{ route('dashboard') }}"
                @class([
                    'block rounded-lg px-4 py-2.5 font-bold',
                    'bg-teal-100 text-teal-900' => request()->routeIs('dashboard'),
                    'text-ink hover:bg-cream-100' => ! request()->routeIs('dashboard'),
                ])
                @if (request()->routeIs('dashboard')) aria-current="page" @endif
            >Dashboard</a>
        </li>
    </ul>
</nav>
