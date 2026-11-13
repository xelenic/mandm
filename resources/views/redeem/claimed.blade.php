@extends('layouts.promo', ['title' => 'Gift claimed'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="check" tone="green" />
        <h1 class="mt-4 text-2xl font-bold text-stone-900">Your gift is reserved!</h1>
        <p class="mt-2 text-stone-600">{{ $claim->gift->getLabel() }}</p>
    </div>

    <div class="mt-6 rounded-2xl border-2 border-dashed border-red-200 bg-red-50 px-4 py-5 text-center">
        <p class="text-xs font-semibold tracking-wider text-red-700 uppercase">Your claim reference</p>
        <p class="mt-1 font-mono text-2xl font-bold tracking-wider text-stone-900 select-all">{{ $claim->reference }}</p>
    </div>

    <ul class="mt-6 space-y-3 text-sm text-stone-700">
        <li class="flex gap-3">
            <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-700 text-xs font-bold text-white">1</span>
            <span><strong>Save this reference</strong>: take a screenshot of this page.</span>
        </li>
        <li class="flex gap-3">
            <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-700 text-xs font-bold text-white">2</span>
            <span>Our team will contact you on <strong class="whitespace-nowrap">{{ $claim->formattedPhone() }}</strong> to arrange your voucher.</span>
        </li>
        <li class="flex gap-3">
            <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-700 text-xs font-bold text-white">3</span>
            <span>Bring your NIC and this reference when you collect your gift.</span>
        </li>
    </ul>
@endsection
