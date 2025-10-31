<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    /** @use HasFactory<\Database\Factories\PetFactory> */
    use HasFactory;

    protected $table = 'PETS';
    protected $primaryKey = 'id_pet';

    protected $fillable = [
        'nome',
        'peso',
        'vacinado',
        'cpf',
        'id_morador',
    ];
}