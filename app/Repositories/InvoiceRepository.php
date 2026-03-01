<?php

namespace App\Repositories;

use App\Models\Boleto;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Boleto $model)
    {
        parent::__construct($model);
    }

    public function findByMorador(int $idMorador): array
    {
        return $this->model->where('id_morador', $idMorador)->get()->all();
    }
}
