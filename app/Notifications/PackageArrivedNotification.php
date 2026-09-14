<?php

namespace App\Notifications;

use App\Models\Encomenda;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * RF04: notifica o morador/destinatário sobre o recebimento de uma
 * encomenda (Correios, Mercado Livre, transportadoras).
 */
class PackageArrivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Encomenda $package,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'encomenda',
            'id_encomenda' => $this->package->id_encomenda,
            'codigo_rastreio' => $this->package->codigo_rastreio,
            'mensagem' => "Sua encomenda (código {$this->package->codigo_rastreio}) chegou na portaria.",
        ];
    }
}
