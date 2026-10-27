@extends('layouts.app')

@section('title', 'Sign in · ' . config('app.name'))

@section('content')
<main class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">

    <section class="sunburst flex items-center justify-center px-6 py-10 lg:py-0">
        <img
            src="{{ asset('images/logo.png') }}"
            alt="Lutong Piyesta — Ulam at Meryenda"
            width="1200" height="543"
            class="h-auto w-[min(18rem,80%)] sm:w-[min(22rem,80%)] lg:w-[min(32rem,80%)]"
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

                <div>
                    <label for="remember" class="flex cursor-pointer items-start gap-3">
                        <input
                            id="remember" name="remember" type="checkbox" value="1"
                            @checked(old('remember'))
                            class="mt-0.5 size-5 shrink-0 cursor-pointer rounded border-2 border-cream-200 accent-teal-700"
                        >
                        <span>
                            <span class="block text-sm font-bold text-ink">Keep me signed in</span>
                            <span class="block text-sm text-ink-soft">Stay signed in on this device. Only use this on your own device.</span>
                        </span>
                    </label>
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
