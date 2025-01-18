<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Enums\TypeSubject;
use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Forms\Components\Select::make('subject_id')
                    ->relationship(
                        name: 'customer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('type_subject', TypeSubject::Customer),
                    )
                    ->label('Cliente')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('type_receipt')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class)
                    ->required(),
                Forms\Components\DatePicker::make('receipt_at')
                    ->label('Fecha')
                    ->required(),
                Forms\Components\Repeater::make('details')
                    ->relationship()
                    ->label('Detalles')
                    ->schema([
                        Forms\Components\Select::make('article_id')
                            ->relationship(name: 'article', titleAttribute: 'name')
                            ->label('Articulo')
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('sale_price')
                            ->label('Precio Venta')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('discount')
                            ->label('Descuento')
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
