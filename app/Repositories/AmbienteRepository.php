<?php

namespace App\Repositories;

use App\Models\Ambiente;

class AmbienteRepository extends BaseRepository implements AmbienteRepositoryInterface
{
    public function __construct(Ambiente $model)
    {
        parent::__construct($model);
    }
}
