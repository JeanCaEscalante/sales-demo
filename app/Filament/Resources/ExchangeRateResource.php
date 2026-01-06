<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Tasa de Cambio';

    protected static ?string $pluralModelLabel = 'Tasas de Cambio';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Moneda')
                    ->options(Currency::where('is_base', false)
                        ->where('is_active', true)
                        ->pluck('name', 'currency_id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('rate')
                    ->label('Tasa (respecto a USD)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->helperText('Ejemplo: Si 1 USD = 4,000 COP, ingrese 4000'),

                Forms\Components\DatePicker::make('effective_date')
                    ->label('Fecha efectiva')
                    ->required()
                    ->default(now())
                    ->maxDate(now()->addDays(7))
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency.code')
                    ->label('Moneda')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency.name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Tasa')
                    ->sortable(),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Fecha Efectiva')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Moneda')
                    ->options(Currency::pluck('name', 'currency_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExchangeRates::route('/'),
        ];
    }
}
