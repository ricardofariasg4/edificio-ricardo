<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Gate;
use App\Enum\CanRegister;
use App\Repositories\BaseRepository;
use App\Repositories\RepositoryInterface;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RepositoryInterface::class, BaseRepository::class);
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

        Gate::define('update-internal-member', function (Usuario $user, Request $request) {
            $value = $user->id_usuario === (int) $request->route('id');
            return $value;
        });

        Gate::define('view-all-users', function ($user) {
            return in_array($user->tipo_usuario, ['admin', 'sindico', 'porteiro']);
        });
    }
}
