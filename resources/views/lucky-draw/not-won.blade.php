{{-- Screen 7: cards without a gift. --}}
<x-layouts.lucky-draw title="Thanks for joining in">
    <x-lucky.panel class="mt-[2cqw] animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-heading text-[8.4cqw] leading-[1.15]">Thanks for<br>joining in!</h1>

        <img
            src="{{ asset('images/lucky-draw/character-waving.webp') }}"
            alt=""
            width="451"
            height="448"
            class="mt-[3cqw] h-auto w-[40cqw] origin-bottom animate-wave [animation-delay:0.8s]"
        >

        <p class="mt-[2cqw] text-[6cqw] leading-snug text-berry">
            This entry didn't win a voucher.<br>Wishing you a sweet Christmas!
        </p>

        <x-lucky.button :href="$menuUrl" image="btn-back-to-menu" label="Back to Menu" primary class="mt-[5cqw] w-[60cqw] animate-rise-in [animation-delay:0.7s]" />
    </x-lucky.panel>
</x-layouts.lucky-draw>
