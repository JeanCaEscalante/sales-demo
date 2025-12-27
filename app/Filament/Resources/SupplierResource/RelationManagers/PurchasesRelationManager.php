<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'Compras';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt_number')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Nro. Comprobante')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('series')
                    ->label('Serie'),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.purchases.edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
