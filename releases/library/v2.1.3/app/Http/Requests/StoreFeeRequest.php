<?php

namespace App\Http\Requests;

class StoreFeeRequest extends UpdateFeeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            ...parent::rules(),
        ];
    }
}
