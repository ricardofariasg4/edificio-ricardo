<?php

namespace App\Repositories;

use App\Models\Reserva;

class ReservaRepository extends BaseRepository implements ReservaRepositoryInterface
{
    public function __construct(Reserva $model)
    {
        parent::__construct($model);
    }

    public function findByAmbienteEData(int $idAmbiente, string $data): array
    {
        return $this->model
            ->where('id_ambiente', $idAmbiente)
            ->where('data', $data)
            ->whereIn('status', ['confirmada', 'fila_espera'])
            ->get()
            ->all();
    }

    public function findConfirmedDatesByAmbiente(int $idAmbiente, ?string $mes): array
    {
        $query = $this->model
            ->where('id_ambiente', $idAmbiente)
            ->where('status', 'confirmada');

        if ($mes !== null) {
            $query->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$mes]);
        }

        return $query->pluck('data')->all();
    }

    public function findByUsuario(int $idUsuario): array
    {
        return $this->model->where('id_usuario', $idUsuario)->get()->all();
    }

    public function findProximoDaFila(int $idAmbiente, string $data): ?Reserva
    {
        return $this->model
            ->where('id_ambiente', $idAmbiente)
            ->where('data', $data)
            ->where('status', 'fila_espera')
            ->orderBy('posicao_fila')
            ->first();
    }
}
