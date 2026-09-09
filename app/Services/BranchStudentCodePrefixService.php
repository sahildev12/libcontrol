<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use Illuminate\Support\Str;

class BranchStudentCodePrefixService
{
    public function generateForBranchName(string $name): string
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name) ?? '');

        if (strlen($letters) >= 3) {
            $candidate = substr($letters, 0, 3);
        } elseif ($letters !== '') {
            $candidate = str_pad($letters, 3, 'X');
        } else {
            $candidate = 'BRN';
        }

        return $this->ensureUnique($candidate);
    }

    public function ensureForBranch(Branch $branch): string
    {
        $existing = strtoupper(trim((string) $branch->student_code_prefix));
        if ($existing !== '') {
            return $existing;
        }

        $prefix = $this->generateForBranchName((string) $branch->name);
        $padding = $this->defaultPadding($branch);

        $branch->update([
            'student_code_prefix' => $prefix,
            'student_code_padding' => $padding,
        ]);

        return $prefix;
    }

    public function defaultPadding(?Branch $branch = null): int
    {
        if ($branch && $branch->student_code_padding) {
            return max(1, min(6, (int) $branch->student_code_padding));
        }

        return max(1, min(6, (int) (PlatformSetting::current()->student_code_padding
            ?: config('libcontrol.defaults.student_code_padding', 3))));
    }

    public function sampleCode(Branch $branch): string
    {
        $prefix = strtoupper(trim((string) $branch->student_code_prefix));
        if ($prefix === '') {
            $prefix = $this->ensureForBranch($branch);
        }

        $padding = $this->defaultPadding($branch->fresh());

        return sprintf('%s-%0'.$padding.'d', $prefix, 1);
    }

    /**
     * @return list<array{branch_id: int, branch_name: string, prefix: string, padding: int, sample_student_code: string}>
     */
    public function stylesForLibrary(): array
    {
        return Branch::query()
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) {
                $prefix = strtoupper(trim((string) $branch->student_code_prefix));
                if ($prefix === '') {
                    $prefix = $this->generateForBranchName((string) $branch->name);
                }

                $padding = $this->defaultPadding($branch);

                return [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'prefix' => $prefix,
                    'padding' => $padding,
                    'sample_student_code' => sprintf('%s-%0'.$padding.'d', $prefix, 1),
                ];
            })
            ->values()
            ->all();
    }

    private function ensureUnique(string $candidate): string
    {
        $candidate = strtoupper(substr($candidate, 0, 20));
        $base = $candidate;
        $suffix = 1;

        while (
            Branch::query()
                ->where('student_code_prefix', $candidate)
                ->exists()
        ) {
            $candidate = strtoupper(substr($base, 0, max(1, 20 - strlen((string) $suffix))).$suffix);
            $suffix++;
        }

        return $candidate;
    }
}
