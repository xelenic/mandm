{{-- Screens 3 and 4: "Congrats! You won a …". --}}
<x-layouts.lucky-draw :title="'You won '.$gift->prizeTitle()" center>
    <x-lucky.panel class="animate-pop-in [animation-delay:0.2s]">
        <img
            src="{{ \App\Support\LuckyDrawImage::url($gift->prizeImage()) }}"
            alt="{{ $gift->prizeTitle() }}"
            class="h-auto w-[54cqw] rounded-[2.4cqw] shadow-[0_1.5cqw_3cqw_rgba(0,0,0,0.18)]"
        >

        <h1 class="mt-[7cqw] font-title text-[7.2cqw] leading-none font-bold text-candy">Congrats!</h1>
        <p class="mt-[1.5cqw] text-[5.4cqw]">You won a</p>
        <p class="mt-[2cqw] font-title text-[10.5cqw] leading-none font-bold text-candy uppercase">{{ $gift->prizeAmount() }}</p>
        <p class="mt-[2cqw] font-title text-[4cqw] font-bold whitespace-nowrap text-candy">{{ $gift->prizeName() }}</p>

        <x-lucky.button :href="route('lucky.details', $code)" size="sm" primary class="mt-[7cqw] animate-rise-in [animation-delay:0.9s]">
            Enter Contact Details
        </x-lucky.button>
    </x-lucky.panel>

    <x-lucky.confetti />
</x-layouts.lucky-draw>
