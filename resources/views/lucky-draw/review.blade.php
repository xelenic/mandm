{{-- Screen 6: "Almost there!" — the winner checks their details before verifying. --}}
<x-layouts.lucky-draw title="Almost there">
    <img src="{{ \App\Support\LuckyDrawImage::url('crew') }}" alt="" width="1018" height="255" class="mt-[6cqw] h-auto w-[84cqw] max-w-none animate-rise-in [animation-delay:0.2s]">

    <x-lucky.panel variant="green" class="mt-[3cqw] w-[82cqw] animate-pop-in [animation-delay:0.3s]">
        <h1 class="font-title text-[6.6cqw] leading-tight font-bold">Almost there!</h1>
        <p class="mt-[1.5cqw] text-[4.8cqw] leading-tight">Please check your details before verifying.</p>
        <p class="mt-[2.5cqw] text-[3.7cqw] leading-snug">
            Make sure your info is correct so we can contact you and send your gift to the right place!
        </p>

        <dl class="mt-[5cqw] grid w-full grid-cols-[30%_4cqw_1fr] gap-y-[2.4cqw] text-left text-[4.4cqw] leading-tight">
            @foreach (['First Name' => $draft['first_name'], 'Last Name' => $draft['last_name'], 'Mobile Number' => $phone] as $label => $value)
                <dt class="animate-rise-in" style="animation-delay: {{ 0.45 + $loop->index * 0.12 }}s">*{{ $label }}</dt>
                <dd class="animate-rise-in text-center" style="animation-delay: {{ 0.45 + $loop->index * 0.12 }}s" aria-hidden="true">:</dd>
                <dd class="min-w-0 animate-rise-in break-words" style="animation-delay: {{ 0.45 + $loop->index * 0.12 }}s">{{ $value }}</dd>
            @endforeach
        </dl>

        <form
            method="POST"
            action="{{ route('redeem.store', $code) }}"
            class="mt-[6cqw] flex w-full items-center justify-center gap-[4cqw]"
            onsubmit="this.querySelector('button[type=submit]').disabled = true"
        >
            @csrf
            <x-lucky.button :href="route('lucky.details', $code)" variant="white" size="sm" class="w-[32cqw]">Back</x-lucky.button>
            <x-lucky.button size="sm" primary class="w-[32cqw]">Verify</x-lucky.button>
        </form>
    </x-lucky.panel>
</x-layouts.lucky-draw>
