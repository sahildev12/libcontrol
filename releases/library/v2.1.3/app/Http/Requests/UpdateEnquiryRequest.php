<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ValidationRules::phoneRequired(),
            'email' => ValidationRules::emailOptional(),
            'message' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:new,contacted,converted,closed'],
        ];
    }
}
