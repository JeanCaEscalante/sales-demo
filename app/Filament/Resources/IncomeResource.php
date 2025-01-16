<?php

namespace App\Filament\Resources;

use App\Enums\TypeReceipt;
use App\Enums\TypeSubject;
use App\Filament\Resources\IncomeResource\Pages;
use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $navigationLabel = 'Ingresos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $pluralLabel = 'Ingresos';

    protected static ?string $label = 'Ingreso';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('subject_id')
                    ->relationship(
                        name: 'supplier',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('type_subject', TypeSubject::Supplier),
                    )
                    ->label('Proveedor')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('type_receipt')
                    ->label('Tipo Comprobante')
                    ->options(TypeReceipt::class)
                    ->required(),
                Forms\Components\DatePicker::make('receipt_at')
                    ->label('Fecha')
                    ->required(),
                Forms\Components\TextInput::make('receipt_series')
                    ->label('Serie'),
                Forms\Components\TextInput::make('num_receipt')
                    ->label('Número')
                    ->required(),
                Forms\Components\TextInput::make('tax')
                    ->label('Impuesto')
                    ->numeric(),
                Forms\Components\TextInput::make('total_purchase')
                    ->label('Total')
                    ->numeric(),
                Forms\Components\Repeater::make('details')
                    ->relationship()
                    ->label('Detalles')
                    ->schema([
                        Forms\Components\Select::make('article_id')
                            ->relationship(name: 'article', titleAttribute: 'name')
                            ->label('Articulo')
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Precio Compra')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('sale_price')
                            ->label('Precio Venta')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(4)
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_receipt')
                    ->label('Tipo Comprobante'),
                Tables\Columns\TextColumn::make('num_receipt')
                    ->label('Número'),
                Tables\Columns\TextColumn::make('receipt_at')
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('total_purchase')
                    ->label('Total'),
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
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
