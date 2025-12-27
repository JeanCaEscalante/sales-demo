<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Discount;
use App\Services\SaleCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

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
                // Sección: Tipo de Documento
                Forms\Components\Section::make('Tipo Documento')
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
                                $set('invoice_number', $result['invoice_number']);
                                $set('serial', $result['serial']);
                            }),
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
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    return;
                                }
                                
                                $customer = \App\Models\Customer::find($state);
                                if ($customer) {
                                    $set('customer_address', $customer->address);
                                }
                            }),
                    ])
                    ->columnSpan(8),

                // Sección: Datos del Documento
                Forms\Components\Section::make('Datos del Documento')
                    ->schema([
                        Forms\Components\TextInput::make('series')
                            ->label('Serie')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Número de Factura')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('serial')
                            ->label('Serie Completa')
                            ->disabled()
                            ->columnSpan(2),
                        
                        Forms\Components\DatePicker::make('operation_date')
                            ->label('Fecha de Operación')
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
                                        if (!$state) {
                                            return;
                                        }
                                        
                                        $product = Product::find($state);
                                        if (!$product) {
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
                                        
                                        // Cargar datos del producto
                                        $set('unit_price', $product->price_out);
                                        $set('quantity', 1);
                                        
                                        // Cargar impuesto si existe
                                        if ($product->tax_rate_id) {
                                            $set('tax_rate_id', $product->tax_rate_id);
                                        }
                                        
                                        // Calcular totales
                                        SaleCalculationService::calculateLineItem($get, $set);
                                    })
                                    ->columnSpan(4),
                                
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
                                            
                                            if (!$validation['valid']) {
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
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Precio Unitario')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        SaleCalculationService::calculateLineItem($get, $set);
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Descuento')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        SaleCalculationService::calculateLineItem($get, $set);
                                    })
                                    ->columnSpan(2),
                                
                                Forms\Components\Select::make('tax_rate_id')
                                    ->label('Impuesto')
                                    ->relationship('taxRate', 'name')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        SaleCalculationService::calculateLineItem($get, $set);
                                    })
                                    ->columnSpan(3),
                                
                                // Resultados calculados
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Monto Impuesto')
                                    ->readOnly()
                                    ->prefix('$')
                                    ->columnSpan(3),
                                
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Total Línea')
                                    ->readOnly()
                                    ->prefix('$')
                                    ->columnSpan(4),
                            ])
                            ->columns(10)
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

                // Sección: Totales
                Forms\Components\Section::make('Totales')
                    ->schema([
                       /*  Forms\Components\Select::make('discount_id')
                            ->label('Código de Descuento')
                            ->relationship('discount', 'code')
                            ->searchable(['code', 'name'])
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                SaleCalculationService::applyDiscount($get, $set, $state);
                            }), */
                        
                        Forms\Components\TextInput::make('total_base')
                            ->label('Subtotal Base')
                            ->readOnly()
                            ->prefix('$')
                            ->default('0.00'),
                        
                        Forms\Components\TextInput::make('total_taxes')
                            ->label('Total Impuestos')
                            ->readOnly()
                            ->prefix('$')
                            ->default('0.00'),
                        
                        Forms\Components\TextInput::make('total_discounts')
                            ->label('Descuentos')
                            ->readOnly()
                            ->prefix('$')
                            ->default('0.00'),
                        
                        Forms\Components\TextInput::make('total_amount')
                            ->label('TOTAL A PAGAR')
                            ->readOnly()
                            ->prefix('$')
                            ->extraAttributes(['class' => 'font-bold text-lg'])
                            ->default('0.00'),
                    ])
                    ->columnSpan(3),

                // Sección: Formas de Pago
                Forms\Components\Section::make('Formas de Pago')
                    ->schema([
                        Forms\Components\Repeater::make('payments')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('constant')
                                    ->label('Forma de pago')
                                    ->options([
                                        'TYPE_CASH' => 'Al contado',
                                        'TYPE_CARD' => 'Tarjeta',
                                        'TYPE_TRANSFER' => 'Transferencia',
                                        'TYPE_DEBIT' => 'Recibo Domiciliado',
                                    ])
                                    ->required(),
                                
                                Forms\Components\TextInput::make('amount')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€'),
                                
                                Forms\Components\DatePicker::make('dueDate')
                                    ->label('Fecha de Vencimiento'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Añadir forma de pago')
                            ->collapsible(),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),
            ])
            ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial')
                    ->label('Nº Documento')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('operation_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type_document')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_document')
                    ->label('Tipo de Documento')
                    ->options(TypeReceipt::class),
                
                Tables\Filters\Filter::make('operation_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('operation_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('operation_date', '<=', $date));
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
        return [];
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