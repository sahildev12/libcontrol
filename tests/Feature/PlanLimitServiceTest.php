<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_enforces_branch_hall_and_seat_limits(): void
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create([
            'plan_tier' => 'starter',
            'student_code_padding' => 3,
        ]);

        $service = app(PlanLimitService::class);

        $this->assertSame(1, $service->limits()['max_branches']);
        $this->assertSame(5, $service->limits()['max_halls']);
        $this->assertSame(100, $service->limits()['max_seats']);

        Branch::factory()->create();
        Hall::factory()->create(['seat_capacity' => 90]);

        $this->assertFalse($service->canAddBranch());
        $this->assertTrue($service->canAddHall());
        $this->assertSame(10, $service->maxSeatCapacityForHall(null));
    }
}
