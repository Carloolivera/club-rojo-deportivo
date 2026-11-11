<?php

namespace App\Filament\Pages;

use App\Mail\ClubBroadcast;
use App\Models\Member;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class BroadcastPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $slug = 'broadcast';

    protected string $view = 'filament.pages.broadcast-page';

    protected static ?string $navigationLabel = 'Comunicados';

    protected static ?string $title = 'Enviar comunicado';

    protected static ?int $navigationSort = 11;

    public string $subject = '';
    public string $body = '';

    public static function canAccess(): bool
    {
        return Gate::allows('send-broadcast');
    }

    public function send(): void
    {
        $this->validate([
            'subject' => ['required', 'string', 'min:5', 'max:200'],
            'body'    => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $recipients = Member::where('status', 'active')
            ->whereNotNull('email')
            ->get()
            ->filter(fn ($m) => filled($m->email));

        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('No hay socios activos con email registrado.')
                ->warning()
                ->send();

            return;
        }

        foreach ($recipients as $member) {
            Mail::to($member->email)->send(new ClubBroadcast($this->subject, $this->body));
        }

        Notification::make()
            ->title("Comunicado enviado a {$recipients->count()} socio(s).")
            ->success()
            ->send();

        $this->subject = '';
        $this->body    = '';
    }

    public function getRecipientsCount(): int
    {
        return Member::where('status', 'active')
            ->whereNotNull('email')
            ->get()
            ->filter(fn ($m) => filled($m->email))
            ->count();
    }
}
