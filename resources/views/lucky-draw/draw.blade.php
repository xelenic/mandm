{{--
    Screen 2: "Enter Lucky Draw".
    After scanning a card the code is already known ($resultUrl). Arriving from
    the home page there is no code yet, so people type the one printed on their card.
--}}
{{-- Typing a code adds a form (and maybe an error), so use the plain background there to keep text clear of the artwork. --}}
<x-layouts.lucky-draw :background="$resultUrl ? 'draw' : 'panel'" title="Enter Lucky Draw">
    <p class="mt-[3cqw] animate-rise-in text-center text-[7.4cqw] leading-tight text-white drop-shadow-[0_2px_3px_rgba(0,0,0,0.45)] [animation-delay:0.3s]">
        Enter for a chance to win a gift voucher.
    </p>

    @if ($resultUrl)
        <x-lucky.button
            :href="$resultUrl"
            image="btn-enter-lucky-draw"
            label="Enter Lucky Draw"
            primary
            data-draw
            class="mt-[4cqw] w-[82cqw] animate-pop-in [animation-delay:0.5s]"
        />
    @else
        <form
            method="POST"
            action="{{ route('redeem.search') }}"
            class="mt-[4cqw] flex w-full animate-rise-in flex-col items-center [animation-delay:0.4s]"
            novalidate
            onsubmit="this.querySelector('button').disabled = true"
        >
            @csrf

            <label for="code" class="text-center text-[5.6cqw] leading-tight text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.5)]">
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
                    'mt-[2cqw] w-[78cqw] rounded-full border-[0.6cqw] border-gold bg-cream px-[5cqw] py-[2.6cqw] text-center font-heading text-[4.3cqw] tracking-wide text-cocoa uppercase shadow-inner transition duration-200 placeholder:text-cocoa/35 focus:scale-[1.02] focus:ring-[1cqw] focus:ring-white/60 focus:outline-none',
                    'animate-shake [animation-delay:0.5s]' => $errors->has('code'),
                ])
            >
            @error('code')
                <p id="code-error" role="alert" class="mt-[2cqw] w-[82cqw] animate-pop-in rounded-[2cqw] bg-cream/95 px-[3cqw] py-[1.5cqw] text-center text-[5cqw] text-berry">
                    {{ $message }}
                </p>
            @enderror

            <x-lucky.button image="btn-enter-lucky-draw" label="Enter Lucky Draw" primary class="mt-[4cqw] w-[82cqw]" />
        </form>
    @endif

    <p class="mt-[3cqw] animate-rise-in text-center text-[6cqw] text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.45)] [animation-delay:0.7s]">
        One entry per card.
    </p>

    @if ($resultUrl)
        {{-- "Drawing your entry…": a short moment of suspense before the result. --}}
        <div id="drawing" class="fixed inset-0 z-50 hidden flex-col items-center justify-center bg-[#5a0909]/85 backdrop-blur-sm" role="status" aria-live="polite">
            <div class="@container w-full max-w-[480px] text-center">
                <img src="{{ asset('images/lucky-draw/logo.webp') }}" alt="" class="lucky-drawing-logo mx-auto h-auto w-[60cqw] drop-shadow-[0_0_6cqw_rgba(255,214,102,0.6)]">
                <p class="lucky-drawing-text mt-[6cqw] font-heading text-[7cqw] text-white">Drawing your entry</p>
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
