<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\LibraryGrowthProfile;
use App\Models\User;
use App\Services\Growth\LibraryGrowthRecommendationService;
use App\Services\Growth\LibraryGrowthScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthRecommendationsTest extends TestCase
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

    public function test_free_library_gets_three_easiest_recommendations_without_expert(): void
    {
        $branch = Branch::factory()->create();

        $recs = $this->recommendationsFor($branch);

        $this->assertFalse($recs['has_package']);
        $this->assertCount(3, $recs['unlocked']);
        $this->assertNotEmpty($recs['locked']);

        foreach ($recs['unlocked'] as $rec) {
            $this->assertSame('easy', $rec['effort']);
            $this->assertNull($rec['expert_service']);
            $this->assertTrue($rec['expert_available'] ?? false);
            $this->assertNotEmpty($rec['diy_steps']);
        }

        foreach ($recs['locked'] as $rec) {
            $this->assertSame(['key', 'gain'], array_keys($rec));
        }

        $this->assertSame((int) collect($recs['locked'])->sum('gain'), $recs['locked_gain']);
    }

    public function test_active_package_unlocks_all_recommendations_with_expert(): void
    {
        $branch = Branch::factory()->create();
        LibraryGrowthProfile::query()->create([
            'branch_id' => $branch->id,
            'active_package' => 'grow',
            'package_active_until' => now()->addMonth(),
        ]);

        $recs = $this->recommendationsFor($branch);

        $this->assertTrue($recs['has_package']);
        $this->assertSame([], $recs['locked']);
        $this->assertNotEmpty($recs['unlocked']);

        foreach ($recs['unlocked'] as $rec) {
            $this->assertNotEmpty($rec['expert_service']);
        }
    }

    public function test_expired_package_does_not_unlock(): void
    {
        $branch = Branch::factory()->create();
        LibraryGrowthProfile::query()->create([
            'branch_id' => $branch->id,
            'active_package' => 'grow',
            'package_active_until' => now()->subDay(),
        ]);

        $recs = $this->recommendationsFor($branch);

        $this->assertFalse($recs['has_package']);
        $this->assertCount(3, $recs['unlocked']);
    }

    public function test_locked_item_completed_manually_still_scores(): void
    {
        $branch = Branch::factory()->create();
        $before = $this->recommendationsFor($branch);
        $this->assertContains('reviews', collect($before['locked'])->pluck('key')->all());

        LibraryGrowthProfile::query()->updateOrCreate(
            ['branch_id' => $branch->id],
            ['google_review_count' => (int) config('growth.review_target', 50)]
        );

        $score = app(LibraryGrowthScoreService::class)->scoreForBranch($branch, false);
        $reviews = collect($score['items'])->firstWhere('key', 'reviews');
        $this->assertSame($reviews['max_points'], $reviews['points']);

        $after = app(LibraryGrowthRecommendationService::class)->fromActionProgress($score);
        $this->assertContains('reviews', collect($after['completed'])->pluck('key')->all());
        $this->assertNotContains('reviews', collect($after['locked'])->pluck('key')->all());
    }

    public function test_hire_expert_from_recommendation_requires_package(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->postJson(route('growth.services.request'), [
                'item_key' => 'gbp_boost',
                'source' => 'recommendation',
            ])
            ->assertForbidden()
            ->assertJsonStructure(['message', 'packages_url']);

        $this->assertDatabaseMissing('growth_orders', [
            'branch_id' => $branch->id,
            'item_key' => 'gbp_boost',
        ]);
    }

    public function test_hire_expert_from_recommendation_allowed_with_package(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        LibraryGrowthProfile::query()->create([
            'branch_id' => $branch->id,
            'active_package' => 'starter',
            'package_active_until' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->postJson(route('growth.services.request'), [
                'item_key' => 'gbp_boost',
                'source' => 'recommendation',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('growth_orders', [
            'branch_id' => $branch->id,
            'item_key' => 'gbp_boost',
            'order_type' => 'service',
        ]);
    }

    public function test_dashboard_shows_free_recommendations_and_unlock_banner(): void
    {
        Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $lockedTitle = (string) config('growth.recommendations.items.instagram.title');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recommendations', false)
            ->assertSee('More recommendations', false)
            ->assertSee('Upgrade to Pro', false)
            ->assertDontSee('View more recommendations', false)
            ->assertDontSee('Locked recommendation', false)
            ->assertDontSee('Do it yourself', false)
            ->assertSee('Hire an expert (Pro)', false)
            ->assertDontSee('>Hire an expert</', false)
            ->assertDontSee($lockedTitle, false);
    }

    public function test_growth_hub_does_not_expose_locked_checklist_details_in_html(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $score = app(LibraryGrowthScoreService::class)->scoreForBranch($branch, false);
        $lockedDetail = (string) collect($score['items'])->firstWhere('key', 'instagram')['detail'];

        $this->actingAs($user)
            ->get(route('growth.index'))
            ->assertOk()
            ->assertSee('More recommendations with Pro', false)
            ->assertDontSee($lockedDetail, false);
    }

    public function test_dashboard_shows_hire_expert_with_package(): void
    {
        $branch = Branch::factory()->create();
        LibraryGrowthProfile::query()->create([
            'branch_id' => $branch->id,
            'active_package' => 'dominate',
            'package_active_until' => now()->addMonth(),
        ]);
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Hire an expert', false)
            ->assertDontSee('View packages', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function recommendationsFor(Branch $branch): array
    {
        $score = app(LibraryGrowthScoreService::class)->scoreForBranch($branch, false);

        return app(LibraryGrowthRecommendationService::class)->fromActionProgress($score);
    }
}
