<?php

namespace App\Enum;

enum CanRegister: int
{
    case SINDICO = 0;
    case PORTEIRO = 1;
    case MORADOR = 2;
    case VISITANTE = 3;
    case PRESTADOR = 3;
    case PET = 3;

    public function canRegisterInternalMember(int $targetRole): bool
    {
        return $this->value < $targetRole;
    }
}