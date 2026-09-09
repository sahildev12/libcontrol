<?php

namespace App\Http\Requests;

use App\Models\Hall;
use App\Services\PlanLimitService;
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
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);

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
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('halls', 'name')
                    ->where(fn ($query) => $query->where('branch_id', $this->route('hall')?->branch_id ?? $this->input('branch_id')))
                    ->ignore($this->route('hall')?->id),
            ],
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
            $minimum = $hall->minimumSeatCapacity();

            if ($newCapacity < $minimum) {
                $validator->errors()->add(
                    'seat_capacity',
                    $minimum > 1
                        ? "Capacity cannot be reduced below {$minimum} while students are assigned."
                        : 'Seat capacity must be at least 1.',
                );

                return;
            }

            try {
                app(PlanLimitService::class)->assertSeatCapacity($newCapacity, $hall->id);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors()['seat_capacity'] ?? [] as $message) {
                    $validator->errors()->add('seat_capacity', $message);
                }
            }
        });
    }
}
