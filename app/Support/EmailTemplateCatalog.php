<?php

namespace App\Support;

final class EmailTemplateCatalog
{
    /**
     * @return array<string, array{label: string, group: string, description: string, subject: string, body: string, headline: ?string, placeholders: list<string>}>
     */
    public static function all(): array
    {
        return [
            'welcome' => [
                'label' => 'Welcome email',
                'group' => 'student',
                'description' => 'Sent when a new student is registered.',
                'subject' => 'Welcome to {library}',
                'body' => "Hi {name},\n\nWelcome to {library}. Your student profile has been created successfully.",
                'headline' => null,
                'placeholders' => ['name', 'library', 'student_code'],
            ],
            'birthday' => [
                'label' => 'Birthday email',
                'group' => 'student',
                'description' => 'Sent automatically on the student birthday.',
                'subject' => 'Happy birthday from {library}',
                'body' => "Hi {name},\n\nHappy birthday from everyone at {library}! Wishing you a wonderful year ahead.",
                'headline' => null,
                'placeholders' => ['name', 'library', 'student_code'],
            ],
            'recovery' => [
                'label' => 'Recovery email',
                'group' => 'student',
                'description' => 'Sent to inactive students to encourage them to return.',
                'subject' => 'We miss you at {library}',
                'body' => "Hi {name},\n\nWe noticed you have been away from {library} for a while. Visit us again when you are ready.",
                'headline' => null,
                'placeholders' => ['name', 'library', 'student_code'],
            ],
            'fee_due_in_5_days' => [
                'label' => 'Fee due in 5 days',
                'group' => 'fee',
                'description' => 'Reminder five days before a fee installment is due.',
                'subject' => 'Your Library Fee Is Due Soon',
                'body' => "Hi {name},\n\nThis is a reminder about your library fee at {library}.\n\nPlease settle the outstanding amount by the due date, or contact the library if you have already paid.",
                'headline' => 'Fee due in 5 days',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'due_date'],
            ],
            'fee_due_in_2_days' => [
                'label' => 'Fee due in 2 days',
                'group' => 'fee',
                'description' => 'Reminder two days before a fee installment is due.',
                'subject' => 'Friendly Reminder: Library Fee Due Shortly',
                'body' => "Hi {name},\n\nThis is a reminder about your library fee at {library}.\n\nPlease settle the outstanding amount by the due date, or contact the library if you have already paid.",
                'headline' => 'Fee due in 2 days',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'due_date'],
            ],
            'fee_due_today' => [
                'label' => 'Fee due today',
                'group' => 'fee',
                'description' => 'Reminder on the fee due date.',
                'subject' => 'Your Library Fee Is Due Today',
                'body' => "Hi {name},\n\nThis is a reminder about your library fee at {library}.\n\nPlease settle the outstanding amount by the due date, or contact the library if you have already paid.",
                'headline' => 'Fee due today',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'due_date'],
            ],
            'fee_overdue_3_days' => [
                'label' => 'Fee overdue 3 days',
                'group' => 'fee',
                'description' => 'Follow-up three days after the due date.',
                'subject' => 'Pending Library Fee - Please Update Your Payment',
                'body' => "Hi {name},\n\nThis is a reminder about your library fee at {library}.\n\nPlease settle the outstanding amount by the due date, or contact the library if you have already paid.",
                'headline' => 'Fee overdue by 3 days',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'due_date'],
            ],
            'fee_overdue_7_days' => [
                'label' => 'Fee overdue 7 days',
                'group' => 'fee',
                'description' => 'Follow-up seven days after the due date.',
                'subject' => 'Important: Your Library Fee Is Still Pending',
                'body' => "Hi {name},\n\nThis is a reminder about your library fee at {library}.\n\nPlease settle the outstanding amount by the due date, or contact the library if you have already paid.",
                'headline' => 'Fee overdue by 7 days',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'due_date'],
            ],
            'fee_payment_received' => [
                'label' => 'Payment received',
                'group' => 'fee',
                'description' => 'Confirmation after a student fee payment is recorded.',
                'subject' => 'Payment Received - Thank You!',
                'body' => "Hi {name},\n\nWe have received your payment at {library}. Thank you!",
                'headline' => 'Payment received',
                'placeholders' => ['name', 'library', 'student_code', 'amount', 'payment_date', 'reference'],
            ],
            'seat_booking_confirmed' => [
                'label' => 'Seat booking confirmed',
                'group' => 'seat',
                'description' => 'Sent when a student seat is assigned or confirmed.',
                'subject' => 'Seat Booking Confirmed',
                'body' => "Hi {name},\n\nYour seat has been confirmed at {library}. We look forward to seeing you!",
                'headline' => 'Booking confirmed',
                'placeholders' => ['name', 'library', 'student_code', 'hall', 'seat', 'plan_ends'],
            ],
            'seat_expiry_in_3_days' => [
                'label' => 'Seat expires in 3 days',
                'group' => 'seat',
                'description' => 'Reminder three days before the seat plan ends.',
                'subject' => 'Your Seat Plan Expires in 3 Days',
                'body' => "Hi {name},\n\nYour seat plan at {library} expires soon. Please renew to keep your seat.",
                'headline' => 'Seat plan expires in 3 days',
                'placeholders' => ['name', 'library', 'student_code', 'hall', 'seat', 'plan_ends'],
            ],
            'seat_expiry_in_1_day' => [
                'label' => 'Seat expires tomorrow',
                'group' => 'seat',
                'description' => 'Reminder one day before the seat plan ends.',
                'subject' => 'Your Seat Plan Expires Tomorrow',
                'body' => "Hi {name},\n\nYour seat plan at {library} expires soon. Please renew to keep your seat.",
                'headline' => 'Seat plan expires tomorrow',
                'placeholders' => ['name', 'library', 'student_code', 'hall', 'seat', 'plan_ends'],
            ],
        ];
    }

    /**
     * @return array{label: string, group: string, description: string, subject: string, body: string, headline: ?string, placeholders: list<string>}|null
     */
    public static function get(string $key): ?array
    {
        $all = self::all();

        return $all[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
