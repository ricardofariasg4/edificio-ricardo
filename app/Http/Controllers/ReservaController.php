<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityCreateException;
use App\Exceptions\EntityNotFoundException;
use App\Helpers\HowToValidate;
use App\Services\ReservaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReservaController extends Controller
{
    protected ReservaService $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    public function index(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-reservas')) {
                $reservas = $this->reservaService->getAllReservas();
            } else {
                $user = Auth::user();
                $reservas = $this->reservaService->getReservasByUsuario($user->id_usuario);
            }
            return response()->json($reservas, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar reservas', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate(HowToValidate::getReservaStoreRules());

            $reserva = $this->reservaService->createReservation(
                $validatedData['id_ambiente'],
                Auth::id(),
                $validatedData['data']
            );

            return response()->json([
                'message' => $reserva['status'] === 'confirmada'
                    ? 'Reserva confirmada com sucesso'
                    : 'Ambiente já reservado nesta data — você entrou na fila de espera',
                'reserva' => $reserva,
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados da reserva',
                'details' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (EntityCreateException $e) {
            return $this->logCriticalAndRespond($e, 'Você já tem uma solicitação ativa para este ambiente nesta data', Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $reserva = $this->reservaService->getReservaById($id);
            Gate::authorize('cancel-reserva', $reserva);

            $reservaCancelada = $this->reservaService->cancelReservation($id);

            return response()->json([
                'message' => 'Reserva cancelada com sucesso',
                'reserva' => $reservaCancelada,
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para cancelar esta reserva.',
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Reserva não encontrada', Response::HTTP_NOT_FOUND);
        }
    }
}
