<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubBroadcast extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $body;

    public function __construct(
        string $subject,
        string $body,
    ) {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.club-broadcast');
    }
}
