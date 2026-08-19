<?php

namespace App\Mail;

use App\Models\SeatBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SeatBooking $booking,
        public int $daysRemaining,
        public string $libraryName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->libraryName} plan expires in {$this->daysRemaining} day(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.plan-expiry-reminder',
        );
    }
}
