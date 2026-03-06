<?php

namespace App\Repositories;

use App\Models\Encomenda;

class PackageRepository extends BaseRepository implements PackageRepositoryInterface
{
    public function __construct(Encomenda $model)
    {
        parent::__construct($model);
    }

    public function findByUsuario(int $idUsuario): array
    {
        return $this->model->where('id_usuario', $idUsuario)->get()->all();
    }
}
