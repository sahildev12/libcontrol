<div x-show="settingsTab === 'website'" x-cloak class="mt-4 space-y-6">
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Public library website</h2>
                    <p class="mt-1 text-xs text-gray-500">Create a simple public page for students with amenities, social links, WhatsApp, and gallery.</p>
                </div>
                <a
                    :href="websiteForm.preview_url || websiteForm.public_url || '#'"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50"
                >
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview website
                </a>
            </div>
        </div>
    </section>

    <form class="space-y-6" novalidate @submit.prevent="saveWebsiteSettings()">
        {{-- 1. Visibility --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 1</p>
                <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Visibility</h2>
                <p class="mt-0.5 text-xs text-gray-500">Turn the public page on or off for students.</p>
            </div>
            <div class="p-5">
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-4">
                    <label class="inline-flex items-center gap-2.5 text-sm font-semibold text-gray-900">
                        <input type="checkbox" x-model="websiteForm.website_enabled" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Enable public website
                    </label>
                    <p class="mt-2 text-xs text-gray-600" x-show="websiteForm.website_enabled">Students can open your public library page and send enquiries.</p>
                    <p class="mt-2 text-xs text-amber-700" x-show="! websiteForm.website_enabled" x-cloak>Website is off — visitors opening your website address are sent to the branch login page, and website enquiries are paused. Use Preview to check the page while you edit.</p>
                </div>
            </div>
        </section>

        {{-- 2. Library details --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 2</p>
                <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Library details</h2>
                <p class="mt-0.5 text-xs text-gray-500">Name, tagline, and address shown on your public page.</p>
            </div>
            <div class="space-y-5 p-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Library name</label>
                        <input
                            type="text"
                            x-model="websiteForm.display_name"
                            maxlength="120"
                            placeholder="e.g. Sunrise Study Hall"
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                        >
                        <p class="mt-1.5 text-xs text-gray-500">Also used for profile completion.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Website tagline</label>
                        <input
                            type="text"
                            x-model="websiteForm.website_tagline"
                            maxlength="255"
                            placeholder="e.g. Quiet AC study space near MG Road"
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                        >
                        <p class="mt-1.5 text-xs text-gray-500">Needed for Growth Score → Website (with WhatsApp + enable).</p>
                    </div>
                </div>
                <div>
                    <label for="website-address" class="block text-sm font-medium text-gray-700">Library address</label>
                    <textarea
                        id="website-address"
                        x-model="websiteForm.website_address"
                        rows="3"
                        maxlength="1000"
                        placeholder="Street, area, city, PIN"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    ></textarea>
                    <p class="mt-1.5 text-xs text-gray-500">Shown in the contact section of your public website.</p>
                </div>
            </div>
        </section>

        {{-- 3. Amenities --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 3</p>
                        <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Amenities</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Select facilities available at your library. These may appear on your public website.</p>
                    </div>
                    <p class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800" x-text="'Selected (' + (websiteForm.website_amenities || []).length + ')'"></p>
                </div>
            </div>
            <div class="space-y-4 p-5">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    </span>
                    <input
                        type="search"
                        x-model="amenitySearch"
                        placeholder="Search amenities..."
                        aria-label="Search amenities"
                        class="block w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    >
                </div>

                <div class="flex flex-wrap items-center gap-2" x-show="(websiteForm.website_amenities || []).length > 0">
                    <template x-for="amenity in websiteForm.website_amenities" :key="'chip-' + amenity">
                        <span class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-900">
                            <span x-text="amenity"></span>
                            <button
                                type="button"
                                class="rounded-full p-0.5 text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                :aria-label="'Remove ' + amenity"
                                @click="toggleAmenity(amenity)"
                            >
                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                    <button type="button" @click="clearAmenities()" class="text-xs font-semibold text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">Clear all</button>
                </div>

                <div class="space-y-2.5">
                    <template x-for="category in filteredAmenityCategories()" :key="category.key">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-3.5 py-3 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                                @click="toggleAmenityCategory(category.key)"
                                :aria-expanded="isAmenityCategoryOpen(category.key)"
                            >
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-900" x-text="category.label"></span>
                                    <span class="block text-xs text-gray-500" x-text="category.items.length + ' amenities'"></span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <span
                                        x-show="selectedCountInCategory(category) > 0"
                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-800"
                                        x-text="selectedCountInCategory(category) + ' selected'"
                                    ></span>
                                    <svg class="size-4 text-gray-500 transition-transform" :class="isAmenityCategoryOpen(category.key) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div x-show="isAmenityCategoryOpen(category.key)" x-cloak class="border-t border-gray-100 px-3.5 py-3.5">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="text-xs text-gray-500" x-text="selectedCountInCategory(category) + ' of ' + category.items.length + ' selected'"></p>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                                        @click.stop="toggleAllAmenitiesInCategory(category)"
                                        x-text="allAmenitiesSelectedInCategory(category) ? 'Clear section' : 'Select all'"
                                    ></button>
                                </div>
                                <div class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2">
                                    <template x-for="item in category.items" :key="category.key + '-' + item">
                                        <label class="flex cursor-pointer items-start gap-2.5 py-1.5 text-sm text-gray-800">
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="isAmenitySelected(item)"
                                                @change="toggleAmenity(item)"
                                            >
                                            <span class="leading-snug" x-text="item"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <p
                        x-show="amenitySearch.trim() !== '' && filteredAmenityCategories().length === 0"
                        class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-6 text-center text-sm text-gray-500"
                    >
                        No amenities match “<span x-text="amenitySearch.trim()"></span>”.
                    </p>
                </div>

                <div>
                    <button
                        type="button"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                        @click="showCustomAmenityInput = !showCustomAmenityInput; if (showCustomAmenityInput) { $nextTick(() => { if ($refs.customAmenityInput) $refs.customAmenityInput.focus() }) }"
                        x-text="showCustomAmenityInput ? 'Hide custom amenity' : '+ Add custom amenity'"
                    ></button>
                    <div x-show="showCustomAmenityInput" x-cloak class="mt-2.5 flex flex-col gap-2 sm:flex-row">
                        <input
                            type="text"
                            x-ref="customAmenityInput"
                            x-model="customAmenityInput"
                            maxlength="120"
                            placeholder="e.g. Dedicated mentoring desk"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            @keydown.enter.prevent="addCustomAmenity()"
                        >
                        <button
                            type="button"
                            @click="addCustomAmenity()"
                            class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            Add
                        </button>
                    </div>
                    <p x-show="customAmenityError" x-cloak class="mt-1.5 text-xs font-medium text-red-600" x-text="customAmenityError"></p>
                </div>
            </div>
        </section>

        {{-- 4. Contact & social --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 4</p>
                <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Contact &amp; social links</h2>
                <p class="mt-0.5 text-xs text-gray-500">WhatsApp and social channels students can use to reach your library.</p>
            </div>
            <div class="p-5">
                @php
                    $socialFields = [
                        'facebook' => ['Facebook URL', 'https://facebook.com/yourlibrary'],
                        'instagram' => ['Instagram URL', 'https://instagram.com/yourlibrary'],
                        'youtube' => ['YouTube URL', 'https://youtube.com/@yourlibrary'],
                        'twitter' => ['Twitter / X URL', 'https://x.com/yourlibrary'],
                        'website' => ['Website URL', 'https://yourlibrary.com'],
                    ];
                @endphp
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="website-whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp number</label>
                        <div
                            class="mt-1.5 flex rounded-lg border focus-within:ring-2"
                            :class="websiteErrors.website_whatsapp ? 'border-red-400 focus-within:border-red-500 focus-within:ring-red-500/20' : 'border-gray-300 focus-within:border-indigo-500 focus-within:ring-indigo-500/30'"
                        >
                            <span class="inline-flex items-center border-r border-gray-200 bg-gray-50 px-3 text-sm text-gray-500 rounded-l-lg">+91</span>
                            <input
                                id="website-whatsapp"
                                type="text"
                                x-model="websiteForm.website_whatsapp"
                                @input="sanitizeWebsiteWhatsapp(); if (websiteErrors.website_whatsapp) validateWebsiteField('website_whatsapp')"
                                @blur="validateWebsiteField('website_whatsapp')"
                                maxlength="10"
                                inputmode="numeric"
                                autocomplete="tel-national"
                                placeholder="10-digit mobile"
                                :aria-invalid="websiteErrors.website_whatsapp ? 'true' : 'false'"
                                class="block w-full rounded-r-lg border-0 px-3 py-2.5 text-sm focus:outline-none focus:ring-0"
                            >
                        </div>
                        <p x-show="websiteErrors.website_whatsapp" x-cloak class="mt-1.5 text-xs font-medium text-red-600" x-text="websiteErrors.website_whatsapp"></p>
                    </div>
                    @foreach ($socialFields as $key => [$label, $placeholder])
                        <div>
                            <label for="website-social-{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                            <input
                                id="website-social-{{ $key }}"
                                type="url"
                                inputmode="url"
                                maxlength="255"
                                x-model.trim="websiteForm.website_social_links.{{ $key }}"
                                @input="if (websiteErrors['website_social_links.{{ $key }}']) validateWebsiteField('website_social_links.{{ $key }}')"
                                @blur="validateWebsiteField('website_social_links.{{ $key }}')"
                                placeholder="{{ $placeholder }}"
                                :aria-invalid="websiteErrors['website_social_links.{{ $key }}'] ? 'true' : 'false'"
                                class="mt-1.5 block w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                :class="websiteErrors['website_social_links.{{ $key }}'] ? 'border-red-400 bg-red-50/40 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/30'"
                            >
                            <p x-show="websiteErrors['website_social_links.{{ $key }}']" x-cloak class="mt-1.5 text-xs font-medium text-red-600" x-text="websiteErrors['website_social_links.{{ $key }}']"></p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 5. Logo --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 5</p>
                <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Website logo</h2>
                <p class="mt-0.5 text-xs text-gray-500">Shown in the header of your public library page.</p>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap items-center gap-4">
                    <img x-show="websiteForm.website_logo_url" :src="websiteForm.website_logo_url" alt="Website logo" class="h-16 w-auto rounded border border-gray-200 bg-white p-2">
                    <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" @change="setWebsiteLogoFile($event)" class="text-sm">
                </div>
            </div>
        </section>

        {{-- 6. Gallery --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50/80 px-5 py-3.5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Step 6</p>
                        <h2 class="mt-0.5 text-sm font-semibold text-gray-900">Gallery photos</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Upload up to 10 photos for your public website gallery. JPG or PNG, max 4&nbsp;MB each.</p>
                    </div>
                    <p class="text-xs font-semibold text-gray-600" x-text="gallerySlotLabel()"></p>
                </div>
            </div>
            <div class="space-y-4 p-5">
                <div class="flex flex-wrap gap-3">
                    <template x-for="photo in (websiteForm.website_gallery || [])" :key="'saved-' + photo.path">
                        <div
                            x-show="photo.path && ! websiteGalleryRemove.includes(photo.path)"
                            class="relative shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100"
                            style="width: 96px; height: 96px;"
                        >
                            <img
                                :src="photo.url"
                                alt=""
                                width="96"
                                height="96"
                                style="width: 96px; height: 96px; object-fit: cover; display: block;"
                            >
                            <button
                                type="button"
                                class="absolute right-1 top-1 z-10 rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-red-700 shadow"
                                @click.prevent="removeGalleryPhoto({ path: photo.path, pending: false })"
                            >Remove</button>
                        </div>
                    </template>

                    <template x-for="photo in websiteGalleryPending" :key="'pending-' + photo.id">
                        <div
                            class="relative shrink-0 overflow-hidden rounded-xl border border-indigo-200 bg-indigo-50"
                            style="width: 96px; height: 96px;"
                        >
                            <img
                                :src="photo.preview"
                                alt=""
                                width="96"
                                height="96"
                                style="width: 96px; height: 96px; object-fit: cover; display: block;"
                            >
                            <button
                                type="button"
                                class="absolute right-1 top-1 z-10 rounded bg-white px-1.5 py-0.5 text-[10px] font-semibold text-red-700 shadow"
                                @click.prevent="removeGalleryPhoto({ id: photo.id, pending: true })"
                            >Remove</button>
                            <span class="absolute bottom-1 left-1 z-10 rounded bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">New</span>
                        </div>
                    </template>
                </div>

                <div x-show="galleryRemainingSlots() > 0">
                    <input
                        type="file"
                        x-ref="galleryFileInput"
                        class="hidden"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        multiple
                        @change="queueGalleryFiles($event)"
                    >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:border-indigo-300 hover:bg-indigo-50/40"
                        @click.prevent="$refs.galleryFileInput && $refs.galleryFileInput.click()"
                    >
                        <svg class="size-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Add photos
                    </button>
                </div>
                <p x-show="galleryError" x-cloak class="text-xs font-medium text-red-600" x-text="galleryError"></p>
            </div>
        </section>

        <div class="flex justify-end rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="websiteSaving">
                <span x-show="! websiteSaving">Save website</span>
                <span x-show="websiteSaving">Saving...</span>
            </button>
        </div>
    </form>
</div>
