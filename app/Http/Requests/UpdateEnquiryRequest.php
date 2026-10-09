<?php

namespace App\Http\Requests;

use App\Models\Enquiry;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['required', Rule::in(array_keys(Enquiry::STATUSES))],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
