@extends('layouts.library-website')

@php
    $reviews = $site['testimonials'] ?? [];
    $reviewItems = $reviews['items'] ?? [];
    $reviewRating = (int) ($reviews['rating'] ?? 5);
    $reviewCount = (int) ($reviews['review_count'] ?? count($reviewItems));
    $avatarColors = ['bg-orange-500', 'bg-emerald-600', 'bg-sky-600', 'bg-violet-600', 'bg-rose-500', 'bg-amber-600', 'bg-teal-600', 'bg-indigo-600'];
    $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
    $galleryImages = array_slice($site['gallery']['images'] ?? [], 0, 7);
@endphp

@section('content')
    @include('website.partials.navbar')

    <main>
        {{-- Hero --}}
        <section id="home" data-lw-section class="relative overflow-clip bg-gradient-to-b from-blue-50/70 via-white to-white pt-[72px]">
            <div class="pointer-events-none absolute -right-40 -top-40 h-[480px] w-[480px] rounded-full bg-blue-100/60 blur-3xl"></div>
            <div class="lw-container relative grid items-center gap-12 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
                <div data-lw-reveal>
                    <span class="lw-eyebrow">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ $site['hero']['label'] }}
                    </span>
                    <h1 class="lw-display mt-6 text-4xl leading-[1.08] sm:text-5xl lg:text-[3.6rem]">
                        {{ $site['hero']['heading_line1'] }}
                        @if (filled($site['hero']['heading_line2'] ?? ''))
                            <span class="block text-blue-700">{{ $site['hero']['heading_line2'] }}</span>
                        @endif
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-relaxed text-slate-500 sm:text-lg">{{ $site['hero']['description'] }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $site['hero']['primary_cta']['href'] }}" class="lw-btn-primary">
                            {{ $site['hero']['primary_cta']['label'] }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <a href="{{ $site['hero']['secondary_cta']['href'] }}" class="lw-btn-secondary">{{ $site['hero']['secondary_cta']['label'] }}</a>
                    </div>

                    @if (! empty($site['about']['stats']))
                        <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-slate-200 pt-8">
                            @foreach ($site['about']['stats'] as $stat)
                                <div>
                                    <dt class="sr-only">{{ $stat['label'] }}</dt>
                                    <dd class="text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ $stat['value'] }}</dd>
                                    <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">{{ $stat['label'] }}</p>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>

                <div class="relative" data-lw-reveal>
                    <div class="overflow-hidden rounded-[2rem] shadow-2xl shadow-blue-900/10 ring-1 ring-slate-200">
                        <img src="{{ $site['hero']['image'] }}" alt="{{ $site['hero']['image_alt'] ?? $site['library_name'] }}" class="aspect-[4/5] w-full object-cover sm:aspect-[5/4] lg:aspect-[4/5]">
                    </div>

                    <div class="absolute -left-3 top-8 flex items-center gap-3 rounded-2xl bg-white/95 p-3 pr-5 shadow-xl ring-1 ring-slate-200 backdrop-blur sm:-left-8">
                        <div class="lw-icon-tile !h-10 !w-10 bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Open today</p>
                            <p class="text-sm font-bold text-slate-900">{{ $site['opening_hours'] }}</p>
                        </div>
                    </div>

                    @if ($reviewItems !== [])
                        <a href="#testimonials" class="absolute -bottom-5 right-3 flex items-center gap-3 rounded-2xl bg-white/95 p-3 pr-5 shadow-xl ring-1 ring-slate-200 backdrop-blur transition hover:-translate-y-0.5 sm:-right-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50">
                                @include('website.partials.google-g', ['class' => 'h-5 w-5'])
                            </div>
                            <div>
                                <div class="flex items-center gap-1">
                                    <span class="text-sm font-bold text-slate-900">{{ number_format($reviewRating, 1) }}</span>
                                    <span class="flex text-amber-400">
                                        @for ($i = 0; $i < 5; $i++)
                                            <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                        @endfor
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $reviewCount }} Google reviews</p>
                            </div>
                        </a>
                    @endif
                </div>
            </div>

            @if (! empty($site['hero']['benefits']))
                <div class="lw-container relative pb-6">
                    <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 rounded-2xl border border-slate-200 bg-white px-6 py-4">
                        @foreach ($site['hero']['benefits'] as $benefit)
                            <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <x-website.icon :name="$benefit['icon']" class="h-5 w-5 text-blue-700" />
                                {{ $benefit['label'] }}
                            </div>
                        @endforeach
                        <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
                            <x-website.icon name="ac" class="h-5 w-5 text-blue-700" />
                            Fully air-conditioned
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- About --}}
        <section id="about" data-lw-section class="lw-section">
            <div class="lw-container grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="relative order-2 lg:order-1" data-lw-reveal>
                    <img src="{{ $site['about']['image'] }}" alt="{{ $site['about']['image_alt'] ?? '' }}" class="aspect-[4/3] w-full rounded-3xl object-cover ring-1 ring-slate-200">
                    @if (filled($site['about']['quote'] ?? ''))
                        <figure class="absolute -bottom-6 left-4 right-4 rounded-2xl bg-blue-700 p-5 text-white shadow-xl sm:left-auto sm:right-6 sm:max-w-xs">
                            <svg class="h-6 w-6 text-blue-300" fill="currentColor" viewBox="0 0 24 24"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
                            <blockquote class="mt-2 text-sm leading-relaxed">{{ $site['about']['quote'] }}</blockquote>
                        </figure>
                    @endif
                </div>
                <div class="order-1 lg:order-2" data-lw-reveal>
                    <span class="lw-eyebrow">{{ $site['about']['label'] }}</span>
                    <h2 class="lw-section-title">
                        {{ $site['about']['heading_line1'] }}
                        <span class="text-blue-700">{{ $site['about']['heading_line2'] }}</span>
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-slate-500">{{ $site['about']['paragraph'] }}</p>
                    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach (array_slice($site['facilities']['items'] ?? [], 0, 4) as $facility)
                            <li class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                {{ $facility['name'] }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="#enquiry" class="lw-btn-primary mt-9">Book a visit</a>
                </div>
            </div>
        </section>

        {{-- Facilities --}}
        <section id="facilities" data-lw-section class="lw-section bg-slate-50">
            <div class="lw-container">
                <div class="lw-section-head" data-lw-reveal>
                    <span class="lw-eyebrow">Facilities</span>
                    <h2 class="lw-section-title">
                        {{ $site['facilities']['heading'] }}
                        <span class="text-blue-700">{{ $site['facilities']['heading_highlight'] }}</span>
                    </h2>
                    <p class="lw-section-lead">{{ $site['facilities']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($site['facilities']['items'] as $facility)
                        <article class="lw-card p-6 transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-900/5" data-lw-reveal>
                            <div class="lw-icon-tile">
                                <x-website.icon :name="$facility['icon']" class="h-5 w-5" />
                            </div>
                            <h3 class="mt-5 text-base font-bold text-slate-900">{{ $facility['name'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $facility['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        @if (! empty($site['steps']['items']))
            <section id="how-it-works" data-lw-section class="lw-section">
                <div class="lw-container">
                    <div class="lw-section-head" data-lw-reveal>
                        <span class="lw-eyebrow">How it works</span>
                        <h2 class="lw-section-title">
                            {{ $site['steps']['heading'] }}
                            <span class="text-blue-700">{{ $site['steps']['heading_highlight'] }}</span>
                        </h2>
                    </div>
                    <div class="relative mt-14">
                    <div class="absolute left-[12%] right-[12%] top-6 hidden border-t-2 border-dashed border-blue-200 lg:block" aria-hidden="true"></div>
                    <ol class="relative grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                        @foreach ($site['steps']['items'] as $index => $step)
                            <li class="relative text-center" data-lw-reveal>
                                <span class="relative mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-700 text-base font-extrabold text-white shadow-lg shadow-blue-700/25 ring-8 ring-white">{{ $index + 1 }}</span>
                                <h3 class="mt-5 text-base font-bold text-slate-900">{{ $step['title'] }}</h3>
                                <p class="mx-auto mt-2 max-w-[16rem] text-sm leading-relaxed text-slate-500">{{ $step['description'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                    </div>
                </div>
            </section>
        @endif

        {{-- Membership --}}
        <section id="membership" data-lw-section class="lw-section bg-slate-50">
            <div class="lw-container">
                <div class="lw-section-head" data-lw-reveal>
                    <span class="lw-eyebrow">Membership plans</span>
                    <h2 class="lw-section-title">
                        {{ $site['membership']['heading'] }}
                        <span class="text-blue-700">{{ $site['membership']['heading_highlight'] }}</span>
                    </h2>
                    <p class="lw-section-lead">{{ $site['membership']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($site['membership']['plans'] as $plan)
                        <article @class([
                            'relative flex flex-col rounded-2xl p-7 transition hover:-translate-y-1',
                            'bg-blue-700 text-white shadow-xl shadow-blue-700/25' => $plan['popular'],
                            'lw-card hover:shadow-lg hover:shadow-blue-900/5' => ! $plan['popular'],
                        ]) data-lw-reveal>
                            @if ($plan['popular'])
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-amber-400 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-900 shadow-sm">Most popular</span>
                            @endif
                            <h3 @class(['text-sm font-semibold uppercase tracking-wide', 'text-blue-100' => $plan['popular'], 'text-slate-500' => ! $plan['popular']])>{{ $plan['name'] }}</h3>
                            <p class="mt-4 flex flex-wrap items-baseline gap-x-1">
                                <span @class(['text-3xl font-extrabold xl:text-4xl', 'text-white' => $plan['popular'], 'text-slate-900' => ! $plan['popular']])>{{ $plan['price'] }}</span>
                                <span @class(['whitespace-nowrap text-sm', 'text-blue-100' => $plan['popular'], 'text-slate-500' => ! $plan['popular']])>{{ $plan['period'] }}</span>
                            </p>
                            <ul @class(['mt-6 flex-1 space-y-3 border-t pt-6 text-sm', 'border-white/15 text-blue-50' => $plan['popular'], 'border-slate-100 text-slate-600' => ! $plan['popular']])>
                                @foreach ($site['membership']['benefits'] as $benefit)
                                    <li class="flex items-start gap-2.5">
                                        <svg @class(['mt-0.5 h-4 w-4 shrink-0', 'text-white' => $plan['popular'], 'text-blue-700' => ! $plan['popular']]) fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        {{ $benefit }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="#enquiry" @class(['mt-8 w-full', 'lw-btn-light' => $plan['popular'], 'lw-btn-secondary' => ! $plan['popular']])>Enquire / Pre-book</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Gallery --}}
        @if ($galleryImages !== [])
            <section id="gallery" data-lw-section class="lw-section">
                <div class="lw-container">
                    <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end" data-lw-reveal>
                        <div class="max-w-xl">
                            <span class="lw-eyebrow">Gallery</span>
                            <h2 class="lw-section-title">{{ $site['gallery']['heading'] }}</h2>
                            <p class="mt-3 text-slate-500">{{ $site['gallery']['description'] }}</p>
                        </div>
                        <a href="#enquiry" class="lw-btn-secondary shrink-0">Visit in person</a>
                    </div>
                    @php($bento = count($galleryImages) >= 5)
                    <div @class([
                        'mt-10 grid gap-3',
                        'auto-rows-[150px] grid-cols-2 sm:auto-rows-[190px] md:grid-cols-4' => $bento,
                        'sm:grid-cols-2 lg:grid-cols-3' => ! $bento,
                    ])>
                        @foreach ($galleryImages as $index => $image)
                            <button type="button" data-lw-gallery-item @class([
                                'lw-gallery-item group relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-700',
                                'col-span-2 row-span-2' => $bento && $index === 0,
                                'md:col-span-2' => $bento && $index === 3,
                                'aspect-[4/3]' => ! $bento,
                            ]) data-lw-reveal>
                                <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" class="h-full w-full object-cover" loading="lazy">
                                <span class="absolute inset-0 bg-gradient-to-t from-slate-950/50 to-transparent opacity-0 transition group-hover:opacity-100"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Testimonials (Google reviews style) --}}
        @if ($reviewItems !== [])
            <section id="testimonials" data-lw-section class="lw-section bg-slate-50">
                <div class="lw-container" data-lw-reveal>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 bg-slate-100 px-6 py-3.5">
                            <h2 class="text-base font-bold text-slate-800">{{ $reviews['heading'] ?? 'Testimonials & Reviews' }}</h2>
                        </div>
                        <div class="grid items-center gap-6 p-6 md:grid-cols-[200px_1fr] md:gap-8 md:p-8">
                            <div class="text-center">
                                <p class="text-xl font-extrabold tracking-wide text-slate-900">{{ $reviews['rating_label'] ?? 'EXCELLENT' }}</p>
                                <div class="mt-1.5 flex justify-center text-amber-400" aria-label="{{ $reviewRating }} out of 5 stars">
                                    @for ($i = 0; $i < 5; $i++)
                                        <svg @class(['h-7 w-7 fill-current', 'text-slate-300' => $i >= $reviewRating]) viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                    @endfor
                                </div>
                                <p class="mt-1.5 text-sm text-slate-600">Based on <span class="font-bold text-slate-900">{{ $reviewCount }} reviews</span></p>
                                <p class="mt-2 text-2xl font-semibold tracking-tight" aria-label="Google">
                                    <span class="text-[#4285F4]">G</span><span class="text-[#EA4335]">o</span><span class="text-[#FBBC05]">o</span><span class="text-[#4285F4]">g</span><span class="text-[#34A853]">l</span><span class="text-[#EA4335]">e</span>
                                </p>
                            </div>

                            <div class="relative min-w-0">
                                <button type="button" data-lw-reviews-prev class="lw-review-arrow -left-4" aria-label="Previous reviews">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div data-lw-reviews-track class="lw-reviews-track">
                                    @foreach ($reviewItems as $index => $review)
                                        @php($stars = (int) ($review['rating'] ?? 5))
                                        <article class="lw-review-card" data-lw-review>
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex min-w-0 items-center gap-3">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-base font-semibold text-white {{ $avatarColors[$index % count($avatarColors)] }}">{{ strtoupper(mb_substr($review['name'], 0, 1)) }}</span>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-bold text-slate-900">{{ $review['name'] }}</p>
                                                        <p class="text-xs text-slate-500">{{ $review['time'] ?? '' }}</p>
                                                    </div>
                                                </div>
                                                @include('website.partials.google-g', ['class' => 'h-5 w-5 shrink-0'])
                                            </div>
                                            <div class="mt-3 flex items-center gap-1">
                                                <span class="flex text-amber-400" aria-label="{{ $stars }} out of 5 stars">
                                                    @for ($i = 0; $i < 5; $i++)
                                                        <svg @class(['h-4 w-4 fill-current', 'text-slate-300' => $i >= $stars]) viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                                    @endfor
                                                </span>
                                                <svg class="ml-0.5 h-4 w-4 text-blue-500" viewBox="0 0 24 24" fill="currentColor" aria-label="Verified"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                                            </div>
                                            <p class="lw-review-text" data-lw-review-text>{{ $review['quote'] }}</p>
                                            <button type="button" data-lw-review-toggle class="mt-2 self-start text-sm text-slate-500 hover:text-slate-800">Read more</button>
                                        </article>
                                    @endforeach
                                </div>
                                <button type="button" data-lw-reviews-next class="lw-review-arrow -right-4" aria-label="Next reviews">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($site['enquiries_enabled'] ?? true)
        {{-- Enquiry / pre-booking --}}
        <section id="enquiry" data-lw-section class="lw-section">
            <div class="lw-container grid items-start gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
                <div data-lw-reveal>
                    <span class="lw-eyebrow">Enquire now</span>
                    <h2 class="lw-section-title">Pre-book your seat or ask a question</h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-500">Tell us what you need — we will call you back to confirm membership, trial, or seat availability.</p>
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center gap-4">
                            <span class="lw-icon-tile">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Call us</p>
                                <a href="{{ $site['phone_href'] }}" class="text-sm font-bold text-slate-900 hover:text-blue-700">{{ $site['phone'] }}</a>
                            </div>
                        </li>
                        @if ($site['whatsapp_url'])
                            <li class="flex items-center gap-4">
                                <span class="lw-icon-tile bg-[#25D366] text-white">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">WhatsApp</p>
                                    <a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" class="text-sm font-bold text-[#128C7E] hover:underline">Chat with us</a>
                                </div>
                            </li>
                        @endif
                        <li class="flex items-center gap-4">
                            <span class="lw-icon-tile">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Opening hours</p>
                                <p class="text-sm font-bold text-slate-900">{{ $site['opening_hours'] }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8" data-lw-reveal>
                    @include('website.partials.enquiry-form')
                </div>
            </div>
        </section>
        @endif

        {{-- FAQ --}}
        @if (! empty($site['faq']['items']))
            <section id="faq" data-lw-section class="lw-section bg-slate-50">
                <div class="lw-container grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                    <div data-lw-reveal>
                        <span class="lw-eyebrow">FAQ</span>
                        <h2 class="lw-section-title">{{ $site['faq']['heading'] }}</h2>
                        <p class="mt-4 text-slate-500">Can't find what you're looking for? <a href="#enquiry" class="font-semibold text-blue-700 hover:underline">Send us a message</a> and we'll get back to you.</p>
                    </div>
                    <div class="space-y-3" data-lw-reveal>
                        @foreach ($site['faq']['items'] as $item)
                            <details class="lw-faq-item group rounded-2xl border border-slate-200 bg-white px-6 py-5 open:shadow-sm">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                                    {{ $item['question'] }}
                                    <span class="lw-faq-icon flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                    </span>
                                </summary>
                                <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ $item['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Rules --}}
        @if (! empty($site['rules']['items']))
            <section id="rules" data-lw-section class="lw-section">
                <div class="lw-container">
                    <div class="lw-section-head" data-lw-reveal>
                        <span class="lw-eyebrow">House rules</span>
                        <h2 class="lw-section-title">
                            {{ $site['rules']['heading'] }}
                            <span class="text-blue-700">{{ $site['rules']['heading_highlight'] }}</span>
                        </h2>
                        <p class="lw-section-lead">{{ $site['rules']['description'] }}</p>
                    </div>
                    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($site['rules']['items'] as $rule)
                            <article class="flex items-start gap-4 rounded-2xl border border-slate-200 p-5" data-lw-reveal>
                                <div class="lw-icon-tile">
                                    <x-website.icon :name="$rule['icon']" class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">{{ $rule['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $rule['description'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Contact / Location --}}
        <section id="contact" data-lw-section class="lw-section bg-slate-50">
            <div class="lw-container">
                <div class="lw-section-head" data-lw-reveal>
                    <span class="lw-eyebrow">{{ $site['contact']['heading'] }}</span>
                    <h2 class="lw-section-title">{{ $site['contact']['subheading'] }}</h2>
                    <p class="lw-section-lead">{{ $site['contact']['description'] }}</p>
                </div>
                <div class="mt-12 grid gap-5 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="lw-card flex flex-col p-7" data-lw-reveal>
                        <dl class="space-y-6 text-sm">
                            <div class="flex gap-4">
                                <span class="lw-icon-tile"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address</dt>
                                    <dd class="mt-1 font-medium leading-relaxed text-slate-800">
                                        {{ $site['address']['line1'] ?? '' }}<br>
                                        {{ $site['address']['line2'] ?? '' }}<br>
                                        {{ $site['address']['line3'] ?? '' }}
                                    </dd>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="lw-icon-tile"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></span>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</dt>
                                    <dd class="mt-1"><a href="{{ $site['phone_href'] }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $site['phone'] }}</a></dd>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="lw-icon-tile"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt>
                                    <dd class="mt-1"><a href="mailto:{{ $site['email'] }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $site['email'] }}</a></dd>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <span class="lw-icon-tile"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Opening hours</dt>
                                    <dd class="mt-1 font-medium text-slate-800">{{ $site['opening_hours'] }}</dd>
                                </div>
                            </div>
                        </dl>
                        <div class="mt-auto flex flex-wrap gap-3 pt-8">
                            <a href="{{ $site['map_directions_url'] }}" target="_blank" rel="noopener" class="lw-btn-primary">Get directions</a>
                            <a href="{{ $site['phone_href'] }}" class="lw-btn-secondary">Call now</a>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white" data-lw-reveal>
                        <iframe
                            title="Library location map"
                            src="{{ $site['map_embed_url'] }}"
                            class="h-full min-h-[380px] w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA band --}}
        <section class="py-16 sm:py-20">
            <div class="lw-container">
                <div class="relative overflow-clip rounded-3xl bg-blue-700 px-6 py-14 text-center sm:px-12" data-lw-reveal>
                    <div class="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-blue-400/30 blur-3xl"></div>
                    <h2 class="relative text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Your seat is waiting.</h2>
                    <p class="relative mx-auto mt-4 max-w-xl text-base text-blue-100">Join {{ $site['library_name'] }} and build a study routine that actually sticks.</p>
                    <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                        <a href="#enquiry" class="lw-btn-light">Pre-book a seat</a>
                        @if ($site['whatsapp_url'])
                            <a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" class="lw-btn-whatsapp">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp us
                            </a>
                        @else
                            <a href="{{ $site['phone_href'] }}" class="lw-btn-ghost-light">Call us</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('website.partials.footer')
@endsection
