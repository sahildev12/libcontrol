<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;

class StudentCodeService
{
    public function preview(?Branch $branch = null): string
    {
        $settings = PlatformSetting::current();
        $prefix = $this->prefix($settings);
        $padding = $this->padding($settings);

        return sprintf('%s-%0'.$padding.'d', $prefix, 1);
    }

    public function generate(Branch $branch): string
    {
        $settings = PlatformSetting::current();
        $prefix = $this->prefix($settings);
        $padding = $this->padding($settings);

        $latest = Student::query()
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
        $prefix = PlatformSetting::current()->student_code_prefix;

        return is_string($prefix) && trim($prefix) !== '';
    }

    private function prefix(PlatformSetting $settings): string
    {
        $prefix = strtoupper(trim((string) $settings->student_code_prefix));

        return $prefix !== '' ? $prefix : 'LIB';
    }

    private function padding(PlatformSetting $settings): int
    {
        return max(1, min(6, (int) ($settings->student_code_padding ?: config('libcontrol.defaults.student_code_padding', 3))));
    }
}
