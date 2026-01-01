<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TypeMovement: string implements HasColor, HasLabel
{
    case INPUT = 'input';
    case OUTPUT = 'output';
    case ADJUSTMENT = 'adjustment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INPUT => 'Entrada',
            self::OUTPUT => 'Salida',
            self::ADJUSTMENT => 'Ajuste',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::INPUT => 'success',
            self::OUTPUT => 'danger',
            self::ADJUSTMENT => 'warning',
        };
    }
}
