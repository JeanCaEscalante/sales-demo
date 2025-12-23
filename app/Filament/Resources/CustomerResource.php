<?php

namespace App\Filament\Resources;

use App\Enums\TypeContact;
use App\Enums\TypeDocument;
use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $pluralLabel = 'Clientes';

    protected static ?string $label = 'Cliente';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
                Forms\Components\TextInput::make('credit_limit')
                    ->label('Límite de Crédito')
                    ->numeric()
                    ->prefix('$'),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

}
