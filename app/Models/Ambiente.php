<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ambiente extends Model
{
    /** @use HasFactory<\Database\Factories\AmbienteFactory> */
    use HasFactory;

    protected $table = 'AMBIENTES';
    protected $primaryKey = 'id_ambiente';

    protected $fillable = [
        'nome',
        'descricao',
        'capacidade',
    ];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_ambiente');
    }
}
