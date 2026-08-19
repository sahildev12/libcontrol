<?php

namespace App\Http\Requests;

use App\Models\Hall;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->isPlatformAdmin()) {
            return;
        }

        $this->merge([
            'branch_id' => $this->user()?->branch_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'seat_capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Hall|null $hall */
            $hall = $this->route('hall');

            if (! $hall instanceof Hall) {
                return;
            }

            $newCapacity = (int) $this->input('seat_capacity');

            if ($hall->hasAssignedStudents() && $newCapacity < $hall->seat_capacity) {
                $validator->errors()->add(
                    'seat_capacity',
                    'Capacity cannot be reduced while students are assigned to this hall.',
                );
            }
        });
    }
}
