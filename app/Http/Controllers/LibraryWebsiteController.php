<?php

namespace App\Http\Controllers;

use App\Services\LibraryWebsiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryWebsiteController extends Controller
{
    public function home(Request $request, LibraryWebsiteService $websiteService): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        if (! \App\Models\PlatformSetting::current()->website_enabled) {
            return redirect()->route('login');
        }

        return view('website.show', [
            'site' => $websiteService->homePayload(),
        ]);
    }

    public function show(LibraryWebsiteService $websiteService): View|RedirectResponse
    {
        return redirect()->route('home');
    }

    public function update(Request $request, LibraryWebsiteService $websiteService): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        LibraryWebsiteService::prepareContactInput($request);

        $validated = $request->validate([
            'website_enabled' => ['nullable', 'boolean'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'website_tagline' => ['nullable', 'string', 'max:255'],
            'website_hero_title' => ['nullable', 'string', 'max:255'],
            'website_about' => ['nullable', 'string', 'max:5000'],
            'website_amenities' => ['nullable', 'array'],
            'website_amenities.*' => ['nullable', 'string', 'max:120'],
            ...LibraryWebsiteService::contactRules(),
            'website_logo' => ['nullable', 'image', 'max:4096'],
            'website_gallery' => ['nullable', 'array', 'max:'.LibraryWebsiteService::GALLERY_MAX],
            'website_gallery.*' => ['nullable', 'image', 'max:4096'],
            'website_gallery_remove' => ['nullable', 'array'],
            'website_gallery_remove.*' => ['nullable', 'string', 'max:255'],
        ], LibraryWebsiteService::contactMessages());

        $settings = \App\Models\PlatformSetting::current();
        $data = collect($validated)->except(['website_logo', 'website_gallery', 'website_gallery_remove'])->all();
        $data['website_enabled'] = $request->boolean('website_enabled');

        if (array_key_exists('display_name', $data)) {
            $name = trim((string) ($data['display_name'] ?? ''));
            $data['display_name'] = $name !== '' ? $name : null;
        }

        if (array_key_exists('website_amenities', $data)) {
            $data['website_amenities'] = array_values(array_filter(array_map(
                static fn ($item) => trim((string) $item),
                $data['website_amenities'] ?? [],
            )));
        }

        if ($request->hasFile('website_logo')) {
            $data['website_logo_path'] = $websiteService->storeLogo($request->file('website_logo'));
        }

        $uploads = $request->file('website_gallery', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }
        $uploads = array_values(array_filter($uploads));

        $remove = array_values(array_filter(array_map(
            'strval',
            $request->input('website_gallery_remove', []),
        )));

        if ($uploads !== [] || $remove !== []) {
            $data['website_gallery'] = $websiteService->syncGallery(
                is_array($settings->website_gallery) ? $settings->website_gallery : [],
                $remove,
                $uploads,
            );
        }

        $settings->update($data);
        $settings = $settings->fresh();

        if (array_key_exists('website_social_links', $data)) {
            LibraryWebsiteService::syncSocialsToGrowthProfiles($settings);
        }

        return response()->json([
            'message' => 'Website settings saved.',
            'website' => $websiteService->settingsPayload($settings),
        ]);
    }
}
