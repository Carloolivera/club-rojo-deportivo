<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberPeriodResource\Pages;
use App\Models\LeaveReason;
use App\Models\MemberPeriod;
use App\Models\Sport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MemberPeriodResource extends Resource
{
    protected static ?string $model = MemberPeriod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Historial de Períodos';

    protected static ?string $modelLabel = 'Período';

    protected static ?string $pluralModelLabel = 'Períodos';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->hasRole('admin') || $user?->hasRole('authority') ? true : false;
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

                Tables\Columns\TextColumn::make('sport.name')
                    ->label('Deporte')
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('Activo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('leaveReason.name')
                    ->label('Motivo de baja')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('leave_note')
                    ->label('Notas')
                    ->limit(50)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sport_id')
                    ->label('Deporte')
                    ->options(Sport::query()->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('leave_reason_id')
                    ->label('Motivo de baja')
                    ->options(LeaveReason::query()->where('is_active', true)->pluck('name', 'id')),

                Tables\Filters\Filter::make('activos')
                    ->label('Solo períodos activos')
                    ->query(fn (Builder $query) => $query->whereNull('ended_at'))
                    ->toggle(),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberPeriods::route('/'),
        ];
    }
}
