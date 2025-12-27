<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Ventas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Usually we don't create sales from the customer relation manager
                // but if we did, we would need the full SaleResource form.
                // For now, let's keep it simple or empty if we only want to view.
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nro. Factura')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('series')
                    ->label('Serie'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
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
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.sales.edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
