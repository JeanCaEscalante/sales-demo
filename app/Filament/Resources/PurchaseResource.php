<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\PurchaseResource\Pages;
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
                                            $set('tax_id', $product->tax_id);

                                            // Resetear precio venta para recalcular
                                            $set('sale_price', null);
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
                                                    ->label('Precio Unitario')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.0001)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        // Resetear precio venta para recalcular desde ganancia
                                                        $set('sale_price', null);
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
                                                        $set('sale_price', null);
                                                        self::updateCalculations($set, $get);
                                                    }),

                                                Forms\Components\TextInput::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->numeric()
                                                    ->prefix('$')
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
                                                            $set('tax_id', null);
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
                                                    ->prefix('$')
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
                                                        $set('sale_price', null);
                                                        self::updateCalculations($set, $get);
                                                    })
                                                    ->helperText('Margen sobre costo'),

                                                Forms\Components\TextInput::make('sale_price')
                                                    ->label('Precio Venta')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        // Primero recalcular profit, luego el resto
                                                        self::recalculateProfitFromSalePrice($set, $get);
                                                        self::updateCalculations($set, $get);
                                                    })
                                                    ->helperText('Sin IVA')
                                                    ->extraAttributes(['class' => 'font-semibold']),

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
                                // Columna 1: Desglose de Subtotales
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('taxable_base')
                                        ->label('Base Imponible')
                                        ->helperText('Subtotal de productos gravados')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),

                                    Forms\Components\TextInput::make('total_exempt')
                                        ->label('Total Exento')
                                        ->helperText('Subtotal de productos sin impuesto')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),
                                ])->columnSpan(1),

                                // Columna 2: Impuestos y Subtotal
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->label('Subtotal')
                                        ->helperText('Suma de todos los productos')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base font-medium']),

                                    Forms\Components\TextInput::make('total_tax')
                                        ->label('Total Impuestos')
                                        ->helperText('IVA y otros impuestos')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-base']),
                                ])->columnSpan(1),

                                // Columna 3: Total Destacado
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('items_count')
                                        ->label('Productos')
                                        ->content(fn (Forms\Get $get) => count($get('items') ?? []).' líneas'),

                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('TOTAL A PAGAR')
                                        ->numeric()
                                        ->prefix('$')
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

        $unitPriceAfterDiscount = $unitPrice * (1 - ($discount / 100));

        if ($unitPriceAfterDiscount > 0 && $salePrice > 0) {
            $realProfit = (($salePrice - $unitPriceAfterDiscount) / $unitPriceAfterDiscount) * 100;
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
        $taxId = $get('tax_id');

        // Validar cantidad mínima
        if ($quantity <= 0) {
            $quantity = 1;
        }

        // ========================================
        // 2. CÁLCULOS DE COMPRA
        // ========================================

        // Precio unitario después de descuento
        $unitPriceAfterDiscount = $unitPrice * (1 - ($discount / 100));

        // Subtotal de compra (base para calcular totales)
        $subtotal = $quantity * $unitPriceAfterDiscount;
        $set('subtotal', number_format($subtotal, 2, '.', ''));

        // ========================================
        // 3. CÁLCULOS DE VENTA
        // ========================================

        // Precio de venta (sin IVA)
        $salePrice = (float) ($get('sale_price') ?? 0);

        // Si no hay precio de venta, calcular desde ganancia
        if ($salePrice <= 0 && $unitPriceAfterDiscount > 0) {
            $salePrice = $unitPriceAfterDiscount * (1 + ($profit / 100));
            $set('sale_price', number_format($salePrice, 2, '.', ''));
        }

        // ========================================
        // 4. CÁLCULOS DE IMPUESTOS
        // ========================================

        $taxRate = 0;
        $taxName = null;
        $unitTaxAmount = 0;
        $taxAmount = 0;

        if (! $taxExempt && $taxId) {
            $tax = \App\Models\TaxRate::find($taxId);
            if ($tax) {
                $taxRate = $tax->rate;
                $taxName = $tax->name;

                // IVA sobre el subtotal de compra (para contabilidad)
                $taxAmount = $subtotal * ($taxRate / 100);

                // IVA unitario sobre precio de venta (para PVP)
                $unitTaxAmount = $salePrice * ($taxRate / 100);
            }
        }

        $set('tax_rate', $taxRate);
        $set('tax_name', $taxName);
        $set('tax_amount', number_format($taxAmount, 2, '.', ''));
        $set('unit_tax_amount', number_format($unitTaxAmount, 2, '.', ''));

        // ========================================
        // 5. PRECIO FINAL Y NETO
        // ========================================

        // Precio final al público (con IVA)
        $finalPrice = $salePrice + $unitTaxAmount;
        $set('final_price', number_format($finalPrice, 2, '.', ''));

        // Neto total de la línea (subtotal compra + impuestos)
        $netTotal = $subtotal + $taxAmount;
        $set('net_total', number_format($netTotal, 2, '.', ''));

        // ========================================
        // 6. ACTUALIZAR TOTALES DEL FORMULARIO
        // ========================================
        self::updateTotals($set, $get);
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

        $taxableSubtotal = 0;
        $exemptSubtotal = 0;
        $totalTax = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $subtotal = (float) ($item['subtotal'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $taxExempt = $item['tax_exempt'] ?? false;

            if ($taxExempt) {
                $exemptSubtotal += $subtotal;
            } else {
                $taxableSubtotal += $subtotal;
                $totalTax += $taxAmount;
            }

            $totalQuantity += $quantity;
        }

        $subtotal = $taxableSubtotal + $exemptSubtotal;
        $totalAmount = $subtotal + $totalTax;

        $prefix = $isRemote ? '../../' : '';

        $set($prefix.'subtotal', number_format($subtotal, 2, '.', ''));
        $set($prefix.'total_exempt', number_format($exemptSubtotal, 2, '.', ''));
        $set($prefix.'taxable_base', number_format($taxableSubtotal, 2, '.', ''));
        $set($prefix.'total_tax', number_format($totalTax, 2, '.', ''));
        $set($prefix.'total_amount', number_format($totalAmount, 2, '.', ''));
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
