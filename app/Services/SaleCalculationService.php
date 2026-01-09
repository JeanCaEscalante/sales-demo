<?php

namespace App\Services;

use Filament\Forms\Get;
use Filament\Forms\Set;

class SaleCalculationService
{
    /**
     * Calcula todos los valores de una línea de artículo de venta
     * Basado en updateCalculations de PurchaseResource
     */
    public static function calculateLineItem(Get $get, Set $set, ?string $path = null): void
    {
        // ========================================
        // 1. OBTENER VALORES BASE
        // ========================================
        $quantity = (float) ($get('quantity') ?? 1);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $taxRateId = $get('tax_rate_id');
        $isExempt = (bool) ($get('tax_exempt') ?? false);

        // Validar cantidad mínima
        if ($quantity <= 0) {
            $quantity = 1;
        }

        // ========================================
        // 2. CÁLCULO DE SUBTOTAL
        // ========================================
        $subtotal = $quantity * $unitPrice;
        $set('subtotal', number_format($subtotal, 2, '.', ''));

        // ========================================
        // 3. CÁLCULOS DE IMPUESTOS
        // ========================================
        $taxRate = 0;
        $taxName = null;
        $taxAmount = 0;

        if (! $isExempt && $taxRateId) {
            $tax = \App\Models\TaxRate::find($taxRateId);
            if ($tax) {
                $taxRate = (float) $tax->rate;
                $taxName = $tax->name;
                $taxAmount = $subtotal * ($taxRate / 100);
            }
        }

        // Establecer valores de impuestos
        $set('tax_rate', $taxRate);
        $set('tax_name', $taxName);
        $set('tax_amount', number_format($taxAmount, 2, '.', ''));

        // ========================================
        // 4. ACTUALIZAR TOTALES DEL FORMULARIO
        // ========================================
        self::calculateDocumentTotals($get, $set);
    }

    /**
     * Calcula los totales generales del documento de venta
     * Basado en updateTotals de PurchaseResource
     */
    public static function calculateDocumentTotals(Get $get, Set $set): void
    {
        $items = $get('items');
        $isRemote = false;

        if ($items === null) {
            $items = $get('../../items');
            $isRemote = true;
        }

        $items = collect($items ?? []);

        $taxableSubtotal = 0;
        $exemptSubtotal = 0;
        $totalTax = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $subtotal = (float) ($item['subtotal'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);
            $taxExempt = $item['tax_exempt'] ?? false;

            if ($taxExempt) {
                $exemptSubtotal += $subtotal;
            } else {
                $taxableSubtotal += $subtotal;
                $totalTax += $taxAmount;
            }

            $totalQuantity += $quantity;
        }

        $subtotal = $taxableSubtotal + $exemptSubtotal;
        $totalAmount = $subtotal + $totalTax;

        $prefix = $isRemote ? '../../' : '';

        $set($prefix.'subtotal', number_format($subtotal, 2, '.', ''));
        $set($prefix.'taxable_base', number_format($taxableSubtotal, 2, '.', ''));
        $set($prefix.'total_exempt', number_format($exemptSubtotal, 2, '.', ''));
        $set($prefix.'total_tax', number_format($totalTax, 2, '.', ''));
        $set($prefix.'total_amount', number_format($totalAmount, 2, '.', ''));
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
