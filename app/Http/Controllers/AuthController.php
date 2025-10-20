<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function register(Request $request) 
    {
        try {
            $request->validate([
                'nome' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:USUARIOS,email',
                'senha' => 'required|string|min:8',
                'cpf' => 'required|string|max:14|unique:USUARIOS,cpf',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar seu registro.'
            ], 422);
        }

        $user = Usuario::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'senha' => Hash::make($request->senha),
            'cpf' => $request->cpf
        ]);

        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request) 
    {
        try {
            // if ($request->headers->has('Authorization')) {
            //     $basicAuth = $request->header('Authorization');
            //     $decode = base64_decode(substr($basicAuth, 6));
            //     list($email, $senha) = explode(':', $decode);
            //     $request->merge(['email' => $email, 'senha' => $senha]);
            // }

            if ($request->hasHeader('php-auth-user') && $request->hasHeader('php-auth-pw')) {
                $email = $request->header('php-auth-user');
                $senha = $request->header('php-auth-pw');
                $request->merge(['email' => $email, 'senha' => $senha]);
            }

            $credentials = $request->validate([
                'email' => 'required|string|email|max:255',
                'senha' => 'required|string|min:8'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar seu login.'
            ], 422);
        }

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Credenciais inválidas.'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request) 
    {
        $user = $request->user();
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->delete();
        return response()->json([
            'message' => 'Logout realizado com sucesso.'
        ], 200);
    }
}
