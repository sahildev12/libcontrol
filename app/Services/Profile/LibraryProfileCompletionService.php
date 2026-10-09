<?php

namespace App\Services\Profile;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class LibraryProfileCompletionService
{
    /**
     * @return array{
     *     score: int,
     *     max: int,
     *     pct: int,
     *     complete: bool,
     *     items: list<array{key: string, label: string, status: string, detail: string, href: string|null}>
     * }
     */
    public function scoreForLibrary(?User $user = null): array
    {
        $settings = PlatformSetting::current();
        $branchIds = $this->branchIdsFor($user);

        $items = [
            $this->basicInfo($settings, $branchIds),
            $this->amenities($settings),
            $this->gallery($settings),
            $this->socials($settings),
        ];

        $done = collect($items)->where('status', 'ok')->count();
        $total = count($items);
        $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        return [
            'score' => $pct,
            'max' => 100,
            'pct' => $pct,
            'complete' => $pct >= 100,
            'items' => $items,
        ];
    }

    /**
     * @return list<int>
     */
    private function branchIdsFor(?User $user): array
    {
        if ($user?->isBranchStaff() && $user->branch_id) {
            return [(int) $user->branch_id];
        }

        return Branch::query()->orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>  $branchIds
     * @return array{key: string, label: string, status: string, detail: string, href: string|null}
     */
    private function basicInfo(PlatformSetting $settings, array $branchIds): array
    {
        $hasName = filled($settings->display_name);
        $hasLogo = filled($settings->logo_path)
            || filled($settings->simple_logo_path)
            || filled($settings->website_logo_path);

        $branchOk = false;
        if ($branchIds !== [] && Schema::hasTable('branches')) {
            $branchOk = Branch::query()
                ->whereIn('id', $branchIds)
                ->where(function ($q) {
                    $q->whereNotNull('phone')->where('phone', '!=', '')
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('address')->where('address', '!=', '');
                        });
                })
                ->exists();
        }

        $ok = $hasName && $hasLogo && $branchOk;
        $missing = [];
        if (! $hasName) {
            $missing[] = 'library name';
        }
        if (! $hasLogo) {
            $missing[] = 'logo';
        }
        if (! $branchOk) {
            $missing[] = 'address or contact';
        }

        return $this->item(
            'basic_info',
            'Basic Information',
            $ok ? 'ok' : 'missing',
            $ok
                ? 'Logo, Library Name, Address, Contact'
                : 'Add '.implode(', ', $missing).'.',
            $this->route('settings.index', ['tab' => 'website'])
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, href: string|null}
     */
    private function amenities(PlatformSetting $settings): array
    {
        $amenities = collect($settings->website_amenities ?? [])->filter()->values();
        $ok = $amenities->isNotEmpty();

        return $this->item(
            'amenities',
            'Amenities',
            $ok ? 'ok' : 'missing',
            $ok
                ? 'AC, WiFi, CCTV, Food, etc.'
                : 'Add AC, WiFi, CCTV, Food, etc. in Settings → Website.',
            $this->route('settings.index', ['tab' => 'website'])
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, href: string|null}
     */
    private function gallery(PlatformSetting $settings): array
    {
        $gallery = collect($settings->website_gallery ?? [])->filter()->values();
        $ok = $gallery->isNotEmpty();

        return $this->item(
            'gallery',
            'Gallery Images',
            $ok ? 'ok' : 'missing',
            $ok
                ? 'Photos of library, halls, seats, etc.'
                : 'Upload photos of your library, halls, or seats.',
            $this->route('settings.index', ['tab' => 'website'])
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, href: string|null}
     */
    private function socials(PlatformSetting $settings): array
    {
        $links = $settings->website_social_links ?? [];
        $filled = collect(['facebook', 'instagram', 'youtube'])
            ->filter(fn ($key) => filled($links[$key] ?? null))
            ->values();
        $ok = $filled->isNotEmpty();

        return $this->item(
            'socials',
            'Social Media Links',
            $ok ? 'ok' : 'missing',
            $ok
                ? 'Facebook, Instagram, YouTube'
                : 'Add Facebook, Instagram, or YouTube in Settings → Website.',
            $this->route('settings.index', ['tab' => 'website'])
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, href: string|null}
     */
    private function item(string $key, string $label, string $status, string $detail, ?string $href): array
    {
        return compact('key', 'label', 'status', 'detail', 'href');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function route(string $name, array $params = []): ?string
    {
        return Route::has($name) ? route($name, $params) : null;
    }
}
