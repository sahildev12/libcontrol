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
        $this->assertSame(1, $service->limits()['max_halls']);
        $this->assertSame(35, $service->limits()['max_seats']);

        Branch::factory()->create();
        Hall::factory()->create(['seat_capacity' => 30]);

        $this->assertFalse($service->canAddBranch());
        $this->assertFalse($service->canAddHall());
        $this->assertSame(5, $service->maxSeatCapacityForHall(null));
    }

    public function test_seat_limit_has_no_free_seat_once_full_and_over_limit_halls_can_only_shrink(): void
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create(['plan_tier' => 'pro', 'student_code_padding' => 3]);
        $service = app(PlanLimitService::class);

        Branch::factory()->create();
        $hall = Hall::factory()->create(['seat_capacity' => 60]);
        Hall::factory()->create(['seat_capacity' => 48]);

        $this->assertSame(0, $service->maxSeatCapacityForHall(null));

        try {
            $service->assertSeatCapacity(1);
            $this->fail('A new hall must not get a seat when the plan is full.');
        } catch (\Illuminate\Validation\ValidationException) {
        }

        $service->assertSeatCapacity(60, $hall->id);
        $service->assertSeatCapacity(55, $hall->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->assertSeatCapacity(61, $hall->id);
    }
}
