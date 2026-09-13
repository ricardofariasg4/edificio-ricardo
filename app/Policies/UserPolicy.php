<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Enum\CanRegister;

class UserPolicy
{
    public function registerInternalMember(Usuario $user, string $targetRole): bool
    {
        $userRole = CanRegister::fromPeopleBuilding($user->tipo_usuario);
        $targetRoleEnum = CanRegister::fromString($targetRole);
        return $userRole->canRegisterInternalMember($targetRoleEnum->value);
    }
}
