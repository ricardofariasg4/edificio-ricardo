<?php

namespace App\Enum;

use App\Enum\PeopleBuilding;

enum CanRegister: int
{
    case ADMIN = -1;
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

    public static function fromPeopleBuilding(PeopleBuilding $role): self
    {
        return match ($role) {
            PeopleBuilding::ADMIN => self::ADMIN,
            PeopleBuilding::SINDICO => self::SINDICO,
            PeopleBuilding::PORTEIRO => self::PORTEIRO,
            PeopleBuilding::MORADOR => self::MORADOR,
            PeopleBuilding::VISITANTE => self::VISITANTE,
            PeopleBuilding::PRESTADOR => self::PRESTADOR,
            PeopleBuilding::PET => self::PET,
        };
    }

    public static function fromString(string $role): self
    {
        return self::fromPeopleBuilding(PeopleBuilding::from($role));
    }
}