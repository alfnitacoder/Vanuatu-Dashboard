<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Stua')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/stua.css') }}">
</head>
<body>
    {{-- Design slot: tokens live in public/css/stua.css. Swap Blade screens here when Design lands. --}}
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
                <svg class="mark" viewBox="0 0 40 40" aria-hidden="true">
                    <path fill="#111111" d="M8 4h24a4 4 0 0 1 4 4v8.2c-2.4 0-4.4 2-4.4 4.4S33.6 25 36 25V32a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-7c2.4 0 4.4-2 4.4-4.4S6.4 16.2 4 16.2V8a4 4 0 0 1 4-4z"/>
                    <path fill="#F6F0E6" d="M14 12h13c2.4 0 4 1.5 4 3.6 0 1.5-1 2.7-2.6 3.2 2 .5 3.2 1.8 3.2 3.6 0 2.4-1.8 4.6-5 4.6H14V12zm4.2 4v3.2h7.1c.8 0 1.4-.5 1.4-1.6s-.6-1.6-1.4-1.6H18.2zm0 6.2V26h7.8c1 0 1.7-.6 1.7-1.8s-.7-1.8-1.7-1.8h-7.8z"/>
                </svg>
                <span>STUA</span>
            </a>
            @auth
                <nav class="nav">
                    <a href="{{ route('dashboard') }}">End of day / En blong dei</a>
                    <a class="nav-pay" href="{{ route('sales.create') }}">Record sale / Rekodem sel</a>
                </nav>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="linkish" type="submit">Log out / Aot</button>
                </form>
            @endauth
        </header>
        <main class="main">
            @if (session('status'))
                <p class="flash">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
