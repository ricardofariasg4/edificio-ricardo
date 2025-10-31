<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    /** @use HasFactory<\Database\Factories\NotificacaoFactory> */
    use HasFactory;
    
    protected $table = 'NOTIFICACOES';
    protected $primaryKey = 'id_notificacao';

    protected $fillable = [
        'titulo',
        'mensagem',
        'icone',
        'imagem',
        'data_envio',
        'id_remetente',
        'id_destinatario',
    ];
}