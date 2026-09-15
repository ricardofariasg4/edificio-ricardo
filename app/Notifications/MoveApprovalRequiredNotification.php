<?php

namespace App\Notifications;

use App\Models\Mudanca;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * RF-Extra-1: notifica síndicos e porteiros quando uma nova mudança é
 * agendada e precisa de aprovação (fluxo de decisão de MoveService).
 */
class MoveApprovalRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Mudanca $move,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'mudanca_pendente',
            'id_mudanca' => $this->move->id,
            'id_morador' => $this->move->id_morador,
            'data' => $this->move->data,
            'mensagem' => 'Uma nova mudança foi agendada e aguarda sua aprovação.',
        ];
    }
}
