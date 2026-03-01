<?php

namespace App\Repositories;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca todos os boletos de um morador específico.
     *
     * @param int $idMorador
     * @return array
     */
    public function findByMorador(int $idMorador): array;
}
