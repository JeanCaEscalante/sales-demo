<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $pluralLabel = 'Compras';

    protected static ?string $label = 'Compra';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // Sección: Empleado
                Forms\Components\Section::make('Empleado')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Empleado')
                            ->relationship('user', 'name')
                            ->searchable(['name', 'document'])
                            ->preload()
                            ->required(),
                    ])
                    ->columnSpan(4),

                // Sección: Selección de Proveedor
                Forms\Components\Section::make('Proveedor')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Proveedor')
                            ->relationship('supplier', 'name')
                            ->searchable(['name', 'document'])
                            ->preload()
                            ->required()
                            ->createOptionForm(fn (Form $form) => SupplierResource::form($form))
                            ->createOptionModalHeading('Crear Proveedor')
                            ->live(),
                    ])
                    ->columnSpan(8),
                Forms\Components\Section::make('Información del Comprobante')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo Comprobante')
                            ->options(TypeReceipt::class)
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('series')
                            ->label('Serie')
                            ->maxLength(10)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Número')
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, Forms\Get $get) {
                                return $rule->where('supplier_id', $get('supplier_id'));
                            })
                            ->columnSpan(2),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Fecha')
                            ->default(now())
                            ->maxDate(now())
                            ->required()
                            ->columnSpan(2),
                    ])->columns(8)
                    ->columnSpanFull(),
                Forms\Components\Section::make('Detalles de la Compra')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('Productos')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship(name: 'product', titleAttribute: 'name')
                                    ->label('Producto')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $product = \App\Models\Product::find($state);

                                        if ($product) {
                                            $set('current_stock', $product->stock);
                                            $set('profit', $product->profit);
                                            $set('price_out', $product->price_out);
                                        }
                                    }),
                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Cantidad Actual')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('profit')
                                    ->label('Ganancia')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->prefix('%')
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateCalculations($set, $get))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('price_out')
                                    ->label('Precio de venta actual')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly()
                                    ->columnSpan(2),
                                //Informacio de la compra
                                Forms\Components\Fieldset::make('Información de la compra')
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateCalculations($set, $get))
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Precio Unitario')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateCalculations($set, $get))
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('net_total')
                                            ->label('Total Neto')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->readOnly()
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('sale_price')
                                            ->label('Precio de venta')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->readOnly()
                                            ->columnSpan(2),
                                    ])
                                    ->columns(8)
                                    ->columnSpanFull(),
                            ])
                            ->columns(8)
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get))
                            ->deleteAction(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                    ]),

                Forms\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\TextInput::make('total_tax')
                            ->label('Impuesto Total')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Costo Total')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                    ])->columns(2),
            ])
            ->columns(12);
    }

    public static function updateCalculations(Forms\Set $set, Forms\Get $get): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitCost = (float) ($get('unit_price') ?? 0);
        $profit = (float) ($get('profit') ?? 0);

        $netTotal = $quantity * $unitCost;
        $set('net_total', number_format($netTotal, 2, '.', ''));

        $salePrice = $unitCost + ($unitCost * ($profit / 100));
        $set('sale_price', number_format($salePrice, 2, '.', ''));

        self::updateTotals($set, $get);
    }

    public static function updateTotals(Forms\Set $set, Forms\Get $get): void
    {
        // Check if we are in a repeater item context
        $items = $get('items');
        $isRemote = false;

        if ($items === null) {
            // We are likely inside the repeater item, so we go up
            $items = $get('../../items');
            $isRemote = true;
        }

        $items = collect($items ?? []);

        $total = $items->reduce(function ($carry, $item) {
            return $carry + ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));
        }, 0);

        $path = $isRemote ? '../../total_amount' : 'total_amount';
        $taxPath = $isRemote ? '../../total_tax' : 'total_tax';

        $set($path, number_format($total, 2, '.', ''));
        $set($taxPath, 0);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Proveedor')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class),
                Tables\Filters\Filter::make('purchase_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query) => $query->whereDate('purchase_date', '>=', $data['from']))
                            ->when($data['until'], fn ($query) => $query->whereDate('purchase_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
