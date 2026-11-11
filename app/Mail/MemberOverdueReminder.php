<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class MemberOverdueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Member $member,
        public readonly Collection $overdueFees,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->overdueFees->count();

        return new Envelope(
            subject: "Recordatorio: tenés {$count} cuota(s) vencida(s) — Club Rojo Deportivo",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.member-overdue-reminder',
            with: [
                'fees_url' => URL::temporarySignedRoute(
                    'member.fees',
                    now()->addDays(7),
                    ['member' => $this->member->id]
                ),
            ],
        );
    }
}
