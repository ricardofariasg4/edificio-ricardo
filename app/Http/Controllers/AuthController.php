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
                'error' => 'Não foi possível validar seu registro: ' . $e->getMessage()
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
        $credentials = $request->validate([
            'email' => 'required|string|email|max:255|unique:USUARIOS,email',
            'senha' => 'required|string|min:8'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
        }

        
    }
}
