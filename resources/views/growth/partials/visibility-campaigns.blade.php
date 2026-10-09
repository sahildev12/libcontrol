@php
    $hasOld = session()->hasOldInput();
    $hasMapsLink = \App\Services\Growth\LibraryGrowthScoreService::isGoogleMapsUrl($profile->google_maps_url);
    $ticked = static fn (string $key): bool => $hasOld ? (bool) old($key) : ($hasMapsLink && (bool) $profile->{$key});
    $gbpChecks = [
        'gbp_claimed' => 'Google Business Profile claimed',
        'gbp_photos' => 'Photos uploaded',
        'gbp_category' => 'Correct category set (e.g. "Library")',
        'gbp_hours' => 'Business hours published',
    ];
    $fieldClass = static fn (string $key): string => 'lc-growth-input'.($errors->has($key) ? ' is-invalid' : '');

    $referralActive = (bool) $profile->referral_campaign_active;
    $offer = $profile->referral_offer_text ?: 'Refer a friend & get ₹200 off';
    $shareMessage = "{$libraryName} referral offer: {$offer}!\n\n"
        ."Bring a friend to study with us. Ask them to give your Student ID ([your Student ID]) when they "
        .($websiteUrl ? "send an enquiry at {$websiteUrl} or join at the desk." : 'enquire or join at the desk.')
        ."\nYou get the reward once they join.";
    $rewardLabels = [
        \App\Models\Referral::REWARD_NOT_DUE => 'Waiting to join',
        \App\Models\Referral::REWARD_DUE => 'Reward due',
        \App\Models\Referral::REWARD_GIVEN => 'Given',
    ];
@endphp

