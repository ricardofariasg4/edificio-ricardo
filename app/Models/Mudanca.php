<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mudanca extends Model
{
    /** @use HasFactory<\Database\Factories\MudancaFactory> */
    use HasFactory;

    protected $table = 'mudancas';
    protected $primaryKey = 'id_mudanca';

    protected $fillable = [
        'data',
        'status',
        'id_morador',
        'id_autorizador'
    ];
}