<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * RF05: notifica moradores sobre uma manutenção predial programada
 * (ex.: elevador, caixa d'água, bombas), agendada por síndico/porteiro.
 */
class MaintenanceScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $titulo,
        private readonly string $descricao,
        private readonly \DateTimeInterface $dataAgendada,
        private readonly Usuario $agendadoPor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'manutencao',
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'data_agendada' => $this->dataAgendada->format('Y-m-d H:i:s'),
            'agendado_por' => $this->agendadoPor->id_usuario,
            'mensagem' => "Manutenção programada: {$this->titulo}.",
        ];
    }
}
