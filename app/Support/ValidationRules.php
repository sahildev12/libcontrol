<?php

namespace App\Support;

class ValidationRules
{
    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function phoneRequired(): array
    {
        return ['required', 'string', 'regex:/^[6-9]\d{9}$/'];
    }

    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function phoneOptional(): array
    {
        return ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'];
    }

    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function emailRequired(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }

    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function emailOptional(): array
    {
        return ['nullable', 'string', 'email', 'max:255'];
    }

    /**
     * Required DOB: past date and at least 5 years old (library membership floor).
     *
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function dateOfBirthRequired(): array
    {
        return [
            'required',
            'date',
            'before:today',
            'before_or_equal:'.now()->subYears(5)->toDateString(),
        ];
    }
}
