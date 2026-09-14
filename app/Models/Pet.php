<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    /** @use HasFactory<\Database\Factories\PetFactory> */
    use HasFactory;

    protected $fillable = [
        'nome',
        'peso',
        'vacinado',
        'cpf',
        'id_morador',
    ];

    public function morador(): BelongsTo
    {
        return $this->belongsTo(Morador::class, 'id_morador', 'usuario_id');
    }
}