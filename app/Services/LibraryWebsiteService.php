<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LibraryWebsiteService
{
    public const GALLERY_MAX = 10;

    /**
     * Allowed hosts per social network; the website link accepts any host.
     *
     * @var array<string, array{label: string, pattern: string|null}>
     */
    public const SOCIAL_LINKS = [
        'facebook' => ['label' => 'Facebook', 'pattern' => '#^https?://(www\.|m\.|web\.)?(facebook\.com|fb\.com|fb\.me)/\S+$#i'],
        'instagram' => ['label' => 'Instagram', 'pattern' => '#^https?://(www\.)?instagram\.com/\S+$#i'],
        'youtube' => ['label' => 'YouTube', 'pattern' => '#^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/\S+$#i'],
        'twitter' => ['label' => 'Twitter / X', 'pattern' => '#^https?://(www\.|mobile\.)?(twitter\.com|x\.com)/\S+$#i'],
        'website' => ['label' => 'Website', 'pattern' => null],
    ];

    /**
     * Strips formatting from the WhatsApp number and adds https:// to bare links before validation.
     */
    public static function prepareContactInput(Request $request): void
    {
        $merge = [];

        if ($request->has('website_whatsapp')) {
            $digits = preg_replace('/\D+/', '', (string) $request->input('website_whatsapp')) ?? '';
            if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $merge['website_whatsapp'] = $digits !== '' ? $digits : null;
        }

        $links = $request->input('website_social_links');
        if (is_array($links)) {
            foreach ($links as $key => $value) {
                $value = trim((string) $value);
                if ($value !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
                    $value = 'https://'.ltrim($value, '/');
                }
                $links[$key] = $value !== '' ? $value : null;
            }
            $merge['website_social_links'] = $links;
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function contactRules(): array
    {
        $rules = [
            'website_social_links' => ['nullable', 'array'],
            'website_whatsapp' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
        ];

        foreach (self::SOCIAL_LINKS as $key => $meta) {
            $rules['website_social_links.'.$key] = array_values(array_filter([
                'nullable', 'string', 'max:255', 'url:http,https',
                $meta['pattern'] !== null ? 'regex:'.$meta['pattern'] : null,
            ]));
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function contactMessages(): array
    {
        $messages = [
            'website_whatsapp.regex' => 'Enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.',
        ];

        foreach (self::SOCIAL_LINKS as $key => $meta) {
            $messages['website_social_links.'.$key.'.url'] = 'Enter a valid '.$meta['label'].' link, e.g. https://…';
            $messages['website_social_links.'.$key.'.max'] = $meta['label'].' link must be 255 characters or fewer.';
            if ($meta['pattern'] !== null) {
                $messages['website_social_links.'.$key.'.regex'] = 'This must be a '.$meta['label'].' link.';
            }
        }

        return $messages;
    }

    /**
     * Mirrors the website Facebook / Instagram links onto every branch growth profile.
     */
    public static function syncSocialsToGrowthProfiles(PlatformSetting $settings): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('library_growth_profiles')) {
            return;
        }

        $links = is_array($settings->website_social_links) ? $settings->website_social_links : [];
        $values = [
            'facebook_url' => filled($links['facebook'] ?? null) ? (string) $links['facebook'] : null,
            'instagram_url' => filled($links['instagram'] ?? null) ? (string) $links['instagram'] : null,
        ];

        foreach (\App\Models\Branch::query()->pluck('id') as $branchId) {
            \App\Models\LibraryGrowthProfile::query()->firstOrCreate(
                ['branch_id' => (int) $branchId],
                ['score_cached' => 0],
            )->forceFill($values)->save();
        }
    }

    public function __construct(
        private PlatformBrandService $platformBrand,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(bool $allowDisabled = false): array
    {
        $settings = PlatformSetting::current();

        if (! $settings->website_enabled && ! $allowDisabled) {
            return ['enabled' => false];
        }

        return $this->homePayload();
    }

    /**
     * @return array<string, mixed>
     */
    public function homePayload(): array
    {
        $config = config('library-website', []);
        $settings = PlatformSetting::current();

        $socialLinks = $this->normalizeSocialLinks($settings->website_social_links);
        foreach ($socialLinks as $key => $value) {
            if ($value !== '') {
                $config['social_links'][$key] = $value;
            }
        }

        $whatsapp = $settings->website_whatsapp ?: ($config['whatsapp'] ?? null);

        return [
            'enabled' => true,
            'library_name' => $settings->display_name ?: ($config['library_name'] ?? 'Library'),
            'subtitle' => $config['subtitle'] ?? 'Library & Study Center',
            'tagline' => $settings->website_tagline ?: ($config['tagline'] ?? ''),
            'footer_tagline' => $config['footer_tagline'] ?? '',
            'logo_url' => $this->websiteLogoUrl($settings),
            'phone' => $config['phone'] ?? '',
            'phone_href' => $config['phone_href'] ?? '',
            'email' => $config['email'] ?? '',
            'whatsapp' => $whatsapp,
            'whatsapp_url' => $this->whatsappUrl($whatsapp),
            'address' => $config['address'] ?? [],
            'opening_hours' => $config['opening_hours'] ?? '',
            'map_embed_url' => $config['map_embed_url'] ?? '',
            'map_directions_url' => $config['map_directions_url'] ?? '',
            'social_links' => $config['social_links'] ?? [],
            'seo' => [
                'title' => ($settings->display_name ?: ($config['library_name'] ?? 'Library')).' | '.($config['subtitle'] ?? 'Library & Study Center'),
                'description' => $config['seo']['description'] ?? '',
            ],
            'navigation' => $config['navigation'] ?? [],
            'hero' => $this->mergeHero($config['hero'] ?? [], $settings),
            'about' => $this->mergeAbout($config['about'] ?? [], $settings),
            'facilities' => $this->mergeFacilities($config['facilities'] ?? [], $settings),
            'gallery' => $this->mergeGallery($config['gallery'] ?? [], $settings),
            'membership' => $config['membership_plans'] ?? [],
            'steps' => $config['steps'] ?? [],
            'testimonials' => $config['testimonials'] ?? [],
            'faq' => $config['faq'] ?? [],
            'social' => $config['social'] ?? [],
            'rules' => $config['rules'] ?? [],
            'contact' => $config['contact'] ?? [],
            'branch_login_url' => route('login'),
            'enquiries_enabled' => (bool) config('libcontrol.modules.enquiries', true),
            'referral_offer' => app(\App\Services\Growth\ReferralService::class)
                ->offerText(\App\Models\Branch::query()->orderBy('id')->value('id')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsPayload(PlatformSetting $settings): array
    {
        return [
            'website_enabled' => (bool) $settings->website_enabled,
            'display_name' => $settings->display_name,
            'website_tagline' => $settings->website_tagline,
            'website_hero_title' => $settings->website_hero_title,
            'website_about' => $settings->website_about,
            'website_amenities' => $this->normalizeAmenities($settings->website_amenities),
            'website_social_links' => $this->normalizeSocialLinks($settings->website_social_links),
            'website_whatsapp' => $settings->website_whatsapp,
            'website_logo_url' => $this->websiteLogoUrl($settings),
            'website_gallery' => $this->galleryItemsForSettings($settings),
            'public_url' => route('home'),
        ];
    }

    public function websiteLogoUrl(PlatformSetting $settings): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $settings->website_logo_path), '/');

        if ($path && Storage::disk('public')->exists($path)) {
            return '/storage/'.$path;
        }

        return $settings->logoWithTextUrl() ?: $settings->simpleLogoUrl();
    }

    public function storeLogo(UploadedFile $file): string
    {
        return $this->platformBrand->storeUpload($file, 'website_logo');
    }

    public function storeGalleryImage(UploadedFile $file): string
    {
        return $file->store('platform/website-gallery', 'public');
    }

    /**
     * @param  list<string>  $paths
     */
    public function deleteGalleryPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $normalized = ltrim(str_replace('\\', '/', (string) $path), '/');
            if ($normalized === '' || ! str_starts_with($normalized, 'platform/website-gallery/')) {
                continue;
            }
            if (Storage::disk('public')->exists($normalized)) {
                Storage::disk('public')->delete($normalized);
            }
        }
    }

    /**
     * @param  list<string>|null  $existing
     * @param  list<string>  $remove
     * @param  list<UploadedFile>  $uploads
     * @return list<string>
     */
    public function syncGallery(?array $existing, array $remove, array $uploads): array
    {
        $paths = array_values(array_filter(array_map(
            static fn ($path) => ltrim(str_replace('\\', '/', (string) $path), '/'),
            is_array($existing) ? $existing : [],
        )));

        $removeNormalized = array_values(array_filter(array_map(
            static fn ($path) => ltrim(str_replace('\\', '/', (string) $path), '/'),
            $remove,
        )));

        if ($removeNormalized !== []) {
            $this->deleteGalleryPaths($removeNormalized);
            $paths = array_values(array_filter(
                $paths,
                static fn (string $path) => ! in_array($path, $removeNormalized, true),
            ));
        }

        $slots = max(0, self::GALLERY_MAX - count($paths));
        foreach (array_slice($uploads, 0, $slots) as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->storeGalleryImage($file);
            }
        }

        return array_slice($paths, 0, self::GALLERY_MAX);
    }

    /**
     * @return list<array{path: string, url: string}>
     */
    public function galleryItemsForSettings(PlatformSetting $settings): array
    {
        $items = [];

        foreach ($this->normalizeGalleryPaths($settings->website_gallery) as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            $items[] = [
                'path' => $path,
                'url' => '/storage/'.$path,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $gallery
     * @return array<string, mixed>
     */
    private function mergeGallery(array $gallery, PlatformSetting $settings): array
    {
        $custom = [];

        foreach ($this->normalizeGalleryPaths($settings->website_gallery) as $index => $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            $custom[] = [
                'src' => '/storage/'.$path,
                'alt' => 'Library gallery photo '.($index + 1),
            ];
        }

        if ($custom !== []) {
            $gallery['images'] = $custom;
        }

        return $gallery;
    }

    /**
     * @param  mixed  $gallery
     * @return list<string>
     */
    private function normalizeGalleryPaths(mixed $gallery): array
    {
        if (! is_array($gallery)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function ($item) {
                if (is_string($item)) {
                    return ltrim(str_replace('\\', '/', $item), '/');
                }
                if (is_array($item) && isset($item['path'])) {
                    return ltrim(str_replace('\\', '/', (string) $item['path']), '/');
                }

                return '';
            },
            $gallery,
        )));
    }

    /**
     * @param  array<string, mixed>  $hero
     * @return array<string, mixed>
     */
    private function mergeHero(array $hero, PlatformSetting $settings): array
    {
        if (filled($settings->website_hero_title)) {
            $hero['heading_line1'] = trim($settings->website_hero_title);
            $hero['heading_line2'] = '';
        }

        if (filled($settings->website_tagline) && empty($hero['description'])) {
            $hero['description'] = $settings->website_tagline;
        }

        return $hero;
    }

    /**
     * @param  array<string, mixed>  $about
     * @return array<string, mixed>
     */
    private function mergeAbout(array $about, PlatformSetting $settings): array
    {
        if (filled($settings->website_about)) {
            $about['paragraph'] = $settings->website_about;
        }

        return $about;
    }

    /**
     * @param  array<string, mixed>  $facilities
     * @return array<string, mixed>
     */
    private function mergeFacilities(array $facilities, PlatformSetting $settings): array
    {
        $amenities = $this->normalizeAmenities($settings->website_amenities);

        if ($amenities === []) {
            return $facilities;
        }

        $facilities['items'] = array_map(
            static fn (string $name) => [
                'icon' => 'peace',
                'name' => $name,
                'description' => 'Available for all members.',
            ],
            $amenities,
        );

        return $facilities;
    }

    /**
     * @param  mixed  $amenities
     * @return list<string>
     */
    private function normalizeAmenities(mixed $amenities): array
    {
        if (! is_array($amenities)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : (is_array($item) ? trim((string) ($item['name'] ?? '')) : ''),
            $amenities,
        )));
    }

    /**
     * @param  mixed  $links
     * @return array<string, string>
     */
    private function normalizeSocialLinks(mixed $links): array
    {
        $defaults = [
            'facebook' => '',
            'instagram' => '',
            'youtube' => '',
            'twitter' => '',
            'website' => '',
        ];

        if (! is_array($links)) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            $defaults[$key] = trim((string) ($links[$key] ?? $value));
        }

        return $defaults;
    }

    private function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }
}
