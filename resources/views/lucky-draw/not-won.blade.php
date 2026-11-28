{{-- Screen 7: cards without a gift. --}}
<x-layouts.lucky-draw title="Thanks for joining in" center>
    <x-lucky.panel class="animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-title text-[8.6cqw] leading-[1.05] font-bold text-candy">Thanks for<br>joining in!</h1>

        <p class="mt-[4cqw] text-[5cqw] leading-snug">
            This entry didn't win a voucher.<br>Wishing you a sweet Christmas!
        </p>

        <img
            src="{{ \App\Support\LuckyDrawImage::url('character-waving') }}"
            alt=""
            width="451"
            height="448"
            class="mt-[3cqw] h-auto w-[38cqw] origin-bottom animate-wave [animation-delay:0.8s]"
        >

        <x-lucky.button :href="$menuUrl" size="sm" primary class="mt-[4cqw] animate-rise-in [animation-delay:0.7s]">Back to Menu</x-lucky.button>
    </x-lucky.panel>
</x-layouts.lucky-draw>
