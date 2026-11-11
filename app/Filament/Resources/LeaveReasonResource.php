<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveReasonResource\Pages;
use App\Models\LeaveReason;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeaveReasonResource extends Resource
{
    protected static ?string $model = LeaveReason::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-left-start-on-rectangle';

    protected static ?string $navigationLabel = 'Motivos de Baja';

    protected static ?string $modelLabel = 'Motivo de Baja';

    protected static ?string $pluralModelLabel = 'Motivos de Baja';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Datos del motivo')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('member_periods_count')
                    ->label('Usos')
                    ->counts('memberPeriods')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveReasons::route('/'),
            'create' => Pages\CreateLeaveReason::route('/create'),
            'edit'   => Pages\EditLeaveReason::route('/{record}/edit'),
        ];
    }
}
