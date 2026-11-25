<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Screen 5 of the lucky draw: the winner's contact details.
 */
class ClaimDetailsRequest extends FormRequest
{
    /** Sri Lankan mobile: 07XXXXXXXX, 7XXXXXXXX, +947XXXXXXXX or 00947XXXXXXXX. */
    private const PHONE_PATTERN = '/^(?:\+94|0094|94|0)?(7\d{8})$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'phone' => preg_replace('/[\s\-().]/', '', (string) $this->input('phone')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid mobile number, e.g. 077 123 4567.',
            'terms.accepted' => 'Please agree to the campaign Terms & Conditions.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone' => 'mobile number',
        ];
    }

    /**
     * The validated details, with the mobile number normalised to +947XXXXXXXX.
     *
     * @return array{first_name: string, last_name: string, phone: string}
     */
    public function details(): array
    {
        preg_match(self::PHONE_PATTERN, $this->validated('phone'), $matches);

        return [
            'first_name' => $this->validated('first_name'),
            'last_name' => $this->validated('last_name'),
            'phone' => '+94'.$matches[1],
        ];
    }

    /**
     * A stored +947XXXXXXXX number as people write it: 077 123 4567.
     */
    public static function localPhone(string $phone): string
    {
        return preg_replace('/^\+94(\d{2})(\d{3})(\d{4})$/', '0$1 $2 $3', $phone) ?? $phone;
    }
}
