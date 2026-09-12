<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Enum\AuthorizedEmployees;

class EnsureRegistrationByAuthorized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!AuthorizedEmployees::tryFrom($user->tipo_usuario->value)) {
            return response()->json([
                'message' => 'Acesso negado. Operação reservada para usuários autorizados.'
            ], 403);
        }

        return $next($request);
    }
}