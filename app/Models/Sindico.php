<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sindico extends Model
{
    /** @use HasFactory<\Database\Factories\SindicoFactory> */
    use HasFactory;

    protected $table = 'SINDICOS';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}