<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeeResource\Pages;
use App\Models\Fee;
use App\Models\Member;
use App\Services\MercadoPagoService;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\BulkAction as FilamentBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class FeeResource extends Resource
{
    protected static ?string $model = Fee::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Cuotas';

    protected static ?string $modelLabel = 'Cuota';

    protected static ?string $pluralModelLabel = 'Cuotas';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole('manager') && $user->sport_id) {
            $query->whereHas('member', fn (Builder $q) => $q->where('sport_id', $user->sport_id));
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Datos de la cuota')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('member_id')
                        ->label('Socio')
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->getSearchResultsUsing(function (string $search): array {
                            $user = Auth::user();
                            $query = Member::query();

                            if ($user->hasRole('manager') && $user->sport_id) {
                                $query->where('sport_id', $user->sport_id);
                            }

                            return $query
                                ->get(['id', 'name'])
                                ->filter(fn ($m) => str_contains(mb_strtolower($m->name), mb_strtolower($search)))
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Member::find($value)?->name),

                    Forms\Components\TextInput::make('period')
                        ->label('Período')
                        ->required()
                        ->regex('/^\d{4}-\d{2}$/')
                        ->placeholder('YYYY-MM')
                        ->helperText('Formato: YYYY-MM (ej: 2026-04)'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01),

                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendiente',
                            'paid'    => 'Pagada',
                            'overdue' => 'Vencida',
                            'exempt'  => 'Exenta',
                        ])
                        ->required()
                        ->native(false)
                        ->default('pending'),

                    Forms\Components\Select::make('payment_method')
                        ->label('Método de pago')
                        ->options([
                            'manual'       => 'Manual (efectivo/transferencia)',
                            'mercadopago'  => 'MercadoPago',
                        ])
                        ->nullable()
                        ->native(false),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->nullable()
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
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
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period')
                    ->label('Período')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        'exempt'  => 'gray',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid'    => 'Pagada',
                        'pending' => 'Pendiente',
                        'overdue' => 'Vencida',
                        'exempt'  => 'Exenta',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Fecha de pago')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'paid'    => 'Pagada',
                        'overdue' => 'Vencida',
                        'exempt'  => 'Exenta',
                    ]),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Período')
                    ->options(fn () => Fee::distinct()->orderBy('period', 'desc')->pluck('period', 'period')),
            ])
            ->actions([
                self::markPaidAction(),
                self::generateMpLinkAction(),
                self::memberPortalLinkAction(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    FilamentBulkAction::make('exportExcel')
                        ->label('Exportar Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn () => Gate::allows('view-reports'))
                        ->action(function (Collection $records) {
                            session(['export_fee_ids' => $records->pluck('id')->toArray()]);
                            return redirect()->route('fees.export.selected.excel');
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period', 'desc');
    }

    private static function markPaidAction(): FilamentAction
    {
        return FilamentAction::make('mark_paid')
            ->label('Marcar pagada')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Fee $record) => $record->status !== 'paid')
            ->requiresConfirmation()
            ->modalHeading('Marcar cuota como pagada')
            ->modalDescription('¿Confirmar que esta cuota fue abonada manualmente?')
            ->action(function (Fee $record): void {
                $record->update([
                    'status'         => 'paid',
                    'payment_method' => 'manual',
                    'paid_at'        => now(),
                ]);

                activity()
                    ->performedOn($record)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'period' => $record->period,
                        'amount' => $record->amount,
                    ])
                    ->log('Cuota marcada como pagada manualmente');
            });
    }

    private static function generateMpLinkAction(): FilamentAction
    {
        return FilamentAction::make('generate_mp_link')
            ->label('Link MP')
            ->icon('heroicon-o-credit-card')
            ->color('info')
            ->visible(fn (Fee $record) => in_array($record->status, ['pending', 'overdue']))
            ->action(function (Fee $record): void {
                $service = app(MercadoPagoService::class);
                $data = $service->createPreference($record);

                \Filament\Notifications\Notification::make()
                    ->title('Link de pago generado')
                    ->body("Preference ID: {$data['preference_id']}")
                    ->success()
                    ->send();
            });
    }

    private static function memberPortalLinkAction(): FilamentAction
    {
        return FilamentAction::make('member_portal_link')
            ->label('Portal socio')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn (Fee $record) => URL::signedRoute('member.fees', ['member' => $record->member_id]))
            ->openUrlInNewTab();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFees::route('/'),
            'create' => Pages\CreateFee::route('/create'),
            'edit'   => Pages\EditFee::route('/{record}/edit'),
        ];
    }
}
