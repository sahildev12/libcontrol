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

        $validated = $request->validate([
            'website_enabled' => ['nullable', 'boolean'],
            'website_tagline' => ['nullable', 'string', 'max:255'],
            'website_hero_title' => ['nullable', 'string', 'max:255'],
            'website_about' => ['nullable', 'string', 'max:5000'],
            'website_amenities' => ['nullable', 'array'],
            'website_amenities.*' => ['nullable', 'string', 'max:120'],
            'website_social_links' => ['nullable', 'array'],
            'website_social_links.facebook' => ['nullable', 'url', 'max:255'],
            'website_social_links.instagram' => ['nullable', 'url', 'max:255'],
            'website_social_links.youtube' => ['nullable', 'url', 'max:255'],
            'website_social_links.twitter' => ['nullable', 'url', 'max:255'],
            'website_social_links.website' => ['nullable', 'url', 'max:255'],
            'website_whatsapp' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
            'website_logo' => ['nullable', 'image', 'max:4096'],
        ]);

        $settings = \App\Models\PlatformSetting::current();
        $data = collect($validated)->except(['website_logo'])->all();
        $data['website_enabled'] = $request->boolean('website_enabled');

        if (array_key_exists('website_amenities', $data)) {
            $data['website_amenities'] = array_values(array_filter(array_map(
                static fn ($item) => trim((string) $item),
                $data['website_amenities'] ?? [],
            )));
        }

        if ($request->hasFile('website_logo')) {
            $data['website_logo_path'] = $websiteService->storeLogo($request->file('website_logo'));
        }

        $settings->update($data);

        return response()->json([
            'message' => 'Website settings saved.',
            'website' => $websiteService->settingsPayload($settings->fresh()),
        ]);
    }
}
