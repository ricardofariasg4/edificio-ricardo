<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityNotFoundException;
use App\Helpers\HowToValidate;
use App\Services\AmbienteService;
use App\Services\ReservaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AmbienteController extends Controller
{
    protected AmbienteService $ambienteService;
    protected ReservaService $reservaService;

    public function __construct(AmbienteService $ambienteService, ReservaService $reservaService)
    {
        $this->ambienteService = $ambienteService;
        $this->reservaService = $reservaService;
    }

    public function index(): JsonResponse
    {
        try {
            $ambientes = $this->ambienteService->getAllAmbientes();
            return response()->json($ambientes, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar ambientes', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            Gate::authorize('manage-ambientes');

            $validatedData = $request->validate(HowToValidate::getAmbienteStoreRules());

            $ambiente = $this->ambienteService->createAmbiente($validatedData);

            return response()->json([
                'message' => 'Ambiente cadastrado com sucesso',
                'ambiente' => $ambiente,
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de cadastro',
                'details' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para cadastrar ambientes.',
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            Gate::authorize('manage-ambientes');

            $this->ambienteService->getAmbienteById($id);

            $validatedData = $request->validate(HowToValidate::getAmbienteUpdateRules());

            $ambiente = $this->ambienteService->updateAmbiente($id, $validatedData);

            return response()->json([
                'message' => 'Ambiente atualizado com sucesso',
                'ambiente' => $ambiente,
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de atualização',
                'details' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para atualizar ambientes.',
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Ambiente não encontrado', Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Gate::authorize('manage-ambientes');

            $this->ambienteService->getAmbienteById($id);
            $this->ambienteService->deleteAmbiente($id);

            return response()->json(['message' => 'Ambiente removido com sucesso'], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para remover ambientes.',
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Ambiente não encontrado', Response::HTTP_NOT_FOUND);
        }
    }

    public function disponibilidade(Request $request, int $id): JsonResponse
    {
        try {
            $this->ambienteService->getAmbienteById($id);

            $mes = $request->query('mes');
            $datasOcupadas = $this->reservaService->getAvailability($id, $mes);

            return response()->json(['datas_ocupadas' => $datasOcupadas], Response::HTTP_OK);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Ambiente não encontrado', Response::HTTP_NOT_FOUND);
        }
    }
}
