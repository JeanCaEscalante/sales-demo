<?php

namespace App\Filament\Resources;

use App\Enums\TypeContact;
use App\Enums\TypeLabel;
use App\Enums\TypeDocument;
use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
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
                        Forms\Components\Select::make('label')
                            ->label('Etiqueta')
                            ->options(TypeLabel::class)
                            ->required(),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Contacto Principal')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type_document')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Términos de Pago')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchases_sum_total_amount')
                    ->label('Total Compras')
                    ->sum('purchases', 'total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_document')
                    ->label('Tipo de Documento')
                    ->options(TypeDocument::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
