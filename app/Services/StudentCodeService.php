<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;

class StudentCodeService
{
    public function __construct(
        private BranchStudentCodePrefixService $branchPrefixes,
    ) {}

    public function preview(?Branch $branch = null): string
    {
        if ($branch) {
            return $this->branchPrefixes->sampleCode($branch);
        }

        $settings = PlatformSetting::current();
        $prefix = $this->platformPrefix($settings);
        $padding = $this->platformPadding($settings);

        return sprintf('%s-%0'.$padding.'d', $prefix, 1);
    }

    public function generate(Branch $branch): string
    {
        $prefix = $this->prefixForBranch($branch);
        $padding = $this->paddingForBranch($branch);

        $latest = Student::query()
            ->where('branch_id', $branch->id)
            ->where('student_code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('student_code');

        $next = 1;

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%0'.$padding.'d', $prefix, $next);
    }

    public function prefixIsConfigured(): bool
    {
        if (Branch::query()->whereNotNull('student_code_prefix')->where('student_code_prefix', '!=', '')->exists()) {
            return true;
        }

        $prefix = PlatformSetting::current()->student_code_prefix;

        return is_string($prefix) && trim($prefix) !== '';
    }

    private function prefixForBranch(Branch $branch): string
    {
        $prefix = strtoupper(trim((string) $branch->student_code_prefix));
        if ($prefix !== '') {
            return $prefix;
        }

        return $this->branchPrefixes->ensureForBranch($branch);
    }

    private function paddingForBranch(Branch $branch): int
    {
        return $this->branchPrefixes->defaultPadding($branch);
    }

    private function platformPrefix(PlatformSetting $settings): string
    {
        $prefix = strtoupper(trim((string) $settings->student_code_prefix));

        return $prefix !== '' ? $prefix : 'LIB';
    }

    private function platformPadding(PlatformSetting $settings): int
    {
        return max(1, min(6, (int) ($settings->student_code_padding ?: config('libcontrol.defaults.student_code_padding', 3))));
    }
}
