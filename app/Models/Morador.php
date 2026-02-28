<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Morador extends Model
{
    /** @use HasFactory<\Database\Factories\MoradorFactory> */
    use HasFactory;
    
    protected $table = 'MORADORES';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_usuario',
        'numero_apto',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class, 'id_morador');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'id_morador');
    }
}