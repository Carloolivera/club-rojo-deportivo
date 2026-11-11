<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OverdueFeeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Collection $fees,
        public readonly int $threshold,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Alerta: {$this->fees->count()} socios con cuota vencida > {$this->threshold} días",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.overdue-fees',
        );
    }
}
