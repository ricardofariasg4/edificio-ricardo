<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PetService;
use App\Helpers\HowToValidate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityCreateException;
use App\Exceptions\EntityUpdateException;

class PetController extends Controller
{

    protected PetService $petService;

    public function __construct(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function index(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-pets')) {
                $pets = $this->petService->getAllPets();
            } else {
                // Morador vê apenas seus próprios pets
                $user = Auth::user();
                $pets = $this->petService->getPetsByMorador($user->id);
            }
            return response()->json($pets, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar pets', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $pet = $this->petService->getPetById($id);
            Gate::authorize('view-pet', $pet);
            return response()->json($pet, Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar este pet.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Pet não encontrado', Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate(HowToValidate::getPetStoreRules());
            Gate::authorize('register-pet', $validatedData['id_morador']);
            $pet = $this->petService->createPet($validatedData);

            return response()->json([
                'message' => 'Pet cadastrado com sucesso',
                'pet' => $pet
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de criação',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para cadastrar pets para este morador.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityCreateException $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao cadastrar pet', Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pet = $this->petService->getPetById($id);
            Gate::authorize('update-pet', $pet);

            $validatedData = $request->validate(HowToValidate::getPetUpdateRules($id));

            $updatedPet = $this->petService->updatePet($id, $validatedData);

            return response()->json([
                'message' => 'Pet atualizado com sucesso',
                'pet' => $updatedPet
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de atualização',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para atualizar este pet.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Pet não encontrado', Response::HTTP_NOT_FOUND);
        } catch (EntityUpdateException $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao atualizar pet', Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $pet = $this->petService->getPetById($id);
            Gate::authorize('delete-pet', $pet);

            $this->petService->deletePet($id);

            return response()->json([
                'message' => 'Pet deletado com sucesso'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para deletar este pet.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Pet não encontrado', Response::HTTP_NOT_FOUND);
        }
    }
}
