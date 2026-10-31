@extends('layouts.app')

@section('content')
<div data-shell data-menu="closed" class="group/shell min-h-dvh">

    @include('partials.flash')

    <header class="fixed inset-x-0 top-0 z-30 flex h-16 items-center justify-between gap-3 bg-teal-700 px-3 text-cream-50 sm:px-5">

        <div class="flex items-center gap-3">
            <button
                type="button"
                data-menu-toggle
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Toggle menu"
                class="flex size-11 cursor-pointer items-center justify-center rounded-lg hover:bg-teal-600 focus-visible:outline-cream-50"
            >
                <svg class="size-6 group-data-[menu=open]/shell:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg class="hidden size-6 group-data-[menu=open]/shell:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="block rounded focus-visible:outline-cream-50" aria-label="Lutong Piyesta home">
                <img src="{{ asset('images/logo.png') }}" alt="" width="1200" height="543" class="block h-12 w-auto">
            </a>
        </div>

        <div class="flex min-w-0 items-center gap-3">
            {{-- Shown by JS only where the browser can do push; stays hidden otherwise --}}
            <button
                type="button"
                data-push-toggle
                hidden
                aria-pressed="false"
                aria-label="Turn on order notifications"
                title="Order notifications"
                class="flex size-11 cursor-pointer items-center justify-center rounded-lg hover:bg-teal-600 focus-visible:outline-cream-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg class="size-6" data-push-icon-off viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.7 3A6 6 0 0 1 18 8c0 2.6.5 4.4 1.1 5.5M6.3 6.3A6 6 0 0 0 6 8c0 7-3 9-3 9h14"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><path d="m2 2 20 20"/></svg>
                <svg class="hidden size-6" data-push-icon-on viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            </button>

            <span class="hidden truncate text-sm font-bold sm:inline sm:text-base" title="{{ auth()->user()->username }}">{{ auth()->user()->username }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="cursor-pointer rounded-lg border-2 border-cream-50/70 px-3 py-1.5 text-sm font-bold hover:bg-cream-50 hover:text-teal-900 focus-visible:outline-cream-50"
                >Sign Out</button>
            </form>
        </div>
    </header>

    {{-- Dimmed backdrop, mobile only --}}
    <div
        data-menu-close
        class="pointer-events-none fixed inset-x-0 bottom-0 top-16 z-20 bg-ink/40 opacity-0 transition-opacity duration-200 group-data-[menu=open]/shell:pointer-events-auto group-data-[menu=open]/shell:opacity-100 lg:hidden"
    ></div>

    <aside
        id="sidebar"
        aria-label="Menu"
        class="invisible fixed bottom-0 left-0 top-16 z-20 w-64 -translate-x-full overflow-y-auto border-r-2 border-cream-200 bg-cream-50 transition-[transform,visibility] duration-200 group-data-[menu=open]/shell:visible group-data-[menu=open]/shell:translate-x-0"
    >
        @include('partials.sidebar-nav')
    </aside>

    <main class="pt-16 transition-[padding] duration-200 lg:group-data-[menu=open]/shell:pl-64">
        <div class="px-4 py-6 sm:px-8 sm:py-8">
            @yield('main')
        </div>
    </main>
</div>
@endsection
