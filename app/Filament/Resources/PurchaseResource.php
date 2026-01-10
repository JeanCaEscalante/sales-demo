<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Currency;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
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
    ->description('Datos del documento de compra y moneda de la transacción')
    ->schema([
        // Primera fila: Datos del comprobante
        Forms\Components\Grid::make(8)
            ->schema([
                Forms\Components\Select::make('document_type')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class)
                    ->required()
                    ->placeholder('Seleccione tipo')
                    ->columnSpan(2),
                    
                Forms\Components\TextInput::make('series')
                    ->label('Serie')
                    ->placeholder('Ej: F001')
                    ->maxLength(10)
                    ->columnSpan(2),
                    
                Forms\Components\TextInput::make('receipt_number')
                    ->label('Número')
                    ->placeholder('Ej: 00001234')
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
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->columnSpan(2),
            ]),
        
        // Divisor visual
        Forms\Components\Grid::make(1)
            ->schema([
                Forms\Components\Placeholder::make('divider')
                    ->label('')
                    ->content('')
                    ->extraAttributes(['class' => 'border-t border-gray-200 dark:border-gray-700 my-2']),
            ]),
        
        // Segunda fila: Moneda y tasa de cambio
        Forms\Components\Grid::make(8)
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Moneda de la Factura')
                    ->options(Currency::query()->where('is_active', true)->pluck('name', 'currency_id'))
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $currency = Currency::find($state);
                        $rate = $currency?->getCurrentRate() ?? 1;
                        $set('exchange_rate', number_format($rate, 2));
                        
                        // Recalcular precios de venta si el toggle está activo
                        self::recalculateSalePricesAfterCurrencyChange($set, $get);
                        
                        // Recalcular totales con nueva tasa
                        self::updateTotals($set, $get);
                    })
                    ->live(onBlur: true)
                    ->default(function () {
                        return Currency::where('is_base', true)->first()?->currency_id;
                    })
                    ->required()
                    ->placeholder('Seleccione moneda')
                    ->searchable()
                    ->preload()
                    ->columnSpan(4),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Tasa de Cambio')
                    ->prefix('1 USD =')
                    ->suffix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '')
                    ->default('1.00')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        // Recalcular totales con nueva tasa
                        self::updateTotals($set, $get);
                    })
                    ->dehydrated()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->helperText('Tasa de conversión a moneda local')
                    ->columnSpan(4),
            ]),
    ])
    ->columns(1)
    ->columnSpanFull()
    ->collapsible(),

                Forms\Components\Section::make('Detalles de la Compra')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('Productos')
                            ->schema([
                                // ================================================
                                // SECCIÓN 1: SELECCIÓN DE PRODUCTO
                                // ================================================
                                Forms\Components\Select::make('product_id')
                                    ->relationship(name: 'product', titleAttribute: 'name')
                                    ->label('Producto')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            // Solo campos que realmente se usan
                                            $set('profit', $product->profit ?? 0);
                                            $set('tax_exempt', $product->tax_exempt ?? false);
                                            $set('tax_rate_id', $product->tax_rate_id ?? null);

                                            // Resetear precio venta para recalcular
                                            $set('sale_price', $product->sale_price ?? null);
                                        }

                                        self::updateCalculations($set, $get);
                                    }),

                                // ================================================
                                // SECCIÓN 2: DATOS DE COMPRA
                                // ================================================
                                Forms\Components\Section::make('Datos de Compra')
                                    ->description('Información sobre la linea de compra')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.01)
                                                    ->step(0.01)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateCalculations($set, $get)),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Costo Unitario (Moneda Factura)')
                                                    ->numeric()
                                                    ->prefix(fn (Forms\Get $get) => Currency::find($get('../../currency_id'))?->symbol ?? '$')
                                                    ->step(0.0001)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        // Resetear precio venta para recalcular desde ganancia
                                                        self::updateCalculations($set, $get);
                                                    }),

                                                Forms\Components\TextInput::make('discount')
                                                    ->label('% Descuento')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->step(0.01)
                                                    ->suffix('%')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        self::updateCalculations($set, $get);
                                                    }),

                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->numeric()
                                                    ->prefix(fn (Forms\Get $get) => Currency::find($get('../../currency_id'))?->symbol ?? '$')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->extraAttributes(['class' => 'font-bold bg-gray-50']),
                                            ]),
                                    ])
                                    ->compact()
                                    ->columnSpanFull(),

                                // ================================================
                                // SECCIÓN 3: IMPUESTOS (Colapsado)
                                // ================================================
                                Forms\Components\Section::make('Impuestos')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\Toggle::make('tax_exempt')
                                                    ->label('Producto Exento de Impuesto')
                                                    ->inline(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                        if ($state) {
                                                            $set('tax_rate_id', null);
                                                        }
                                                        self::updateCalculations($set, $get);
                                                    })->columnSpan(2),

                                                //solo visible en crear
                                                Forms\Components\Select::make('tax_rate_id')
                                                    ->label('Tipo de Impuesto')
                                                    ->relationship('tax', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un impuesto')
                                                    ->hidden(fn (Forms\Get $get) => $get('tax_exempt'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateCalculations($set, $get))
                                                    ->visibleOn('create'),

                                                //solo visible en editar
                                                Forms\Components\TextInput::make('tax_name')
                                                    ->label('Tipo de Impuesto')
                                                    ->disabled()
                                                    ->hidden(fn (Forms\Get $get) => $get('tax_exempt'))
                                                    ->visibleOn('edit'),

                                                Forms\Components\TextInput::make('tax_amount')
                                                    ->label('Impuesto')
                                                    ->numeric()
                                                    ->prefix(fn (Forms\Get $get) => Currency::find($get('../../currency_id'))?->symbol ?? '$')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->helperText('Monto de IVA')
                                                    ->extraAttributes(['class' => 'font-semibold']),

                                            ]),
                                    ])
                                    ->columns(4)
                                    ->collapsible()
                                    ->collapsed()
                                    ->compact()
                                    ->columnSpanFull(),

                                // ================================================
                                // SECCIÓN 4: CONFIGURACIÓN DE VENTA
                                // ================================================

                                Forms\Components\Section::make('Configuración de Venta')
                                    ->description('Define el precio de venta al público')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\Toggle::make('update_sale_price')
                                                    ->label('Actualizar Precio de Venta')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        if ($state) {
                                                            // Al activar, limpiar precio para forzar recálculo automático
                                                            $set('sale_price', null);
                                                        } else {
                                                            // Al desactivar, limpiar todos los campos relacionados
                                                            $set('sale_price', null);
                                                            $set('final_price', null);
                                                            $set('unit_tax_amount', null);
                                                        }
                                                        self::updateCalculations($set, $get);
                                                    })
                                                    ->helperText('Autoriza modificar el precio del producto')
                                                    ->columnSpanFull(),

                                                Forms\Components\TextInput::make('profit')
                                                    ->label('% Ganancia')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(500)
                                                    ->step(0.01)
                                                    ->suffix('%')
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        // Recalcular precio venta desde nueva ganancia
                                                        self::updateCalculations($set, $get);
                                                    })
                                                    ->helperText('Margen sobre costo'),

                                                Forms\Components\TextInput::make('sale_price')
                                                    ->label('Nuevo Precio Venta (Base)')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->prefix(fn () => Currency::where('is_base', true)->first()?->symbol ?? '$')
                                                    ->required(fn (Forms\Get $get) => $get('update_sale_price'))
                                                    ->disabled(fn (Forms\Get $get) => ! $get('update_sale_price'))
                                                    ->rules([
                                                        fn (Forms\Get $get): array => $get('update_sale_price') 
                                                            ? ['required', 'numeric', 'min:0.01'] 
                                                            : []
                                                    ])
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        // Primero recalcular profit, luego el resto
                                                        self::recalculateProfitFromSalePrice($set, $get);
                                                        self::updateCalculations($set, $get);
                                                    })
                                                    ->helperText(fn () => 'Precio en '.Currency::where('is_base', true)->first()?->name ?? 'moneda base')
                                                    ->extraAttributes(['class' => 'font-semibold bg-blue-50']),

                                                Forms\Components\TextInput::make('unit_tax_amount')
                                                    ->label('IVA Unitario')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->helperText('Impuesto por unidad')
                                                    ->extraAttributes(['class' => 'text-gray-600']),

                                                Forms\Components\TextInput::make('final_price')
                                                    ->label('Precio Final')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->helperText('PVP con IVA')
                                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                                            ]),
                                    ])
                                    ->compact()
                                    ->columnSpanFull()
                                    ->visibleOn('create'),
                            ])
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {

                                if ($data['tax_exempt'] ?? false) {
                                    $data['tax_rate_id'] = null;
                                    $data['tax_rate'] = null;
                                    $data['tax_name'] = null;
                                    $data['tax_amount'] = 0;
                                } elseif (! empty($data['tax_rate_id'])) {
                                    $tax = \App\Models\TaxRate::find($data['tax_rate_id']);
                                    if ($tax) {
                                        $data['tax_rate'] = $tax->rate;
                                        $data['tax_name'] = $tax->name;
                                    }
                                }

                                // Asegurar que el toggle se guarde correctamente
                                $data['update_sale_price'] = (bool) ($data['update_sale_price'] ?? false);

                                // Si el toggle está activo, asegurar que el precio de venta se guarde con redondeo
                                if ($data['update_sale_price'] && !empty($data['sale_price'])) {
                                    $data['sale_price'] = round((float) $data['sale_price'], 2);
                                } elseif (! $data['update_sale_price']) {
                                    // Si el toggle está desactivado, limpiar el precio de venta
                                    $data['sale_price'] = null;
                                }

                                return $data;
                            })
                            ->columnSpanFull()
                            ->defaultItems(1)
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['product_id'])
                                    ? \App\Models\Product::find($state['product_id'])?->name ?? 'Producto'
                                    : 'Nuevo Producto'
                            )
                            ->addActionLabel('+ Agregar Producto')
                            ->deleteAction(
                                fn (Action $action) => $action
                                    ->requiresConfirmation()
                                    ->modalHeading('¿Eliminar producto?')
                                    ->after(fn (Forms\Set $set, Forms\Get $get) => self::updateTotals($set, $get))
                            ),
                    ]),

                Forms\Components\Section::make('Resumen de Compra')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Columna 1: Desglose de Subtotales (Moneda Factura)
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('taxable_base')
                                        ->label('Base Imponible')
                                        ->helperText('Subtotal de productos gravados')
                                        ->numeric()
                                        ->prefix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),

                                    Forms\Components\TextInput::make('total_exempt')
                                        ->label('Total Exento')
                                        ->helperText('Subtotal de productos sin impuesto')
                                        ->numeric()
                                        ->prefix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),
                                ])->columnSpan(1),

                                // Columna 2: Impuestos y Subtotal (Moneda Factura)
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Subtotal Factura')
                                        ->helperText('Suma de todos los productos')
                                        ->numeric()
                                        ->prefix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base font-medium']),

                                    Forms\Components\TextInput::make('total_tax')
                                        ->label('Total Impuestos')
                                        ->helperText('IVA y otros impuestos')
                                        ->numeric()
                                        ->prefix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),
                                ])->columnSpan(1),

                                // Columna 3: Total Factura (Moneda Original)
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('items_count')
                                        ->label('Productos')
                                        ->content(fn (Forms\Get $get) => count($get('items') ?? []).' líneas'),

                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('TOTAL FACTURA')
                                        ->helperText('Monto total en moneda de la factura')
                                        ->numeric()
                                        ->prefix(fn (Forms\Get $get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-2xl font-bold']),
                                ])->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

            ])
            ->columns(12);
    }

    /**
     * Recalcula el profit cuando el usuario edita sale_price manualmente.
     * Llamar ANTES de updateCalculations() cuando cambia sale_price.
     */
    public static function recalculateProfitFromSalePrice(Forms\Set $set, Forms\Get $get): void
    {
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $salePrice = (float) ($get('sale_price') ?? 0);
        $exchangeRate = (float) ($get('../../exchange_rate') ?? 1);

        $unitPriceAfterDiscount = $unitPrice * (1 - ($discount / 100));
        
        // Convertir costo a moneda base para cálculo correcto del profit
        $unitPriceInBase = $unitPriceAfterDiscount / $exchangeRate;

        if ($unitPriceInBase > 0 && $salePrice > 0) {
            $realProfit = (($salePrice - $unitPriceInBase) / $unitPriceInBase) * 100;
            $set('profit', number_format(max(0, $realProfit), 2, '.', ''));
        }
    }

    public static function updateCalculations(Forms\Set $set, Forms\Get $get): void
    {
        // ========================================
        // 1. OBTENER VALORES BASE
        // ========================================
        $quantity = (float) ($get('quantity') ?? 1);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $profit = (float) ($get('profit') ?? 0);
        $taxExempt = (bool) ($get('tax_exempt') ?? false);
        $taxId = $get('tax_rate_id');
        $updateSalePrice = (bool) ($get('update_sale_price') ?? false);

        // Obtener tasa de cambio del formulario principal
        $exchangeRate = (float) ($get('../../exchange_rate') ?? 1);
        $currencyId = $get('../../currency_id');

        // Validar cantidad mínima
        if ($quantity <= 0) {
            $quantity = 1;
        }

        // ========================================
        // 2. CÁLCULOS DE COMPRA (EN MONEDA FACTURA)
        // ========================================

        // Precio unitario después de descuento
        $unitPriceAfterDiscount = $unitPrice * (1 - ($discount / 100));

        // Subtotal de compra (en moneda factura)
        $subtotal = $quantity * $unitPriceAfterDiscount;
        $set('subtotal', number_format($subtotal, 2, '.', ''));

        // ========================================
        // 3. CONVERSIÓN A MONEDA BASE (USD)
        // ========================================

        // Convertir costo a moneda base para inventario
        $unitPriceInBase = $unitPriceAfterDiscount / $exchangeRate;
        $subtotalInBase = $subtotal / $exchangeRate;

        // ========================================
        // 4. CÁLCULOS DE VENTA (EN MONEDA BASE)
        // ========================================

        // Precio de venta actual del producto (en moneda base)
        $productId = $get('product_id');
        $currentSalePrice = 0;
        if ($productId) {
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $currentSalePrice = (float) $product->sale_price;
            }
        }

        // Precio de venta del formulario (en moneda base)
        $salePrice = (float) ($get('sale_price') ?? 0);

        // Si se autoriza actualizar, calcular siempre desde costo convertido + margen
        if ($updateSalePrice && $unitPriceInBase > 0) {
            // Siempre recalcular el precio cuando el toggle está activo
            // Cálculo: costo base * (1 + margen/100) con redondeo a 2 decimales
            $newSalePrice = round($unitPriceInBase * (1 + ($profit / 100)), 2, PHP_ROUND_HALF_UP);
            
            // Solo actualizar si el usuario no ha modificado manualmente el precio
            if ($salePrice <= 0 || abs($salePrice - $newSalePrice) < 0.01) {
                $salePrice = $newSalePrice;
                $set('sale_price', number_format($salePrice, 2, '.', ''));
            }
        }

        // ========================================
        // 5. CÁLCULOS DE IMPUESTOS (EN MONEDA FACTURA)
        // ========================================

        $taxRate = 0;
        $taxName = null;
        $taxAmount = 0;

        if (! $taxExempt && $taxId) {
            $tax = \App\Models\TaxRate::find($taxId);
            if ($tax) {
                $taxRate = $tax->rate;
                $taxName = $tax->name;

                // IVA sobre el subtotal en moneda factura (para mostrar al usuario)
                $taxAmount = $subtotal * ($taxRate / 100);
            }
        }

        $set('tax_rate', $taxRate);
        $set('tax_name', $taxName);
        $set('tax_amount', number_format($taxAmount, 2, '.', ''));

        // ========================================
        // 6. PRECIO FINAL Y NETO
        // ========================================

        // Precio final al público (con IVA) - en moneda base para PVP
        if ($salePrice > 0) {
            $unitTaxAmountSale = $salePrice * ($taxRate / 100);
            $finalPrice = $salePrice + $unitTaxAmountSale;
            $set('final_price', number_format($finalPrice, 2, '.', ''));
            $set('unit_tax_amount', number_format($unitTaxAmountSale, 2, '.', ''));
        }

        // ========================================
        // 7. ACTUALIZAR TOTALES DEL FORMULARIO
        // ========================================
        self::updateTotals($set, $get);
    }

    /**
     * Recalcula todos los precios de venta cuando cambia la moneda o tasa
     */
    public static function recalculateSalePricesAfterCurrencyChange(Forms\Set $set, Forms\Get $get): void
    {
        $items = $get('items');
        
        if ($items === null) {
            return;
        }

        $items = collect($items ?? []);
        $exchangeRate = (float) ($get('exchange_rate') ?? 1);

        foreach ($items as $index => $item) {
            if ($item['update_sale_price'] ?? false) {
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $profit = (float) ($item['profit'] ?? 0);
                
                // Calcular costo en moneda base
                $unitPriceAfterDiscount = $unitPrice * (1 - ($discount / 100));
                $unitPriceInBase = $unitPriceAfterDiscount / $exchangeRate;
                
                // Recalcular precio de venta con redondeo
                if ($unitPriceInBase > 0) {
                    $newSalePrice = round($unitPriceInBase * (1 + ($profit / 100)), 2, PHP_ROUND_HALF_UP);
                    $set("items.{$index}.sale_price", number_format($newSalePrice, 2, '.', ''));
                }
            }
        }
    }

    public static function updateTotals(Forms\Set $set, Forms\Get $get): void
    {
        $items = $get('items');
        $isRemote = false;

        if ($items === null) {
            $items = $get('../../items');
            $isRemote = true;
        }

        $items = collect($items ?? []);

        // Obtener tasa de cambio
        $exchangeRate = (float) ($get('exchange_rate') ?? 1);

        $taxableSubtotal = 0;
        $exemptSubtotal = 0;
        $totalTax = 0;
        $totalQuantity = 0;
        $subtotalInOriginalCurrency = 0;

        foreach ($items as $item) {
            $subtotal = (float) ($item['subtotal'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $taxExempt = $item['tax_exempt'] ?? false;

            // Subtotal en moneda original (para mostrar al usuario)
            $subtotalInOriginalCurrency += $subtotal;

            if ($taxExempt) {
                $exemptSubtotal += $subtotal; // Mantener en moneda original
            } else {
                $taxableSubtotal += $subtotal; // Mantener en moneda original
                $totalTax += $taxAmount; // Impuestos ya están en moneda original
            }

            $totalQuantity += $quantity;
        }

        // Calcular totales en moneda original (factura)
        $subtotalInOriginal = $taxableSubtotal + $exemptSubtotal;
        $totalAmountInOriginal = $subtotalInOriginal + $totalTax;

        // Convertir a moneda base (USD) solo para el campo informativo
        $totalAmountInBase = $totalAmountInOriginal / $exchangeRate;

        $prefix = $isRemote ? '../../' : '';

        // Actualizar campos en moneda original (factura)
        $set($prefix.'subtotal', number_format($subtotalInOriginal, 2, '.', ''));
        $set($prefix.'total_exempt', number_format($exemptSubtotal, 2, '.', ''));
        $set($prefix.'taxable_base', number_format($taxableSubtotal, 2, '.', ''));
        $set($prefix.'total_tax', number_format($totalTax, 2, '.', ''));
        $set($prefix.'total_amount', number_format($totalAmountInOriginal, 2, '.', ''));

        // Actualizar campos informativos en cabecera
        $set($prefix.'total_invoice_currency', number_format($totalAmountInOriginal, 2, '.', ''));
        $set($prefix.'exchanged_amount', number_format($totalAmountInBase, 2, '.', ''));
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
                    ->numeric()
                    ->prefix('$')
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
