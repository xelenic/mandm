@extends('layouts.promo', ['title' => 'Find your gift'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="gift" tone="amber" />
        <h1 class="mt-4 text-2xl font-bold text-balance text-stone-900">Did you win a gift?</h1>
        <p class="mt-2 text-stone-600">
            Scan the QR code inside your Christmas pack with your phone camera to find out.
        </p>
    </div>

    <div class="mt-8 border-t border-stone-200 pt-6">
        <h2 class="text-base font-semibold text-stone-900">Can't scan the QR code?</h2>
        <p class="mt-1 text-sm text-stone-600">Type the code printed at the bottom right of your card instead.</p>

        <form
            method="POST"
            action="{{ route('redeem.search') }}"
            class="mt-5 space-y-5"
            novalidate
            onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.textContent = 'Searching…';"
        >
            @csrf

            <x-promo.field
                name="code"
                label="Your code"
                placeholder="MM-ABCD2345EFGH"
                hint="Letters and numbers only. Upper or lower case is fine."
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                class="font-mono tracking-wider uppercase placeholder:normal-case placeholder:tracking-normal"
                required
                autofocus
            />

            <button
                type="submit"
                class="w-full rounded-xl bg-red-700 px-4 py-3.5 text-base font-semibold text-white shadow-lg shadow-red-700/30 transition hover:bg-red-800 focus-visible:ring-4 focus-visible:ring-red-600/30 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70"
            >
                Find my gift
            </button>
        </form>
    </div>
@endsection
