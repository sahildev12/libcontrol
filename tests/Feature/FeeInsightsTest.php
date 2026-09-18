<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeeInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_management_page_shows_insight_cards(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'status' => 'active',
            'fee_amount' => 2000,
            'amount_paid' => 500,
            'joining_date' => now()->subMonth()->toDateString(),
            'plan_expiry_date' => now()->addDays(3)->toDateString(),
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        FeePayment::create([
            'seat_booking_id' => $booking->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('fees.index'))
            ->assertOk()
            ->assertSee('Received This Month', false)
            ->assertSee('Pending This Month', false)
            ->assertSee('Total Outstanding', false)
            ->assertSee('Expiring Soon', false)
            ->assertSee('₹500.00', false);
    }

    public function test_insight_summary_calculates_monthly_and_outstanding_metrics(): void
    {
        Carbon::setTestNow('2026-09-18 12:00:00');

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'status' => 'active',
            'fee_amount' => 3000,
            'amount_paid' => 1000,
            'joining_date' => '2026-09-01',
            'plan_expiry_date' => '2026-09-25',
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        FeePayment::create([
            'seat_booking_id' => $booking->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => '2026-09-10',
        ]);

        $insights = app(FeeService::class)->insightSummary($branch->id);

        $this->assertSame('September 2026', $insights['month_label']);
        $this->assertSame(1000.0, $insights['received']);
        $this->assertSame(1, $insights['payment_count']);
        $this->assertSame(2000.0, $insights['outstanding']);
        $this->assertSame(1, $insights['active_plans']);
        $this->assertSame(1, $insights['expiring_soon_count']);

        Carbon::setTestNow();
    }
}
