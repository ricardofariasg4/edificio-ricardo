<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Gate;
use App\Enum\CanRegister;
use App\Enum\PeopleBuilding;
use App\Repositories\BaseRepository;
use App\Repositories\RepositoryInterface;
use Illuminate\Http\Request;
use App\Repositories\PetRepository;
use App\Repositories\PetRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RepositoryInterface::class, BaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PetRepositoryInterface::class, PetRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('register-internal-member', function (Usuario $user, string $targetRole) {
            $userRole = CanRegister::from($user->tipo_usuario);
            return $userRole?->canRegisterInternalMember($targetRole);
        });

        Gate::define('update-internal-member', function (Usuario $user, Request $request) {
            $value = $user->id_usuario === (int) $request->route('id');
            return $value;
        });

        Gate::define('view-all-users', function ($user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        Gate::define('delete-user', function (Usuario $user, int $targetUserId) {
            if ($user->id_usuario === $targetUserId) {
                return false;
            }
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO]);
        });

        Gate::define('view-user', function (Usuario $user, int $targetUserId) {
            if ($user->id_usuario === $targetUserId) {
                return true;
            }
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        Gate::define('view-all-pets', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        Gate::define('view-pet', function (Usuario $user, $pet) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            
            if ($user->tipo_usuario === PeopleBuilding::MORADOR) {
                return $user->id_usuario === $pet->id_morador;
            }
            return false;
        });

        Gate::define('register-pet', function (Usuario $user, int $idMorador) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            
            if ($user->tipo_usuario === PeopleBuilding::MORADOR) {
                return $user->id_usuario === $idMorador;
            }
            return false;
        });

        Gate::define('update-pet', function (Usuario $user, $pet) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            
            if ($user->tipo_usuario === PeopleBuilding::MORADOR) {
                return $user->id_usuario === $pet->id_morador;
            }
            return false;
        });

        Gate::define('delete-pet', function (Usuario $user, $pet) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            
            if ($user->tipo_usuario === PeopleBuilding::MORADOR) {
                return $user->id_usuario === $pet->id_morador;
            }
            return false;
        });
    }
}
