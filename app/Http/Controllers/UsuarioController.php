<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::all();
        return response()->json($usuarios);
    }

    public function show($id)
    {
        $usuario = Usuario::find($id);
        
        if ($usuario) {
            return response()->json($usuario);
        } else {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:USUARIOS,email',
            'senha' => 'required|string|min:8',
            'cpf' => 'required|string|max:14|unique:USUARIOS,cpf',
            'idade' => 'required|integer|min:12',
            'tipo_usuario' => 'required|string|in:sindico,porteiro,morador,prestador,visitante'
        ]);

        $usuario = Usuario::create($validatedData);
        return response()->json($usuario, 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $validatedData = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:USUARIOS,email,' . $id . ',id_usuario',
            'senha' => 'sometimes|required|string|min:8',
            'cpf' => 'sometimes|required|string|max:14|unique:USUARIOS,cpf,' . $id . ',id_usuario',
            'idade' => 'sometimes|required|integer|min:0',
            'tipo_usuario' => 'sometimes|required|string|in:morador,porteiro,sindico'
        ]);

        $usuario->update($validatedData);
        return response()->json($usuario);
    }

    public function destroy($id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $usuario->delete();
        return response()->json(['message' => 'Usuário deletado com sucesso']);
    }
}
