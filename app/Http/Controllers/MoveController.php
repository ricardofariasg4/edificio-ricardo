<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MoveService;
use App\Helpers\HowToValidate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityCreateException;

class MoveController extends Controller
{
    protected MoveService $moveService;

    public function __construct(MoveService $moveService)
    {
        $this->moveService = $moveService;
    }

    public function index(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-moves')) {
                $moves = $this->moveService->getAllMoves();
            } else {
                // Morador vê apenas suas próprias mudanças
                $user = Auth::user();
                $moves = $this->moveService->getMovesByMorador($user->id);
            }
            return response()->json($moves, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar mudanças', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $move = $this->moveService->getMoveById($id);
            Gate::authorize('view-move', $move);
            return response()->json($move, Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar esta mudança.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Mudança não encontrada', Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate(HowToValidate::getMoveStoreRules());
            
            Gate::authorize('register-move', $validatedData['id_morador']);

            $move = $this->moveService->createMove($validatedData);

            return response()->json([
                'message' => 'Mudança agendada com sucesso (Pendente de Aprovação)',
                'move' => $move
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de agendamento',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para agendar esta mudança.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityCreateException $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao agendar mudança', Response::HTTP_BAD_REQUEST);
        }
    }

    public function listPendingMoves(): JsonResponse
    {
        try {
            Gate::authorize('approve-move');
            $pendingMoves = $this->moveService->getPendingMoves();
            return response()->json($pendingMoves, Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar mudanças pendentes.'
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function listRejectedMoves(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-moves')) {
                $rejectedMoves = $this->moveService->getRejectedMoves();
            } else {
                $user = Auth::user();
                $rejectedMoves = $this->moveService->getRejectedMovesByMorador($user->id);
            }

            return response()->json($rejectedMoves, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar mudanças recusadas', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function makeDecision(Request $request, int $id): JsonResponse
    {
        try {
            Gate::authorize('approve-move');
            
            $validatedData = $request->validate(HowToValidate::getMoveDecisionRules());

            $observacao = $validatedData['observacao'] ?? null;
            if ($validatedData['decision'] === 'recusado' && trim((string) $observacao) === '') {
                throw ValidationException::withMessages([
                    'observacao' => ['O campo observacao é obrigatório quando a decisão for recusado.']
                ]);
            }
            
            $updatedMove = $this->moveService->makeDecision(
                $id,
                $validatedData['decision'],
                Auth::user(),
                $observacao
            );

            return response()->json([
                'message' => 'Decisão registrada com sucesso',
                'move' => $updatedMove
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Dados de decisão inválidos',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para aprovar/recusar mudanças.'
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json(['message' => 'Atualização direta não implementada. Use o fluxo de decisão.'], Response::HTTP_NOT_IMPLEMENTED);
    }

    public function destroy(int $id): JsonResponse
    {
         return response()->json(['message' => 'Exclusão não implementada.'], Response::HTTP_NOT_IMPLEMENTED);
    }
}
