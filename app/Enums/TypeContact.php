<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeContact: string implements HasLabel
{
    case Email = 'email';
    case Phone = 'phone ';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Teléfono',
        };
    }
}
