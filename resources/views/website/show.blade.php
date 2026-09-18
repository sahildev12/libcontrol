@extends('layouts.library-website')

@section('content')
    @include('website.partials.navbar')

    <main>
        {{-- Hero --}}
        <section id="home" data-lw-section class="relative min-h-[88vh] overflow-hidden pt-20">
            <img src="{{ $site['hero']['image'] }}" alt="{{ $site['hero']['image_alt'] }}" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 lw-hero-overlay"></div>

            <div class="relative lw-container flex min-h-[calc(88vh-5rem)] flex-col justify-center py-16 lg:py-24">
                <div class="max-w-2xl" data-lw-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">{{ $site['hero']['label'] }}</p>
                    <h1 class="lw-display mt-4 text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">
                        {{ $site['hero']['heading_line1'] }}<br>
                        <span class="text-blue-200">{{ $site['hero']['heading_line2'] }}</span>
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-100 sm:text-lg">{{ $site['hero']['description'] }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $site['hero']['primary_cta']['href'] }}" class="lw-btn-primary">{{ $site['hero']['primary_cta']['label'] }} →</a>
                        <a href="{{ $site['hero']['secondary_cta']['href'] }}" class="lw-btn-outline">{{ $site['hero']['secondary_cta']['label'] }} →</a>
                    </div>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        @foreach ($site['hero']['benefits'] as $benefit)
                            <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <x-website.icon :name="$benefit['icon']" class="h-5 w-5 text-blue-200" />
                                <span class="text-sm font-medium text-white">{{ $benefit['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="absolute bottom-8 right-4 hidden max-w-xs rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur-md lg:block" data-lw-reveal>
                    <p class="lw-display text-lg font-semibold italic leading-snug text-white">"{{ $site['hero']['quote'] }}"</p>
                </div>
            </div>
        </section>

        {{-- About --}}
        <section id="about" data-lw-section class="lw-section bg-white">
            <div class="lw-container grid items-center gap-12 lg:grid-cols-2">
                <div data-lw-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">{{ $site['about']['label'] }}</p>
                    <h2 class="lw-display mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">
                        {{ $site['about']['heading_line1'] }}<br>{{ $site['about']['heading_line2'] }}
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-slate-600">{{ $site['about']['paragraph'] }}</p>
                    <div class="mt-8 grid gap-6 sm:grid-cols-3">
                        @foreach ($site['about']['stats'] as $stat)
                            <div>
                                <p class="text-2xl font-bold text-blue-800">{{ $stat['value'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ $site['about']['cta']['href'] }}" class="lw-btn-secondary mt-8">{{ $site['about']['cta']['label'] }} →</a>
                </div>
                <div class="relative" data-lw-reveal>
                    <img src="{{ $site['about']['image'] }}" alt="{{ $site['about']['image_alt'] }}" class="w-full rounded-3xl object-cover shadow-xl ring-1 ring-slate-200">
                    <div class="absolute -bottom-6 -left-4 max-w-xs rounded-2xl bg-blue-900 p-5 text-white shadow-xl sm:left-6">
                        <p class="text-sm italic leading-relaxed">"{{ $site['about']['quote'] }}"</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Facilities --}}
        <section id="facilities" data-lw-section class="lw-section bg-stone-50">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <h2 class="lw-display text-3xl font-bold text-slate-900 sm:text-4xl">
                        {{ $site['facilities']['heading'] }}<br>
                        <span class="text-blue-800">{{ $site['facilities']['heading_highlight'] }}</span>
                    </h2>
                    <p class="mt-4 text-slate-600">{{ $site['facilities']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($site['facilities']['items'] as $facility)
                        <article class="lw-card p-6 transition hover:-translate-y-1 hover:shadow-md" data-lw-reveal>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-800">
                                <x-website.icon :name="$facility['icon']" />
                            </div>
                            <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $facility['name'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $facility['description'] }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="mt-10 text-center" data-lw-reveal>
                    <a href="{{ $site['facilities']['cta']['href'] }}" class="lw-btn-secondary">{{ $site['facilities']['cta']['label'] }} →</a>
                </div>
            </div>
        </section>

        {{-- Gallery --}}
        <section id="gallery" data-lw-section class="lw-section bg-white">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <h2 class="lw-display text-3xl font-bold text-slate-900 sm:text-4xl">{{ $site['gallery']['heading'] }}</h2>
                    <p class="mt-4 text-slate-600">{{ $site['gallery']['description'] }}</p>
                </div>
                <div class="mt-12 columns-1 gap-4 sm:columns-2 lg:columns-4">
                    @foreach ($site['gallery']['images'] as $image)
                        <button type="button" data-lw-gallery-item class="lw-gallery-item group mb-4 block w-full overflow-hidden rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-700" data-lw-reveal>
                            <div class="relative">
                                <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" class="w-full object-cover" loading="lazy">
                                <div class="absolute inset-0 bg-blue-950/0 transition group-hover:bg-blue-950/25"></div>
                                <p class="absolute bottom-3 left-3 right-3 translate-y-2 text-left text-sm font-medium text-white opacity-0 transition group-hover:translate-y-0 group-hover:opacity-100">{{ $image['alt'] }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
                <div class="mt-10 text-center" data-lw-reveal>
                    <a href="{{ $site['gallery']['cta']['href'] }}" class="lw-btn-secondary">{{ $site['gallery']['cta']['label'] }} →</a>
                </div>
            </div>
        </section>

        {{-- Membership --}}
        <section id="membership" data-lw-section class="lw-section bg-stone-50">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <h2 class="lw-display text-3xl font-bold text-slate-900 sm:text-4xl">
                        {{ $site['membership']['heading'] }}<br>
                        <span class="text-blue-800">{{ $site['membership']['heading_highlight'] }}</span>
                    </h2>
                    <p class="mt-4 text-slate-600">{{ $site['membership']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($site['membership']['plans'] as $plan)
                        <article @class([
                            'lw-card relative flex flex-col p-6 transition hover:-translate-y-1 hover:shadow-md',
                            'ring-2 ring-blue-700' => $plan['popular'],
                        ]) data-lw-reveal>
                            @if ($plan['popular'])
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-blue-700 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-white">Most Popular</span>
                            @endif
                            <h3 class="text-lg font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                            <p class="mt-3">
                                <span class="text-3xl font-bold text-blue-800">{{ $plan['price'] }}</span>
                                <span class="text-sm text-slate-500">{{ $plan['period'] }}</span>
                            </p>
                            <ul class="mt-6 space-y-2 text-sm text-slate-600">
                                @foreach ($site['membership']['benefits'] as $benefit)
                                    <li class="flex items-start gap-2"><span class="text-blue-700">✓</span> {{ $benefit }}</li>
                                @endforeach
                            </ul>
                            <a href="#contact" class="lw-btn-primary mt-8 w-full text-center">Enquire Now</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Testimonials --}}
        <section id="testimonials" data-lw-section class="lw-section bg-white">
            <div class="lw-container">
                <h2 class="lw-display text-center text-3xl font-bold text-slate-900 sm:text-4xl" data-lw-reveal>{{ $site['testimonials']['heading'] }}</h2>
                <div class="mx-auto mt-12 max-w-3xl" data-lw-reveal>
                    <div data-lw-testimonial-track>
                        @foreach ($site['testimonials']['items'] as $index => $testimonial)
                            <article data-lw-testimonial-slide @class(['lw-card p-8 text-center', 'hidden' => $index > 0])>
                                <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] }}" class="mx-auto h-16 w-16 rounded-full object-cover ring-2 ring-blue-100">
                                <div class="mt-3 flex justify-center gap-0.5 text-amber-400" aria-label="5 star rating">
                                    @for ($i = 0; $i < 5; $i++)
                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                                <p class="mt-5 text-base leading-relaxed text-slate-600">"{{ $testimonial['quote'] }}"</p>
                                <p class="mt-4 font-semibold text-slate-900">{{ $testimonial['name'] }}</p>
                                <p class="text-sm text-slate-500">{{ $testimonial['role'] }}</p>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-6 flex justify-center gap-2">
                        @foreach ($site['testimonials']['items'] as $index => $testimonial)
                            <button type="button" data-lw-testimonial-dot @class(['h-2.5 w-2.5 rounded-full transition', $index === 0 ? 'bg-blue-700' : 'bg-slate-300']) aria-label="Show testimonial {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Social --}}
        <section id="social" data-lw-section class="lw-section bg-stone-50">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <h2 class="lw-display text-3xl font-bold text-slate-900 sm:text-4xl">
                        {{ $site['social']['heading'] }}<br>
                        <span class="text-blue-800">{{ $site['social']['heading_highlight'] }}</span>
                    </h2>
                    <p class="mt-4 text-slate-600">{{ $site['social']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($site['social']['posts'] as $post)
                        <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200" data-lw-reveal>
                            <div class="relative aspect-square overflow-hidden">
                                <img src="{{ $post['image'] }}" alt="{{ $post['caption'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
                                <div class="absolute left-3 top-3 rounded-full bg-white/90 p-1.5">
                                    <svg class="h-4 w-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm5 5a3 3 0 100 6 3 3 0 000-6zm5.5-.75a1 1 0 10-2 0 1 1 0 002 0z"/></svg>
                                </div>
                                <div class="absolute bottom-0 p-4 text-white">
                                    <p class="text-sm font-medium">{{ $post['caption'] }}</p>
                                    <div class="mt-2 flex items-center gap-3 text-xs text-slate-200">
                                        <span>♥ {{ $post['likes'] }}</span>
                                        <span>💬 {{ $post['comments'] }}</span>
                                        <span>{{ $post['date'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4" data-lw-reveal>
                    @if (! empty($site['social_links']['instagram']))
                        <a href="{{ $site['social_links']['instagram'] }}" target="_blank" rel="noopener" class="lw-btn-secondary">Instagram</a>
                    @endif
                    @if (! empty($site['social_links']['facebook']))
                        <a href="{{ $site['social_links']['facebook'] }}" target="_blank" rel="noopener" class="lw-btn-secondary">Facebook</a>
                    @endif
                    @if (! empty($site['social_links']['youtube']))
                        <a href="{{ $site['social_links']['youtube'] }}" target="_blank" rel="noopener" class="lw-btn-secondary">YouTube</a>
                    @endif
                    <a href="{{ $site['social']['cta']['href'] }}" class="lw-btn-primary">{{ $site['social']['cta']['label'] }} →</a>
                </div>
            </div>
        </section>

        {{-- Rules --}}
        <section id="rules" data-lw-section class="lw-section bg-white">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <h2 class="lw-display text-3xl font-bold text-slate-900 sm:text-4xl">
                        {{ $site['rules']['heading'] }}<br>
                        <span class="text-blue-800">{{ $site['rules']['heading_highlight'] }}</span>
                    </h2>
                    <p class="mt-4 text-slate-600">{{ $site['rules']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($site['rules']['items'] as $rule)
                        <article class="lw-card p-6 text-center" data-lw-reveal>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-800">
                                <x-website.icon :name="$rule['icon']" />
                            </div>
                            <h3 class="mt-4 font-semibold text-slate-900">{{ $rule['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $rule['description'] }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="mt-10 text-center" data-lw-reveal>
                    <a href="{{ $site['rules']['cta']['href'] }}" class="lw-btn-secondary">{{ $site['rules']['cta']['label'] }} →</a>
                </div>
            </div>
        </section>

        {{-- Contact / Location --}}
        <section id="contact" data-lw-section class="lw-section bg-stone-50">
            <div class="lw-container">
                <div class="mx-auto max-w-3xl text-center" data-lw-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">{{ $site['contact']['heading'] }}</p>
                    <h2 class="lw-display mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">{{ $site['contact']['subheading'] }}</h2>
                    <p class="mt-4 text-slate-600">{{ $site['contact']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-8 lg:grid-cols-2">
                    <div class="lw-card p-8" data-lw-reveal>
                        <h3 class="text-lg font-semibold text-slate-900">Contact information</h3>
                        <dl class="mt-6 space-y-5 text-sm">
                            <div>
                                <dt class="font-semibold text-slate-500">Address</dt>
                                <dd class="mt-1 text-slate-800">
                                    {{ $site['address']['line1'] ?? '' }}<br>
                                    {{ $site['address']['line2'] ?? '' }}<br>
                                    {{ $site['address']['line3'] ?? '' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500">Phone</dt>
                                <dd class="mt-1"><a href="{{ $site['phone_href'] }}" class="text-blue-800 hover:underline">{{ $site['phone'] }}</a></dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500">Email</dt>
                                <dd class="mt-1"><a href="mailto:{{ $site['email'] }}" class="text-blue-800 hover:underline">{{ $site['email'] }}</a></dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500">Opening Hours</dt>
                                <dd class="mt-1 text-slate-800">{{ $site['opening_hours'] }}</dd>
                            </div>
                        </dl>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ $site['map_directions_url'] }}" target="_blank" rel="noopener" class="lw-btn-primary">Get Directions</a>
                            <a href="{{ $site['phone_href'] }}" class="lw-btn-secondary">Call Now</a>
                            @if ($site['whatsapp_url'])
                                <a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" class="lw-btn-secondary">WhatsApp</a>
                            @endif
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-2xl shadow-sm ring-1 ring-slate-200" data-lw-reveal>
                        <iframe
                            title="Library location map"
                            src="{{ $site['map_embed_url'] }}"
                            class="h-full min-h-[360px] w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-300">
        <div class="lw-container py-14">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-white">{{ $site['library_name'] }}</p>
                    <p class="mt-1 text-sm text-slate-400">{{ $site['subtitle'] }}</p>
                    <p class="mt-4 text-sm italic text-slate-400">{{ $site['footer_tagline'] }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Quick Links</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($site['navigation'] as $item)
                            <li><a href="{{ $item['href'] }}" class="hover:text-white">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Follow Us</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        @if (! empty($site['social_links']['instagram']))
                            <li><a href="{{ $site['social_links']['instagram'] }}" target="_blank" rel="noopener" class="hover:text-white">Instagram</a></li>
                        @endif
                        @if (! empty($site['social_links']['facebook']))
                            <li><a href="{{ $site['social_links']['facebook'] }}" target="_blank" rel="noopener" class="hover:text-white">Facebook</a></li>
                        @endif
                        @if (! empty($site['social_links']['youtube']))
                            <li><a href="{{ $site['social_links']['youtube'] }}" target="_blank" rel="noopener" class="hover:text-white">YouTube</a></li>
                        @endif
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-white">Get In Touch</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>{{ $site['phone'] }}</li>
                        <li>{{ $site['email'] }}</li>
                        <li>{{ $site['address']['line1'] ?? '' }}, {{ $site['address']['line3'] ?? '' }}</li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
                © {{ now()->year }} {{ $site['library_name'] }}. All rights reserved.
            </div>
        </div>
    </footer>
@endsection
