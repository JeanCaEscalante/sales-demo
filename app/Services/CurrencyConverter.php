<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;

class CurrencyConverter
{
    /**
     * Convierte un monto de una moneda a USD
     */
    public function toUSD(float $amount, string $fromCurrencyCode, ?Carbon $date = null): float
    {
        if ($fromCurrencyCode === 'USD') {
            return $amount;
        }

        $rate = $this->getRate($fromCurrencyCode, $date);

        if (! $rate) {
            throw new \Exception("No se encontró tasa de cambio para {$fromCurrencyCode}");
        }

        return $amount / $rate;
    }

    /**
     * Convierte un monto de USD a otra moneda
     */
    public function fromUSD(float $amount, string $toCurrencyCode, ?Carbon $date = null): float
    {
        if ($toCurrencyCode === 'USD') {
            return $amount;
        }

        $rate = $this->getRate($toCurrencyCode, $date);

        if (! $rate) {
            throw new \Exception("No se encontró tasa de cambio para {$toCurrencyCode}");
        }

        return $amount * $rate;
    }

    /**
     * Convierte entre dos monedas cualquiera
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Primero convertimos a USD, luego a la moneda destino
        $amountInUSD = $this->toUSD($amount, $fromCurrency, $date);

        return $this->fromUSD($amountInUSD, $toCurrency, $date);
    }

    /**
     * Obtiene la tasa de cambio para una fecha específica
     */
    private function getRate(string $currencyCode, ?Carbon $date = null): ?float
    {
        $currency = Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            return null;
        }

        if ($currency->is_base) {
            return 1.0;
        }

        $date = $date ?? now();

        $exchangeRate = ExchangeRate::where('currency_id', $currency->id)
            ->where('effective_date', '<=', $date)
            ->latest('effective_date')
            ->first();

        return $exchangeRate?->rate;
    }
}
