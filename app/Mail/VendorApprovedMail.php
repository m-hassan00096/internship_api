<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $setupUrl,
        public string $vendorName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم اعتماد طلب انضمامك إلى منصة BaytFolio 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
