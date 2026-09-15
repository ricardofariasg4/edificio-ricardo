<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Porteiro extends Model
{
    /** @use HasFactory<\Database\Factories\PorteiroFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'turno_de_trabalho',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
