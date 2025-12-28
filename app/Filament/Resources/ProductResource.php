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
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'name')
                    ->label('Unidad de medida')
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->label('Código'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
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
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Precio unitario')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $set('price_out', $state * (1 + $get('profit') / 100));
                    })
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('price_out')
                    ->label('Precio de venta')
                    ->numeric()
                    ->readOnly()
                    ->required(),
                Forms\Components\Toggle::make('is_exempt')
                    ->label('Exento de impuesto')
                    ->live()
                    ->inline(false)
                    ->default(false),
                Forms\Components\Select::make('tax_rate_id')
                    ->relationship(name: 'tax_rate', titleAttribute: 'name')
                    ->label('Impuesto')
                    ->searchable()
                    ->preload()
                    ->requiredIf('is_exempt', false)
                    ->visible(fn (Forms\Get $get) => ! $get('is_exempt')),
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
