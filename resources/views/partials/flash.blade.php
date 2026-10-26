{{-- Notification: fixed so that it disappearing never shifts the page --}}
@php($flashError = $errors->first('daily_item') ?: $errors->first('item') ?: $errors->first('order'))

@if (session('status'))
    <p
        role="status"
        data-flash="4000"
        class="pointer-events-none fixed left-1/2 top-20 z-40 w-[min(32rem,calc(100%-2rem))] -translate-x-1/2 rounded-lg border-2 border-teal-500 bg-teal-100 px-4 py-3 font-semibold text-teal-900 shadow-lg transition-opacity duration-500"
    >{{ session('status') }}</p>
@endif

@if ($flashError)
    <p
        role="alert"
        data-flash="8000"
        class="pointer-events-none fixed left-1/2 top-20 z-40 w-[min(32rem,calc(100%-2rem))] -translate-x-1/2 rounded-lg border-2 border-danger bg-cream-50 px-4 py-3 font-semibold text-danger shadow-lg transition-opacity duration-500"
    >{{ $flashError }}</p>
@endif
