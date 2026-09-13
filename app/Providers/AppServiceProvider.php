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
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceRepositoryInterface;
use App\Repositories\PackageRepository;
use App\Repositories\PackageRepositoryInterface;
use App\Repositories\MoveRepository;
use App\Repositories\MoveRepositoryInterface;

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
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
        $this->app->bind(MoveRepositoryInterface::class, MoveRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gates para User
        Gate::define('register-internal-member', function (Usuario $user, string $targetRole) {
            $userRole = CanRegister::fromPeopleBuilding($user->tipo_usuario);
            $targetRoleEnum = CanRegister::fromString($targetRole);
            return $userRole->canRegisterInternalMember($targetRoleEnum->value);
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

        // Gates para Pet
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

        // Gates para Invoice
        Gate::define('view-all-invoices', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });
        
        Gate::define('view-invoice', function (Usuario $user, $invoice) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            // Morador pode ver apenas seus próprios boletos
            if ($user->tipo_usuario === PeopleBuilding::MORADOR) {
                return $user->id_usuario === $invoice->id_morador;
            }
            return false;
        });
        
        Gate::define('register-invoice', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });
        
        Gate::define('update-invoice', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });
        
        Gate::define('delete-invoice', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        // Gates para Package (Encomendas)
        Gate::define('view-all-packages', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        Gate::define('view-package', function (Usuario $user, $package) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            return $user->id_usuario === $package->id_usuario;
        });

        Gate::define('register-package', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        // Gates para Move (Mudanças)
        Gate::define('view-all-moves', function (Usuario $user) {
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });

        Gate::define('view-move', function (Usuario $user, $move) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO])) {
                return true;
            }
            return $user->id_usuario === $move->id_morador;
        });

        Gate::define('register-move', function (Usuario $user, int $idMorador) {
            if (in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO])) {
                return true;
            }
            // Morador só pode agendar sua própria mudança
            return $user->id_usuario === $idMorador;
        });

        Gate::define('approve-move', function (Usuario $user) {
            // Síndico e Admin aprovam definitivamente, Porteiro aprova provisoriamente
            return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
        });
    }
}
