<?php

namespace App\Filament\Resources;

use App\Enums\TypeContact;
use App\Enums\TypeDocument;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $pluralLabel = 'Clientes';

    protected static ?string $label = 'Cliente';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Documento' => $record->type_document->getLabel().': '.$record->document,
            'Crédito' => '$'.number_format($record->credit_limit ?? 0, 2),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'document', 'address'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información del Cliente')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Select::make('type_document')
                                    ->label('Tipo de documento')
                                    ->options(TypeDocument::class)
                                    ->live()
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('document')
                                    ->label('Número de documento')
                                    ->placeholder(fn (Forms\Get $get) => match ($get('type_document')?->value ?? $get('type_document')) {
                                        'J' => '900.123.456-7',
                                        'N' => '1.234.567.890',
                                        default => 'Ingrese el documento'
                                    })
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre completo / Razón social')
                                    ->placeholder('Ej: Juan Pérez o Empresa S.A.')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->placeholder('Calle, número, barrio, ciudad...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Contactos')
                            ->icon('heroicon-o-phone')
                            ->description('Agrega los medios de contacto del cliente')
                            ->schema([
                                Forms\Components\Repeater::make('contacts')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('type_contact')
                                            ->label('Tipo')
                                            ->options(TypeContact::class)
                                            ->live()
                                            ->required()
                                            ->native(false),
                                        Forms\Components\TextInput::make('contact')
                                            ->label('Contacto')
                                            ->placeholder(fn (Forms\Get $get) => match ($get('type_contact')?->value ?? $get('type_contact')) {
                                                'phone', 'mobile' => '+57 300 123 4567',
                                                'email' => 'cliente@email.com',
                                                'whatsapp' => '+57 300 123 4567',
                                                default => ''
                                            })
                                            ->required(),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('¿Contacto principal?')
                                            ->inline(false),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['contact'] ?? 'Nuevo contacto'
                                    )
                                    ->defaultItems(1)
                                    ->addActionLabel('Agregar contacto')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Crédito')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('credit_limit')
                                    ->label('Límite de crédito')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->helperText('Monto máximo permitido a crédito'),
                                Forms\Components\Placeholder::make('credit_used')
                                    ->label('Crédito utilizado')
                                    ->content(fn (?Customer $record): string => '$'.number_format($record?->sales()->where('status', 'pending')->sum('total_amount') ?? 0, 2)
                                    )
                                    ->hidden(fn (?Customer $record) => $record === null),
                                Forms\Components\Placeholder::make('credit_available')
                                    ->label('Disponible')
                                    ->content(function (?Customer $record): string {
                                        if (! $record) {
                                            return '—';
                                        }
                                        $used = $record->sales()->where('status', 'pending')->sum('total_amount');
                                        $available = ($record->credit_limit ?? 0) - $used;

                                        return '$'.number_format(max(0, $available), 2);
                                    })
                                    ->hidden(fn (?Customer $record) => $record === null),
                            ]),
 
                        Forms\Components\Section::make('Resumen')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('sales_count')
                                    ->label('Total de compras')
                                    ->content(fn (?Customer $record): string => $record?->sales()->count() ?? '0'
                                    ),
                                Forms\Components\Placeholder::make('total_purchased')
                                    ->label('Monto total')
                                    ->content(fn (?Customer $record): string => '$'.number_format($record?->sales()->sum('total_amount') ?? 0, 2)
                                    ),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Cliente desde')
                                    ->content(fn (?Customer $record): string => $record?->created_at?->format('d/m/Y') ?? '—'
                                    ),
                            ])
                            ->hidden(fn (?Customer $record) => $record === null),

                        Forms\Components\Section::make('Notas')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('')
                                    ->placeholder('Notas internas sobre este cliente...')
                                    ->rows(3),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record): string => $record->type_document->getLabel().': '.$record->document
                    ),
                Tables\Columns\TextColumn::make('primary_contact')
                    ->label('Contacto')
                    ->getStateUsing(function (Customer $record) {
                        $primary = $record->contacts()->where('is_primary', true)->first()
                            ?? $record->contacts()->first();

                        return $primary?->contact ?? '—';
                    })
                    ->icon(fn (Customer $record) => match (
                        $record->contacts()->where('is_primary', true)->first()?->type_contact?->value
                        ?? $record->contacts()->first()?->type_contact?->value
                    ) {
                        'phone', 'mobile' => 'heroicon-o-phone',
                        'email' => 'heroicon-o-envelope',
                        'whatsapp' => 'heroicon-o-chat-bubble-left',
                        default => null
                    })
                    ->copyable()
                    ->copyMessage('Copiado'),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite crédito')
                    ->money('USD')
                    ->sortable()
                    ->color(fn (Customer $record): string => ($record->credit_limit ?? 0) > 0 ? 'success' : 'gray'
                    ),
                Tables\Columns\TextColumn::make('sales_count')
                    ->label('Compras')
                    ->counts('sales')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'info',
                        $state < 15 => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('sales_sum_total_amount')
                    ->label('Total comprado')
                    ->sum('sales', 'total_amount')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(35)
                    ->tooltip(fn (Customer $record) => $record->address)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type_document')
                    ->label('Tipo documento')
                    ->options(TypeDocument::class),
                Tables\Filters\Filter::make('has_credit')
                    ->label('Con crédito')
                    ->query(fn ($query) => $query->where('credit_limit', '>', 0))
                    ->toggle(),
                Tables\Filters\Filter::make('has_sales')
                    ->label('Con compras')
                    ->query(fn ($query) => $query->has('sales'))
                    ->toggle(),
                Tables\Filters\Filter::make('no_sales')
                    ->label('Sin compras')
                    ->query(fn ($query) => $query->doesntHave('sales'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('newSale')
                        ->label('Nueva venta')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('primary')
                        ->url(fn (Customer $record) => route('filament.admin.resources.sales.create', ['customer_id' => $record->id])
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin clientes registrados')
            ->emptyStateDescription('Agrega tu primer cliente para comenzar a vender.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar cliente')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
