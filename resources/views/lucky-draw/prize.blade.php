{{--
    Screens 3 and 4: "Congratulations!". The whole card (voucher, amount and
    text) is one image from the design; the button sits in its empty bottom area.
--}}
<x-layouts.lucky-draw :title="'You won '.$gift->prizeTitle()">
    <div class="relative mt-[2cqw] w-[82cqw] animate-pop-in [animation-delay:0.2s]">
        {{-- Warm golden glow behind the card. --}}
        <div class="absolute -inset-[4cqw] -z-10 animate-pulse rounded-[8cqw] bg-[radial-gradient(closest-side,rgba(255,214,102,0.55),transparent)]" aria-hidden="true"></div>

        <img
            src="{{ \App\Support\LuckyDrawImage::url($gift->prizeImage()) }}"
            alt="Congratulations! You won a {{ $gift->prizeTitle() }}. Enter your contact details to claim your prize."
            width="910"
            height="1087"
            class="h-auto w-full drop-shadow-[0_10px_20px_rgba(0,0,0,0.45)]"
        >

        <div class="absolute bottom-[3.6%] left-1/2 w-[72%] -translate-x-1/2">
            <x-lucky.button
                :href="route('lucky.details', $code)"
                image="btn-enter-contact-details"
                label="Enter Contact Details"
                primary
                class="w-full animate-rise-in [animation-delay:0.9s]"
            />
        </div>
    </div>

    <x-lucky.confetti />
</x-layouts.lucky-draw>
