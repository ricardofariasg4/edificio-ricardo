<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Morador extends Model
{
    /** @use HasFactory<\Database\Factories\MoradorFactory> */
    use HasFactory;

    // Eloquent pluraliza "morador" para "moradors" (regras em inglês), por isso
    // é necessário declarar a tabela explicitamente.
    protected $table = 'moradores';

    protected $fillable = [
        'usuario_id',
        'numero_apto',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class, 'id_morador', 'usuario_id');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'id_morador', 'usuario_id');
    }
}
