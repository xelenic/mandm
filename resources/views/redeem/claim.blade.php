@extends('layouts.promo', ['title' => 'You won!'])

@section('content')
    <div class="text-center">
        <x-promo.icon name="gift" tone="amber" />
        <p class="mt-4 text-sm font-semibold tracking-wider text-red-700 uppercase">Congratulations!</p>
        <h1 class="mt-1 text-2xl font-bold text-balance text-stone-900">You've won a {{ $qrCode->gift->getLabel() }}</h1>
        <p class="mt-2 text-stone-600">{{ $qrCode->gift->tagline() }}</p>
    </div>

    <form
        method="POST"
        action="{{ route('redeem.store', $qrCode->code) }}"
        class="mt-8 space-y-5"
        novalidate
        onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.textContent = 'Claiming…';"
    >
        @csrf

        <p class="text-sm text-stone-600">Enter your details so we can arrange your gift.</p>

        <x-promo.field name="name" label="Full name" autocomplete="name" required />

        <x-promo.field
            name="phone"
            label="Mobile number"
            type="tel"
            inputmode="tel"
            autocomplete="tel"
            placeholder="077 123 4567"
            required
        />

        <x-promo.field
            name="email"
            label="Email address"
            type="email"
            inputmode="email"
            autocomplete="email"
            placeholder="you@example.com"
            required
        />

        <x-promo.field
            name="nic"
            label="NIC number"
            autocapitalize="characters"
            placeholder="199012345678 or 901234567V"
            hint="You may be asked to show your NIC when you collect your gift."
            required
        />

        <div>
            <label class="flex items-start gap-3 text-sm text-stone-700">
                <input
                    type="checkbox"
                    name="consent"
                    value="1"
                    @checked(old('consent'))
                    @error('consent') aria-invalid="true" aria-errormessage="consent-error" @enderror
                    class="mt-0.5 size-5 shrink-0 rounded border-stone-300 accent-red-700"
                    required
                >
                <span>I confirm these details are correct and agree to be contacted about my gift.</span>
            </label>
            @error('consent')
                <p id="consent-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-red-700 px-4 py-3.5 text-base font-semibold text-white shadow-lg shadow-red-700/30 transition hover:bg-red-800 focus-visible:ring-4 focus-visible:ring-red-600/30 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70"
        >
            Claim my gift
        </button>
    </form>

    <p class="mt-5 text-center text-xs text-stone-500">Your details are only used to deliver your gift.</p>
@endsection
