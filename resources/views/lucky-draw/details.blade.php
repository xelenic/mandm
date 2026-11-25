{{-- Screen 5: the winner enters their contact details. --}}
<x-layouts.lucky-draw title="Your details">
    <x-lucky.panel class="mt-[3cqw] animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-title font-bold text-[5.5cqw] leading-tight">Review Your Details</h1>
        <x-lucky.divider />
        <p class="text-[4.55cqw] leading-tight text-berry">Please check your details before submitting.</p>

        <form
            method="POST"
            action="{{ route('lucky.details.save', $code) }}"
            class="mt-[4cqw] w-full text-left"
            novalidate
            onsubmit="this.querySelector('button[type=submit]').disabled = true"
        >
            @csrf

            <div class="space-y-[2.6cqw]">
                @foreach ([
                    ['first_name', 'First Name', 'text', 'given-name', null],
                    ['last_name', 'Last Name', 'text', 'family-name', null],
                    ['phone', 'Mobile Number', 'tel', 'tel', '07X XXX XXXX'],
                ] as [$field, $label, $type, $autocomplete, $placeholder])
                    <div>
                        <div class="grid grid-cols-[34%_1fr] items-center gap-[2cqw]">
                            <label for="{{ $field }}" class="font-heading text-[5.2cqw] leading-tight text-[#8b1a12]">{{ $label }}</label>
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
                                    'w-full rounded-[2cqw] border-[0.35cqw] bg-white px-[2.5cqw] py-[1.2cqw] text-[5.4cqw] text-stone-900 shadow-inner transition duration-200 placeholder:text-stone-400 hover:border-gold focus:-translate-y-[0.3cqw] focus:shadow-[0_1cqw_2cqw_rgba(200,144,43,0.25)] focus:ring-[0.8cqw] focus:outline-none',
                                    'border-sand focus:border-gold focus:ring-gold/30' => ! $errors->has($field),
                                    'animate-shake border-berry focus:ring-berry/25 [animation-delay:0.6s]' => $errors->has($field),
                                ])
                            >
                        </div>
                        @error($field)
                            <p id="{{ $field }}-error" class="mt-[1cqw] text-right text-[4cqw] leading-tight text-berry">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-[5cqw]">
                <label class="flex items-start gap-[2.5cqw] text-[4.1cqw] leading-snug text-[#8b1a12]">
                    <input
                        type="checkbox"
                        name="terms"
                        value="1"
                        @checked(old('terms', $draft !== null))
                        @error('terms') aria-invalid="true" aria-errormessage="terms-error" @enderror
                        class="mt-[0.4cqw] size-[5.4cqw] shrink-0 cursor-pointer appearance-none rounded-[1.4cqw] border-[0.45cqw] border-[#c89a5a] bg-white transition duration-200 hover:scale-110 active:scale-90 checked:scale-110 checked:border-[#8b1a12] checked:bg-[#8b1a12] checked:bg-[url('data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20viewBox=%220%200%2016%2016%22%20fill=%22white%22%3E%3Cpath%20d=%22M6.2%2011.3%203.1%208.2l1.1-1.1%202%202%205.6-5.6%201.1%201.1z%22/%3E%3C/svg%3E')] bg-center bg-no-repeat focus-visible:ring-[0.8cqw] focus-visible:ring-gold/40 focus-visible:outline-none"
                    >
                    <span>
                        I agree to the campaign
                        <a href="{{ route('lucky.terms') }}" target="_blank" rel="noopener" class="underline decoration-[#c89a5a] underline-offset-2">Terms &amp; Conditions</a>.
                    </span>
                </label>
                @error('terms')
                    <p id="terms-error" class="mt-[1cqw] text-[4cqw] text-berry">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-[6cqw] flex justify-center">
                <x-lucky.button image="btn-review-details" label="Review Details" primary class="w-[60cqw]" />
            </div>
        </form>
    </x-lucky.panel>
</x-layouts.lucky-draw>
