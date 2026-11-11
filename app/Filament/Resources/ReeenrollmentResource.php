<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReeenrollmentResource\Pages;
use App\Models\Reenrollment;
use Filament\Actions\Action as FilamentAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReeenrollmentResource extends Resource
{
    protected static ?string $model = Reenrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Reempadronamientos';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes de Reempadronamiento';

    protected static ?int $navigationSort = 12;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.name')
                    ->label('Socio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('member.sport.name')
                    ->label('Deporte')
                    ->sortable(),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Solicitado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'  => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Resuelto por')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Resuelto el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'  => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),
            ])
            ->actions([
                FilamentAction::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar solicitud')
                    ->modalDescription('¿Confirma la aprobación? El socio volverá a estado activo.')
                    ->visible(fn (Reenrollment $record): bool => $record->status === 'pending')
                    ->action(function (Reenrollment $record): void {
                        $user = Auth::user();
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);
                        $record->member->activate($user);
                    }),

                FilamentAction::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar solicitud')
                    ->modalDescription('¿Confirma el rechazo? El socio permanecerá inactivo.')
                    ->visible(fn (Reenrollment $record): bool => $record->status === 'pending')
                    ->action(function (Reenrollment $record): void {
                        $record->update([
                            'status'      => 'rejected',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReeenrollments::route('/'),
        ];
    }
}
