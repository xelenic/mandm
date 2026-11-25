{{-- Screen 1: the menu people land on after scanning their card. --}}
<x-layouts.lucky-draw background="menu">
    <div class="mt-[1cqw] animate-rise-in [animation-delay:0.25s]">
        <img
            src="{{ \App\Support\LuckyDrawImage::url('crew') }}"
            alt=""
            width="1018"
            height="255"
            class="h-auto w-[90cqw] max-w-none animate-float [animation-delay:1s]"
        >
    </div>

    <nav class="mt-[3cqw] flex w-full flex-col items-center gap-[2.6cqw]" aria-label="Lucky draw menu">
        <x-lucky.button :href="$claimUrl" image="btn-claim-your-prize" label="Claim Your Prize" primary class="w-[88cqw] animate-rise-in [animation-delay:0.45s]" />
        <x-lucky.button :href="route('lucky.campaign')" image="btn-campaign-details" label="Campaign Details" class="w-[78cqw] animate-rise-in [animation-delay:0.55s]" />
        <x-lucky.button :href="route('lucky.terms')" image="btn-terms-conditions" label="Terms & Conditions" class="w-[78cqw] animate-rise-in [animation-delay:0.65s]" />
        <x-lucky.button :href="route('lucky.winners')" image="btn-winners-gallery" label="Winners Gallery" class="w-[78cqw] animate-rise-in [animation-delay:0.75s]" />
    </nav>
</x-layouts.lucky-draw>
