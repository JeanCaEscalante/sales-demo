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
                Forms\Components\Section::make('Información del Comprobante')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship(name: 'supplier', titleAttribute: 'name')
                            ->label('Proveedor')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo Comprobante')
                            ->options(TypeReceipt::class)
                            ->required(),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Fecha')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        Forms\Components\TextInput::make('series')
                            ->label('Serie')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Número')
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, Forms\Get $get) {
                                return $rule->where('supplier_id', $get('supplier_id'));
                            }),
                    ])->columns(3),

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
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            $set('unit_cost', $product->price_in);
                                            $set('suggested_price', $product->price_out);
                                        }
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Costo Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get)),
                                Forms\Components\TextInput::make('suggested_price')
                                    ->label('Precio Venta Sug.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                            ])
                            ->columns(5)
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
                            ->label('Monto Total')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                    ])->columns(2),
            ]);
    }

    public static function updateTotals(Forms\Set $set, Forms\Get $get): void
    {
        $selectedProducts = collect($get('items'))->filter(fn ($item) => ! empty($item['product_id']) && ! empty($item['quantity']) && ! empty($item['unit_cost']));

        $total = $selectedProducts->reduce(function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['unit_cost']);
        }, 0);

        $set('total_amount', number_format($total, 2, '.', ''));
        // For now, tax is 0 or manual, but we could implement tax logic here if needed.
        $set('total_tax', 0);
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
