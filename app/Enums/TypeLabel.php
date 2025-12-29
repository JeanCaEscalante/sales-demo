<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeLabel: string implements HasLabel
{
    case OFICINA = 'oficina';
    case CASA = 'casa';
    case CELULAR = 'celular';
    case TRABAJO = 'trabajo';
    case OTRO = 'otro';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OFICINA => 'Oficina',
            self::CASA => 'Casa',
            self::CELULAR => 'Celular',
            self::TRABAJO => 'Trabajo',
            self::OTRO => 'Otro',
        };
    }
}
