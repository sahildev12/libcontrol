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
        'plan_tier',
        'max_seats_override',
        'max_halls_override',
        'max_branches_override',
        'display_name',
        'logo_path',
        'simple_logo_path',
        'logo_with_text_path',
        'favicon_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_code_padding' => 'integer',
            'max_seats_override' => 'integer',
            'max_halls_override' => 'integer',
            'max_branches_override' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'student_code_padding' => config('libcontrol.defaults.student_code_padding', 3),
            'plan_tier' => config('libcontrol.defaults.plan_tier', 'starter'),
        ]);
    }

    public function planTier(): string
    {
        $tier = (string) ($this->plan_tier ?: config('libcontrol.defaults.plan_tier', 'starter'));

        return array_key_exists($tier, config('libcontrol.plans', [])) ? $tier : 'starter';
    }

    public function displayName(): string
    {
        return $this->display_name
            ?: config('libcontrol.install.product_name')
            ?: config('app.name')
            ?: config('libcontrol.product.name');
    }

    public function simpleLogoUrl(): ?string
    {
        return $this->assetUrl($this->simple_logo_path, config('libcontrol.brand.default_simple_logo'));
    }

    public function logoWithTextUrl(): ?string
    {
        return $this->assetUrl(
            $this->logo_with_text_path ?: $this->logo_path,
            config('libcontrol.brand.default_logo_with_text'),
        );
    }

    public function logoUrl(): ?string
    {
        return $this->logoWithTextUrl() ?: $this->simpleLogoUrl();
    }

    public function faviconUrl(): ?string
    {
        return $this->assetUrl($this->favicon_path, config('libcontrol.brand.default_favicon'));
    }

    private function assetUrl(?string $path, ?string $defaultPublicPath = null): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return '/storage/'.$path;
        }

        if ($defaultPublicPath && file_exists(public_path($defaultPublicPath))) {
            return asset($defaultPublicPath);
        }

        return null;
    }
}
