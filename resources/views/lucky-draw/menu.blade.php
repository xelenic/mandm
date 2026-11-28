{{-- Screen 1: the menu people land on after scanning their card. --}}
<x-layouts.lucky-draw gifts>
    <div class="mt-[9cqw] animate-rise-in [animation-delay:0.25s]">
        <img
            src="{{ \App\Support\LuckyDrawImage::url('crew') }}"
            alt=""
            width="1018"
            height="255"
            class="h-auto w-[84cqw] max-w-none animate-float [animation-delay:1s]"
        >
    </div>

    <nav class="mt-[7cqw] flex w-full flex-col items-center gap-[4.4cqw]" aria-label="Lucky draw menu">
        <x-lucky.button :href="$claimUrl" variant="green" size="lg" primary class="mb-[4cqw] w-[82cqw] animate-rise-in [animation-delay:0.45s]">
            Claim your Prize
        </x-lucky.button>
        <x-lucky.button :href="route('lucky.campaign')" data-how-to-join class="w-[67cqw] animate-rise-in [animation-delay:0.55s]">
            Campaign Details
        </x-lucky.button>
        <x-lucky.button :href="route('lucky.terms')" class="w-[67cqw] animate-rise-in [animation-delay:0.65s]">
            Terms &amp; Conditions
        </x-lucky.button>
        <x-lucky.button :href="route('lucky.winners')" class="w-[67cqw] animate-rise-in [animation-delay:0.75s]">
            Winners Gallery
        </x-lucky.button>
    </nav>

    <x-lucky.how-to-join :open="request()->boolean('how-to-join')" />
</x-layouts.lucky-draw>
