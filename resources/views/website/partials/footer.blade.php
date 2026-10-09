<footer class="border-t border-slate-200 bg-white">
    <div class="lw-container py-14">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                <p class="text-base font-extrabold text-slate-900">{{ $site['library_name'] }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $site['subtitle'] }}</p>
                @if (filled($site['footer_tagline'] ?? ''))
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-500">{{ $site['footer_tagline'] }}</p>
                @endif
                <div class="mt-5 flex gap-2">
                    @if (! empty($site['social_links']['instagram']))
                        <a href="{{ $site['social_links']['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:text-blue-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm0 2a3 3 0 00-3 3v10a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H7zm5 3.5a4.5 4.5 0 110 9 4.5 4.5 0 010-9zm0 2a2.5 2.5 0 100 5 2.5 2.5 0 000-5zm5-3a1 1 0 110 2 1 1 0 010-2z"/></svg>
                        </a>
                    @endif
                    @if (! empty($site['social_links']['facebook']))
                        <a href="{{ $site['social_links']['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:text-blue-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14 8V6.5c0-.8.2-1.5 1.5-1.5H17V2h-2.6C11.4 2 10.5 3.9 10.5 6.3V8H8v3h2.5v11H14V11h2.6l.4-3h-3z"/></svg>
                        </a>
                    @endif
                    @if (! empty($site['social_links']['youtube']))
                        <a href="{{ $site['social_links']['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:text-blue-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 00.5 6.2 31 31 0 000 12a31 31 0 00.5 5.8 3 3 0 002.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 002.1-2.1A31 31 0 0024 12a31 31 0 00-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>
                        </a>
                    @endif
                    @if (! empty($site['whatsapp_url']))
                        <a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-emerald-200 hover:text-emerald-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.05 0C5.5 0 .16 5.33.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.9A11.82 11.82 0 0012.05 0zm0 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88a9.88 9.88 0 019.88 9.89c0 5.45-4.43 9.88-9.88 9.88zm5.42-7.41c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.59-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35z"/></svg>
                        </a>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-900">Explore</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                    @foreach (array_slice($site['navigation'], 0, 5) as $item)
                        <li><a href="{{ $item['href'] }}" class="hover:text-blue-700">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-900">Members</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                    @foreach (array_slice($site['navigation'], 5) as $item)
                        <li><a href="{{ $item['href'] }}" class="hover:text-blue-700">{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a href="#enquiry" class="hover:text-blue-700">Pre-book a seat</a></li>
                    <li><a href="{{ $site['branch_login_url'] }}" class="hover:text-blue-700">Branch login</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-900">Get in touch</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                    <li><a href="{{ $site['phone_href'] }}" class="hover:text-blue-700">{{ $site['phone'] }}</a></li>
                    <li><a href="mailto:{{ $site['email'] }}" class="break-all hover:text-blue-700">{{ $site['email'] }}</a></li>
                    <li class="leading-relaxed">{{ implode(', ', array_filter([rtrim($site['address']['line1'] ?? '', ', '), $site['address']['line3'] ?? ''])) }}</li>
                    <li>{{ $site['opening_hours'] }}</li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-2 border-t border-slate-200 pt-6 text-xs text-slate-400 sm:flex-row">
            <p>© {{ now()->year }} {{ $site['library_name'] }}. All rights reserved.</p>
            <a href="#home" class="hover:text-slate-600">Back to top ↑</a>
        </div>
    </div>
</footer>
