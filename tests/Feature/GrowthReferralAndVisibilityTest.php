<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\LibraryGrowthProfile;
use App\Models\PlatformSetting;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use App\Services\Growth\LibraryGrowthScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthReferralAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'growth.enabled' => true,
            'libcontrol.modules.enquiries' => true,
            'libcontrol.license_server.enabled' => false,
        ]);

        PlatformSetting::current()->update([
            'website_enabled' => true,
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $this->branch = Branch::factory()->create(['require_student_contact' => false]);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    private function startCampaign(): void
    {
        $this->actingAs($this->user)
            ->post(route('growth.referral-campaign'), ['referral_offer_text' => 'Refer a friend & get ₹200 off'])
            ->assertRedirect();
        auth()->logout();
    }

    private function referrer(): Student
    {
        return Student::factory()->create([
            'branch_id' => $this->branch->id,
            'student_code' => 'LIB-007',
            'name' => 'Riya Sharma',
            'phone' => '9811111111',
        ]);
    }

    public function test_website_enquiry_with_student_id_creates_referral_when_campaign_live(): void
    {
        $this->startCampaign();
        $referrer = $this->referrer();

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Aman Gupta',
            'phone' => '9822222222',
            'referred_by' => 'lib-007',
        ])->assertCreated();

        $enquiry = Enquiry::query()->where('name', 'Aman Gupta')->firstOrFail();
        $this->assertDatabaseHas('referrals', [
            'enquiry_id' => $enquiry->id,
            'referrer_student_id' => $referrer->id,
            'status' => Referral::STATUS_ENQUIRY,
            'reward_status' => Referral::REWARD_NOT_DUE,
        ]);
    }

    public function test_website_enquiry_rejects_unknown_or_self_referral(): void
    {
        $this->startCampaign();
        $this->referrer();

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Aman Gupta',
            'phone' => '9822222222',
            'referred_by' => 'LIB-999',
        ])->assertStatus(422)->assertJsonValidationErrors(['referred_by']);

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Riya Again',
            'phone' => '9811111111',
            'referred_by' => 'LIB-007',
        ])->assertStatus(422)->assertJsonValidationErrors(['referred_by']);

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_referral_code_is_ignored_when_campaign_is_not_live(): void
    {
        $this->referrer();

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Aman Gupta',
            'phone' => '9822222222',
            'referred_by' => 'LIB-007',
        ])->assertCreated();

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_converting_referred_enquiry_makes_reward_due_and_reward_can_be_given(): void
    {
        $this->startCampaign();
        $referrer = $this->referrer();

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Aman Gupta',
            'phone' => '9822222222',
            'referred_by' => 'LIB-007',
        ])->assertCreated();

        $enquiry = Enquiry::query()->where('name', 'Aman Gupta')->firstOrFail();

        $this->actingAs($this->user)
            ->postJson(route('enquiries.convert', $enquiry))
            ->assertOk()
            ->assertJsonPath('enquiry.referred_by', 'Riya Sharma (LIB-007)');

        $referral = Referral::query()->firstOrFail();
        $this->assertSame(Referral::STATUS_JOINED, $referral->status);
        $this->assertSame(Referral::REWARD_DUE, $referral->reward_status);
        $this->assertNotNull($referral->joined_at);
        $this->assertDatabaseMissing('students', ['name' => 'Aman Gupta']);

        $this->actingAs($this->user)
            ->post(route('growth.referrals.reward', $referral))
            ->assertRedirect();

        $this->assertSame(Referral::REWARD_GIVEN, $referral->fresh()->reward_status);
        $this->assertNotNull($referral->fresh()->reward_given_at);
        $this->assertSame($referrer->id, $referral->referrer_student_id);
    }

    public function test_adding_student_with_referred_by_records_joined_referral(): void
    {
        $referrer = $this->referrer();

        $this->actingAs($this->user)->postJson(route('students.store'), [
            'name' => 'Neha Verma',
            'gender' => 'female',
            'date_of_birth' => '2002-04-10',
            'referred_by' => 'LIB-007',
        ])->assertCreated();

        $this->assertDatabaseHas('referrals', [
            'referrer_student_id' => $referrer->id,
            'referred_name' => 'Neha Verma',
            'status' => Referral::STATUS_JOINED,
            'reward_status' => Referral::REWARD_DUE,
        ]);

        $this->actingAs($this->user)->postJson(route('students.store'), [
            'name' => 'Bad Code',
            'gender' => 'female',
            'date_of_birth' => '2002-04-10',
            'referred_by' => 'NOPE-1',
        ])->assertStatus(422)->assertJsonValidationErrors(['referred_by']);
    }

    public function test_referral_score_counts_real_joins_not_the_toggle(): void
    {
        $this->startCampaign();
        $scores = app(LibraryGrowthScoreService::class);

        $item = collect($scores->scoreForBranch($this->branch, false)['items'])->firstWhere('key', 'referral');
        $this->assertSame(4, $item['points']);

        $referrer = $this->referrer();
        $friend = Student::factory()->create(['branch_id' => $this->branch->id, 'student_code' => 'LIB-008']);
        Referral::query()->create([
            'branch_id' => $this->branch->id,
            'referrer_student_id' => $referrer->id,
            'referred_student_id' => $friend->id,
            'referred_name' => $friend->name,
            'status' => Referral::STATUS_JOINED,
            'reward_status' => Referral::REWARD_DUE,
            'joined_at' => now(),
        ]);

        $item = collect($scores->scoreForBranch($this->branch, false)['items'])->firstWhere('key', 'referral');
        $this->assertSame(10, $item['points']);
    }

    public function test_google_checklist_needs_a_google_maps_link(): void
    {
        $this->actingAs($this->user)
            ->post(route('growth.profile.update'), ['gbp_claimed' => 1, 'gbp_photos' => 1])
            ->assertSessionHasErrors('google_maps_url');

        $this->actingAs($this->user)
            ->post(route('growth.profile.update'), ['gbp_claimed' => 1, 'google_maps_url' => 'https://example.com/my-library'])
            ->assertSessionHasErrors('google_maps_url');

        $this->actingAs($this->user)
            ->post(route('growth.profile.update'), [
                'gbp_claimed' => 1,
                'gbp_photos' => 1,
                'gbp_category' => 1,
                'gbp_hours' => 1,
                'google_maps_url' => 'maps.app.goo.gl/AbC123',
                'google_review_url' => 'https://g.page/r/xyz/review',
            ])
            ->assertSessionHasNoErrors();

        $profile = LibraryGrowthProfile::query()->where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertSame('https://maps.app.goo.gl/AbC123', $profile->google_maps_url);

        $item = collect(app(LibraryGrowthScoreService::class)->scoreForBranch($this->branch, false)['items'])->firstWhere('key', 'gbp');
        $this->assertSame(15, $item['points']);

        $profile->forceFill(['google_maps_url' => null])->save();
        $item = collect(app(LibraryGrowthScoreService::class)->scoreForBranch($this->branch, false)['items'])->firstWhere('key', 'gbp');
        $this->assertSame(0, $item['points']);
    }

    public function test_social_links_sync_between_growth_and_website_settings(): void
    {
        $this->actingAs($this->user)
            ->post(route('growth.profile.update'), [
                'facebook_url' => 'facebook.com/sunrise-library',
                'instagram_url' => 'https://instagram.com/sunrise',
            ])
            ->assertSessionHasNoErrors();

        $links = PlatformSetting::current()->fresh()->website_social_links;
        $this->assertSame('https://facebook.com/sunrise-library', $links['facebook']);
        $this->assertSame('https://instagram.com/sunrise', $links['instagram']);

        $this->actingAs($this->user)
            ->post(route('growth.profile.update'), ['facebook_url' => 'https://instagram.com/wrong'])
            ->assertSessionHasErrors('facebook_url');

        $admin = User::factory()->create(['branch_id' => null]);
        \App\Models\Admin::query()->create(['user_id' => $admin->id, 'admin_type' => \App\Models\Admin::TYPE_CLIENT]);

        $this->actingAs($admin)
            ->postJson(route('settings.website.update'), [
                'website_enabled' => 1,
                'website_social_links' => ['facebook' => 'https://facebook.com/new-page', 'instagram' => ''],
            ])
            ->assertOk();

        $profile = LibraryGrowthProfile::query()->where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertSame('https://facebook.com/new-page', $profile->facebook_url);
        $this->assertNull($profile->instagram_url);
    }

    public function test_growth_page_shows_services_and_referrals_without_review_kit(): void
    {
        $this->actingAs($this->user)
            ->get(route('growth.index'))
            ->assertOk()
            ->assertSee('Individual services')
            ->assertSee('Google Maps listing link')
            ->assertSee('Referral campaign')
            ->assertDontSee('Review request kit');
    }
}
