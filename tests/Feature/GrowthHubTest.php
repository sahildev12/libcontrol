<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\User;
use App\Services\Growth\LibraryGrowthScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrowthHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'growth.enabled' => true,
            'libcontrol.modules.enquiries' => true,
            'libcontrol.license_server.enabled' => false,
        ]);
    }

    public function test_growth_score_increases_when_checklist_and_enquiries_present(): void
    {
        $branch = Branch::factory()->create();
        $profile = LibraryGrowthProfile::query()->create([
            'branch_id' => $branch->id,
            'gbp_claimed' => true,
            'gbp_photos' => true,
            'gbp_category' => true,
            'gbp_hours' => true,
            'google_maps_url' => 'https://maps.app.goo.gl/abc123',
            'google_review_count' => 50,
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'https://instagram.com/example',
            'last_whatsapp_campaign_at' => now(),
            'referral_campaign_active' => true,
            'active_package' => 'grow',
            'package_active_until' => now()->addMonth(),
        ]);

        Enquiry::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Lead',
            'phone' => '9876543210',
            'status' => 'new',
        ]);

        $result = app(LibraryGrowthScoreService::class)->scoreForBranch($branch);

        $this->assertGreaterThanOrEqual(70, $result['score']);
        $this->assertSame($result['score'], $profile->fresh()->score_cached);
    }

    public function test_client_admin_can_open_growth_hub(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('growth.index'))
            ->assertOk()
            ->assertSee('Grow My Library', false)
            ->assertSee('Recommended next steps', false)
            ->assertSee('Growth Score', false)
            ->assertSee('Starter', false)
            ->assertSee('Dominate', false);
    }

    public function test_package_request_creates_growth_order_and_local_ticket(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 99], 201),
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        config([
            'libcontrol.deployment.license_key' => 'test-license-key-not-placeholder-123456',
        ]);

        $this->actingAs($user)
            ->postJson(route('growth.packages.request'), [
                'item_key' => 'starter',
                'message' => 'Please call me',
            ])
            ->assertCreated()
            ->assertJsonPath('order.item_name', 'Starter');

        $this->assertDatabaseHas('growth_orders', [
            'item_key' => 'starter',
            'order_type' => 'package',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_service_request_emails_phenomit_with_client_details(): void
    {
        Http::fake(['*' => Http::response(['id' => 99], 201)]);
        \Illuminate\Support\Facades\Mail::fake();
        config(['growth.notify_email' => 'contact@phenomit.com, sales@phenomit.com']);

        $branch = Branch::factory()->create(['name' => 'Sunrise Study Hall', 'phone' => '9876500000']);
        $user = User::factory()->create(['branch_id' => $branch->id, 'name' => 'Ravi Owner', 'email' => 'ravi@example.com']);

        $this->actingAs($user)
            ->postJson(route('growth.services.request'), ['item_key' => 'gbp_boost'])
            ->assertCreated()
            ->assertJsonPath('message', 'Request sent to Phenomit. Our team will contact you shortly.');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\GrowthOrderRequestedMail::class, function ($mail) {
            $html = $mail->render();

            return $mail->hasTo('contact@phenomit.com')
                && $mail->hasTo('sales@phenomit.com')
                && $mail->hasReplyTo('ravi@example.com')
                && str_contains($html, 'Google Business Profile Boost')
                && str_contains($html, 'Ravi Owner')
                && str_contains($html, 'Sunrise Study Hall')
                && str_contains($html, '9876500000');
        });
    }

    public function test_public_website_enquiry_creates_enquiry(): void
    {
        Branch::factory()->create();
        \App\Models\PlatformSetting::current()->update(['website_enabled' => true]);

        $this->postJson(route('website.enquiries.store'), [
            'name' => 'Website Lead',
            'phone' => '9876543210',
            'email' => 'lead@example.com',
            'message' => 'Need a seat',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Thank you! We received your enquiry and will contact you soon.');

        $this->assertDatabaseHas('enquiries', [
            'name' => 'Website Lead',
            'phone' => '9876543210',
            'status' => 'new',
        ]);
    }

    public function test_referral_campaign_updates_profile(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->post(route('growth.referral-campaign'), [
                'referral_offer_text' => 'Refer a friend & get ₹300 off',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('library_growth_profiles', [
            'branch_id' => $branch->id,
            'referral_campaign_active' => 1,
            'referral_offer_text' => 'Refer a friend & get ₹300 off',
        ]);
    }

    public function test_package_request_completes_local_seo_checklist_points(): void
    {
        $branch = Branch::factory()->create();
        GrowthOrder::query()->create([
            'branch_id' => $branch->id,
            'order_type' => 'package',
            'item_key' => 'starter',
            'item_name' => 'Starter',
            'status' => GrowthOrder::STATUS_NEW,
        ]);

        $result = app(LibraryGrowthScoreService::class)->scoreForBranch($branch, false);
        $localSeo = collect($result['items'])->firstWhere('key', 'local_seo');

        $this->assertSame('ok', $localSeo['status']);
        $this->assertSame(10, $localSeo['points']);
    }

    public function test_website_social_links_count_toward_instagram_and_facebook_score(): void
    {
        $branch = Branch::factory()->create();
        \App\Models\PlatformSetting::current()->update([
            'website_social_links' => [
                'instagram' => 'https://instagram.com/demo-library',
                'facebook' => 'https://facebook.com/demo-library',
            ],
        ]);

        $result = app(LibraryGrowthScoreService::class)->scoreForBranch($branch, false);
        $instagram = collect($result['items'])->firstWhere('key', 'instagram');
        $facebook = collect($result['items'])->firstWhere('key', 'facebook');

        $this->assertSame(5, $instagram['points']);
        $this->assertSame(5, $facebook['points']);
    }
}
