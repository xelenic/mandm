{{-- Screen 8: the winner's details were saved. --}}
<x-layouts.lucky-draw title="Details received">
    <x-lucky.panel class="mt-[2cqw] animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-title font-bold text-[11.6cqw] leading-[1.05]">Details<br>Received!</h1>

        <p class="mt-[4cqw] text-[8.2cqw] leading-[1.25] text-berry">
            Our team will contact you to verify your win and arrange delivery of your gift voucher.
        </p>

        <x-lucky.button :href="$menuUrl" image="btn-ok" label="OK" primary class="mt-[6cqw] w-[45cqw] animate-rise-in [animation-delay:0.7s]" />
    </x-lucky.panel>

    <x-lucky.confetti />
</x-layouts.lucky-draw>
