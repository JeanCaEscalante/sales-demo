<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxeResource\Pages;
use App\Filament\Resources\TaxeResource\RelationManagers;
use App\Models\Taxe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxeResource extends Resource
{
    protected static ?string $model = Taxe::class;

    protected static ?string $pluralLabel = 'Impuestos';

    protected static ?string $label = 'Impuesto';

    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('country')
                    ->label('País'),
                Forms\Components\TextInput::make('state')
                    ->label('Estado/Región'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del impuesto'),
                Forms\Components\TextInput::make('rate')
                    ->label('Tasa'),
                Forms\Components\TextInput::make('priority')
                    ->label('Prioridad')
                    ->numeric()
                    ->columnSpanFull(),
                Forms\Components\Checkbox::make('is_composed')
                    ->label('Compuesto'),
                Forms\Components\Checkbox::make('is_shipping')
                    ->label('Envió')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country')
                    ->label('País'),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado/Región'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Tasa'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad'),
                Tables\Columns\IconColumn::make('is_composed')
                    ->boolean()
                    ->label('Compuesto'),
                Tables\Columns\IconColumn::make('is_shipping')
                    ->boolean()
                    ->label('Envió')
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTaxe::route('/create'),
            'edit' => Pages\EditTaxe::route('/{record}/edit'),
        ];
    }
}
