@props([
    'title' => null,
    // Gift stacks in the bottom corners (the menu, screen 1).
    'gifts' => false,
    // Room kept clear at the bottom for the footer (and the gifts, when shown).
    'bottom' => null,
    // Centre the content in the space below the logo (screens with a single panel).
    'center' => false,
])

@php
    use App\Support\LuckyDrawImage;

    // Hanging baubles across the red band: [image, left edge, width, swing delay], in % of the screen width.
    $baubles = [
        ['bauble-gold-small', 3.5, 15, '0s'],
        ['bauble-cream-long', 10, 22, '-1.4s'],
        ['bauble-gold', 66.5, 27, '-0.7s'],
        ['bauble-cream-small', 86.5, 11, '-2.1s'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        {{-- Claim pages are personal; keep them out of search engines. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#d70100">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('promo.name') }}</title>

        <link rel="preload" as="image" href="{{ LuckyDrawImage::url('logo') }}">
        @vite(['resources/css/app.css'])
    </head>
    {{-- Outside the phone-width column (tablets, desktop) the page stays a warm yellow. --}}
    <body class="lucky min-h-svh bg-[#f3c400] font-condensed text-candy-text antialiased">
        <div class="@container relative mx-auto flex min-h-svh w-full max-w-[480px] flex-col overflow-hidden bg-sunny shadow-2xl shadow-black/20">
            {{-- Red band across the top, with baubles hanging from it. --}}
            <div class="absolute inset-x-0 top-0 h-[25cqw] bg-candy" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0" aria-hidden="true">
                @foreach ($baubles as [$image, $left, $width, $delay])
                    <img
                        src="{{ LuckyDrawImage::url($image) }}"
                        alt=""
                        class="absolute top-0 h-auto origin-top animate-swing drop-shadow-[0_2cqw_2cqw_rgba(0,0,0,0.18)]"
                        style="left: {{ $left }}%; width: {{ $width }}%; animation-delay: {{ $delay }}"
                    >
                @endforeach
            </div>

            <header class="relative pt-[9cqw]">
                <a href="{{ session('lucky_draw.menu', route('home')) }}" class="mx-auto block w-[45cqw] animate-drop-in transition-transform duration-300 hover:scale-[1.03] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-candy">
                    <img
                        src="{{ LuckyDrawImage::url('logo') }}"
                        alt="M&amp;M's 2026 Christmas Lucky Draw"
                        width="659"
                        height="524"
                        class="h-auto w-full drop-shadow-[0_1.5cqw_1.5cqw_rgba(0,0,0,0.2)]"
                    >
                </a>
            </header>

            <main @class([
                'relative z-10 flex flex-1 flex-col items-center px-[6cqw] pt-[3cqw]',
                'justify-center' => $center,
                $bottom ?? ($gifts ? 'pb-[36cqw]' : 'pb-[14cqw]'),
            ])>
                {{ $slot }}
            </main>

            @if ($gifts)
                <img src="{{ LuckyDrawImage::url('gifts-left') }}" alt="" width="642" height="610" class="pointer-events-none absolute bottom-0 left-0 h-auto w-[34cqw] animate-rise-in [animation-delay:0.6s]" aria-hidden="true">
                <img src="{{ LuckyDrawImage::url('gifts-right') }}" alt="" width="642" height="610" class="pointer-events-none absolute right-0 bottom-0 h-auto w-[34cqw] animate-rise-in [animation-delay:0.7s]" aria-hidden="true">
            @endif

            <footer class="relative z-10 pb-[5cqw] text-center font-sans text-[2.9cqw] text-stone-800">
                {{ config('promo.copyright') }}
            </footer>
        </div>
    </body>
</html>
