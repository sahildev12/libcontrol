<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\PlatformSetting;
use App\Models\SeatBooking;
use App\Services\BranchBrandService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class FeeAlertService
{
    /** @var array<int, array{key: string, subject: string, headline: string}> */
    public const DUE_OFFSETS = [
        -5 => [
            'key' => 'fee_due_in_5_days',
            'subject' => 'Your Library Fee Is Due Soon',
            'headline' => 'Fee due in 5 days',
        ],
        -2 => [
            'key' => 'fee_due_in_2_days',
            'subject' => 'Friendly Reminder: Library Fee Due Shortly',
            'headline' => 'Fee due in 2 days',
        ],
        0 => [
            'key' => 'fee_due_today',
            'subject' => 'Your Library Fee Is Due Today',
            'headline' => 'Fee due today',
        ],
        3 => [
            'key' => 'fee_overdue_3_days',
            'subject' => 'Pending Library Fee - Please Update Your Payment',
            'headline' => 'Fee overdue by 3 days',
        ],
        7 => [
            'key' => 'fee_overdue_7_days',
            'subject' => 'Important: Your Library Fee Is Still Pending',
            'headline' => 'Fee overdue by 7 days',
        ],
    ];

    public function __construct(
        private EmailAutomationLogger $logger,
        private BranchBrandService $branchBrandService,
    ) {}

    public function sendScheduledAlerts(?int $branchId = null): int
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_fee_alerts_enabled) {
            return 0;
        }

        $today = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'));
        $sent = 0;

        foreach (self::DUE_OFFSETS as $offsetDays => $template) {
            $targetDate = $today->copy()->addDays(-$offsetDays);

            $query = FeeInstallment::query()
                ->with([
                    'booking.student',
                    'booking.seat.hall.branch',
                ])
                ->whereNull('paid_at')
                ->whereDate('due_date', $targetDate->toDateString())
                ->whereHas('booking', function ($bookingQuery) use ($branchId) {
                    $bookingQuery
                        ->whereNull('cancelled_at')
                        ->where('status', '!=', 'cancelled');

                    if ($branchId) {
                        $bookingQuery->whereHas('seat.hall', fn ($q) => $q->where('branch_id', $branchId));
                    }
                });

            foreach ($query->cursor() as $installment) {
                if ($this->sendInstallmentAlert($installment->fresh([
                    'booking.student',
                    'booking.seat.hall.branch',
                ]), $template)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @param  array{key: string, subject: string, headline: string}  $template
     */
    public function sendInstallmentAlert(FeeInstallment $installment, array $template): bool
    {
        $installment->refresh();
        if ($installment->paid_at !== null) {
            return false;
        }

        $booking = $installment->booking;
        if (! $booking || $booking->cancelled_at || $booking->status === 'cancelled') {
            return false;
        }

        $student = $booking->student;
        $email = trim((string) ($student?->effectiveEmail() ?: $student?->email));
        if ($email === '') {
            return false;
        }

        $relatedId = $installment->id;
        if ($this->logger->wasSent($template['key'], FeeInstallment::class, $relatedId)) {
            return false;
        }

        $branch = $booking->seat?->hall?->branch;
        $libraryName = $branch
            ? $this->branchBrandService->displayName($branch)
            : PlatformSetting::current()->displayName();

        $amount = number_format((float) $installment->amount, 2);
        $dueDate = $installment->due_date?->format('M d, Y') ?? 'N/A';
        $support = config('libcontrol.support.public_email') ?: config('libcontrol.support.email');

        $body = "Hi {$student->name},\n\n"
            ."This is a reminder about your library fee at {$libraryName}.\n\n"
            ."Please settle the outstanding amount by the due date, or contact the library if you have already paid.";

        $context = [
            'Student' => $student->name.($student->student_code ? " ({$student->student_code})" : ''),
            'Amount' => '₹'.$amount,
            'Due date' => $dueDate,
            'Fee period' => 'Installment #'.$installment->installment_number,
            'Contact' => $support,
        ];

        try {
            Mail::to($email)->send(new AutomationAlertMail(
                subjectLine: $template['subject'],
                headline: $template['headline'],
                bodyText: $body,
                libraryName: $libraryName,
                context: $context,
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            $template['key'],
            $email,
            FeeInstallment::class,
            $relatedId,
            $branch?->id,
            ['amount' => $installment->amount, 'due_date' => $installment->due_date?->toDateString()],
        );
    }

    public function sendPaymentReceived(SeatBooking $booking, FeePayment $payment): bool
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_fee_alerts_enabled) {
            return false;
        }

        $booking->loadMissing(['student', 'seat.hall.branch']);
        $student = $booking->student;
        $email = trim((string) ($student?->effectiveEmail() ?: $student?->email));
        if ($email === '') {
            return false;
        }

        if ($this->logger->wasSent('fee_payment_received', FeePayment::class, $payment->id)) {
            return false;
        }

        $branch = $booking->seat?->hall?->branch;
        $libraryName = $branch
            ? $this->branchBrandService->displayName($branch)
            : PlatformSetting::current()->displayName();

        $amount = number_format((float) $payment->amount, 2);
        $paidOn = $payment->payment_date?->format('M d, Y') ?? now()->format('M d, Y');
        $reference = $payment->reference ?: ('PAY-'.$payment->id);

        $body = "Hi {$student->name},\n\n"
            ."We have received your payment at {$libraryName}. Thank you!";

        try {
            Mail::to($email)->send(new AutomationAlertMail(
                subjectLine: 'Payment Received - Thank You!',
                headline: 'Payment received',
                bodyText: $body,
                libraryName: $libraryName,
                context: [
                    'Student' => $student->name.($student->student_code ? " ({$student->student_code})" : ''),
                    'Amount paid' => '₹'.$amount,
                    'Payment date' => $paidOn,
                    'Reference' => $reference,
                ],
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            'fee_payment_received',
            $email,
            FeePayment::class,
            $payment->id,
            $branch?->id,
            ['amount' => $payment->amount],
        );
    }
}
