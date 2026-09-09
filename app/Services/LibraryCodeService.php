<?php

namespace App\Services;

use App\Models\PlatformSetting;

class LibraryCodeService
{
    public function ensure(PlatformSetting $settings): string
    {
        $existing = trim((string) $settings->library_code);
        if ($existing !== '') {
            return $existing;
        }

        $code = $this->generateUnique();
        $settings->update(['library_code' => $code]);

        return $code;
    }

    public function generateUnique(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (
            PlatformSetting::query()->where('library_code', $code)->exists()
        );

        return $code;
    }
}
