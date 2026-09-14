<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitante extends Model
{
    /** @use HasFactory<\Database\Factories\VisitanteFactory> */
    use HasFactory;

    protected $table = 'VISITANTES';
    protected $primaryKey = 'id_usuario';
    public $incrementing = false;

    protected $fillable = [
        'id_usuario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}