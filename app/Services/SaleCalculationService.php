<?php

namespace App\Services;

use Filament\Forms\Get;
use Filament\Forms\Set;

class SaleCalculationService
{
    /**
     * Calcula todos los valores de una línea de artículo
     */
    public static function calculateLineItem(Get $get, Set $set, ?string $path = null): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $discount = (float) ($get('discount') ?? 0); // Ahora es porcentaje

        // Calcular subtotal (cantidad × precio unitario) y aplicar descuento (porcentaje)
        $subtotal = ($quantity * $unitPrice) * (1 - ($discount / 100));
        $set('subtotal', number_format($subtotal, 2, '.', ''));

        // Obtener tasa de impuesto
        $taxRateId = $get('tax_rate_id');
        $taxRateValue = 0;
        $isExempt = (bool) ($get('tax_exempt') ?? false);

        if (! $isExempt && $taxRateId) {
            $taxRate = \App\Models\TaxRate::find($taxRateId);
            if ($taxRate) {
                $taxRateValue = (float) $taxRate->rate;
                $set('tax_rate', $taxRateValue);
                $set('tax_name', $taxRate->name);
            }
        } else {
            $set('tax_rate', 0);
            $set('tax_amount', 0);
        }

        if (! $isExempt) {
            $taxAmount = $subtotal * ($taxRateValue / 100);
            $set('tax_amount', number_format($taxAmount, 2, '.', ''));
        } else {
            $set('tax_amount', '0.00');
        }
    }

    /**
     * Calcula los totales generales del documento de venta
     */
    public static function calculateDocumentTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];

        $subtotal = 0;
        $taxableBase = 0;
        $totalExempt = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $itemSubtotal = (float) ($item['subtotal'] ?? 0);
            $subtotal += $itemSubtotal;

            if (isset($item['tax_exempt']) && $item['tax_exempt']) {
                $totalExempt += $itemSubtotal;
            } else {
                $taxableBase += $itemSubtotal;
                $totalTax += (float) ($item['tax_amount'] ?? 0);
            }
        }

        // Obtener descuentos globales si existen
        $globalDiscounts = (float) ($get('total_discounts') ?? 0);

        // Calcular total
        $totalAmount = $subtotal + $totalTax - $globalDiscounts;

        // Establecer valores
        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('taxable_base', number_format($taxableBase, 2, '.', ''));
        $set('total_exempt', number_format($totalExempt, 2, '.', ''));
        $set('total_tax', number_format($totalTax, 2, '.', ''));
        $set('total_amount', number_format($totalAmount, 2, '.', ''));
    }

    /**
     * Valida el stock disponible de un producto
     */
    public static function validateStock(int $productId, float $quantity): array
    {
        $product = \App\Models\Product::find($productId);

        if (! $product) {
            return [
                'valid' => false,
                'message' => 'Producto no encontrado',
            ];
        }

        if ($product->stock <= 0) {
            return [
                'valid' => false,
                'message' => 'Producto sin stock disponible',
            ];
        }

        if ($product->stock < $quantity) {
            return [
                'valid' => false,
                'message' => "Stock insuficiente. Disponible: {$product->stock}",
            ];
        }

        return [
            'valid' => true,
            'available_stock' => $product->stock,
        ];
    }

    /**
     * Aplica un descuento al total del documento
     */
    public static function applyDiscount(Get $get, Set $set, ?int $discountId): void
    {
        if (! $discountId) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        $discount = \App\Models\Discount::find($discountId);

        if (! $discount || ! $discount->is_active) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        // Validar fechas
        $now = now();
        if ($discount->start_date && $now->lt($discount->start_date)) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        if ($discount->end_date && $now->gt($discount->end_date)) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        // Validar máximo de usos
        if ($discount->max_uses && $discount->used >= $discount->max_uses) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        $subtotal = (float) ($get('subtotal') ?? 0);

        // Validar monto mínimo
        if ($discount->min_purchase_amount && $subtotal < $discount->min_purchase_amount) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);

            return;
        }

        // Calcular descuento
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = $subtotal * ($discount->value / 100);
        } else {
            $discountAmount = $discount->value;
        }

        // No puede exceder el subtotal
        $discountAmount = min($discountAmount, $subtotal);

        $set('total_discounts', number_format($discountAmount, 2, '.', ''));
        self::calculateDocumentTotals($get, $set);
    }

    /**
     * Genera el número de serie basado en el tipo de documento
     */
    public static function generateSeriesNumber(string $documentType, int $userId): array
    {
        $date = now()->format('Ymd');

        if ($documentType === 'bill') {
            $series = "FTA-{$date}";
            $prefix = $series;
        } else {
            $series = "TKT-{$date}";
            $prefix = $series;
        }

        // Buscar el último número de la serie
        $lastSale = \App\Models\Sale::where('user_id', $userId)
            ->where('series', $series)
            ->orderBy('number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastSale && $lastSale->number) {
            // Extraer el número de la serie (ej: FTA-20231225-5 -> 5)
            $parts = explode('-', $lastSale->number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        }

        $documentNumber = "{$prefix}-{$nextNumber}";

        return [
            'series' => $series,
            'number' => $documentNumber,
        ];
    }
}
