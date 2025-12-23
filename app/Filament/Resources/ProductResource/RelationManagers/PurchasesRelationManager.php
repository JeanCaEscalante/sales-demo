<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseItems';

    protected static ?string $label = 'Compra';

    protected static ?string $title = 'Compras';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                Tables\Columns\TextColumn::make('purchase.document_type')
                    ->label('Tipo Comprobante'),
                Tables\Columns\TextColumn::make('purchase.receipt_number')
                    ->label('Número'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Precio Compra'),
            ])
            ->filters([
                //
            ]);
    }
}
