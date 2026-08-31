<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public ?string $recipientName = null,
        public int $expireMinutes = 60,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = config('mail.reply_to.address');

        return new Envelope(
            subject: config('libspace.product.name').' password reset',
            replyTo: $replyTo
                ? [new Address($replyTo, (string) config('mail.reply_to.name'))]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-reset',
        );
    }
}
