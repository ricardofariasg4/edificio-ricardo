<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Gate;
use App\Enum\CanRegister;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
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
    }
}
