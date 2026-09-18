<div x-show="settingsTab === 'website'" x-cloak class="mt-4 space-y-6">
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Public library website</h2>
            <p class="mt-1 text-xs text-gray-500">Create a simple public page for students with amenities, social links, WhatsApp, and about us.</p>
        </div>
        <form class="space-y-4 p-5" @submit.prevent="saveWebsiteSettings()">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="checkbox" x-model="websiteForm.website_enabled" class="rounded border-gray-300 text-indigo-600">
                Enable public website
            </label>
            <p class="text-xs text-gray-500">Public URL: <a :href="websiteForm.public_url" target="_blank" class="text-indigo-600 hover:underline" x-text="websiteForm.public_url"></a></p>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hero title</label>
                    <input type="text" x-model="websiteForm.website_hero_title" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tagline</label>
                    <input type="text" x-model="websiteForm.website_tagline" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">About us</label>
                <textarea rows="5" x-model="websiteForm.website_about" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">WhatsApp number</label>
                <input type="text" x-model="websiteForm.website_whatsapp" maxlength="10" inputmode="numeric" placeholder="10-digit mobile" class="mt-1 block w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Amenities</label>
                <div class="mt-2 space-y-2">
                    <template x-for="(amenity, index) in websiteForm.website_amenities" :key="index">
                        <div class="flex gap-2">
                            <input type="text" x-model="websiteForm.website_amenities[index]" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Wi-Fi, AC, CCTV, etc.">
                            <button type="button" @click="websiteForm.website_amenities.splice(index, 1)" class="rounded-lg border border-gray-300 px-3 text-sm text-gray-600">Remove</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="websiteForm.website_amenities.push('')" class="mt-2 text-sm font-semibold text-indigo-600">+ Add amenity</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook URL</label>
                    <input type="url" x-model="websiteForm.website_social_links.facebook" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram URL</label>
                    <input type="url" x-model="websiteForm.website_social_links.instagram" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">YouTube URL</label>
                    <input type="url" x-model="websiteForm.website_social_links.youtube" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Twitter / X URL</label>
                    <input type="url" x-model="websiteForm.website_social_links.twitter" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Website URL</label>
                    <input type="url" x-model="websiteForm.website_social_links.website" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Website logo</label>
                <div class="mt-2 flex items-center gap-4">
                    <img x-show="websiteForm.website_logo_url" :src="websiteForm.website_logo_url" alt="Website logo" class="h-16 w-auto rounded border border-gray-200 bg-white p-2">
                    <input type="file" accept="image/*" @change="websiteLogoFile = $event.target.files[0] || null" class="text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="websiteSaving">
                    <span x-show="! websiteSaving">Save website</span>
                    <span x-show="websiteSaving">Saving...</span>
                </button>
            </div>
        </form>
    </section>
</div>
