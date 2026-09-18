<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_page_uses_finance_url(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('finance.index'))
            ->assertOk()
            ->assertSee('Finance', false);
    }

    public function test_legacy_profit_loss_url_redirects_to_finance(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get('/profit-loss?tab=overview')
            ->assertRedirect('/finance?tab=overview');
    }

    public function test_finance_charts_endpoint_returns_expected_structure(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->getJson(route('finance.charts', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'collection_period' => 'this_month',
            'trend_months' => 6,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'fee_collection' => [
                    'expected',
                    'scheduled_due',
                    'received',
                    'collected_on_due',
                    'other_collected',
                    'pending',
                    'received_percentage',
                    'pending_percentage',
                ],
                'fee_income_vs_expenses' => [
                    'labels',
                    'student_fee_income',
                    'total_expenses',
                ],
                'income_expenses_profit' => [
                    'labels',
                    'total_income',
                    'total_expenses',
                    'total_profit',
                ],
            ]);
    }

    public function test_fee_collection_percentages_cap_when_received_exceeds_scheduled_due(): void
    {
        Carbon::setTestNow('2026-09-17 12:00:00');

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $booking = SeatBooking::create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'payment_plan' => 'installments',
            'fee_amount' => 10000,
            'joining_date' => '2026-09-01',
            'plan_expiry_date' => '2027-09-01',
            'status' => 'active',
        ]);

        FeeInstallment::create([
            'seat_booking_id' => $booking->id,
            'installment_number' => 1,
            'amount' => 8100,
            'due_date' => '2026-09-15',
        ]);

        FeePayment::create([
            'seat_booking_id' => $booking->id,
            'amount' => 61175,
            'payment_method' => 'cash',
            'payment_date' => '2026-09-10',
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('finance.charts', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-17',
            'collection_period' => 'this_month',
            'trend_months' => 6,
        ]));

        $response->assertOk();

        $fee = $response->json('fee_collection');
        $this->assertSame(8100.0, (float) $fee['expected']);
        $this->assertSame(8100.0, (float) $fee['scheduled_due']);
        $this->assertSame(61175.0, (float) $fee['received']);
        $this->assertSame(8100.0, (float) $fee['collected_on_due']);
        $this->assertSame(53075.0, (float) $fee['other_collected']);
        $this->assertSame(0.0, (float) $fee['pending']);
        $this->assertSame(100.0, (float) $fee['received_percentage']);
        $this->assertSame(0.0, (float) $fee['pending_percentage']);
        $this->assertSame(8100.0, (float) $fee['pie_received']);
        $this->assertSame(0.0, (float) $fee['pie_pending']);

        Carbon::setTestNow();
    }

    public function test_fee_collection_includes_monthly_recurring_expectations(): void
    {
        Carbon::setTestNow('2026-09-17 12:00:00');

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        foreach ([4500, 3500, 6000] as $index => $feeAmount) {
            $student = Student::factory()->create(['branch_id' => $branch->id]);

            SeatBooking::create([
                'seat_id' => $seat->id,
                'student_id' => $student->id,
                'time_slot' => 'full_day',
                'fee_type' => 'monthly',
                'payment_plan' => 'full',
                'fee_amount' => $feeAmount,
                'joining_date' => '2026-08-01',
                'plan_expiry_date' => '2027-08-01',
                'status' => 'active',
            ]);

            FeePayment::create([
                'seat_booking_id' => SeatBooking::query()->latest('id')->value('id'),
                'amount' => $feeAmount,
                'payment_method' => 'cash',
                'payment_date' => '2026-09-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'received_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('finance.charts', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-17',
            'collection_period' => 'this_month',
            'trend_months' => 6,
        ]));

        $response->assertOk();

        $fee = $response->json('fee_collection');
        $this->assertSame(14000.0, (float) $fee['scheduled_due']);
        $this->assertSame(14000.0, (float) $fee['received']);
        $this->assertSame(0.0, (float) $fee['other_collected']);
        $this->assertSame(100.0, (float) $fee['received_percentage']);

        Carbon::setTestNow();
    }

    public function test_trend_charts_use_full_month_window_independent_of_date_from(): void
    {
        Carbon::setTestNow('2026-09-17 12:00:00');

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Expense::create([
            'branch_id' => $branch->id,
            'category' => 'rent',
            'title' => 'August rent',
            'amount' => 5000,
            'expense_date' => '2026-08-15',
            'payment_method' => 'cash',
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('finance.charts', [
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-17',
            'collection_period' => 'this_month',
            'trend_months' => 6,
        ]));

        $response->assertOk();

        $labels = $response->json('fee_income_vs_expenses.labels');
        $expenses = $response->json('fee_income_vs_expenses.total_expenses');

        $this->assertCount(6, $labels);
        $this->assertSame('Apr 2026', $labels[0]);
        $this->assertSame('Sep 2026', $labels[5]);
        $this->assertSame(5000.0, (float) ($expenses[4] ?? 0));

        Carbon::setTestNow();
    }
}
