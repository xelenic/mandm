@extends('layouts.promo', ['title' => 'Already claimed'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="lock" tone="stone" />
        <h1 class="mt-4 text-2xl font-bold text-stone-900">This gift has already been claimed</h1>
        <p class="mt-3 text-stone-600">
            The gift in this package was claimed on {{ $claimedAt->format('j F Y') }}.
            Each QR code can only be used once.
        </p>
        <p class="mt-3 text-sm text-stone-500">
            If you claimed it yourself, our team will contact you using the details you entered.
        </p>
    </div>
@endsection
