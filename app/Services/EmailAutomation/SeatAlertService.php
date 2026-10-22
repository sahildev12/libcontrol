<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\SeatBooking;
use App\Services\BranchBrandService;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SeatAlertService
{
    /** Extra expiry offsets beyond the branch N-day reminder (handled by SendPlanExpiryReminders). */
    public const EXTRA_EXPIRY_OFFSETS = [
        3 => [
            'key' => 'seat_expiry_in_3_days',
            'subject' => 'Your Seat Plan Expires in 3 Days',
            'headline' => 'Seat plan expires in 3 days',
        ],
        1 => [
            'key' => 'seat_expiry_in_1_day',
            'subject' => 'Your Seat Plan Expires Tomorrow',
            'headline' => 'Seat plan expires tomorrow',
        ],
    ];

    public function __construct(
        private EmailAutomationLogger $logger,
        private BranchBrandService $branchBrandService,
        private DashboardService $dashboardService,
        private OwnerRecipientResolver $owners,
    ) {}

    public function sendScheduledAlerts(?int $branchId = null): int
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_seat_alerts_enabled) {
            return 0;
        }

        $sent = 0;
        $sent += $this->sendExpirySequence($branchId);
        $sent += $this->sendOwnerDigests($settings, $branchId);

        return $sent;
    }

    public function sendBookingConfirmed(SeatBooking $booking): bool
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_seat_alerts_enabled) {
            return false;
        }

        $booking->loadMissing(['student', 'seat.hall.branch']);
        $student = $booking->student;
        $email = trim((string) ($student?->effectiveEmail() ?: $student?->email));
        if ($email === '') {
            return false;
        }

        if ($this->logger->wasSent('seat_booking_confirmed', SeatBooking::class, $booking->id)) {
            return false;
        }

        $branch = $booking->seat?->hall?->branch;
        $libraryName = $branch
            ? $this->branchBrandService->displayName($branch)
            : PlatformSetting::current()->displayName();

        $body = "Hi {$student->name},\n\n"
            ."Your seat has been confirmed at {$libraryName}. We look forward to seeing you!";

        try {
            Mail::to($email)->send(new AutomationAlertMail(
                subjectLine: 'Seat Booking Confirmed',
                headline: 'Booking confirmed',
                bodyText: $body,
                libraryName: $libraryName,
                context: [
                    'Student' => $student->name.($student->student_code ? " ({$student->student_code})" : ''),
                    'Hall' => $booking->seat?->hall?->name ?? '—',
                    'Seat' => (string) ($booking->seat?->seat_number ?? '—'),
                    'Plan ends' => $booking->plan_expiry_date?->format('M d, Y') ?? '—',
                ],
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            'seat_booking_confirmed',
            $email,
            SeatBooking::class,
            $booking->id,
            $branch?->id,
        );
    }

    private function sendExpirySequence(?int $branchId): int
    {
        $today = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'));
        $sent = 0;

        $branchQuery = Branch::query();
        if ($branchId) {
            $branchQuery->where('id', $branchId);
        }

        foreach ($branchQuery->cursor() as $branch) {
            foreach (self::EXTRA_EXPIRY_OFFSETS as $daysBefore => $template) {
                $targetDate = $today->copy()->addDays($daysBefore);

                $bookings = SeatBooking::query()
                    ->with(['student', 'seat.hall'])
                    ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branch->id))
                    ->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('plan_expiry_date', $targetDate->toDateString())
                    ->get();

                foreach ($bookings as $booking) {
                    if ($this->sendExpiryAlert($booking->fresh(['student', 'seat.hall.branch']), $template, $branch)) {
                        $sent++;
                    }
                }
            }
        }

        return $sent;
    }

    /**
     * @param  array{key: string, subject: string, headline: string}  $template
     */
    private function sendExpiryAlert(SeatBooking $booking, array $template, Branch $branch): bool
    {
        $booking->refresh();
        if ($booking->cancelled_at || $booking->status === 'cancelled') {
            return false;
        }

        $student = $booking->student;
        $email = trim((string) ($student?->effectiveEmail() ?: $student?->email));
        if ($email === '') {
            return false;
        }

        if ($this->logger->wasSent($template['key'], SeatBooking::class, $booking->id)) {
            return false;
        }

        $libraryName = $this->branchBrandService->displayName($branch);
        $body = "Hi {$student->name},\n\n"
            ."Your seat plan at {$libraryName} is ending soon. Please renew to keep your seat.";

        try {
            Mail::to($email)->send(new AutomationAlertMail(
                subjectLine: $template['subject'],
                headline: $template['headline'],
                bodyText: $body,
                libraryName: $libraryName,
                context: [
                    'Student' => $student->name.($student->student_code ? " ({$student->student_code})" : ''),
                    'Hall' => $booking->seat?->hall?->name ?? '—',
                    'Seat' => (string) ($booking->seat?->seat_number ?? '—'),
                    'Expires on' => $booking->plan_expiry_date?->format('M d, Y') ?? '—',
                ],
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            $template['key'],
            $email,
            SeatBooking::class,
            $booking->id,
            $branch->id,
            ['plan_expiry_date' => $booking->plan_expiry_date?->toDateString()],
        );
    }

    private function sendOwnerDigests(PlatformSetting $settings, ?int $branchId): int
    {
        $emails = $this->owners->clientAdminEmails();
        if ($emails === []) {
            return 0;
        }

        $stats = $this->dashboardService->statsForBranch($branchId);
        $available = (int) ($stats['available'] ?? 0);
        $total = (int) ($stats['total_seats'] ?? 0);
        $occupied = (int) ($stats['occupied'] ?? 0) + (int) ($stats['expiring_soon'] ?? 0) + (int) ($stats['on_trial'] ?? 0);
        $occupancy = $total > 0 ? round(($occupied / $total) * 100, 1) : 0.0;
        $libraryName = $settings->displayName();
        $todayKey = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'))->toDateString();
        $relatedId = $branchId ?: 0;
        $sent = 0;

        $vacantThreshold = max(1, (int) ($settings->seat_vacant_alert_threshold ?? 5));
        if ($available >= $vacantThreshold) {
            $templateKey = 'seat_vacant_digest_'.$todayKey;
            if (! $this->logger->wasSent($templateKey, 'platform_digest', $relatedId)) {
                $body = "You currently have {$available} vacant seat(s) at {$libraryName}.\n\n"
                    .'Consider a short promotion or outreach to fill open capacity.';

                if ($this->sendOwnerMail(
                    $emails,
                    'You Have Vacant Seats Ready to Fill',
                    'Vacant seats available',
                    $body,
                    $libraryName,
                    [
                        'Vacant seats' => (string) $available,
                        'Total seats' => (string) $total,
                        'Occupancy' => $occupancy.'%',
                    ],
                    $templateKey,
                    $relatedId,
                )) {
                    $sent++;
                }
            }
        }

        $highPct = max(50, min(100, (int) ($settings->seat_high_occupancy_pct ?? 90)));
        if ($total > 0 && $occupancy >= $highPct) {
            $templateKey = 'seat_high_occupancy_'.$todayKey;
            if (! $this->logger->wasSent($templateKey, 'platform_digest', $relatedId)) {
                $body = "{$libraryName} is at {$occupancy}% occupancy.\n\n"
                    .'You may want to open waitlists or plan capacity for new students.';

                if ($this->sendOwnerMail(
                    $emails,
                    'Your Library Is Nearly Full',
                    'High occupancy alert',
                    $body,
                    $libraryName,
                    [
                        'Occupancy' => $occupancy.'%',
                        'Occupied' => (string) $occupied,
                        'Available' => (string) $available,
                    ],
                    $templateKey,
                    $relatedId,
                )) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @param  list<string>  $emails
     * @param  array<string, mixed>  $context
     */
    private function sendOwnerMail(
        array $emails,
        string $subject,
        string $headline,
        string $body,
        string $libraryName,
        array $context,
        string $templateKey,
        int $relatedId,
    ): bool {
        try {
            Mail::to($emails)->send(new AutomationAlertMail(
                subjectLine: $subject,
                headline: $headline,
                bodyText: $body,
                libraryName: $libraryName,
                context: $context,
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            $templateKey,
            implode(',', $emails),
            'platform_digest',
            $relatedId,
            null,
            $context,
        );
    }
}
