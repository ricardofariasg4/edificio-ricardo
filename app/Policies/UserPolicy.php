<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Enum\CanRegister;

class UserPolicy
{
    public function registerInternalMember(Usuario $user, string $targetRole): bool
    {
        $userRole = CanRegister::from($user->tipo_usuario);
        return $userRole?->canRegisterInternalMember($targetRole);
    }
}
