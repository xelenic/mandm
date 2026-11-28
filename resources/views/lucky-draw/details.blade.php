{{-- Screen 5: the winner enters their contact details. --}}
<x-layouts.lucky-draw title="Personal details">
    <img src="{{ \App\Support\LuckyDrawImage::url('crew') }}" alt="" width="1018" height="255" class="mt-[6cqw] h-auto w-[84cqw] max-w-none animate-rise-in [animation-delay:0.2s]">

    <x-lucky.panel variant="green" class="mt-[3cqw] w-[82cqw] animate-pop-in [animation-delay:0.3s]">
        <h1 class="font-title text-[6.6cqw] leading-tight font-bold">Personal Details</h1>

        <form
            method="POST"
            action="{{ route('lucky.details.save', $code) }}"
            class="mt-[5cqw] w-full text-left"
            novalidate
            onsubmit="this.querySelector('button[type=submit]').disabled = true"
        >
            @csrf

            <div class="space-y-[3.5cqw]">
                @foreach ([
                    ['first_name', 'First Name', 'text', 'given-name', null],
                    ['last_name', 'Last Name', 'text', 'family-name', null],
                    ['phone', 'Mobile Number', 'tel', 'tel', '07X XXX XXXX'],
                ] as [$field, $label, $type, $autocomplete, $placeholder])
                    <div>
                        <div class="grid grid-cols-[30%_1fr] items-center gap-[2cqw]">
                            <label for="{{ $field }}" class="text-[4.4cqw] leading-tight">*{{ $label }}</label>
                            <input
                                id="{{ $field }}"
                                name="{{ $field }}"
                                type="{{ $type }}"
                                value="{{ old($field, $draft[$field] ?? '') }}"
                                autocomplete="{{ $autocomplete }}"
                                @if ($type === 'tel') inputmode="tel" @endif
                                @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                                required
                                @error($field) aria-invalid="true" aria-errormessage="{{ $field }}-error" @enderror
                                @class([
                                    'w-full rounded-full border-[0.5cqw] bg-white px-[4cqw] py-[1.8cqw] text-[4.8cqw] text-stone-900 transition duration-200 placeholder:text-stone-400 focus:-translate-y-[0.3cqw] focus:ring-[1cqw] focus:ring-white/40 focus:outline-none',
                                    'border-white' => ! $errors->has($field),
                                    'animate-shake border-sunny [animation-delay:0.6s]' => $errors->has($field),
                                ])
                            >
                        </div>
                        @error($field)
                            <p id="{{ $field }}-error" class="mt-[1cqw] text-right text-[4cqw] leading-tight text-sunny">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-[6cqw]">
                <label class="flex items-start justify-center gap-[2cqw] text-[3.9cqw] leading-snug">
                    <span aria-hidden="true">*</span>
                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        @checked(old('terms', $draft !== null))
                        @error('terms') aria-invalid="true" aria-errormessage="terms-error" @enderror
                        class="mt-[0.4cqw] size-[4.8cqw] shrink-0 cursor-pointer appearance-none rounded-[1.2cqw] border-[0.45cqw] border-white bg-transparent transition duration-200 hover:scale-110 active:scale-90 checked:scale-110 checked:bg-white checked:bg-[url('data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20viewBox=%220%200%2016%2016%22%20fill=%22%2300a831%22%3E%3Cpath%20d=%22M6.2%2011.3%203.1%208.2l1.1-1.1%202%202%205.6-5.6%201.1%201.1z%22/%3E%3C/svg%3E')] bg-center bg-no-repeat focus-visible:ring-[0.8cqw] focus-visible:ring-white/50 focus-visible:outline-none"
                    >
                    <span>
                        I agree to the campaign
                        <a href="{{ route('lucky.terms') }}" target="_blank" rel="noopener" class="underline underline-offset-2 hover:text-sunny">Terms &amp; Conditions.</a>
                    </span>
                </label>
                @error('terms')
                    <p id="terms-error" class="mt-[1cqw] text-center text-[4cqw] text-sunny">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-[6cqw] flex justify-center">
                <x-lucky.button size="sm" primary class="w-[46cqw]">Review Details</x-lucky.button>
            </div>
        </form>
    </x-lucky.panel>
</x-layouts.lucky-draw>
