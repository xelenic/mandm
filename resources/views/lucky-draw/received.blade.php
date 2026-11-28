{{-- Screen 8: the winner's details were saved. --}}
<x-layouts.lucky-draw title="Details received" center>
    <x-lucky.panel class="animate-pop-in py-[9cqw] [animation-delay:0.2s]">
        <h1 class="font-title text-[8.6cqw] leading-[1.05] font-bold text-candy">Details<br>Received!</h1>

        <p class="mt-[6cqw] text-[6cqw] leading-[1.25]">
            Our team will contact you to verify your win and arrange delivery of your gift voucher.
        </p>

        <x-lucky.button :href="$menuUrl" size="sm" primary class="mt-[7cqw] w-[32cqw] animate-rise-in [animation-delay:0.7s]">OK</x-lucky.button>
    </x-lucky.panel>

    <x-lucky.confetti />
</x-layouts.lucky-draw>
