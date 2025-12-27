<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $pluralLabel = 'Productos';

    protected static ?string $label = 'Producto';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship(name: 'category', titleAttribute: 'category_name')
                    ->label('Categoría')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'name')
                    ->label('Unidad de medida')
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('stock')
                    ->label('Cantidad')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('min_stock')
                    ->label('Cantidad mínima')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('profit')
                    ->label('Ganancia')
                    ->numeric(),
                Forms\Components\TextInput::make('price_in')
                    ->label('Último precio de compra')
                    ->numeric(),
                Forms\Components\TextInput::make('price_out')
                    ->label('Precio de venta')
                    ->numeric()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->rows(4)
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_out')
                    ->label('Precio de venta')
                    ->sortable(),
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
