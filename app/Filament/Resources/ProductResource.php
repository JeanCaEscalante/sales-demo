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

    protected static ?string $navigationLabel = 'Artículos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $pluralLabel = 'Artículos';

    protected static ?string $label = 'Artículo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship(name: 'category', titleAttribute: 'category_name')
                    ->label('Categoría')
                    ->searchable(),
                Forms\Components\Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'name')
                    ->label('Unidad de medida')
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->label('Código'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('stock')
                    ->label('Cantidad')
                    ->numeric(),
                Forms\Components\TextInput::make('price_in')
                    ->label('Último precio de compra')
                    ->numeric(),
                Forms\Components\TextInput::make('price_out')
                    ->label('Precio de venta')
                    ->numeric(),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código'),
                Tables\Columns\TextColumn::make('description')
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
