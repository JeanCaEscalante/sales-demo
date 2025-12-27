<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Kardex / Movimientos';

    protected static ?string $pluralLabel = 'Movimientos de Inventario';

    protected static ?string $label = 'Movimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Producto')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Usuario')
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->readOnly(),
                Forms\Components\TextInput::make('reason')
                    ->label('Motivo')
                    ->readOnly(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('previous_stock')
                    ->label('Stock Anterior')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('new_stock')
                    ->label('Stock Nuevo')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('reference_type')
                    ->label('Tipo Referencia')
                    ->readOnly(),
                Forms\Components\TextInput::make('reference_id')
                    ->label('ID Referencia')
                    ->readOnly(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull()
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'input' => 'success',
                        'output' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'input' => 'Entrada',
                        'output' => 'Salida',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cant.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('previous_stock')
                    ->label('Ant.')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('new_stock')
                    ->label('Nuevo')
                    ->numeric()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Producto')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'input' => 'Entrada',
                        'output' => 'Salida',
                    ]),
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motivo')
                    ->options([
                        'Compra' => 'Compra',
                        'Venta' => 'Venta',
                        'Ajuste' => 'Ajuste',
                        'Devolución' => 'Devolución',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Movimiento de Inventario')
                    ->modalDescription('Detalles del movimiento de inventario'),
            ])
            ->bulkActions([
                // No bulk actions for audit logs
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventoryMovements::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Movements are created via services
    }
}
