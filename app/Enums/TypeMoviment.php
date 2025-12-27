<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeMoviment: string implements HasLabel
{
    case INPUT = 'input';
    case OUTPUT = 'output';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INPUT => 'Entrada',
            self::OUTPUT => 'Salida',
        };
    }
}
