<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#136f6b">
    @if (filled(config('webpush.vapid.public_key')) && auth()->check())
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        <meta name="push-subscriptions-url" content="{{ route('push-subscriptions.store') }}">
    @endif
    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=baloo-2:600,700|nunito-sans:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh antialiased">
    {{-- Thin bar across the top while a request is being sent or a page is loading --}}
    <div data-progress hidden role="progressbar" aria-label="Loading" class="page-progress"></div>

    @yield('content')
</body>
</html>
