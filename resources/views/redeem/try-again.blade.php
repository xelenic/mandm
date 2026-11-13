@extends('layouts.promo', ['title' => 'Try again'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="star" tone="red" />
        <h1 class="mt-4 text-2xl font-bold text-stone-900">Not a winner this time</h1>
        <p class="mt-3 text-stone-600">
            This pack doesn't have a gift, but some Christmas packs contain a
            Spa Ceylon or Shangri-La dinner voucher.
        </p>
        <p class="mt-4 text-lg font-semibold text-balance text-red-700">Try again with another pack. Good luck!</p>
        <p class="mt-3 text-sm text-stone-500">Thank you for celebrating Christmas with us.</p>
    </div>
@endsection
