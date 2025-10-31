<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Encomenda extends Model
{
    /** @use HasFactory<\Database\Factories\EncomendaFactory> */
    use HasFactory;

    protected $table = 'ENCOMENDAS';
    protected $primaryKey = 'id_encomenda';

    protected $fillable = [
        'codigo_rastreio',
        'data_recebimento'
    ];
}