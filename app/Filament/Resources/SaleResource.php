<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $pluralLabel = 'Ventas';

    protected static ?string $label = 'Venta';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección: Vendedor
                Forms\Components\Section::make('Vendedor')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Vendedor')
                            ->relationship('user', 'name')
                            ->default(Auth::id())
                            ->required(),
                    ])
                    ->columnSpan(4),

                // Sección: Selección de Cliente
                Forms\Components\Section::make('Cliente')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable(['name', 'document'])
                            ->preload()
                            ->required()
                            ->createOptionForm(fn (Form $form) => CustomerResource::form($form))
                            ->createOptionModalHeading('Crear Cliente')
                            ->live(),
                    ])
                    ->columnSpan(8),

                // Sección: Datos del Documento
                Forms\Components\Section::make('Datos del Documento')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo Comprobante')
                            ->options(TypeReceipt::class)
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $result = SaleCalculationService::generateSeriesNumber(
                                    $state,
                                    Auth::id()
                                );

                                $set('series', $result['series']);
                                $set('number', $result['number']);
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('series')
                            ->label(fn (Get $get) => match ($get('document_type')) {
                                TypeReceipt::Bill => 'Serie de Factura',
                                TypeReceipt::Ticket => 'Serie de Ticket',
                                default => 'Serie',
                            })
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('number')
                            ->label(fn (Get $get) => match ($get('document_type')) {
                                TypeReceipt::Bill => 'Número de Factura',
                                TypeReceipt::Ticket => 'Número de Ticket',
                                default => 'Número',
                            })
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),
                        Forms\Components\DatePicker::make('sale_date')
                            ->label('Fecha del Documento')
                            ->required()
                            ->default(now())
                            ->columnSpan(2),
                    ])
                    ->columns(8)
                    ->columnSpanFull(),

                // Sección: Productos
                Forms\Components\Section::make('Productos/Artículos')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                // Selección de Producto
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto/Artículo')
                                    ->relationship('product', 'name')
                                    ->searchable(['name', 'code'])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = Product::find($state);
                                        if (! $product) {
                                            return;
                                        }

                                        // Validar stock
                                        if ($product->stock <= 0) {
                                            Notification::make()
                                                ->warning()
                                                ->title('Sin stock')
                                                ->body("El producto {$product->name} no tiene stock disponible")
                                                ->send();

                                            $set('product_id', null);

                                            return;
                                        }

                                        // Cargar datos del producto e impuestos
                                        $set('unit_price', $product->sale_price);
                                        $set('quantity', 1);
                                        $set('tax_rate_id', $product->tax_rate_id);

                                        // Snapshot de impuestos
                                        if ($product->taxRate) {
                                            $set('tax_rate', $product->taxRate->rate);
                                            $set('tax_name', $product->taxRate->name);
                                        }

                                        // Calcular totales
                                        SaleCalculationService::calculateLineItem($get, $set);
                                    }),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Precio Unitario')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        SaleCalculationService::calculateLineItem($get, $set);
                                        // Actualizar totales del documento después del cálculo de línea
                                        SaleCalculationService::calculateDocumentTotals($get, $set);
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $validation = SaleCalculationService::validateStock($productId, $state);

                                            if (! $validation['valid']) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title('Stock insuficiente')
                                                    ->body($validation['message'])
                                                    ->send();

                                                $set('quantity', $validation['available_stock'] ?? 1);

                                                return;
                                            }
                                        }

                                        SaleCalculationService::calculateLineItem($get, $set);
                                        // Actualizar totales del documento después del cálculo de línea
                                        SaleCalculationService::calculateDocumentTotals($get, $set);
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal Neto')
                                    ->readOnly()
                                    ->prefix('$'),

                                Forms\Components\Hidden::make('tax_rate'),
                                Forms\Components\Hidden::make('tax_name'),
                                Forms\Components\Hidden::make('tax_amount'),
                                Forms\Components\Hidden::make('tax_rate_id'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Añadir artículo')
                            ->collapsible()
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                SaleCalculationService::calculateDocumentTotals($get, $set);
                            })
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action
                                    ->after(function (Get $get, Set $set) {
                                        SaleCalculationService::calculateDocumentTotals($get, $set);
                                    })
                            ),
                    ])
                    ->columnSpan(9),

                // Grupo de secciones en la columna derecha
                Forms\Components\Group::make([

                    // Sección: Totales
                    Forms\Components\Section::make('Totales')
                        ->schema([
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->readOnly()
                                ->prefix('$')
                                ->default('0.00'),

                            Forms\Components\TextInput::make('taxable_base')
                                ->label('Base Imponible')
                                ->readOnly()
                                ->prefix('$')
                                ->default('0.00'),

                            Forms\Components\TextInput::make('total_tax')
                                ->label('Total Impuestos')
                                ->readOnly()
                                ->prefix('$')
                                ->default('0.00'),

                            Forms\Components\TextInput::make('total_amount')
                                ->label('TOTAL A PAGAR')
                                ->readOnly()
                                ->prefix('$')
                                ->extraAttributes(['class' => 'font-bold text-lg'])
                                ->default('0.00'),
                        ]),
                    // Sección: Moneda y Tasa de Cambio
                    Forms\Components\Section::make('Moneda')
                        ->schema([
                            Forms\Components\Select::make('currency_id')
                                ->label('Moneda')
                                ->options(Currency::query()->pluck('name', 'currency_id'))
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {

                                    $currency = Currency::find($state)?->getCurrentRate();
                                    $total_amount = $get('total_amount');
                                    $exchanged_amount = $total_amount * $currency;
                                    $set('exchange_rate', number_format($currency, 2));
                                    $set('exchanged_amount', number_format($exchanged_amount, 2));
                                })
                                ->live(onBlur: true)
                                ->default('USD'),

                            Forms\Components\TextInput::make('exchange_rate')
                                ->label('Tasa de Cambio')
                                ->readOnly(),

                            Forms\Components\TextInput::make('exchanged_amount')
                                ->label('TOTAL AL CAMBIO')
                                ->readOnly()
                                ->prefix('$')
                                ->extraAttributes(['class' => 'font-bold text-lg'])
                                ->default('0.00'),
                        ])->visibleOn('create'),

                ])
                    ->columnSpan(3),
            ])
            ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nº Documento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo Comprobante')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado Pago')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class),

                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('sale_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('sale_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
