<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCustomers extends BaseWidget
{
    protected static ?string $heading = 'Top 5 Clientes';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->withSum('sales', 'total_amount')
                    ->orderByDesc('sales_sum_total_amount')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('document')
                    ->label('Documento'),
                Tables\Columns\TextColumn::make('sales_sum_total_amount')
                    ->label('Total Compras')
                    ->money('USD'),
            ]);
    }
}
