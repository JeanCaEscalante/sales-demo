<?php

namespace App\Filament\Resources;

use App\Enums\TypeContact;
use App\Enums\TypeDocument;
use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $pluralLabel = 'Proveedores';

    protected static ?string $label = 'Proveedor';

    protected static ?string $navigationIcon = 'heroicon-s-building-storefront';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type_document')
                    ->label('Tipo documento')
                    ->options(TypeDocument::class)
                    ->required(),
                Forms\Components\TextInput::make('document')
                    ->label('Documento')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('payment_terms')
                    ->label('Términos de Pago'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('contacts')
                    ->relationship()
                    ->label('Contactos')
                    ->schema([
                        Forms\Components\Select::make('type_contact')
                            ->label('Tipo contacto')
                            ->options(TypeContact::class)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('contact')
                            ->label('Contacto')
                            ->required(),
                        Forms\Components\TextInput::make('label')
                            ->label('Etiqueta')
                            ->placeholder('Ej: Oficina, Casa, Principal')
                            ->maxLength(50),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Contacto Principal')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_document')
                    ->label('Tipo documento'),
                Tables\Columns\TextColumn::make('document')
                    ->label('Documento'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección'),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

}
