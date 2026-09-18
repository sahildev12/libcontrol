<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;

class LibraryWebsiteService
{
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
            'gallery' => $config['gallery'] ?? [],
            'membership' => $config['membership_plans'] ?? [],
            'testimonials' => $config['testimonials'] ?? [],
            'social' => $config['social'] ?? [],
            'rules' => $config['rules'] ?? [],
            'contact' => $config['contact'] ?? [],
            'branch_login_url' => route('login'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsPayload(PlatformSetting $settings): array
    {
        return [
            'website_enabled' => (bool) $settings->website_enabled,
            'website_tagline' => $settings->website_tagline,
            'website_hero_title' => $settings->website_hero_title,
            'website_about' => $settings->website_about,
            'website_amenities' => $this->normalizeAmenities($settings->website_amenities),
            'website_social_links' => $this->normalizeSocialLinks($settings->website_social_links),
            'website_whatsapp' => $settings->website_whatsapp,
            'website_logo_url' => $this->websiteLogoUrl($settings),
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

    public function storeLogo(\Illuminate\Http\UploadedFile $file): string
    {
        return $this->platformBrand->storeUpload($file, 'website_logo');
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
