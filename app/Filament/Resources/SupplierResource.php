<?php

namespace App\Filament\Resources;

use App\Enums\TypeContact;
use App\Enums\TypeDocument;
use App\Enums\TypeLabel;
use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $pluralLabel = 'Proveedores';

    protected static ?string $label = 'Proveedor';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Documento' => $record->type_document->getLabel().': '.$record->document,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'document', 'address'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información del Proveedor')
                            ->icon('heroicon-o-building-storefront')
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
                                        'nit' => '900.123.456-7',
                                        'cedula' => '1.234.567.890',
                                        'pasaporte' => 'AB1234567',
                                        default => 'Ingrese el documento'
                                    })
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre / Razón Social')
                                    ->placeholder('Ej: Distribuidora Nacional S.A.')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->placeholder('Calle, número, ciudad...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Contactos')
                            ->icon('heroicon-o-phone')
                            ->description('Medios de contacto del proveedor')
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
                                                'email' => 'contacto@empresa.com',
                                                'whatsapp' => '+57 300 123 4567',
                                                default => ''
                                            })
                                            ->required(),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('Principal')
                                            ->inline(),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->cloneable()
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
                        Forms\Components\Section::make('Notas internas')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('')
                                    ->placeholder('Notas privadas sobre este proveedor...')
                                    ->rows(4),
                            ])
                            ->collapsible(),

                        Forms\Components\Section::make('Resumen')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('purchases_count')
                                    ->label('Total de compras')
                                    ->content(fn (?Supplier $record): string => $record?->purchases()->count() ?? '0'
                                    ),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Registrado')
                                    ->content(fn (?Supplier $record): string => $record?->created_at?->format('d/m/Y') ?? '—'
                                    ),
                            ])
                            ->hidden(fn (?Supplier $record) => $record === null),
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
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Supplier $record): string => $record->type_document->getLabel().': '.$record->document
                    ),
                Tables\Columns\TextColumn::make('primary_contact')
                    ->label('Contacto principal')
                    ->getStateUsing(function (Supplier $record) {
                        $primary = $record->contacts()->where('is_primary', true)->first()
                            ?? $record->contacts()->first();

                        return $primary?->contact ?? '—';
                    })
                    ->icon(fn (Supplier $record) => match (
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
                Tables\Columns\TextColumn::make('purchases_count')
                    ->label('Compras')
                    ->counts('purchases')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'info',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(40)
                    ->tooltip(fn (Supplier $record) => $record->address)
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
                Tables\Filters\Filter::make('has_purchases')
                    ->label('Con compras')
                    ->query(fn ($query) => $query->has('purchases'))
                    ->toggle(),
                Tables\Filters\Filter::make('no_purchases')
                    ->label('Sin compras')
                    ->query(fn ($query) => $query->doesntHave('purchases'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin proveedores')
            ->emptyStateDescription('Agrega tu primer proveedor para comenzar.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar proveedor')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
