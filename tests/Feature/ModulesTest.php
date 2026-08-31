<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_can_update_branch_details(): void
    {
        $branch = Branch::factory()->create(['name' => 'Main Center']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->patchJson(route('branch.update'), [
            'name' => 'Updated Center',
            'contact_person' => 'Jane Doe',
            'phone' => '9999999999',
            'email' => 'center@example.com',
            'address' => '123 Library Street',
        ]);

        $response->assertOk()->assertJsonPath('branch.name', 'Updated Center');
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Center',
            'contact_person' => 'Jane Doe',
        ]);
    }

    public function test_branch_user_can_create_student(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create(['name' => 'Main']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Rahul Kumar',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
            'email' => 'rahul@example.com',
        ]);

        $response->assertCreated()->assertJsonPath('student.name', 'Rahul Kumar');
        $this->assertDatabaseHas('students', [
            'branch_id' => $branch->id,
            'name' => 'Rahul Kumar',
        ]);
    }

    public function test_branch_user_can_create_enquiry_and_convert(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('enquiries.store'), [
            'name' => 'Lead Person',
            'phone' => '8888888888',
            'message' => 'Interested in monthly plan',
        ]);

        $create->assertCreated();
        $enquiryId = $create->json('enquiry.id');

        $convert = $this->actingAs($user)->postJson(route('enquiries.convert', $enquiryId));
        $convert->assertOk()->assertJsonPath('enquiry.status', 'converted');

        $this->assertDatabaseHas('students', ['name' => 'Lead Person']);
        $this->assertDatabaseHas('enquiries', ['id' => $enquiryId, 'status' => 'converted']);
    }

    public function test_seat_assignment_rejects_conflicting_slot(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 1]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ])->assertCreated();

        $conflict = $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1200,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $conflict->assertStatus(422);
    }

    public function test_seat_transfer_closes_old_booking_preserves_fees_and_rejects_conflicts(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 1]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 2]);
        $seatC = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 3]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $other = Student::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 2000,
            'payment_plan' => 'full',
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ]);
        $create->assertCreated();
        $oldBookingId = $create->json('booking.id');

        \App\Models\SeatBooking::query()->whereKey($oldBookingId)->update([
            'amount_paid' => 500,
            'fee_paid_at' => now(),
        ]);

        $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $other->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatC->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('seat-assignments.transfer'), [
            'booking_id' => $oldBookingId,
            'hall_id' => $hall->id,
            'seat_id' => $seatC->id,
            'time_slot' => 'full_day',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('seat-assignments.transfer'), [
            'booking_id' => $oldBookingId,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '10:00',
            'custom_end_time' => '12:00',
        ])->assertStatus(422);

        $transfer = $this->actingAs($user)->postJson(route('seat-assignments.transfer'), [
            'booking_id' => $oldBookingId,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '10:00',
            'custom_end_time' => '12:00',
        ]);
        $transfer->assertOk();

        $this->assertDatabaseHas('seat_bookings', [
            'id' => $oldBookingId,
            'status' => 'cancelled',
        ]);
        $this->assertNotNull(\App\Models\SeatBooking::query()->find($oldBookingId)?->cancelled_at);

        $newBooking = \App\Models\SeatBooking::query()
            ->where('student_id', $student->id)
            ->whereNull('cancelled_at')
            ->first();

        $this->assertNotNull($newBooking);
        $this->assertSame($seatB->id, $newBooking->seat_id);
        $this->assertSame('custom_hours', $newBooking->time_slot);
        $this->assertSame('2000.00', (string) $newBooking->fee_amount);
        $this->assertSame('500.00', (string) $newBooking->amount_paid);
        $this->assertSame(1, \App\Models\SeatBooking::query()
            ->where('student_id', $student->id)
            ->whereNull('cancelled_at')
            ->count());

        $this->actingAs($user)->get(route('seats.index'))->assertOk();
    }

    public function test_fee_can_be_updated(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 4]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'name' => 'Fee Student']);

        $create = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $create->assertCreated();
        $bookingId = $create->json('row.id');

        $this->actingAs($user)->patchJson(route('fees.update', $bookingId), [
            'fee_type' => 'yearly',
            'fee_amount' => 12000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addYear()->toDateString(),
        ])->assertOk()->assertJsonPath('row.fee_amount', '12000.00');

        $this->assertDatabaseHas('seat_bookings', [
            'id' => $bookingId,
            'fee_type' => 'yearly',
        ]);
    }

    public function test_adding_fee_for_assigned_student_updates_existing_booking(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'name' => 'Computer Lab']);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 2]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'name' => 'Aarav Sharma']);

        $first = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '10:00',
            'custom_end_time' => '14:00',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $first->assertCreated();

        $this->actingAs($user)
            ->get(route('fees.index'))
            ->assertOk()
            ->assertSee('Computer Lab', false)
            ->assertSee('current_assignment', false);

        $second = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '10:00',
            'custom_end_time' => '14:00',
            'fee_type' => 'yearly',
            'fee_amount' => 2000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonths(2)->toDateString(),
        ]);

        $second->assertOk()->assertJsonPath('row.id', $first->json('row.id'));
        $this->assertDatabaseCount('seat_bookings', 1);
        $this->assertDatabaseHas('seat_bookings', [
            'id' => $first->json('row.id'),
            'fee_type' => 'yearly',
            'fee_amount' => '2000.00',
        ]);
    }

    public function test_fee_installments_can_be_created_and_marked_paid(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 8]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'name' => 'Installment Student']);

        $create = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'yearly',
            'payment_plan' => 'installments',
            'installment_count' => 3,
            'installment_frequency' => 'monthly',
            'first_due_date' => now()->toDateString(),
            'fee_amount' => 3000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonths(3)->toDateString(),
        ]);

        $create->assertCreated()->assertJsonPath('row.installment_count', 3);
        $this->assertSame('installments', $create->json('row.payment_plan'));
        $this->assertSame('yearly', $create->json('row.fee_type'));
        $bookingId = $create->json('row.id');
        $installmentId = $create->json('row.installments.0.id');

        $this->assertDatabaseCount('fee_installments', 3);

        $this->actingAs($user)
            ->postJson(route('fees.installments.pay', [$bookingId, $installmentId]))
            ->assertOk()
            ->assertJsonPath('row.installments_paid', 1);

        $this->assertNotNull(\App\Models\FeeInstallment::query()->find($installmentId)?->paid_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'fee_installment.updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_fees_can_be_bulk_removed(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 11]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 12]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $first = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ])->assertCreated()->json('row.id');

        $second = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1100,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ])->assertCreated()->json('row.id');

        $this->actingAs($user)
            ->postJson(route('fees.bulk-destroy'), ['ids' => [$first, $second]])
            ->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertDatabaseHas('seat_bookings', ['id' => $first, 'status' => 'cancelled']);
        $this->assertDatabaseHas('seat_bookings', ['id' => $second, 'status' => 'cancelled']);
    }

    public function test_monthly_and_yearly_fees_calculate_plan_end_dates(): void
    {
        Carbon::setTestNow('2026-08-08');

        try {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 21]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 22]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $monthly = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
            'fee_amount' => 1500,
            'joining_date' => '2026-08-08',
        ]);

        $monthly->assertCreated()
            ->assertJsonPath('row.fee_type', 'monthly')
            ->assertJsonPath('row.plan_expiry_date_iso', '2026-09-07')
            ->assertJsonPath('row.payment_plan', 'full')
            ->assertJsonPath('row.plan_status', 'active');

        $yearly = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'fee_type' => 'yearly',
            'payment_plan' => 'full',
            'fee_amount' => 12000,
            'joining_date' => '2026-08-08',
        ]);

        $yearly->assertCreated()->assertJsonPath('row.plan_expiry_date_iso', '2027-08-07');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_one_time_and_custom_fees(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 31]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 32]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'fee_type' => 'one_time',
            'payment_plan' => 'full',
            'fee_amount' => 500,
            'joining_date' => '2026-08-08',
        ])->assertCreated()
            ->assertJsonPath('row.fee_type', 'one_time')
            ->assertJsonPath('row.plan_expiry_date', null);

        $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'fee_type' => 'custom',
            'payment_plan' => 'full',
            'fee_amount' => 800,
            'joining_date' => '2026-08-08',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'fee_type' => 'custom',
            'payment_plan' => 'full',
            'fee_amount' => 800,
            'joining_date' => '2026-08-08',
            'plan_expiry_date' => '2026-10-08',
        ])->assertCreated()->assertJsonPath('row.plan_expiry_date_iso', '2026-10-08');
    }

    public function test_installment_schedule_splits_the_total_amount(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 41]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'yearly',
            'payment_plan' => 'installments',
            'installment_count' => 4,
            'installment_frequency' => 'monthly',
            'first_due_date' => '2026-08-08',
            'fee_amount' => 12000,
            'joining_date' => '2026-08-08',
        ]);

        $create->assertCreated()
            ->assertJsonPath('row.payment_plan', 'installments')
            ->assertJsonPath('row.installment_count', 4)
            ->assertJsonPath('row.installments.0.amount', '3000.00')
            ->assertJsonPath('row.installments.3.amount', '3000.00')
            ->assertJsonPath('row.installments.0.due_date_iso', '2026-08-08')
            ->assertJsonPath('row.installments.1.due_date_iso', '2026-09-08');
    }

    public function test_one_time_forces_full_payment_and_custom_frequency_is_flexible(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 51]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 52]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $oneTime = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'fee_type' => 'one_time',
            'payment_plan' => 'installments',
            'installment_count' => 2,
            'installment_frequency' => 'monthly',
            'first_due_date' => now()->toDateString(),
            'fee_amount' => 500,
            'joining_date' => now()->toDateString(),
        ]);

        $oneTime->assertCreated()
            ->assertJsonPath('row.payment_plan', 'full')
            ->assertJsonPath('row.amount_paid', 0);
        $this->assertContains($oneTime->json('row.payment_status'), ['pending', 'unpaid', 'overdue']);

        $flexible = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'fee_type' => 'membership',
            'payment_plan' => 'installments',
            'installment_frequency' => 'custom',
            'first_due_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonths(4)->toDateString(),
            'fee_amount' => 12000,
            'joining_date' => now()->toDateString(),
        ]);

        $flexible->assertCreated()
            ->assertJsonPath('row.fee_type', 'membership')
            ->assertJsonPath('row.is_flexible_installment', true)
            ->assertJsonPath('row.installment_count', null)
            ->assertJsonCount(0, 'row.installments');
        $this->assertContains($flexible->json('row.payment_status'), ['pending', 'unpaid', 'overdue']);
        $this->assertSame(0, (int) $flexible->json('row.amount_paid'));
    }

    public function test_installment_count_must_be_at_least_two(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 61]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'yearly',
            'payment_plan' => 'installments',
            'installment_count' => 1,
            'installment_frequency' => 'monthly',
            'first_due_date' => now()->toDateString(),
            'fee_amount' => 12000,
            'joining_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['installment_count']);
    }

    public function test_receive_payment_applies_amount_and_covers_installments(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 71]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'yearly',
            'payment_plan' => 'installments',
            'installment_count' => 4,
            'installment_frequency' => 'monthly',
            'first_due_date' => now()->toDateString(),
            'fee_amount' => 4000,
            'joining_date' => now()->toDateString(),
        ])->assertCreated();

        $bookingId = $create->json('row.id');

        $first = $this->actingAs($user)->postJson(route('fees.payments.store', $bookingId), [
            'amount' => 1000,
            'note' => 'First installment',
        ])->assertOk();

        $first->assertJsonPath('row.amount_paid', 1000)
            ->assertJsonCount(1, 'row.payments')
            ->assertJsonPath('row.installments_paid', 1)
            ->assertJsonPath('row.payment_status', 'partial');

        $advance = $this->actingAs($user)->postJson(route('fees.payments.store', $bookingId), [
            'amount' => 1500,
            'note' => 'Advance',
        ])->assertOk();

        $advance->assertJsonPath('row.amount_paid', 2500)
            ->assertJsonCount(2, 'row.payments')
            ->assertJsonPath('row.installments_paid', 2)
            ->assertJsonPath('row.amount_due', 1500);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'fees.payment_received',
            'user_id' => $user->id,
        ]);
    }

    public function test_weekly_frequency_builds_weekly_due_dates(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 81]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('fees.store'), [
            'student_id' => $student->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'custom',
            'payment_plan' => 'installments',
            'installment_count' => 2,
            'installment_frequency' => 'weekly',
            'first_due_date' => '2026-08-20',
            'plan_expiry_date' => '2026-09-20',
            'fee_amount' => 1000,
            'joining_date' => '2026-08-20',
        ])->assertCreated();

        $create->assertJsonPath('row.installment_frequency', 'weekly')
            ->assertJsonPath('row.installments.0.due_date_iso', '2026-08-20')
            ->assertJsonPath('row.installments.1.due_date_iso', '2026-08-27');
    }

    public function test_module_pages_load_for_branch_user(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $routes = [
            'students.index',
            'enquiries.index',
            'seat-assignments.index',
            'fees.index',
            'notifications.index',
            'activity-logs.index',
            'settings.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_notifications_can_be_marked_as_read(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        \App\Models\Enquiry::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Lead Person',
            'phone' => '9876543210',
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('New enquiry')
            ->assertSee('Lead Person');

        $this->actingAs($user)
            ->postJson(route('notifications.mark-all-read'))
            ->assertOk();

        $this->assertDatabaseHas('notification_reads', [
            'user_id' => $user->id,
            'alert_key' => 'new_enquiry:'.\App\Models\Enquiry::query()->value('id'),
        ]);
    }
}
