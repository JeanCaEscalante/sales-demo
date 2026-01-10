<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TypePaymentMethod: string implements HasColor, HasLabel
{
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case TRANSFER = 'transfer';
    case MOBILE_PAY = 'mobile_pay';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::CREDIT_CARD => 'Tarjeta de Crédito',
            self::DEBIT_CARD => 'Tarjeta de Débito',
            self::TRANSFER => 'Transferencia',
            self::MOBILE_PAY => 'Pago Móvil',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::CASH => 'success',
            self::CREDIT_CARD => 'primary',
            self::DEBIT_CARD => 'warning',
            self::TRANSFER => 'info',
            self::MOBILE_PAY => 'secondary',
        };
    }
}
