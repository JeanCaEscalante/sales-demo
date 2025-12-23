<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $salesToday = Sale::whereDate('created_at', Carbon::today())->sum('total_amount');
        $salesMonth = Sale::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');
        
        $lowStockCount = Product::whereColumn('stock', '<=', 'min_stock')->count();

        return [
            Stat::make('Ventas de Hoy', '$' . number_format($salesToday, 2))
                ->description('Total vendido hoy')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Ventas del Mes', '$' . number_format($salesMonth, 2))
                ->description('Total vendido este mes')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('Stock Bajo', $lowStockCount)
                ->description('Productos por debajo del mínimo')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
