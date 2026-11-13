<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    /** Sri Lankan mobile: 07XXXXXXXX, 7XXXXXXXX, +947XXXXXXXX or 00947XXXXXXXX. */
    private const PHONE_PATTERN = '/^(?:\+94|0094|94|0)?(7\d{8})$/';

    /** Sri Lankan NIC: old format (9 digits + V/X) or new format (12 digits). */
    private const NIC_PATTERN = '/^(?:\d{9}[VX]|\d{12})$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => trim((string) $this->input('email')),
            'phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('phone')),
            'nic' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('nic'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN],
            'email' => ['required', 'email', 'max:255'],
            'nic' => ['required', 'string', 'regex:'.self::NIC_PATTERN],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid Sri Lankan mobile number, e.g. 077 123 4567.',
            'nic.regex' => 'Please enter a valid NIC number, e.g. 199012345678 or 901234567V.',
            'consent.accepted' => 'Please confirm your details and agree to be contacted.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'phone' => 'mobile number',
            'nic' => 'NIC number',
        ];
    }

    /**
     * The validated details to store, with the phone number normalised to +947XXXXXXXX.
     *
     * @return array{name: string, phone: string, email: string, nic: string}
     */
    public function claimDetails(): array
    {
        preg_match(self::PHONE_PATTERN, $this->validated('phone'), $matches);

        return [
            'name' => $this->validated('name'),
            'phone' => '+94'.$matches[1],
            'email' => $this->validated('email'),
            'nic' => $this->validated('nic'),
        ];
    }
}
