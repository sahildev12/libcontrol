<?php

namespace App\Mail;

use App\Models\Branch;
use App\Models\GrowthOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GrowthOrderRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $catalog
     */
    public function __construct(
        public GrowthOrder $order,
        public array $catalog,
        public ?Branch $branch,
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->order->order_type === 'package' ? 'Package' : 'Service';
        $library = $this->order->library_name ?: ($this->branch?->name ?: 'a library');

        return new Envelope(
            subject: "New growth request: {$this->order->item_name} ({$type}) — {$library}",
            replyTo: filled($this->order->contact_email)
                ? [new Address($this->order->contact_email, (string) $this->order->contact_name)]
                : [],
        );
    }

    public function content(): Content
    {
        $phone = preg_replace('/\D+/', '', (string) ($this->order->contact_phone ?: $this->branch?->phone));

        return new Content(
            markdown: 'emails.growth-order-requested',
            with: [
                'typeLabel' => $this->order->order_type === 'package' ? 'Package' : 'Service',
                'phone' => $phone !== '' ? $phone : null,
                'whatsappUrl' => $phone !== '' ? 'https://wa.me/'.(strlen($phone) === 10 ? '91'.$phone : $phone) : null,
            ],
        );
    }
}
