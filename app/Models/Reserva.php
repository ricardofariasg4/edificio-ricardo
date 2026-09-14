<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    /** @use HasFactory<\Database\Factories\ReservaFactory> */
    use HasFactory;

    protected $table = 'RESERVAS';
    protected $primaryKey = 'id_reserva';

    protected $fillable = [
        'id_ambiente',
        'id_usuario',
        'data',
        'status',
        'posicao_fila',
    ];

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
