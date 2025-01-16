<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeDocument: string implements HasLabel
{
    case Natural = 'N';
    case Legal = 'J';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Natural => 'Natural',
            self::Legal => 'Jurídica ',
        };
    }
}
