<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityNotFoundException;
use App\Helpers\HowToValidate;
use App\Models\Usuario;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $page = max((int) $request->query('page', 1), 1);
            $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

            $result = $this->notificationService->listForUser(Auth::user(), $page, $perPage);

            return response()->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar notificações', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAsRead(string $id): JsonResponse
    {
        try {
            $this->notificationService->markAsRead(Auth::user(), $id);

            return response()->json(['message' => 'Notificação marcada como lida'], Response::HTTP_OK);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Notificação não encontrada', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * RF03: porteiro registra a chegada de uma entrega de app (ifood, rappi
     * etc) e notifica o morador/visitante destinatário.
     */
    public function notifyDelivery(Request $request): JsonResponse
    {
        try {
            Gate::authorize('send-delivery-notification');

            $validatedData = $request->validate(HowToValidate::getDeliveryNotificationRules());

            $destinatario = Usuario::findOrFail($validatedData['id_destinatario']);

            $this->notificationService->notifyDelivery(
                $destinatario,
                $validatedData['aplicativo'],
                Auth::user(),
                $validatedData['observacao'] ?? null
            );

            return response()->json(['message' => 'Notificação de entrega enviada com sucesso'], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados da notificação',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Apenas porteiros podem enviar notificações de entrega.'
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * RF05: síndico/porteiro agenda uma manutenção predial e notifica todos
     * os moradores.
     */
    public function notifyMaintenance(Request $request): JsonResponse
    {
        try {
            Gate::authorize('send-maintenance-notification');

            $validatedData = $request->validate(HowToValidate::getMaintenanceNotificationRules());

            $this->notificationService->notifyMaintenanceScheduled(
                $validatedData['titulo'],
                $validatedData['descricao'],
                new \DateTimeImmutable($validatedData['data_agendada']),
                Auth::user()
            );

            return response()->json(['message' => 'Notificação de manutenção enviada a todos os moradores'], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados da notificação',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para agendar manutenções.'
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
