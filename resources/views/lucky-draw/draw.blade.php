{{--
    Screen 2: "Enter Lucky Draw".
    After scanning a card the code is already known ($resultUrl). Arriving from
    the home page there is no code yet, so people type the one printed on their card.
--}}
<x-layouts.lucky-draw title="Enter Lucky Draw">
    <p class="mt-[14cqw] animate-rise-in text-center text-[6.2cqw] leading-tight [animation-delay:0.3s]">
        Enter for a chance to win a gift voucher.
    </p>

    @if ($resultUrl)
        <x-lucky.button :href="$resultUrl" variant="green" size="lg" primary data-draw class="mt-[5cqw] w-[72cqw] animate-pop-in [animation-delay:0.5s]">
            Enter Lucky Draw
        </x-lucky.button>
    @else
        <form
            method="POST"
            action="{{ route('redeem.search') }}"
            class="mt-[4cqw] flex w-full animate-rise-in flex-col items-center [animation-delay:0.4s]"
            novalidate
            onsubmit="this.querySelector('button').disabled = true"
        >
            @csrf

            <label for="code" class="text-center text-[4.8cqw] leading-tight">
                Type the code printed at the bottom right of your card
            </label>
            <input
                id="code"
                name="code"
                type="text"
                value="{{ old('code') }}"
                placeholder="MM-XXXXXXXXXXXX"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                required
                @error('code') aria-invalid="true" aria-errormessage="code-error" @enderror
                @class([
                    'mt-[2.5cqw] w-[78cqw] rounded-full border-[0.6cqw] bg-white px-[5cqw] py-[2.6cqw] text-center font-title text-[5.2cqw] font-bold tracking-wide text-cocoa uppercase shadow-inner transition duration-200 placeholder:font-condensed placeholder:font-normal placeholder:text-stone-400 focus:scale-[1.02] focus:ring-[1cqw] focus:ring-mint/30 focus:outline-none',
                    'border-mint' => ! $errors->has('code'),
                    'animate-shake border-candy [animation-delay:0.5s]' => $errors->has('code'),
                ])
            >
            @error('code')
                <p id="code-error" role="alert" class="mt-[2cqw] w-[82cqw] animate-pop-in rounded-[3cqw] bg-white px-[3cqw] py-[1.5cqw] text-center text-[4.6cqw] text-candy">
                    {{ $message }}
                </p>
            @enderror

            <x-lucky.button variant="green" size="lg" primary class="mt-[4cqw] w-[72cqw]">Enter Lucky Draw</x-lucky.button>
        </form>
    @endif

    <p class="mt-[4cqw] animate-rise-in text-center text-[5.4cqw] [animation-delay:0.7s]">
        One entry per card.
    </p>

    <img
        src="{{ \App\Support\LuckyDrawImage::url('character-standing') }}"
        alt=""
        width="900"
        height="904"
        class="mt-[8cqw] h-auto w-[54cqw] animate-rise-in [animation-delay:0.85s]"
    >

    @if ($resultUrl)
        {{-- "Drawing your entry…": a short moment of suspense before the result. --}}
        <div id="drawing" class="fixed inset-0 z-50 hidden flex-col items-center justify-center bg-sunny/95 backdrop-blur-sm" role="status" aria-live="polite">
            <div class="@container w-full max-w-[480px] text-center">
                <img src="{{ \App\Support\LuckyDrawImage::url('logo') }}" alt="" class="lucky-drawing-logo mx-auto h-auto w-[60cqw] drop-shadow-[0_2cqw_3cqw_rgba(0,0,0,0.25)]">
                <p class="lucky-drawing-text mt-[6cqw] font-title text-[7.2cqw] font-bold text-candy">Drawing your entry</p>
            </div>
        </div>

        <script>
            (() => {
                const overlay = document.getElementById('drawing');
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                document.querySelector('[data-draw]').addEventListener('click', (event) => {
                    if (reduceMotion || event.metaKey || event.ctrlKey || event.shiftKey) return;
                    event.preventDefault();
                    const href = event.currentTarget.href; // only set while the click is being handled
                    overlay.classList.replace('hidden', 'flex');
                    setTimeout(() => { window.location.href = href; }, 1400);
                });

                // Coming back with the browser's Back button: don't keep showing the overlay.
                window.addEventListener('pageshow', () => overlay.classList.replace('flex', 'hidden'));
            })();
        </script>
    @endif
</x-layouts.lucky-draw>
