<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\LibraryScheduleService;
use App\Services\SeatStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchLoginHoursAndTrialSeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_branch_with_login_user(): void
    {
        $admin = $this->platformAdmin();

        $response = $this->actingAs($admin)->postJson(route('branch.store'), [
            'name' => 'East Library',
            'contact_person' => 'Priya',
            'phone' => '9876543210',
            'email' => 'east.admin@LibControl.test',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('branch.email', 'east.admin@LibControl.test');

        $this->assertDatabaseHas('users', [
            'email' => 'east.admin@LibControl.test',
            'name' => 'Priya',
        ]);

        $user = User::query()->where('email', 'east.admin@LibControl.test')->first();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertSame((int) $response->json('branch.id'), $user->branch_id);
    }

    public function test_platform_admin_can_reset_branch_password_once(): void
    {
        $admin = $this->platformAdmin();
        $branch = Branch::factory()->create(['name' => 'West Library']);
        $login = User::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'west.admin@LibControl.test',
            'password' => 'old-password',
        ]);

        $response = $this->actingAs($admin)->postJson(route('branch.reset-password', $branch), [
            'password' => 'CustomPass1',
        ]);

        $response->assertOk()
            ->assertJsonPath('login_email', 'west.admin@LibControl.test')
            ->assertJsonPath('password', 'CustomPass1');

        $this->assertTrue(Hash::check('CustomPass1', $login->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $login->fresh()->password));
    }

    public function test_library_hours_change_slot_labels_and_windows(): void
    {
        $branch = Branch::factory()->create([
            'library_open_time' => '08:00:00',
            'library_close_time' => '16:00:00',
            'is_open_24_hours' => false,
        ]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->patchJson(route('settings.update'), [
            'display_name' => 'Hours Test',
            'expiry_reminder_days' => 10,
            'library_open_time' => '10:00',
            'library_close_time' => '18:00',
            'is_open_24_hours' => false,
        ])->assertOk();

        $branch->refresh();
        $schedule = LibraryScheduleService::forBranch($branch);

        $this->assertSame([600, 1080], $schedule->slotWindow('full_day'));
        $this->assertStringContainsString('10:00 AM', $schedule->slotLabel('full_day'));
        $this->assertStringContainsString('6:00 PM', $schedule->slotLabel('full_day'));
    }

    public function test_24_hour_library_uses_full_day_window(): void
    {
        $branch = Branch::factory()->create(['is_open_24_hours' => true]);
        $schedule = LibraryScheduleService::forBranch($branch);

        $this->assertSame(0, $schedule->openMinutes());
        $this->assertSame(24 * 60, $schedule->closeMinutes());
        $this->assertSame([0, 24 * 60], $schedule->slotWindow('full_day'));
        $this->assertSame('Full Day (open 24 hours)', $schedule->slotLabel('full_day'));
        $this->assertSame('Custom Hours (any time · open 24 hours)', $schedule->slotLabel('custom_hours'));
    }

    public function test_custom_hours_are_clamped_to_library_hours(): void
    {
        $branch = Branch::factory()->create([
            'library_open_time' => '09:00:00',
            'library_close_time' => '18:00:00',
            'is_open_24_hours' => false,
        ]);
        $schedule = LibraryScheduleService::forBranch($branch);

        $this->assertSame([540, 1080], $schedule->slotWindow('custom_hours', '08:00', '19:00'));
        $this->assertSame('Custom Hours (9:00 AM – 6:00 PM)', $schedule->slotLabel('custom_hours'));
    }

    public function test_half_day_booking_is_vacant_outside_current_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 15:30:00', 'Asia/Kolkata'));

        [$branch, $user, $seat, $student] = $this->branchWithSeat();

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '09:00:00',
            'custom_end_time' => '13:00:00',
            'fee_type' => 'monthly',
            'fee_amount' => 500,
            'joining_date' => '2026-08-01',
            'plan_expiry_date' => '2026-12-31',
            'status' => 'occupied',
        ]);

        $seat->load('bookings.student');
        $status = app(SeatStatusService::class)->resolveForSeat($seat, $branch);

        $this->assertSame('available', $status);

        $response = $this->actingAs($user)->getJson(route('seats.data'));
        $response->assertOk();
        $this->assertSame('available', collect($response->json('seats'))->firstWhere('id', $seat->id)['status']);

        Carbon::setTestNow();
    }

    public function test_full_day_booking_is_occupied_during_library_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 11:00:00', 'Asia/Kolkata'));

        [$branch, $user, $seat, $student] = $this->branchWithSeat();

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 500,
            'joining_date' => '2026-08-01',
            'plan_expiry_date' => '2026-12-31',
            'status' => 'occupied',
        ]);

        $seat->load('bookings.student');
        $this->assertSame('occupied', app(SeatStatusService::class)->resolveForSeat($seat, $branch));

        Carbon::setTestNow();
    }

    public function test_trial_seat_assignment_respects_conflicts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 10:00:00', 'Asia/Kolkata'));

        [, $user, $seat, $student] = $this->branchWithSeat();
        $otherStudent = Student::factory()->create(['branch_id' => $seat->hall->branch_id, 'student_type' => 'trial']);

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '09:00:00',
            'custom_end_time' => '13:00:00',
            'fee_type' => 'monthly',
            'fee_amount' => 500,
            'joining_date' => '2026-08-19',
            'plan_expiry_date' => '2026-08-31',
            'status' => 'occupied',
        ]);

        $fullDayConflict = $this->actingAs($user)->postJson(route('trial-seats.store'), [
            'student_id' => $otherStudent->id,
            'hall_id' => $seat->hall_id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'trial_start' => '2026-08-19',
            'trial_days' => 1,
            'fee_amount' => 0,
        ]);

        $fullDayConflict->assertStatus(422);

        $afternoon = $this->actingAs($user)->postJson(route('trial-seats.store'), [
            'student_id' => $otherStudent->id,
            'hall_id' => $seat->hall_id,
            'seat_id' => $seat->id,
            'time_slot' => 'custom_hours',
            'custom_start_time' => '14:00',
            'custom_end_time' => '18:00',
            'trial_start' => '2026-08-19',
            'trial_days' => 1,
            'fee_amount' => 50,
        ]);

        $afternoon->assertCreated();
        $this->assertDatabaseHas('seat_bookings', [
            'seat_id' => $seat->id,
            'student_id' => $otherStudent->id,
            'status' => 'on_trial',
            'time_slot' => 'custom_hours',
        ]);

        $this->actingAs($user)->get(route('trial-seats.index'))
            ->assertOk()
            ->assertSee('Trial Seats');

        Carbon::setTestNow();
    }

    public function test_trial_seat_assignment_allows_empty_optional_fee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00', 'Asia/Kolkata'));

        [, $user, $seat, $student] = $this->branchWithSeat();
        $trialStudent = Student::factory()->create([
            'branch_id' => $seat->hall->branch_id,
            'student_type' => 'trial',
        ]);

        $response = $this->actingAs($user)->postJson(route('trial-seats.store'), [
            'student_id' => $trialStudent->id,
            'hall_id' => $seat->hall_id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'trial_start' => '2026-08-27',
            'trial_days' => 14,
            'fee_amount' => null,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('seat_bookings', [
            'seat_id' => $seat->id,
            'student_id' => $trialStudent->id,
            'status' => 'on_trial',
            'fee_amount' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_expired_seats_become_vacant_after_one_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 11:00:00', 'Asia/Kolkata'));

        [$branch, $user, $seat, $student] = $this->branchWithSeat();

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 500,
            'joining_date' => '2026-07-01',
            'plan_expiry_date' => '2026-08-18',
            'status' => 'occupied',
        ]);

        $seat->load('bookings.student');
        $this->assertSame('expired', app(SeatStatusService::class)->resolveForSeat($seat, $branch));

        Carbon::setTestNow(Carbon::parse('2026-08-20 11:00:00', 'Asia/Kolkata'));
        $seat->unsetRelation('bookings');
        $seat->load('bookings.student');
        $this->assertSame('available', app(SeatStatusService::class)->resolveForSeat($seat, $branch));

        $response = $this->actingAs($user)->getJson(route('seats.data'));
        $this->assertSame('available', collect($response->json('seats'))->firstWhere('id', $seat->id)['status']);

        Carbon::setTestNow();
    }

    public function test_trial_map_hides_regular_expired_and_shows_trial_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 11:00:00', 'Asia/Kolkata'));

        [$branch, $user, $regularSeat, $regularStudent] = $this->branchWithSeat();
        $hall = $regularSeat->hall;
        $trialSeat = Seat::factory()->create([
            'hall_id' => $hall->id,
            'seat_number' => '2',
            'row_number' => 1,
            'column_number' => 2,
        ]);
        $trialStudent = Student::factory()->create(['branch_id' => $branch->id, 'student_type' => 'trial']);

        SeatBooking::query()->create([
            'seat_id' => $regularSeat->id,
            'student_id' => $regularStudent->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 500,
            'joining_date' => '2026-07-01',
            'plan_expiry_date' => '2026-08-18',
            'status' => 'occupied',
        ]);

        SeatBooking::query()->create([
            'seat_id' => $trialSeat->id,
            'student_id' => $trialStudent->id,
            'time_slot' => 'full_day',
            'fee_type' => 'custom',
            'fee_amount' => 0,
            'joining_date' => '2026-08-17',
            'plan_expiry_date' => '2026-08-18',
            'trial_start' => '2026-08-17',
            'trial_end' => '2026-08-18',
            'status' => 'on_trial',
        ]);

        $response = $this->actingAs($user)->getJson(route('trial-seats.data'));
        $response->assertOk();

        $seats = collect($response->json('seats'));
        $this->assertNull($seats->firstWhere('id', $regularSeat->id));

        $trialPayload = $seats->firstWhere('id', $trialSeat->id);
        $this->assertNotNull($trialPayload);
        $this->assertSame('expired', $trialPayload['status']);
        $this->assertTrue($trialPayload['expired_from_trial']);

        $regularMap = $this->actingAs($user)->getJson(route('seats.data'));
        $regularSeats = collect($regularMap->json('seats'));
        $this->assertNotNull($regularSeats->firstWhere('id', $regularSeat->id));
        $this->assertSame('expired', $regularSeats->firstWhere('id', $regularSeat->id)['status']);
        $this->assertNull($regularSeats->firstWhere('id', $trialSeat->id));

        Carbon::setTestNow();
    }

    /**
     * @return array{0: Branch, 1: User, 2: Seat, 3: Student}
     */
    private function branchWithSeat(): array
    {
        $branch = Branch::factory()->create([
            'library_open_time' => '09:00:00',
            'library_close_time' => '18:00:00',
            'is_open_24_hours' => false,
        ]);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'name' => 'Hall A', 'seat_capacity' => 4]);
        $seat = Seat::factory()->create([
            'hall_id' => $hall->id,
            'seat_number' => '1',
            'row_number' => 1,
            'column_number' => 1,
        ]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'student_type' => 'regular']);

        return [$branch, $user, $seat->load('hall'), $student];
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        return $user;
    }
}
