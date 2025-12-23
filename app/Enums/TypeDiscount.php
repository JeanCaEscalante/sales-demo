<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeDiscount: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Percentage => 'Porcentaje',
            self::Fixed => 'Fijo',
        };
    }
}
