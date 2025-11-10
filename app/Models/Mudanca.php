<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mudanca extends Model
{
    /** @use HasFactory<\Database\Factories\MudancaFactory> */
    use HasFactory;

    protected $table = 'MUDANCAS';
    protected $primaryKey = 'id_mudanca';

    protected $fillable = [
        'data',
        'status',
        'id_morador',
        'id_autorizador'
    ];

    // public function foiAutorizadaPor(): BelongsTo
    // {
    //     return $this->belongsTo(Usuario::class, 'id_autorizador');
    // }

    // public function pertenceAoMorador()
    // {
    //     return $this->belongsTo(Morador::class, 'id_morador');
    // }
}