<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Claim pages are personal; keep them out of search engines. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#991b1b">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('promo.name') }}</title>

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-red-900 font-sans text-stone-800 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(ellipse_at_top,#dc2626_0%,#991b1b_45%,#450a0a_100%)]">
            {{-- Soft falling-snow texture. --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle,rgba(255,255,255,0.28)_1px,transparent_1.6px)] bg-size-[26px_26px] [mask-image:linear-gradient(to_bottom,black,transparent_75%)]"></div>

            <main class="relative mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-10">
                <header class="mb-6 text-center text-white">
                    <p class="text-xs font-semibold tracking-[0.3em] text-red-100/80 uppercase">Merry Christmas</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight">{{ config('promo.name') }}</p>
                </header>

                <div class="rounded-3xl bg-white p-6 shadow-2xl shadow-black/30 sm:p-8">
                    @yield('content')
                </div>

                @if (filled(config('promo.support_contact')))
                    <p class="mt-6 text-center text-sm text-red-100">
                        Need help? Contact us at <span class="font-semibold text-white">{{ config('promo.support_contact') }}</span>
                    </p>
                @endif
            </main>
        </div>
    </body>
</html>
