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
        $discountAmount = (float) ($get('discount_amount') ?? 0);
        
        // Calcular base imponible (cantidad × precio unitario) - descuento
        $taxBase = ($quantity * $unitPrice) - $discountAmount;
        
        // Obtener tasa de impuesto
        $taxRateId = $get('tax_rate_id');
        $taxRateValue = 0;
        
        if ($taxRateId) {
            $taxRate = \App\Models\TaxRate::find($taxRateId);
            if ($taxRate) {
                $taxRateValue = (float) $taxRate->rate;
            }
        }
        
        $taxAmount = $taxBase * ($taxRateValue / 100);
        $set('tax_amount', number_format($taxAmount, 2, '.', ''));
        
        // Calcular monto neto total (subtotal en el modelo)
        $total = $taxBase + $taxAmount;
        $set('total', number_format($total, 2, '.', ''));
    }
    
    /**
     * Calcula los totales generales del documento de venta
     */
    public static function calculateDocumentTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        
        $totalBase = 0;
        $totalTaxes = 0;
        
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount_amount'] ?? 0);
            
            $itemBase = ($quantity * $unitPrice) - $discount;
            $totalBase += $itemBase;
            $totalTaxes += (float) ($item['tax_amount'] ?? 0);
        }
        
        // Obtener descuentos globales si existen
        $globalDiscounts = (float) ($get('total_discounts') ?? 0);
        
        // Calcular total
        $totalAmount = $totalBase + $totalTaxes - $globalDiscounts;
        
        // Establecer valores
        $set('total_base', number_format($totalBase, 2, '.', ''));
        $set('total_taxes', number_format($totalTaxes, 2, '.', ''));
        $set('total_amount', number_format($totalAmount, 2, '.', ''));
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
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        $discount = \App\Models\Discount::find($discountId);
        
        if (!$discount || !$discount->is_active) {
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
        
        $totalBase = (float) ($get('total_base') ?? 0);
        
        // Validar monto mínimo
        if ($discount->min_purchase_amount && $totalBase < $discount->min_purchase_amount) {
            $set('total_discounts', '0.00');
            self::calculateDocumentTotals($get, $set);
            return;
        }
        
        // Calcular descuento
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = $totalBase * ($discount->value / 100);
        } else {
            $discountAmount = $discount->value;
        }
        
        // No puede exceder el total base
        $discountAmount = min($discountAmount, $totalBase);
        
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