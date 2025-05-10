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
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('discount_code')
                    ->label('Codigo descuento')
                    ->required()
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('codeGenerator')
                            ->icon('heroicon-s-gift')
                            ->action(function (Forms\Set $set) {
                                $set('discount_code', self::generar_codigo_descuento());
                            })
                    ),
                Forms\Components\TextInput::make('discount_value')
                    ->label('Valor del descuento')
                    ->required(),
                Forms\Components\DateTimePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('Fecha de fin'),
                Forms\Components\TextInput::make('min_amount')
                    ->label('Monto minimo')
                    ->required(),
                Forms\Components\TextInput::make('max_uses')
                    ->label('Maximo de uso')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Tipo descuento'),
                Tables\Columns\TextColumn::make('discount_code')
                    ->label('Codigo descuento'),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Valor del descuento'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de inicio'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de fin'),
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

    public static function generar_codigo_descuento()
    {
        $longitud = 8; // Longitud del código
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $codigo = '';

        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }

        return $codigo;
    }
}
