<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boleto extends Model
{
    /** @use HasFactory<\Database\Factories\BoletoFactory> */
    use HasFactory;

    protected $fillable = [
        'status_pagamento',
        'vencimento',
        'valor',
        'id_morador',
        'id_notificador',
    ];

    public function foiNotificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_notificador');
    }

    public function pertenceAoMorador(): BelongsTo
    {
        return $this->belongsTo(Morador::class, 'id_morador', 'usuario_id');
    }
}