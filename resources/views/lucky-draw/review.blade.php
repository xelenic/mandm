{{-- Screen 6: the winner checks their details before submitting. --}}
<x-layouts.lucky-draw title="Review your details">
    <x-lucky.panel class="mt-[3cqw] animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-title font-bold text-[5.5cqw] leading-tight">Review Your Details</h1>
        <x-lucky.divider />
        <p class="text-[4.55cqw] leading-tight text-berry">Please check your details before submitting.</p>

        <dl class="mt-[5cqw] w-full space-y-[2.6cqw] text-left font-heading text-[5cqw] text-[#8b1a12]">
            @foreach (['First Name' => $draft['first_name'], 'Last Name' => $draft['last_name'], 'Mobile Number' => $phone] as $label => $value)
                <div
                    class="flex animate-rise-in gap-[1.5cqw] rounded-[2cqw] border-[0.35cqw] border-sand bg-white/40 px-[2.5cqw] py-[1.5cqw] transition duration-200 hover:border-gold hover:bg-white/70"
                    style="animation-delay: {{ 0.45 + $loop->index * 0.12 }}s"
                >
                    <dt class="shrink-0">{{ $label }}&nbsp;:</dt>
                    <dd class="min-w-0 break-words">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <form
            method="POST"
            action="{{ route('redeem.store', $code) }}"
            class="mt-[9cqw] flex w-full items-center justify-center gap-[2.5cqw]"
            onsubmit="this.querySelector('button[type=submit]').disabled = true"
        >
            @csrf
            <x-lucky.button :href="route('lucky.details', $code)" image="btn-back" label="Back" class="w-[24cqw]" />
            <x-lucky.button image="btn-confirm-submit" label="Confirm & Submit" primary class="w-[44cqw]" />
        </form>
    </x-lucky.panel>
</x-layouts.lucky-draw>
