<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Issue #9: notifica o próximo da fila de espera quando uma reserva
 * confirmada é cancelada e ele é promovido automaticamente.
 */
class ReservationPromotedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reserva $reserva,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $ambiente = $this->reserva->ambiente;

        return [
            'tipo' => 'reserva_promovida',
            'id_reserva' => $this->reserva->id_reserva,
            'id_ambiente' => $this->reserva->id_ambiente,
            'data' => $this->reserva->data,
            'mensagem' => "Sua reserva de {$ambiente->nome} para {$this->reserva->data} foi confirmada — a vaga anterior foi liberada.",
        ];
    }
}
