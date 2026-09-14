<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * RF03: notifica morador/visitante sobre uma entrega de serviço por
 * aplicativo (ifood, rappi etc), registrada por um porteiro na portaria.
 */
class DeliveryNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $aplicativo,
        private readonly Usuario $registradoPor,
        private readonly ?string $observacao = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'entrega',
            'aplicativo' => $this->aplicativo,
            'observacao' => $this->observacao,
            'registrado_por' => $this->registradoPor->id_usuario,
            'mensagem' => "Você tem uma entrega de {$this->aplicativo} na portaria.",
        ];
    }
}
