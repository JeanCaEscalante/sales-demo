<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeSubject: string implements HasLabel
{
    case Supplier = 'supplier';
    case Customer = 'customer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Supplier => 'Proveedor',
            self::Customer => 'Cliente',
        };
    }
}
