<?php

namespace App\Services;

use Filament\Forms\Get;
use Filament\Forms\Set;

class SaleCalculationService
{
    /**
     * Calcula todos los valores de una línea de artículo
     */
    public static function calculateLineItem(Get $get, Set $set, string $path = null): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $isExempt = $get('is_exempt_operation') ?? false;
        
        // Calcular base imponible (cantidad × precio unitario)
        $taxBase = $quantity * $unitPrice;
        $set('tax_base', number_format($taxBase, 2, '.', ''));
        
        // Si está exento, no hay impuestos
        if ($isExempt) {
            $set('tax_amount', '0.00');
            $set('surcharge_equivalence_amount', '0.00');
            $set('net_amount', number_format($taxBase, 2, '.', ''));
            return;
        }
        
        // Obtener tasa de impuesto
        $taxRate = (float) ($get('tax_rate') ?? 0);
        $taxAmount = $taxBase * ($taxRate / 100);
        $set('tax_amount', number_format($taxAmount, 2, '.', ''));
        
        // Calcular recargo de equivalencia si aplica
        $surchargeRate = (float) ($get('surcharge_rate') ?? 0);
        $surchargeAmount = $taxBase * ($surchargeRate / 100);
        $set('surcharge_equivalence_amount', number_format($surchargeAmount, 2, '.', ''));
        
        // Calcular monto neto total
        $netAmount = $taxBase + $taxAmount + $surchargeAmount;
        $set('net_amount', number_format($netAmount, 2, '.', ''));
    }
    
    /**
     * Calcula los totales generales del documento de venta
     */
    public static function calculateDocumentTotals(Get $get, Set $set): void
    {
        $items = $get('Artículos') ?? [];
        
        $subtotalBase = 0;
        $subtotalTaxes = 0;
        $subtotalSurcharge = 0;
        
        foreach ($items as $item) {
            $subtotalBase += (float) ($item['tax_base'] ?? 0);
            $subtotalTaxes += (float) ($item['tax_amount'] ?? 0);
            $subtotalSurcharge += (float) ($item['surcharge_equivalence_amount'] ?? 0);
        }
        
        // Obtener descuentos si existen
        $subtotalDiscounts = (float) ($get('subtotal_discounts') ?? 0);
        
        // Calcular total
        $total = $subtotalBase + $subtotalTaxes + $subtotalSurcharge - $subtotalDiscounts;
        
        // Establecer valores
        $set('subtotal_base', number_format($subtotalBase, 2, '.', ''));
        $set('subtotal_taxes', number_format($subtotalTaxes + $subtotalSurcharge, 2, '.', ''));
        $set('total', number_format($total, 2, '.', ''));
    }
    
    /**
     * Valida el stock disponible de un producto
     */
    public static function validateStock(int $productId, float $quantity): array
    {
        $product = \App\Models\Product::find($productId);
        
        if (!$product) {
            return [
                'valid' => false,
                'message' => 'Producto no encontrado'
            ];
        }
        
        if ($product->stock <= 0) {
            return [
                'valid' => false,
                'message' => 'Producto sin stock disponible'
            ];
        }
        
        if ($product->stock < $quantity) {
            return [
                'valid' => false,
                'message' => "Stock insuficiente. Disponible: {$product->stock}"
            ];
        }
        
        return [
            'valid' => true,
            'available_stock' => $product->stock
        ];
    }
    
    /**
     * Aplica un descuento al total del documento
     */
    public static function applyDiscount(Get $get, Set $set, ?int $discountId): void
    {
        if (!$discountId) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        $discount = \App\Models\Discount::find($discountId);
        
        if (!$discount || !$discount->is_active) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        // Validar fechas
        $now = now();
        if ($discount->start_date && $now->lt($discount->start_date)) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        if ($discount->end_date && $now->gt($discount->end_date)) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        // Validar máximo de usos
        if ($discount->max_uses && $discount->used >= $discount->max_uses) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        $subtotalBase = (float) ($get('subtotal_base') ?? 0);
        
        // Validar monto mínimo
        if ($discount->min_purchase_amount && $subtotalBase < $discount->min_purchase_amount) {
            $set('subtotal_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        // Calcular descuento
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = $subtotalBase * ($discount->value / 100);
        } else {
            $discountAmount = $discount->value;
        }
        
        // No puede exceder el subtotal
        $discountAmount = min($discountAmount, $subtotalBase);
        
        $set('subtotal_discounts', number_format($discountAmount, 2, '.', ''));
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
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastSale && $lastSale->invoice_number) {
            // Extraer el número de la serie (ej: FTA-20231225-5 -> 5)
            $parts = explode('-', $lastSale->invoice_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        }
        
        $invoiceNumber = "{$prefix}-{$nextNumber}";
        
        return [
            'series' => $series,
            'invoice_number' => $invoiceNumber,
            'serial' => $invoiceNumber
        ];
    }
}