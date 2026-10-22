@extends('layouts.main')

@section('title', 'Create Transaction · ' . config('app.name'))

@section('main')
<div class="w-full">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous(route('transactions.index')) }}" class="flex size-10 items-center justify-center rounded-lg text-ink-soft hover:bg-cream-100 hover:text-ink">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="font-display text-3xl font-bold text-teal-900">Create Transaction</h1>
    </div>
</div>
@endsection
