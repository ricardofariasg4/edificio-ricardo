<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Enum\AuthorizedEmployess;

class GaranteCadastroPorAutorizado
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!AuthorizedEmployess::tryFrom($user->tipo_usuario)) {
            return response()->json(['message' => 'Acesso negado. Apenas síndicos e porteiros podem cadastrar novos usuários.'], 403);
        }

        return $next($request);
    }
}