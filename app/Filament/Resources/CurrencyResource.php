<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Moneda';

    protected static ?string $pluralModelLabel = 'Monedas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->formatStateUsing(fn ($state): string => strtoupper($state))
                    ->required()
                    ->maxLength(3)
                    ->placeholder('USD, EUR, COP...'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Dólar Estadounidense'),

                Forms\Components\TextInput::make('symbol')
                    ->label('Símbolo')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('$, €, COP$'),

                Forms\Components\Toggle::make('is_base')
                    ->label('¿Es moneda base?')
                    ->helperText('Solo una moneda puede ser la base (USD)')
                    ->disabled(fn (?Currency $record) => $record?->is_base ?? false),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Símbolo'),

                Tables\Columns\IconColumn::make('is_base')
                    ->label('Base')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('current_rate')
                    ->label('Tasa Actual')
                    ->getStateUsing(fn (Currency $record) => $record?->getCurrentRate())
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : 'N/A'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCurrencies::route('/'),
        ];
    }
}
