<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $pluralLabel = 'Productos';

    protected static ?string $label = 'Producto';

    protected static ?int $navigationSort = 1;

    // Badge con conteo de productos con stock bajo
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereColumn('stock', '<=', 'min_stock')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Columna principal (2/3)
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información del Producto')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Código/SKU')
                                    ->placeholder('AUTO-001')
                                    ->helperText('Dejar vacío para generar automáticamente')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del producto')
                                    ->placeholder('Ej: Laptop Dell Inspiron 15')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'category_name')
                                    ->label('Categoría')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('category_name')
                                            ->label('Nombre de categoría')
                                            ->required(),
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('unit_id')
                                    ->relationship('unit', 'name')
                                    ->label('Unidad de medida')
                                    ->preload()
                                    ->searchable(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->placeholder('Descripción detallada del producto...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Precios y Márgenes')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Precio de costo')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateSalePrice($set, $get))
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\TextInput::make('profit')
                                    ->label('Margen de ganancia')
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->numeric()
                                    ->default(30)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::calculateSalePrice($set, $get)),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de venta')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->extraAttributes(['class' => 'font-bold text-success-600'])
                                    ->required(),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpan(['lg' => 2]),

                // Sidebar (1/3)
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Imagen')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),

                        Forms\Components\Section::make('Inventario')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stock actual')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('adjustStock')
                                            ->icon('heroicon-o-plus-circle')
                                            ->tooltip('Ajustar stock')
                                    ),
                                Forms\Components\TextInput::make('min_stock')
                                    ->label('Stock mínimo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(5)
                                    ->helperText('Alerta cuando llegue a este nivel')
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('Impuestos')
                            ->icon('heroicon-o-receipt-percent')
                            ->schema([
                                Forms\Components\Toggle::make('is_tax_exempt')
                                    ->label('Exento de impuesto')
                                    ->live()
                                    ->default(false),
                                Forms\Components\Select::make('tax_rate_id')
                                    ->relationship('tax_rate', 'name')
                                    ->label('Tasa de impuesto')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => ! $get('is_tax_exempt')),
                            ]),

                        Forms\Components\Section::make('Estado')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Producto activo')
                                    ->default(true)
                                    ->helperText('Los productos inactivos no aparecen en ventas'),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=P&background=random')
                    ->size(40),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->fontFamily('mono')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => $record->category?->category_name ?? 'Sin categoría'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record): string => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    })
                    ->icon(fn (Product $record): ?string => $record->stock <= $record->min_stock ? 'heroicon-o-exclamation-triangle' : null),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Costo')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio venta')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Margen')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'category_name')
                    ->label('Categoría')
                    ->preload()
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bajo')
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sin stock')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', 0))
                    ->toggle(),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('adjustStock')
                        ->label('Ajustar stock')
                        ->icon('heroicon-o-plus-circle')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('type')
                                ->label('Tipo de ajuste')
                                ->options([
                                    'add' => 'Agregar stock',
                                    'subtract' => 'Restar stock',
                                    'set' => 'Establecer cantidad',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->rows(2),
                        ])
                        ->action(function (Product $record, array $data) {
                            // Lógica de ajuste de stock
                        }),
                    Tables\Actions\ReplicateAction::make()
                        ->excludeAttributes(['code', 'stock']),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->emptyStateHeading('No hay productos')
            ->emptyStateDescription('Comienza agregando tu primer producto al inventario.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear producto')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    private static function sanitizeMoneyValue(?string $value): float
    {
        return (float) str_replace(',', '', $value ?? '0');
    }

    private static function calculateSalePrice(Forms\Set $set, Forms\Get $get): void
    {
        $profit = self::sanitizeMoneyValue($get('profit'));
        $unitPrice = self::sanitizeMoneyValue($get('unit_price'));

        $salePrice = $unitPrice * (1 + $profit / 100);

        $set('sale_price', number_format($salePrice, 2, '.', ''));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchasesRelationManager::class,
            RelationManagers\DiscountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
