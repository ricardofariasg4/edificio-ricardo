<?php

namespace App\Enum;

use App\Enum\PeopleBuilding;

/**
 * Hierarquia de quem pode cadastrar quem (RF01/RF07). Não é backed por int
 * porque VISITANTE/PRESTADOR/PET compartilham o mesmo nível hierárquico
 * (nenhum pode cadastrar ninguém) e PHP não permite valores duplicados
 * entre casos de um enum backed.
 */
enum CanRegister
{
    case ADMIN;
    case SINDICO;
    case PORTEIRO;
    case MORADOR;
    case VISITANTE;
    case PRESTADOR;
    case PET;

    /**
     * Nível hierárquico: quanto menor, maior o poder de cadastro. Um papel
     * só pode cadastrar papéis com nível estritamente maior que o seu.
     */
    public function level(): int
    {
        return match ($this) {
            self::ADMIN => -1,
            self::SINDICO => 0,
            self::PORTEIRO => 1,
            self::MORADOR => 2,
            self::VISITANTE, self::PRESTADOR, self::PET => 3,
        };
    }

    public function canRegisterInternalMember(self $targetRole): bool
    {
        return $this->level() < $targetRole->level();
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
