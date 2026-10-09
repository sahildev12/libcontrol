<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\Growth\LibraryBusinessPerformanceService;
use App\Services\Growth\LibraryCombinedGrowthScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'growth.enabled' => true,
            'libcontrol.modules.enquiries' => true,
        ]);
    }

    public function test_business_performance_uses_agreed_weights_and_renormalizes_missing_data(): void
    {
        $branch = Branch::factory()->create([
            'phone' => '9876543210',
            'address' => 'Hisar',
            'is_open_24_hours' => true,
        ]);

        $result = app(LibraryBusinessPerformanceService::class)->scoreForLibrary(null, $branch->id);

        $this->assertSame(100, $result['max']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertCount(8, $result['factors']);

        $keys = collect($result['factors'])->pluck('key')->all();
        $this->assertSame([
            'profile_completion',
            'occupancy',
            'renewals',
            'branch_staff',
            'membership_payment',
            'expenses_finance',
            'operations',
            'system_usage',
        ], $keys);

        $weightSum = round(collect($result['factors'])->sum('weight'), 2);
        $this->assertEquals(1.0, $weightSum);
    }

    public function test_occupancy_factor_scores_from_seat_utilization(): void
    {
        $branch = Branch::factory()->create([
            'phone' => '9876543210',
            'is_open_24_hours' => true,
        ]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'seat_capacity' => 2]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'phone' => '9876500001']);

        SeatBooking::query()->create([
            'seat_id' => $seatA->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'status' => 'active',
            'fee_type' => 'monthly',
            'joining_date' => now()->subDays(10)->toDateString(),
            'plan_expiry_date' => now()->addDays(20)->toDateString(),
            'fee_amount' => 1000,
            'amount_paid' => 1000,
        ]);

        $result = app(LibraryBusinessPerformanceService::class)->scoreForLibrary(null, $branch->id);
        $occupancy = collect($result['factors'])->firstWhere('key', 'occupancy');

        $this->assertTrue($occupancy['available']);
        $this->assertGreaterThan(0, $occupancy['score']);
        $this->assertLessThanOrEqual(100, $occupancy['score']);
        // 1 of 2 seats occupied → ~50%
        $this->assertEqualsWithDelta(50.0, $occupancy['score'], 1.0);
        unset($seatB);
    }

    public function test_growth_score_combines_action_and_business_weights(): void
    {
        $branch = Branch::factory()->create();
        $combined = app(LibraryCombinedGrowthScoreService::class)->forBranch($branch);

        $expected = (int) round(
            ($combined['action_pct'] * 0.40) + ($combined['business_pct'] * 0.60)
        );

        $this->assertSame($expected, $combined['growth_score']);
        $this->assertSame(0.4, $combined['formula']['action_weight']);
        $this->assertSame(0.6, $combined['formula']['business_weight']);
    }

    public function test_dashboard_shows_business_performance_and_growth_score(): void
    {
        $branch = Branch::factory()->create([
            'phone' => '9876543210',
            'is_open_24_hours' => true,
        ]);
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Growth Score', false)
            ->assertSee('Business Performance', false)
            ->assertDontSee('Profile Completion', false)
            ->assertSee('Recommendations', false)
            ->assertSee('Attention Required', false)
            ->assertDontSee('Seat Utilization', false);
    }

    public function test_settings_shows_business_performance_tab_for_client_admin(): void
    {
        Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('settings.index', ['tab' => 'business']))
            ->assertOk()
            ->assertSee('Business Performance', false)
            ->assertSee('Occupancy &amp; Seat Utilization', false)
            ->assertSee('System Usage &amp; Activity', false);
    }
}
