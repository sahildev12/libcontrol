<?php

namespace App\Http\Requests;

use App\Models\Hall;
use App\Models\Seat;
use App\Services\HallSeatGenerator;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHallRequest extends FormRequest
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
        $branchId = $this->resolvedBranchId();
        $branchRule = $this->user()?->isPlatformAdmin()
            ? Rule::exists('branches', 'id')
            : Rule::exists('branches', 'id')->where(fn ($query) => $query->where('id', $branchId));

        return [
            'branch_id' => ['required', 'integer', $branchRule],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('halls', 'name')->where(fn ($query) => $query->where('branch_id', $branchId)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'seat_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'continue_seat_numbering' => ['sometimes', 'boolean'],
            'continue_from_hall_id' => [
                'nullable',
                'integer',
                Rule::exists('halls', 'id')->where(fn ($query) => $query->where('branch_id', $branchId)),
                Rule::requiredIf(fn () => $this->boolean('continue_seat_numbering')),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $planLimits = app(PlanLimitService::class);

            try {
                $planLimits->assertCanAddHall();
                $planLimits->assertSeatCapacity((int) $this->input('seat_capacity'));
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            if (! $this->boolean('continue_seat_numbering')) {
                return;
            }

            $branchId = (int) $this->input('branch_id');
            $sourceHallId = (int) $this->input('continue_from_hall_id');
            $sourceHall = Hall::query()
                ->where('branch_id', $branchId)
                ->find($sourceHallId);

            if (! $sourceHall) {
                $validator->errors()->add('continue_from_hall_id', 'Select a hall to continue seat numbering from.');

                return;
            }

            $generator = app(HallSeatGenerator::class);
            $start = $generator->nextSeatNumberAfterHall($sourceHallId);
            $end = $start + (int) $this->input('seat_capacity') - 1;

            $conflict = Seat::query()
                ->whereHas('hall', fn ($query) => $query->where('branch_id', $branchId))
                ->whereRaw('CAST(seat_number AS UNSIGNED) BETWEEN ? AND ?', [$start, $end])
                ->exists();

            if ($conflict) {
                $validator->errors()->add(
                    'continue_from_hall_id',
                    "Seat numbers {$start}–{$end} overlap with existing seats in this branch. Choose a different source hall or turn off continue numbering."
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'continue_seat_numbering' => $this->boolean('continue_seat_numbering'),
        ]);

        if (! $this->filled('branch_id')) {
            $branchId = $this->resolvedBranchId();

            if ($branchId) {
                $this->merge(['branch_id' => $branchId]);
            }
        }
    }

    private function resolvedBranchId(): ?int
    {
        $user = $this->user();

        if (! $user) {
            return null;
        }

        if ($user->isPlatformAdmin()) {
            if ($this->filled('branch_id')) {
                return (int) $this->input('branch_id');
            }

            $sessionBranchId = session('active_branch_id');

            if ($sessionBranchId && $sessionBranchId !== 'all') {
                return (int) $sessionBranchId;
            }

            return null;
        }

        return $user->branch_id ? (int) $user->branch_id : null;
    }
}
