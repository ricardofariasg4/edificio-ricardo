<?php

namespace App\Enum;

enum CanRegister: string
{
    case ADMIN = 'admin';
    case SINDICO = 'sindico';
    case PORTEIRO = 'porteiro';
    case MORADOR = 'morador';

    public function canRegisterInternalMember(string $targetRole): bool
    {
        return match ($this) {
            self::ADMIN => in_array($targetRole, ['*']),
            self::SINDICO => in_array($targetRole, [
                'porteiro',
                'morador',
                'visitante',
                'pet',
                'prestador'
            ]),
            self::MORADOR => in_array($targetRole, [
                'visitante',
                'pet',
                'prestador'
            ]),
        };
    }
}

