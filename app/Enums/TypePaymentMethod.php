<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypePaymentMethod: string implements HasLabel
{
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case TRANSFER = 'transfer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::CREDIT_CARD => 'Tarjeta de Crédito',
            self::DEBIT_CARD => 'Tarjeta de Débito',
            self::TRANSFER => 'Transferencia',
        };
    }
}
