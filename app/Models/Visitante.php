<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitante extends Model
{
    /** @use HasFactory<\Database\Factories\VisitanteFactory> */
    use HasFactory;

    protected $table = 'VISITANTES';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_usuario',
    ];
}