<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeReceipt: string implements HasLabel
{
    case Bill = 'bill';
    case Ticket = 'ticket';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Bill => 'Factura',
            self::Ticket => 'Ticket',
        };
    }
}
