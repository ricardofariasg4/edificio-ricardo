<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boleto extends Model
{
    /** @use HasFactory<\Database\Factories\BoletoFactory> */
    use HasFactory;

    protected $table = 'BOLETOS';
    protected $primaryKey = 'id_boleto';

    protected $fillable = [
        'status_pagamento',
        'vencimento',
        'valor'
    ];
}