<?php

namespace App\Repositories;

use App\Models\Mudanca;

class MoveRepository extends BaseRepository implements MoveRepositoryInterface
{
    public function __construct(Mudanca $model)
    {
        parent::__construct($model);
    }

    public function findByMorador(int $idMorador): array
    {
        return $this->model->where('id_morador', $idMorador)->get()->all();
    }

    public function findPending(): array
    {
        return $this->model->where('status', 'pendente')->get()->all();
    }
}
