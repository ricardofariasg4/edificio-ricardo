<?php

namespace App\Services;

use App\Enum\PeopleBuilding;
use App\Exceptions\EntityNotFoundException;
use App\Models\Encomenda;
use App\Models\Mudanca;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Notifications\DeliveryNotification;
use App\Notifications\MaintenanceScheduledNotification;
use App\Notifications\MoveApprovalRequiredNotification;
use App\Notifications\PackageArrivedNotification;
use App\Notifications\ReservationPromotedNotification;
use DateTimeInterface;

class NotificationService
{
    /**
     * RF03: notifica um morador/visitante sobre uma entrega de app,
     * registrada por um porteiro.
     */
    public function notifyDelivery(Usuario $destinatario, string $aplicativo, Usuario $registradoPor, ?string $observacao = null): void
    {
        $destinatario->notify(new DeliveryNotification($aplicativo, $registradoPor, $observacao));
    }

    /**
     * RF05: notifica todos os moradores sobre uma manutenção predial
     * programada.
     */
    public function notifyMaintenanceScheduled(string $titulo, string $descricao, DateTimeInterface $dataAgendada, Usuario $agendadoPor): void
    {
        $moradores = Usuario::where('tipo_usuario', PeopleBuilding::MORADOR->value)->get();

        foreach ($moradores as $morador) {
            $morador->notify(new MaintenanceScheduledNotification($titulo, $descricao, $dataAgendada, $agendadoPor));
        }
    }

    /**
     * RF-Extra-1: notifica síndicos e porteiros quando uma mudança precisa
     * de aprovação.
     */
    public function notifyMoveApprovalRequired(Mudanca $move): void
    {
        $aprovadores = Usuario::whereIn('tipo_usuario', [
            PeopleBuilding::SINDICO->value,
            PeopleBuilding::PORTEIRO->value,
        ])->get();

        foreach ($aprovadores as $aprovador) {
            $aprovador->notify(new MoveApprovalRequiredNotification($move));
        }
    }

    /**
     * RF04: notifica o destinatário sobre a chegada de uma encomenda.
     */
    public function notifyPackageArrived(Encomenda $package): void
    {
        $usuario = Usuario::find($package->id_usuario);
        $usuario?->notify(new PackageArrivedNotification($package));
    }

    /**
     * Issue #9: notifica o usuário que acaba de ser promovido da fila de
     * espera para uma reserva confirmada.
     */
    public function notifyReservationPromoted(Usuario $usuario, Reserva $reserva): void
    {
        $usuario->notify(new ReservationPromotedNotification($reserva));
    }

    /**
     * Lista as notificações do usuário autenticado, mais recentes primeiro,
     * paginadas.
     */
    public function listForUser(Usuario $user, int $page = 1, int $perPage = 20): array
    {
        $paginator = $user->notifications()->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ];
    }

    /**
     * Marca uma notificação específica do usuário como lida.
     */
    public function markAsRead(Usuario $user, string $notificationId): void
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification === null) {
            throw new EntityNotFoundException('Notificação', "id={$notificationId}");
        }

        $notification->markAsRead();
    }
}
