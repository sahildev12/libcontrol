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

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('id', $branchId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'seat_capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
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

        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        if ($user->isPlatformAdmin() && session('active_branch_id')) {
            return (int) session('active_branch_id');
        }

        return null;
    }
}
