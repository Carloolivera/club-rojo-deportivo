<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Mail\MemberOverdueReminder;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\LeaveReason;
use App\Models\Member;
use App\Models\Reenrollment;
use App\Models\Sport;
use App\Rules\UniqueDni;
use Illuminate\Support\Facades\Mail;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction as FilamentBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Socios';

    protected static ?string $modelLabel = 'Socio';

    protected static ?string $pluralModelLabel = 'Socios';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole('manager') && $user->sport_id) {
            $query->where('sport_id', $user->sport_id);
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Datos personales')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('dni')
                        ->label('DNI')
                        ->required()
                        ->numeric()
                        ->minLength(7)
                        ->maxLength(8)
                        ->rules(fn (Get $get, $record) => [
                            new UniqueDni($record?->id),
                        ])
                        ->helperText('Sin puntos ni espacios'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('address')
                        ->label('Domicilio')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('birthdate')
                        ->label('Fecha de nacimiento')
                        ->required()
                        ->maxDate(now())
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, $state) => $set('is_minor', $state && \Carbon\Carbon::parse($state)->age < 18)
                        ),

                    Forms\Components\Hidden::make('is_minor'),
                ]),

            Section::make('Categoría y deporte')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('sport_id')
                        ->label('Deporte')
                        ->options(fn () => self::sportOptions())
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->rules([
                            function () {
                                return function (string $attribute, mixed $value, \Closure $fail) {
                                    $user = Auth::user();
                                    if ($user->hasRole('manager') && $user->sport_id && (int) $value !== $user->sport_id) {
                                        $fail('No podés asignar un socio a un deporte que no administrás.');
                                    }
                                };
                            },
                        ]),

                    Forms\Components\Select::make('fee_category_id')
                        ->label('Categoría de cuota')
                        ->options(fn () => FeeCategory::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->native(false),

                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'active' => 'Activo',
                            'inactive' => 'Inactivo',
                            'suspended' => 'Suspendido',
                            'pending' => 'Pendiente',
                        ])
                        ->required()
                        ->native(false)
                        ->default('active'),
                ]),

            Section::make('Responsables')
                ->description('Requerido para menores de 18 años.')
                ->visible(fn (Get $get) => (bool) $get('is_minor'))
                ->schema([
                    Forms\Components\Repeater::make('guardians')
                        ->label('Responsables')
                        ->relationship('guardians')
                        ->minItems(1)
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('dni')
                                ->label('DNI')
                                ->numeric()
                                ->maxLength(8),

                            Forms\Components\Select::make('relationship')
                                ->label('Vínculo')
                                ->options([
                                    'padre' => 'Padre',
                                    'madre' => 'Madre',
                                    'tutor' => 'Tutor/a legal',
                                    'otro' => 'Otro',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(30),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),
                        ]),
                ]),

            Section::make('Notas y consentimiento')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas internas')
                        ->maxLength(2000)
                        ->rows(3),

                    Forms\Components\Checkbox::make('consent')
                        ->label('El socio otorga consentimiento informado (Ley 25.326) para el tratamiento de sus datos personales.')
                        ->required()
                        ->accepted()
                        ->helperText('Obligatorio para completar el registro.')
                        ->hiddenOn('edit'),
                ]),
        ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            Section::make('Datos personales')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('dni')->label('DNI'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('phone')->label('Teléfono'),
                    TextEntry::make('address')->label('Domicilio')->columnSpanFull(),
                    TextEntry::make('birthdate')->label('Fecha de nacimiento')->date('d/m/Y'),
                    IconEntry::make('is_minor')->label('Menor de edad')->boolean(),
                ]),

            Section::make('Categoría y estado')
                ->columns(3)
                ->schema([
                    TextEntry::make('sport.name')->label('Deporte')->badge(),
                    TextEntry::make('feeCategory.name')->label('Categoría de cuota'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'active' => 'success',
                            'inactive', 'suspended' => 'danger',
                            'pending' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'active' => 'Activo',
                            'inactive' => 'Inactivo',
                            'suspended' => 'Suspendido',
                            'pending' => 'Pendiente',
                            default => $state,
                        }),
                ]),

            Section::make('Consentimiento')
                ->columns(2)
                ->schema([
                    TextEntry::make('consent_at')->label('Fecha de consentimiento')->dateTime('d/m/Y H:i'),
                    TextEntry::make('consent_ip')->label('IP de registro'),
                ]),

            Section::make('Notas')
                ->schema([
                    TextEntry::make('notes')->label('')->placeholder('Sin notas.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('sport.name')
                    ->label('Deporte')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('feeCategory.name')
                    ->label('Categoría')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive', 'suspended' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'suspended' => 'Suspendido',
                        'pending' => 'Pendiente',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_minor')
                    ->label('Menor')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-minus')
                    ->falseIcon('')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'suspended' => 'Suspendido',
                        'pending' => 'Pendiente',
                    ]),

                Tables\Filters\SelectFilter::make('sport_id')
                    ->label('Deporte')
                    ->options(fn () => Sport::pluck('name', 'id'))
                    ->visible(fn () => Auth::user()->hasRole('admin')),

                Tables\Filters\SelectFilter::make('fee_category_id')
                    ->label('Categoría de cuota')
                    ->options(fn () => FeeCategory::pluck('name', 'id')),

                Tables\Filters\Filter::make('dni')
                    ->form([
                        Forms\Components\TextInput::make('dni_value')
                            ->label('DNI')
                            ->numeric()
                            ->maxLength(8),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['dni_value'],
                        fn ($q) => $q->where('dni_hash', \App\Support\Dni::hash((string) trim($data['dni_value'])))
                    )
                    )
                    ->label('Buscar por DNI'),

                Tables\Filters\Filter::make('has_overdue_fees')
                    ->label('Con cuotas vencidas')
                    ->query(fn (Builder $query) => $query->whereHas(
                        'fees',
                        fn ($q) => $q->where('status', 'overdue')
                    ))
                    ->toggle(),

                Tables\Filters\Filter::make('is_minor')
                    ->label('Menores de edad')
                    ->query(fn (Builder $query) => $query->where('is_minor', true))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de alta')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false)
                            ->maxDate(now()),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false)
                            ->maxDate(now()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = 'Alta desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['until']) $indicators[] = 'Alta hasta ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    self::activateAction(),
                    self::deactivateAction(),
                    self::suspendAction(),
                    self::reenrollmentAction(),
                    self::sendOverdueReminderAction(),
                ])->label('Acciones'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    FilamentBulkAction::make('exportExcel')
                        ->label('Exportar Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            session(['export_member_ids' => $records->pluck('id')->toArray()]);
                            return redirect()->route('members.export.selected.excel');
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function activateAction(): FilamentAction
    {
        return FilamentAction::make('activate')
            ->label('Activar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Member $record) => $record->status !== 'active')
            ->requiresConfirmation()
            ->action(fn (Member $record) => $record->activate(Auth::user()));
    }

    private static function deactivateAction(): FilamentAction
    {
        return FilamentAction::make('deactivate')
            ->label('Dar de baja')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Member $record) => $record->status === 'active')
            ->form([
                Forms\Components\Select::make('reason')
                    ->label('Motivo de baja')
                    ->options(fn () => LeaveReason::pluck('name', 'id'))
                    ->required()
                    ->native(false),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas adicionales')
                    ->maxLength(500),
            ])
            ->action(fn (Member $record, array $data) => $record->deactivate(Auth::user(), (int) $data['reason'], $data['notes'] ?? null));
    }

    private static function suspendAction(): FilamentAction
    {
        return FilamentAction::make('suspend')
            ->label('Suspender')
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->visible(fn (Member $record) => $record->status === 'active')
            ->form([
                Forms\Components\Textarea::make('notes')
                    ->label('Motivo de suspensión')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(fn (Member $record, array $data) => $record->suspend(Auth::user(), $data['notes']));
    }

    private static function reenrollmentAction(): FilamentAction
    {
        return FilamentAction::make('solicitar-reempadronamiento')
            ->label('Solicitar reempadronamiento')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->visible(fn (Member $record) => $record->status === 'inactive')
            ->requiresConfirmation()
            ->modalHeading('Solicitar reempadronamiento')
            ->modalDescription('Se enviará una solicitud de reempadronamiento para este socio. Un administrador deberá aprobarla.')
            ->action(function (Member $record): void {
                $hasPending = Reenrollment::where('member_id', $record->id)
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPending) {
                    \Filament\Notifications\Notification::make()
                        ->title('Ya existe una solicitud pendiente para este socio.')
                        ->danger()
                        ->send();

                    return;
                }

                Reenrollment::create([
                    'member_id'    => $record->id,
                    'status'       => 'pending',
                    'requested_at' => now(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Solicitud de reempadronamiento creada.')
                    ->success()
                    ->send();
            });
    }

    private static function sendOverdueReminderAction(): FilamentAction
    {
        return FilamentAction::make('send-overdue-reminder')
            ->label('Enviar recordatorio de cuota')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->visible(fn (Member $record) => filled($record->email))
            ->requiresConfirmation()
            ->modalHeading('Enviar recordatorio de cuota vencida')
            ->modalDescription(fn (Member $record) => "Se enviará un email a {$record->name} con el detalle de sus cuotas vencidas.")
            ->action(function (Member $record): void {
                $overdueFees = Fee::where('member_id', $record->id)
                    ->where('status', 'overdue')
                    ->get();

                if ($overdueFees->isEmpty()) {
                    \Filament\Notifications\Notification::make()
                        ->title('Este socio no tiene cuotas vencidas.')
                        ->warning()
                        ->send();

                    return;
                }

                Mail::to($record->email)->send(new MemberOverdueReminder($record, $overdueFees));

                \Filament\Notifications\Notification::make()
                    ->title("Recordatorio enviado a {$record->name}.")
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }

    private static function sportOptions(): array
    {
        $user = Auth::user();
        if ($user->hasRole('manager') && $user->sport_id) {
            return Sport::where('id', $user->sport_id)->pluck('name', 'id')->toArray();
        }

        return Sport::pluck('name', 'id')->toArray();
    }
}
