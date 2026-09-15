<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestadorDeServico extends Model
{
    /** @use HasFactory<\Database\Factories\PrestadorDeServicoFactory> */
    use HasFactory;

    // Eloquent pluraliza "prestador_de_servico" para "prestador_de_servicos"
    // (pluraliza só a última palavra), por isso é necessário declarar a tabela
    // explicitamente.
    protected $table = 'prestadores_de_servico';

    protected $fillable = [
        'usuario_id',
        'data_ultimo_trabalho',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
