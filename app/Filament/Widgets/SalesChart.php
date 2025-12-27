<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Ventas de los últimos 7 días';

    protected function getData(): array
    {
        // If Trend package is not installed, we can do it manually.
        // Let's check if Trend is available.

        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[] = Sale::whereDate('created_at', $date)->sum('total_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ventas ($)',
                    'data' => $data,
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
