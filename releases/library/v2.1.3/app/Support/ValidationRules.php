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
}
