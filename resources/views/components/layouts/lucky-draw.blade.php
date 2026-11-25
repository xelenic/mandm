@props([
    'title' => null,
    // Background artwork: "menu" (screen 1), "draw" (screen 2) or "panel" (all other screens).
    'background' => 'panel',
])

@php
    $backgroundUrl = asset("images/lucky-draw/bg-{$background}.webp");
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        {{-- Claim pages are personal; keep them out of search engines. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#7a0d0d">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('promo.name') }}</title>

        <link rel="preload" as="image" href="{{ $backgroundUrl }}">
        <link rel="preload" as="image" href="{{ asset('images/lucky-draw/logo.webp') }}">
        @vite(['resources/css/app.css'])
    </head>
    {{-- Outside the phone-width column (tablets, desktop) the page stays a deep Christmas red. --}}
    <body class="lucky min-h-svh bg-[radial-gradient(ellipse_at_top,#9b1414_0%,#5a0909_70%)] font-condensed text-cocoa antialiased">
        <div
            class="@container relative mx-auto flex min-h-svh w-full max-w-[480px] flex-col bg-[#a3120f] bg-cover bg-center bg-no-repeat shadow-2xl shadow-black/50"
            style="background-image: url('{{ $backgroundUrl }}')"
        >
            <div class="lucky-snow" aria-hidden="true"></div>

            <header class="relative pt-[7cqw]">
                {{-- Drops in, then sways gently (two layers, so the two movements combine). --}}
                <a href="{{ session('lucky_draw.menu', route('home')) }}" class="mx-auto block w-[52cqw] animate-drop-in transition-transform duration-300 hover:scale-[1.03] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    <img
                        src="{{ asset('images/lucky-draw/logo.webp') }}"
                        alt="M&amp;M's 2026 Christmas Lucky Draw"
                        width="659"
                        height="524"
                        class="h-auto w-full origin-top animate-sway drop-shadow-[0_4px_6px_rgba(0,0,0,0.35)] [animation-delay:0.9s]"
                    >
                </a>
            </header>

            <main class="relative flex flex-1 flex-col items-center px-[6cqw] pt-[3cqw] pb-[34cqw]">
                {{ $slot }}
            </main>

            <footer class="relative pb-[4cqw] text-center font-sans text-[3cqw] text-stone-700">
                {{ config('promo.copyright') }}
            </footer>
        </div>
    </body>
</html>
