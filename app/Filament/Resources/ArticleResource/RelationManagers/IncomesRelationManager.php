<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class IncomesRelationManager extends RelationManager
{
    protected static string $relationship = 'incomes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('article_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('article_id')
            ->columns([
                Tables\Columns\TextColumn::make('income.type_receipt')
                    ->label('Tipo Comprobante'),
                Tables\Columns\TextColumn::make('income.num_receipt')
                    ->label('Número'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Precio Compra'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
