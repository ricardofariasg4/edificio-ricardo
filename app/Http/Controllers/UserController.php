<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Helpers\HowToValidate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helpers\CpfExtractor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use AuthorizesRequests;
    protected $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        if (Gate::denies('view-all-users')) {
            return response()->json([
                'message' => 'Acesso negado. Apenas administradores, síndicos e porteiros podem visualizar todos os usuários.'
            ], 403);
        }
        
        return $this->userService->listAllUsers();
    }

    public function show(int $id): mixed
    {
        return $this->userService->listUserById($id);
    }

    public function store(Request $request)
    {
        $request->merge(['cpf' => CpfExtractor::extractNumbers($request->cpf)]);
    
        try {
            $validatedData = $request->validate([
                'nome' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:USUARIOS,email',
                'senha' => 'required|string|min:8',
                'cpf' => 'required|string|max:14|unique:USUARIOS,cpf',
                'idade' => 'required|integer|min:12',
                'tipo_usuario' => 'required|string|in:sindico,porteiro,morador,prestador,visitante'
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'erro' => "Não foi possível validar os dados de criação",
                    'detalhes' => $e->errors()
                ], 
                422
            );
        }
        
        if (Gate::denies('register-internal-member', $request->input('tipo_usuario'))) {
            return response()->json(
                [
                    'message' => 'Você não está autorizado a registrar usuários do tipo ' . $request->input('tipo_usuario')
                ], 
                403
            );
        }
        
        $user = $this->userService->createNewUser($validatedData);

        return response()->json($user, 201);
    }

    public function update(Request $request, int $id): mixed
    {
        $dataToUpdate = json_decode($request->getContent(), true);
        $dataToValidate = [];
        
        foreach (array_keys($dataToUpdate) as $key) {
            $rule = HowToValidate::getRuleByField($key);
            $dataToValidate[$key] = $rule;
        }

        try {
            $validatedData = $request->validate($dataToValidate);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'erro' => "Não foi possível validar os dados de atualização",
                    'detalhes' => $e->errors()
                ], 
                422
            );
        }

        if (Gate::denies('update-internal-member', [$request, Auth::user()])) {
            return response()->json(
                [
                    'message' => 'Você não está autorizado a atualizar usuários do tipo ' . $request->input('tipo_usuario')
                ], 
                403
            );
        }

        return $this->userService->updateUserById($validatedData, $id);
    }

    public function destroy(int $id): mixed
    {
        return $this->userService->deleteUserById($id);
    }
}
