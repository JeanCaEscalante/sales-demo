<?php

namespace App\Services;

use App\Models\Discount;
use Illuminate\Support\Carbon;

class DiscountService
{
    /**
     * Valida si un descuento puede ser aplicado
     */
    public function validateDiscount(Discount $discount, float $subtotal): array
    {
        // Verificar si está activo
        if (!$discount->is_active) {
            return [
                'valid' => false,
                'message' => 'El descuento no está activo'
            ];
        }
        
        // Validar fechas
        $now = Carbon::now();
        
        if ($discount->start_date && $now->lt($discount->start_date)) {
            return [
                'valid' => false,
                'message' => 'El descuento aún no está vigente'
            ];
        }
        
        if ($discount->end_date && $now->gt($discount->end_date)) {
            return [
                'valid' => false,
                'message' => 'El descuento ha expirado'
            ];
        }
        
        // Validar máximo de usos
        if ($discount->max_uses && $discount->used >= $discount->max_uses) {
            return [
                'valid' => false,
                'message' => 'El descuento ha alcanzado el máximo de usos permitidos'
            ];
        }
        
        // Validar monto mínimo de compra
        if ($discount->min_purchase_amount && $subtotal < $discount->min_purchase_amount) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'El monto mínimo de compra es %.2f€. Subtotal actual: %.2f€',
                    $discount->min_purchase_amount,
                    $subtotal
                )
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Calcula el monto del descuento
     */
    public function calculateDiscountAmount(Discount $discount, float $subtotal): float
    {
        if ($discount->type === 'percentage') {
            $amount = $subtotal * ($discount->value / 100);
        } else {
            $amount = $discount->value;
        }
        
        // El descuento no puede exceder el subtotal
        return min($amount, $subtotal);
    }
    
    /**
     * Aplica un descuento y actualiza su contador de usos
     */
    public function applyDiscount(Discount $discount): void
    {
        $discount->increment('used');
    }
    
    /**
     * Busca un descuento por código
     */
    public function findByCode(string $code): ?Discount
    {
        return Discount::where('code', $code)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Valida y calcula un descuento por código
     */
    public function processDiscountCode(string $code, float $subtotal): array
    {
        $discount = $this->findByCode($code);
        
        if (!$discount) {
            return [
                'valid' => false,
                'message' => 'Código de descuento no encontrado o inactivo'
            ];
        }
        
        $validation = $this->validateDiscount($discount, $subtotal);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        $amount = $this->calculateDiscountAmount($discount, $subtotal);
        
        return [
            'valid' => true,
            'discount' => $discount,
            'amount' => $amount,
            'message' => sprintf(
                'Descuento aplicado: %s (%.2f€)',
                $discount->name,
                $amount
            )
        ];
    }
}