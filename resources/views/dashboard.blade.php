@extends('layouts.app')

@section('title', 'Dashboard · ' . config('app.name'))

@section('content')
<main class="flex min-h-dvh flex-col items-center justify-center gap-6 px-6 text-center">
    <img src="{{ asset('images/logo.png') }}" alt="" width="600" height="600" class="size-28 rounded-full">
    <div>
        <h1 class="font-display text-3xl font-bold text-teal-900">Welcome, {{ auth()->user()->username }}</h1>
        <p class="mt-1 text-ink-soft">The register screens will go here.</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="cursor-pointer rounded-lg border-2 border-teal-700 px-5 py-2 font-bold text-teal-700 hover:bg-teal-700 hover:text-cream-50">Sign out</button>
    </form>
</main>
@endsection
