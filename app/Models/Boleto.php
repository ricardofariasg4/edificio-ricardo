<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boleto extends Model
{
    /** @use HasFactory<\Database\Factories\BoletoFactory> */
    use HasFactory;

    protected $table = 'BOLETOS';
    protected $primaryKey = 'id_boleto';

    protected $fillable = [
        'status_pagamento',
        'vencimento',
        'valor'
    ];

    public function foiNotificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function pertenceAoMorador(): BelongsTo
    {
        return $this->belongsTo(Morador::class, 'id_morador');
    }
}