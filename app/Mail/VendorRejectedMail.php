<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $vendorName,
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('vendor/registration.emails.rejected_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
