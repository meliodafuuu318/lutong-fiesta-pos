@extends('layouts.app')

@section('title', 'Sign in · ' . config('app.name'))

@section('content')
<main class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">

    <section class="sunburst flex items-center justify-center px-6 py-10 lg:py-0">
        <img
            src="{{ asset('images/logo.png') }}"
            alt="Lutong Fiesta — Ulam at Merienda"
            width="600" height="600"
            class="size-36 rounded-full shadow-[0_12px_0_0_rgb(11_63_61/0.35)] sm:size-44 lg:size-80"
        >
    </section>

    <section class="flex items-center justify-center px-6 py-12 sm:px-10">
        <div class="w-full max-w-sm">
            <h1 class="font-display text-4xl font-bold leading-tight text-teal-900">Sign in</h1>
            <p class="mt-1 text-ink-soft">Use your staff account to open the register.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="username" class="block text-sm font-bold text-ink">Username</label>
                    <input
                        id="username" name="username" type="text"
                        value="{{ old('username') }}"
                        autocomplete="username" autocapitalize="none" spellcheck="false"
                        required autofocus
                        @class([
                            'mt-1.5 w-full rounded-lg border-2 bg-cream-50 px-4 py-3 text-base text-ink placeholder:text-ink-soft/60',
                            'border-cream-200 focus:border-teal-500' => ! $errors->has('username'),
                            'border-danger' => $errors->has('username'),
                        ])
                        @if ($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif
                    >
                    @error('username')
                        <p id="username-error" class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-ink">Password</label>
                    <div class="relative mt-1.5">
                        <input
                            id="password" name="password" type="password"
                            autocomplete="current-password" required
                            @class([
                                'w-full rounded-lg border-2 bg-cream-50 py-3 pl-4 pr-20 text-base text-ink',
                                'border-cream-200 focus:border-teal-500' => ! $errors->has('password'),
                                'border-danger' => $errors->has('password'),
                            ])
                            @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                        >
                        <button
                            type="button" data-toggle-password="password" aria-pressed="false"
                            class="absolute inset-y-0 right-0 cursor-pointer px-4 text-sm font-bold text-teal-700 hover:text-teal-900"
                        >Show</button>
                    </div>
                    @error('password')
                        <p id="password-error" class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
                >Sign in</button>
            </form>
        </div>
    </section>
</main>
@endsection
