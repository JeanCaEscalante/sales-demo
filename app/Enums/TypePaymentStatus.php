<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TypePaymentStatus: string implements HasColor, HasLabel
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case PARTIAL = 'partial';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PAID => 'Pagado',
            self::PENDING => 'Pendiente',
            self::PARTIAL => 'Parcial',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PAID => 'success',
            self::PENDING => 'warning',
            self::PARTIAL => 'primary',
        };
    }
}
