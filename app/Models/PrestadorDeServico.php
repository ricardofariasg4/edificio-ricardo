<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestadorDeServico extends Model
{
    /** @use HasFactory<\Database\Factories\PrestadorDeServicoFactory> */
    use HasFactory;

    // Schema::create('PRESTADORES_DE_SERVICO', function (Blueprint $table) {
    //     $table->unsignedInteger('id_usuario')->index('idx_prestador_usuario_idusuario');
    //     $table->dateTime('data_ultimo_trabalho')->nullable();
    //     $table->timestamps();
    //     $table->primary(['id_usuario']);
    // });

    protected $table = 'PRESTADORES_DE_SERVICO';
    protected $primaryKey = 'id_usuario';
    public $incrementing = false;

    protected $fillable = [
        'id_usuario',
        'data_ultimo_trabalho',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}