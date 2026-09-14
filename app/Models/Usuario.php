<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enum\PeopleBuilding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UsuarioFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cpf',
        'idade',
        'tipo_usuario',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'senha',
        'remember_token',
        'idade'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'senha' => 'hashed',
            'tipo_usuario' => PeopleBuilding::class,
        ];
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function sindico()
    {
        return $this->hasOne(Sindico::class, 'usuario_id');
    }

    public function porteiro()
    {
        return $this->hasOne(Porteiro::class, 'usuario_id');
    }

    public function morador()
    {
        return $this->hasOne(Morador::class, 'usuario_id');
    }

    public function prestadorDeServico()
    {
        return $this->hasOne(PrestadorDeServico::class, 'usuario_id');
    }

    public function visitante()
    {
        return $this->hasOne(Visitante::class, 'usuario_id');
    }

    public function encomenda()
    {
        return $this->hasMany(Encomenda::class, 'id_usuario');
    }

    // public function mudancasAutorizadas()
    // {
    //     return $this->hasMany(Mudanca::class, 'id_autorizador');
    // }

    public function boletosNotificados()
    {
        return $this->hasMany(Boleto::class, 'id_notificador');
    }
}