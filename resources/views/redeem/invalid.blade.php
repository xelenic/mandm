@extends('layouts.promo', ['title' => 'Code not found'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="question" tone="stone" />
        <h1 class="mt-4 text-2xl font-bold text-stone-900">We couldn't find this code</h1>
        <p class="mt-3 text-stone-600">
            Please scan the QR code inside your Christmas chocolate package again.
            If it still doesn't work, the code may be damaged.
        </p>
    </div>
@endsection