<section id="visibility-form">
    <h2 class="lc-growth-section-title">Visibility &amp; campaigns</h2>
    <p class="lc-growth-section-sub">Keep your Google and social presence up to date, and let your students bring their friends.</p>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <form
            method="POST"
            action="{{ route('growth.profile.update') }}"
            class="lc-growth-panel lc-growth-card__pad"
            novalidate
            x-data="{
                mapsUrl: @js((string) old('google_maps_url', $profile->google_maps_url ?? '')),
                get mapsOk() {
                    return /^(https?:\/\/)?((www\.)?google\.[a-z.]+\/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl\/maps|g\.page|g\.co\/kgs|share\.google)(\/\S*)?$/i.test(this.mapsUrl.trim());
                },
            }"
        >
            @csrf
            <h3 class="text-sm font-bold text-[#082D70]">Visibility checklist</h3>
            <p class="mt-1 text-xs text-[#64748B]">This updates your Growth Score.</p>

            <div class="lc-growth-group">
                <p class="lc-growth-group__title">Google Business Profile</p>
                <label for="growth-maps-url" class="lc-growth-field-label">Google Maps listing link</label>
                <div class="flex gap-2">
                    <input id="growth-maps-url" type="url" name="google_maps_url" x-model="mapsUrl" maxlength="500" placeholder="https://maps.app.goo.gl/…" class="{{ $fieldClass('google_maps_url') }}">
                    @if ($hasMapsLink)
                        <a href="{{ $profile->google_maps_url }}" target="_blank" rel="noopener" class="lc-growth-btn-outline mt-1 shrink-0">Open</a>
                    @endif
                </div>
                @error('google_maps_url')
                    <p class="lc-growth-error">{{ $message }}</p>
                @else
                    <p class="lc-growth-hint">On Google Maps, open your library → <strong>Share</strong> → <strong>Copy link</strong>. The checklist unlocks once a Google Maps link is added.</p>
                @enderror

                <div class="mt-3 space-y-2">
                    @foreach ($gbpChecks as $key => $label)
                        <label class="lc-growth-check">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($ticked($key)) :disabled="! mapsOk" class="rounded border-gray-300 text-[#2563EB] disabled:opacity-40">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="lc-growth-group">
                <p class="lc-growth-group__title">Google reviews</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="growth-review-count" class="lc-growth-field-label">Review count</label>
                        <input id="growth-review-count" type="number" min="0" max="100000" name="google_review_count" value="{{ old('google_review_count', $profile->google_review_count) }}" class="{{ $fieldClass('google_review_count') }}">
                        @error('google_review_count')<p class="lc-growth-error">{{ $message }}</p>@else<p class="lc-growth-hint">Target: {{ $reviewTarget }}+ reviews.</p>@enderror
                    </div>
                    <div>
                        <label for="growth-review-url" class="lc-growth-field-label">Review link</label>
                        <input id="growth-review-url" type="url" name="google_review_url" maxlength="500" value="{{ old('google_review_url', $profile->google_review_url) }}" placeholder="https://g.page/r/…/review" class="{{ $fieldClass('google_review_url') }}">
                        @error('google_review_url')<p class="lc-growth-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="lc-growth-group">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="lc-growth-group__title !mb-0">Social pages</p>
                </div>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="growth-facebook" class="lc-growth-field-label">Facebook page</label>
                        <input id="growth-facebook" type="url" name="facebook_url" maxlength="255" value="{{ old('facebook_url', ($socialLinks['facebook'] ?? '') ?: $profile->facebook_url) }}" placeholder="https://facebook.com/yourlibrary" class="{{ $fieldClass('facebook_url') }}">
                        @error('facebook_url')<p class="lc-growth-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="growth-instagram" class="lc-growth-field-label">Instagram profile</label>
                        <input id="growth-instagram" type="url" name="instagram_url" maxlength="255" value="{{ old('instagram_url', ($socialLinks['instagram'] ?? '') ?: $profile->instagram_url) }}" placeholder="https://instagram.com/yourlibrary" class="{{ $fieldClass('instagram_url') }}">
                        @error('instagram_url')<p class="lc-growth-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="lc-growth-btn-navy mt-4">Save &amp; refresh score</button>
        </form>

        <div id="referral-kit" class="lc-growth-panel lc-growth-card__pad" x-data="{ copied: false }">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-[#082D70]">Referral campaign</h3>
                    <p class="mt-1 text-xs text-[#64748B]">Reward students who bring friends. Each student’s ID is their referral code.</p>
                </div>
                <span class="lc-growth-status {{ $referralActive ? 'lc-growth-status--live' : 'lc-growth-status--paused' }}">
                    <span class="size-1.5 rounded-full {{ $referralActive ? 'bg-green-600' : 'bg-slate-400' }}"></span>
                    {{ $referralActive ? 'Live' : ($profile->referral_offer_text ? 'Paused' : 'Not started') }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="lc-growth-stat">
                    <p class="lc-growth-stat__value">{{ $referralSummary['total'] }}</p>
                    <p class="lc-growth-stat__label">Referrals</p>
                </div>
                <div class="lc-growth-stat">
                    <p class="lc-growth-stat__value">{{ $referralSummary['joined'] }}</p>
                    <p class="lc-growth-stat__label">Joined</p>
                </div>
                <div class="lc-growth-stat">
                    <p class="lc-growth-stat__value {{ $referralSummary['rewards_due'] > 0 ? '!text-amber-600' : '' }}">{{ $referralSummary['rewards_due'] }}</p>
                    <p class="lc-growth-stat__label">Rewards due</p>
                </div>
            </div>

            <form method="POST" action="{{ route('growth.referral-campaign') }}" class="lc-growth-group">
                @csrf
                <label for="growth-referral-offer" class="lc-growth-field-label">Offer</label>
                <input id="growth-referral-offer" type="text" name="referral_offer_text" required minlength="5" maxlength="255" value="{{ old('referral_offer_text', $offer) }}" class="{{ $fieldClass('referral_offer_text') }}">
                @error('referral_offer_text')<p class="lc-growth-error">{{ $message }}</p>@else<p class="lc-growth-hint">Shown on your website enquiry form while the campaign is live.</p>@enderror
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit" class="lc-growth-btn-navy">{{ $referralActive ? 'Update offer' : 'Start campaign' }}</button>
                    @if ($referralActive)
                        <button type="submit" form="growth-referral-stop" class="lc-growth-btn-outline">Pause</button>
                    @endif
                </div>
            </form>
            @if ($referralActive)
                <form id="growth-referral-stop" method="POST" action="{{ route('growth.referral-campaign.stop') }}" class="hidden">@csrf</form>
            @endif

            <div class="lc-growth-group">
                <p class="lc-growth-group__title">How it works</p>
                <ol class="lc-growth-steps">
                    <li><span>Send the message below to your students (WhatsApp, notice board, Promotion email).</span></li>
                    <li><span>Their friend gives the student’s ID in the website enquiry form, or you enter it in <strong>Referred by</strong> when adding the student.</span></li>
                    <li><span>When the friend joins, the reward shows as <strong>due</strong> below — give it (e.g. fee discount) and mark it given.</span></li>
                </ol>
            </div>

            <div class="lc-growth-group">
                <div class="flex items-center justify-between gap-2">
                    <p class="lc-growth-group__title !mb-0">Message for students</p>
                    <button type="button" class="lc-growth-btn-outline !min-h-[30px] !px-2.5 !text-[11px]" @click="navigator.clipboard.writeText($refs.share.value).then(() => { copied = true; setTimeout(() => copied = false, 1600); })" x-text="copied ? 'Copied!' : 'Copy'">Copy</button>
                </div>
                <textarea x-ref="share" readonly rows="4" class="lc-growth-input mt-2 bg-white/70 text-xs leading-relaxed">{{ $shareMessage }}</textarea>
                <p class="lc-growth-hint">Also send it by email from <a href="{{ route('promotion.index') }}" class="font-semibold text-[#2563EB] hover:underline">Promotion</a>.</p>
            </div>
        </div>
    </div>

    <div id="referrals" class="lc-growth-card mt-4 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#E2E8F0] px-5 py-3">
            <h3 class="text-sm font-bold text-[#082D70]">Referrals</h3>
            <p class="text-xs text-[#64748B]">{{ $referralSummary['rewards_given'] }} reward{{ $referralSummary['rewards_given'] === 1 ? '' : 's' }} given so far</p>
        </div>
        @if ($referralSummary['items'] === [])
            <p class="px-5 py-6 text-center text-sm text-[#64748B]">No referrals yet. Start the campaign and share the message with your students.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-[#F8FAFC] text-left text-[11px] font-semibold uppercase tracking-wide text-[#64748B]">
                        <tr>
                            <th class="px-5 py-2.5">Date</th>
                            <th class="px-3 py-2.5">Referred by</th>
                            <th class="px-3 py-2.5">Friend</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-5 py-2.5 text-right">Reward</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F1F5F9] text-[#334155]">
                        @foreach ($referralSummary['items'] as $row)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-[#64748B]">{{ $row['created_at'] }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-[#0F172A]">{{ $row['referrer_name'] ?? '—' }}</p>
                                    <p class="text-xs text-[#64748B]">{{ $row['referrer_code'] }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-[#0F172A]">{{ $row['friend_name'] }}</p>
                                    <p class="text-xs text-[#64748B]">{{ $row['friend_code'] ?? $row['friend_phone'] ?? '' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($row['status'] === \App\Models\Referral::STATUS_JOINED)
                                        <span class="lc-growth-status lc-growth-status--live">Joined</span>
                                    @else
                                        <span class="lc-growth-status bg-sky-50 text-sky-700">Enquiry</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($row['reward_status'] === \App\Models\Referral::REWARD_DUE)
                                        <form method="POST" action="{{ route('growth.referrals.reward', $row['id']) }}" x-on:submit.prevent="if (await confirmDialog({ title: 'Reward given', message: {{ \Illuminate\Support\Js::from('Mark the reward as given to '.($row['referrer_name'] ?? 'this student').'?') }}, confirmLabel: 'Mark given', tone: 'primary' })) $el.submit()">
                                            @csrf
                                            <button type="submit" class="lc-growth-btn-navy !min-h-[30px] !text-[11px]">Mark reward given</button>
                                        </form>
                                    @elseif ($row['reward_status'] === \App\Models\Referral::REWARD_GIVEN)
                                        <span class="text-xs font-semibold text-green-700">Given · {{ $row['reward_given_at'] }}</span>
                                    @else
                                        <span class="text-xs text-[#94A3B8]">{{ $rewardLabels[$row['reward_status']] ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@if ($errors->hasAny(['google_maps_url', 'google_review_count', 'google_review_url', 'facebook_url', 'instagram_url', 'referral_offer_text']))
    <script>
        document.addEventListener('DOMContentLoaded', () => document.getElementById('visibility-form')?.scrollIntoView({ block: 'start' }));
    </script>
@endif
