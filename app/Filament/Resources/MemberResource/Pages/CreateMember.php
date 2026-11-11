<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Request;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['consent'])) {
            $data['consent_at'] = now();
            $data['consent_ip'] = Request::ip();
        }
        unset($data['consent']);

        // CA06: second-line defense — minor must have at least one guardian
        $isMinor = $data['is_minor'] ?? false;
        if ($isMinor && empty($data['guardians'])) {
            Notification::make()
                ->title('El socio es menor de edad — debe registrar al menos un responsable.')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
