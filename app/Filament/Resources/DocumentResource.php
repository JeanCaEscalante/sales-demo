<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $pluralLabel = 'Ventas';

    protected static ?string $label = 'Venta';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo Documento')
                    ->schema([
                        Forms\Components\Select::make('type_receipt')
                            ->label('Tipo Comprobante')
                            ->options(TypeReceipt::class)
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Get $set, Set $get) {
                                $user = User::find(Auth::user()->id);
                                if (in_array($state, ['R1', 'R2'])) {
                                    $date = Carbon::now()->toDateString();
                                    $set('invoice_series_code', 'R-'.$user->sellerProfile->serialSlug.'-'.str_replace('-', '', $date));

                                    // buscar todas las facturas generadas por este cliente el dia de hoy
                                    $todayInvoices = Invoice::where('user_id', Auth::user()->id)->where('invoice_series_code', $get('invoice_series_code'))->whereIn('type_status', [TypeStatus::CORRECT, TypeStatus::ACCEPTED_ERRORS, TypeStatus::INCORRECT])->get();
                                    $maxNumber = 0;
                                    foreach ($todayInvoices as $invoices) {
                                        $currentNumber = $invoices->invoice_number;

                                        if ($currentNumber > $maxNumber) {
                                            $maxNumber = $currentNumber;
                                        }
                                    }

                                    $set('invoice_number', $maxNumber + 1);

                                    $set('serial', $get('invoice_series_code').'-'.$get('invoice_number'));
                                } else {
                                    $date = Carbon::now()->toDateString();
                                    $set('invoice_series_code', $user->sellerProfile->serialSlug.'-'.str_replace('-', '', $date));

                                    // buscar todas las facturas generadas por este cliente el dia de hoy
                                    $todayInvoices = Invoice::where('user_id', Auth::user()->id)->where('invoice_series_code', $get('invoice_series_code'))->whereIn('type_status', [TypeStatus::CORRECT, TypeStatus::ACCEPTED_ERRORS, TypeStatus::INCORRECT])->get();
                                    $maxNumber = 0;
                                    foreach ($todayInvoices as $invoices) {
                                        $currentNumber = $invoices->invoice_number;

                                        if ($currentNumber > $maxNumber) {
                                            $maxNumber = $currentNumber;
                                        }
                                    }

                                    $set('invoice_number', $maxNumber + 1);

                                    $set('serial', $get('invoice_series_code').'-'.$get('invoice_number'));
                                }
                            }),
                    ])
                    ->columnSpan(4),
                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make('Selección de cliente')
                            ->schema([
                                Forms\Components\Select::make('customer_company_id')->label('Cliente')
                                    ->relationship(
                                        name: 'customer', titleAttribute: 'name'
                                    )
                                    ->searchable(['name', 'taxNumber'])
                                    ->preload()
                                    ->createOptionForm(fn (Form $form) => CustomerResource::form($form))
                                    ->createOptionModalHeading('Crear Cliente')
                                    ->live()
                                    ->columnSpan(4),

                            ])->columns(4),
                        Forms\Components\Tabs\Tab::make('Datos Personales')
                            ->schema([
                                Forms\Components\Select::make('customer_type_id')->label('Tipo de Documento')
                                    ->options([
                                        '01' => 'NIF',
                                        '02' => 'NIF-IVA',
                                        '03' => 'Pasaporte',
                                        '04' => 'Documento oficial de identificación expedido por el país o territorio de residencia',
                                        '05' => 'Certificado de residencia',
                                        '06' => 'Otro documento probatorio',
                                        '07' => 'No censado',
                                    ])
                                    ->default('01')
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_tax_number')
                                    ->label(fn (Get $get) => match ($get('customer_type_id')) {
                                        '01' => 'NIF',
                                        '02' => 'NIF-IVA',
                                        '03' => 'Pasaporte',
                                        '04' => 'Documento oficial de identificación expedido por el país o territorio de residencia',
                                        '05' => 'Certificado de residencia',
                                        '06' => 'Otro documento probatorio',
                                        '07' => 'No censado',
                                        default => 'NIF'
                                    })
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\Hidden::make('customer_id'),
                                Forms\Components\TextInput::make('customer_name')->label('Nombre')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_first_surname')->label('Primer Apellido')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled()
                                    ->hidden(fn (Get $get) => $get('entity_type') != 1),
                                Forms\Components\TextInput::make('customer_last_surname')->label('Segundo Apellido')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled()
                                    ->hidden(fn (Get $get) => $get('entity_type') != 1),
                                Forms\Components\TextInput::make('customer_address')->label('Dirección')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_post_code')->label('Código Postal')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_town')->label('Ciudad')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_province')->label('Provincia')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                            ])->columns(6),
                        Forms\Components\Tabs\Tab::make('Datos de Contacto')
                            ->schema([
                                Forms\Components\TextInput::make('customer_email')->label('Correo Electrónico')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_phone')->label('Teléfono')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                                Forms\Components\TextInput::make('customer_website')->label('Sitio Web')
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->disabled(),
                            ])->columns(6),
                       

                    ])->columnSpan(8),
                    Forms\Components\Tabs::make('data_invoice')
                            ->schema([
                                Forms\Components\Tabs\Tab::make('Principales')
                                    ->schema([
                                        Forms\Components\TextInput::make('invoice_series_code')->label('Lote')
                                            ->live()
                                            ->disabled()
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('serial')->label('Serial')
                                            ->disabled()
                                            ->live()
                                            ->formatStateUsing(function (Get $get, $state) {
                                                if ($state === null) {
                                                    return $get('invoice_series_code').'-'.$get('invoice_number');
                                                }

                                                return $get('serial');
                                            })
                                            ->unique(ignoreRecord: true)
                                            ->columnSpan(3),
                                        Forms\Components\DatePicker::make('operation_date')
                                            ->label('Fecha de Operación')
                                            ->required()
                                            ->columnSpan(6),
                                    ])->columns(6),
                                Forms\Components\Tabs\Tab::make('Pagos')
                                    ->schema([
                                        Forms\Components\Repeater::make('payments')->label('')
                                            ->schema([
                                                Forms\Components\Select::make('constant')->label('Forma de pago')
                                                    ->options([
                                                        'TYPE_CASH' => 'Al contado',
                                                        'TYPE_DEBIT' => 'Recibo Domiciliado',
                                                        'TYPE_RECEIPT' => 'Recibo',
                                                        'TYPE_TRANSFER' => 'Transferencia',
                                                        'TYPE_ACCEPTED_BILL_OF_EXCHANGE' => 'Letra Aceptada',
                                                        'TYPE_DOCUMENTARY_CREDIT' => 'Crédito Documentario',
                                                        'TYPE_CONTRACT_AWARD' => 'Adjudicación de contrato',
                                                        'TYPE_BILL_OF_EXCHANGE' => 'Letra de cambio',
                                                        'TYPE_TRANSFERABLE_IOU' => 'Pagaré a la Orden',
                                                        'TYPE_IOU' => 'Pagaré No a la Orden',
                                                        'TYPE_CHEQUE' => 'Cheque',
                                                        'TYPE_REIMBURSEMENT' => 'Reposición',
                                                        'TYPE_SPECIAL' => 'Especiales',
                                                        'TYPE_SETOFF' => 'Compensación',
                                                        'TYPE_POSTGIRO' => 'Giro postal',
                                                        'TYPE_CERTIFIED_CHEQUE' => 'Cheque conformado',
                                                        'TYPE_BANKERS_DRAFT' => 'Cheque bancario',
                                                        'TYPE_CASH_ON_DELIVERY' => 'Pago contra reembolso',
                                                        'TYPE_CARD' => 'Pago mediante tarjeta',
                                                    ]),
                                                Forms\Components\TextInput::make('amount')->label('Cantidad')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.1)
                                                    ->suffix('€'),
                                                Forms\Components\TextInput::make('iban')->label('IBAN'),
                                                Forms\Components\TextInput::make('bic')->label('BIC'),
                                                Forms\Components\DatePicker::make('dueDate')->label('Vencimiento'),
                                            ])
                                            ->defaultItems(0)
                                            ->columnSpan(4)
                                            ->addActionLabel('Añadir forma de pago')
                                            ->collapsible(),
                                    ])->columns(4),
                            ])->columnSpanFull(),
                            Forms\Components\Section::make('Artículos')
                    ->schema([
                        Forms\Components\Repeater::make('Artículos')
                            ->label('')
                            ->schema([
                                Forms\Components\Tabs::make('service_primary')
                                    ->tabs([
                                        Forms\Components\Tabs\Tab::make('Datos Principales')
                                            ->schema([
                                                Forms\Components\Select::make('template_service')->label('Cargar plantilla del servicio o producto')
                                                    ->options([])
                                                    ->searchable(['name', 'firstSurname', 'taxNumber'])
                                                    ->loadingMessage('Cargando servicios...')
                                                    ->searchPrompt('Busca entre tus servicios')
                                                    ->noSearchResultsMessage('No se encontraron servicios')
                                                    ->preload()
                                                    ->live(onBlur: true)
                                                    ->hint('Lista de Servicios')
                                                    ->columnSpanFull(),
                                                    Forms\Components\TextInput::make('name')->label('Nombre del Servicio o Producto')
                                                    ->required()
                                                    ->columnSpanFull(),
                                                // Esto se llamaba specialTaxableEventCode
                                                Forms\Components\Toggle::make('is_exempt_operation')->label('¿El producto / servicio es libre de impuestos?')
                                                    ->live(onBlur: true)
                                                    ->required()
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {

                                                        if ($get('quantity') === null || $get('unit_price') === null) {
                                                            return;
                                                        }

                                                        if ($state === true) {
                                                            $set('tax_rate', null);
                                                            $set('surcharge_type', null);
                                                           // ServiceResource::updatePriceAfterChange($get, $set, 'root', true);
                                                        }
                                                    })
                                                    ->inline(false)
                                                    ->default(0),
                                                    
                                                   
                                            ])->columnSpanFull()->columnStart(1),
                                        Forms\Components\Tabs\Tab::make('Datos Opcionales')
                                            ->schema([
                                                Forms\Components\Section::make('Detalles del Producto / Servicio')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('description')->label('Descripción'),
                                                        Forms\Components\TextInput::make('article_code')->label('Código de artículo'),
                                                    ]),
                                            ]),
                                    ]),
                                    Forms\Components\Section::make('Precio del Servicio')
                                    ->schema([
                                        Forms\Components\TextInput::make('unit_price')->label('Precio Por Unidad')
                                            ->required()
                                            ->placeholder(0)
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                               // self::updatePriceAfterChange($get, $set, 'root', true);
                                            })
                                            ->regex('/^[0-9,.]+$/')
                                            ->minValue(0)
                                            ->prefix('€')
                                            ->columnSpan(2),
                                            Forms\Components\TextInput::make('quantity')->label('Cantidad')
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->numeric()
                                            ->step(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                //self::updatePriceAfterChange($get, $set, 'root', true);
                                            })->columnSpan(2),
                                            Forms\Components\TextInput::make('tax_base')->label('Base Imponible')
                                            ->required()
                                            ->readOnly()
                                            ->placeholder(0)
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                               // self::updatePriceAfterChange($get, $set, 'root', true);
                                            })
                                            ->regex('/^[0-9,.]+$/')
                                            ->minValue(0)
                                            ->prefix('€')
                                            ->columnSpan(2),
                                            Forms\Components\TextInput::make('tax_amount')->label('Cuota Repercutida')
                                          //->requiredIf('is_exempt_operation', false) Realizar una validacion acorde.
                                            ->placeholder(0)
                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->prefix('€')
                                            ->columnSpan(2),
                                            Forms\Components\TextInput::make('surcharge_equivalence_amount')->label('Cuota Recargo Equivalencia')
                                            ->requiredIf('tax_regime_key', '18')
                                            ->placeholder(0)
                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->prefix('€')
                                            ->columnSpan(2)
                                            ->hidden(fn ($get) => !in_array($get('operation_qualification'), ['S1', 'S2']) || $get('tax_type') !== '01' || $get('tax_regime_key') !== '18'),
                                            Forms\Components\TextInput::make('charged_amount')->label('Cargos a la Base Imponible')
                                            ->readOnly()
                                            ->placeholder(0)
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->prefix('€')
                                            ->columnSpan(2),
                                            Forms\Components\TextInput::make('discount_amount')->label('Descuentos a la Base Imponible')
                                            ->readOnly()
                                            ->placeholder(0)
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->prefix('€')
                                            ->columnSpan(2),
                                            Forms\Components\TextInput::make('net_amount')->label('Base Liquidable')
                                            ->required()
                                            ->placeholder(0)
                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->stripCharacters(',')
                                            ->prefix('€')
                                            ->columnSpan(4),

                                    ])->columns(4)->columnSpan(4),
                            ])
                            ->addActionLabel('Añadir servicio a la factura')
                            ->collapsible()
                            ->relationship('details')
                            ->deleteAction(function (Forms\Components\Actions\Action $action) {
                                $action->after(function (Get $get, Set $set) {
                                    $set('subtotal_base', null);
                                    $set('total', null);
                                });
                            }),
                    ])->columnSpan(9),
                    Forms\Components\Section::make('Total a pagar')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_base')->label('Subtotal Imponible')
                            ->placeholder(0)
                            ->readOnly()
                            ->live(onBlur: true)
                            ->stripCharacters(',')
                            ->prefix('€'),
                            Forms\Components\TextInput::make('subtotal_taxes')->label('Subtotal de Impuestos')
                            ->placeholder(0)
                            ->readOnly()
                            ->live(onBlur: true)
                            ->stripCharacters(',')
                            ->prefix('€'),
                            Forms\Components\TextInput::make('subtotal_discounts')->label('Subtotal Descuentos')
                            ->placeholder(0)
                            ->readOnly()
                            ->live(onBlur: true)
                            ->stripCharacters(',')
                            ->prefix('€'),
                            Forms\Components\TextInput::make('total')->label('Total')
                            ->placeholder(0)
                            ->readOnly()
                            ->live(onBlur: true)
                            ->stripCharacters(',')
                            ->prefix('€')
                            ->afterStateUpdated(function ($livewire, TextInput $component) {
                                $livewire->validateOnly($component->getStatePath());
                            }),
                           
                    ])->columnSpan(3),
            ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_receipt')
                    ->label('Categoría'),
                Tables\Columns\TextColumn::make('num_receipt')
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('receipt_at')
                    ->label('Descripción'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
