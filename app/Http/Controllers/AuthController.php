<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Helpers\CpfExtractor;
use App\Helpers\HowToValidate;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request) 
    {
        try {
            // Trata o CPF antes da validação
            $request->merge(['cpf' => CpfExtractor::extractNumbers($request->cpf)]);
            
            $request->validate([
                'nome' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:USUARIOS,email',
                'senha' => 'required|string|min:8',
                'cpf' => 'required|string|max:14|unique:USUARIOS,cpf',
                'tipo_usuario' => HowToValidate::getRuleByField('tipo_usuario'),
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
            'cpf' => CpfExtractor::extractNumbers($request->cpf),
            'tipo_usuario' => $request->tipo_usuario,
        ]);
        
        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => $user
        ], 201);
    }

    public function login(Request $request) 
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|string|email|max:255',
                'senha' => 'required|string|min:8'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar seu login.'
            ], 422);
        }

        $credentials['password'] = $credentials['senha'];
        unset($credentials['senha']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Credenciais inválidas.'
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => Auth::user()
        ], 200);
    }

    public function logout(Request $request) 
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout realizado com sucesso.'
        ], 200);
    }
}
