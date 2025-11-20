<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Helpers\HowToValidate;
use \Illuminate\Database\Eloquent\Collection;

class UserController extends Controller
{
    protected $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): Collection
    {
        return $this->userService->listAllUsers();
    }

    public function show(int $id): mixed
    {
        return $this->userService->listUserById($id);
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

        $validatedData = $request->validate($dataToValidate);
        
        return $this->userService->updateUserById($validatedData, $id);
    }

    public function destroy(int $id): mixed
    {
        return $this->userService->deleteUserById($id);
    }
}
