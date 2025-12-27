<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProducts extends BaseWidget
{
    protected static ?string $heading = 'Top 5 Productos más vendidos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->withSum('saleItems', 'quantity')
                    ->orderByDesc('sale_items_sum_quantity')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código'),
                Tables\Columns\TextColumn::make('sale_items_sum_quantity')
                    ->label('Cantidad Vendida')
                    ->numeric(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock Actual')
                    ->numeric(),
            ]);
    }
}
