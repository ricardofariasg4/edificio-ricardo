<?php

namespace App\Repositories;

use App\Models\Pet;

class PetRepository extends BaseRepository implements PetRepositoryInterface
{
    public function __construct(Pet $model)
    {
        parent::__construct($model);
    }

    public function findByMorador(int $idMorador): array
    {
        return $this->model->where('id_morador', $idMorador)->get()->all();
    }
}