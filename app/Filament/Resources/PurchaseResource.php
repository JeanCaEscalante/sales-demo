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
                Forms\Components\Select::make('supplier_id')
                    ->relationship(
                        name: 'supplier',
                        titleAttribute: 'name',
                    )
                    ->label('Proveedor')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('document_type')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class)
                    ->required(),
                Forms\Components\DatePicker::make('purchase_date')
                    ->label('Fecha')
                    ->required(),
                Forms\Components\TextInput::make('series')
                    ->label('Serie'),
                Forms\Components\TextInput::make('receipt_number')
                    ->label('Número')
                    ->required(),
                Forms\Components\TextInput::make('total_tax')
                    ->label('Impuesto')
                    ->numeric(),
                Forms\Components\TextInput::make('total_amount')
                    ->label('Total')
                    ->numeric(),
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('Detalles')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship(name: 'product', titleAttribute: 'name')
                            ->label('Producto')
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Precio Compra')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('suggested_price')
                            ->label('Precio Venta Sugerido')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(4)
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo Comprobante'),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Número'),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total'),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
