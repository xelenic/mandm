@extends('layouts.promo', ['title' => 'Code not found'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="question" tone="stone" />
        <h1 class="mt-4 text-2xl font-bold text-stone-900">We couldn't find this code</h1>
        <p class="mt-3 text-stone-600">
            Please scan the QR code inside your Christmas chocolate package again.
            If it still doesn't work, the code may be damaged.
        </p>
        <a
            href="{{ route('home') }}"
            class="mt-6 inline-block rounded-xl bg-red-700 px-5 py-3 text-base font-semibold text-white shadow-lg shadow-red-700/30 transition hover:bg-red-800 focus-visible:ring-4 focus-visible:ring-red-600/30 focus-visible:outline-none"
        >
            Type the code from your card
        </a>
    </div>
@endsection
