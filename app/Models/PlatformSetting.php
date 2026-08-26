<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_code_prefix',
        'student_code_padding',
        'display_name',
        'logo_path',
        'favicon_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_code_padding' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'student_code_padding' => config('libspace.defaults.student_code_padding', 3),
        ]);
    }

    public function displayName(): string
    {
        return $this->display_name ?: config('app.name', 'LibSpace');
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo_path)) {
            return '/storage/'.ltrim(str_replace('\\', '/', $this->logo_path), '/');
        }

        $default = config('libspace.brand.default_logo_with_text') ?: config('libspace.brand.default_simple_logo');

        if ($default && file_exists(public_path($default))) {
            return asset($default);
        }

        return null;
    }

    public function faviconUrl(): ?string
    {
        if ($this->favicon_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->favicon_path)) {
            return '/storage/'.ltrim(str_replace('\\', '/', $this->favicon_path), '/');
        }

        $default = config('libspace.brand.default_favicon');

        if ($default && file_exists(public_path($default))) {
            return asset($default);
        }

        return null;
    }
}
