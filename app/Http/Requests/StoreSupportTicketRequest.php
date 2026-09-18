<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::in(['general', 'billing', 'technical', 'feature'])],
            'priority' => ['nullable', Rule::in(['normal', 'high'])],
            'reporter_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
