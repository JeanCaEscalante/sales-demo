<?php

namespace App\Filament\Resources;

use App\Enums\TypeDiscount;
use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationLabel = 'Descuentos';

    protected static ?string $pluralLabel = 'Descuentos';

    protected static ?string $label = 'Descuento';

    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('discount_type')
                    ->label('Tipo descuento')
                    ->options(TypeDiscount::class)
                    ->required(),
                Forms\Components\TextInput::make('discount_code')
                    ->label('Codigo descuento')
                    ->required(),
                Forms\Components\TextInput::make('discount_value')
                    ->label('Valor del descuento')
                    ->required(),
                Forms\Components\DateTimePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('Fecha de fin'),
                Forms\Components\TextInput::make('min_amount')
                    ->label('Valor del descuento')
                    ->required(),
                Forms\Components\TextInput::make('max_uses')
                    ->label('Maximo de uso')
                    ->required(),
                Forms\Components\TextInput::make('used')
                    ->label('Valor del descuento')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
