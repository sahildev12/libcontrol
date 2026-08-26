<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
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
