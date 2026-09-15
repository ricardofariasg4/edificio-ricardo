<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Helpers\HowToValidate;
use App\Helpers\CpfExtractor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    protected $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        try {
            Gate::authorize('view-all-users');
            $response = response()->json($this->userService->listAllUsers(), Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            $response = response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar todos os usuários.'
            ], Response::HTTP_FORBIDDEN);
        }
        return $response;
    }

    public function show(int $id)
    {
        try {
            // Nota: view-user já cobre "é o próprio usuário OU é funcionário
            // (admin/síndico/porteiro)" — chamar view-all-users antes bloquearia
            // um morador de ver o próprio perfil.
            Gate::authorize('view-user', $id);
            $response = response()->json($this->userService->listUserById($id), Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            $response = response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar este usuário.'
            ], Response::HTTP_FORBIDDEN);
        }
        return $response;
    }

    public function store(Request $request)
    {
        $request->merge(['cpf' => CpfExtractor::extractNumbers($request->cpf)]);
    
        try {
            $validatedData = $request->validate([
                'nome' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:usuarios,email',
                'senha' => 'required|string|min:8',
                'cpf' => 'required|string|max:14|unique:usuarios,cpf',
                'idade' => 'required|integer|min:12',
                'tipo_usuario' => 'required|string|in:sindico,porteiro,morador,prestador,visitante'
            ]);
            Gate::authorize('register-internal-member', $request->input('tipo_usuario'));
            $user = $this->userService->createNewUser($validatedData);
            $response = response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            $response = response()->json(
                [
                    'error' => "Não foi possível validar os dados de criação",
                    'details' => $e->errors()
                ], 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (AuthorizationException $e) {
            $response = response()->json(
                [
                    'message' => 'Você não está autorizado a registrar usuários do tipo ' . $request->input('tipo_usuario')
                ], 
                Response::HTTP_FORBIDDEN
            );
        }

        return $response;
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
            Gate::authorize('update-internal-member', $request);
            $response = response()->json($this->userService->updateUserById($validatedData, $id), Response::HTTP_OK);
        } catch (ValidationException $e) {
            $response = response()->json(
                [
                    'error' => "Não foi possível validar os dados de atualização",
                    'details' => $e->errors()
                ], 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (AuthorizationException $e) {
            $response = response()->json(
                [
                    'message' => 'Você não está autorizado a atualizar este usuário.'
                ], 
                Response::HTTP_FORBIDDEN
            );
        }

        return $response;
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('delete-user', $id);

        if ($this->userService->deleteUserById($id)) {
            return response()->json(['message' => 'Usuário deletado com sucesso.'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Não foi possível deletar o usuário.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
