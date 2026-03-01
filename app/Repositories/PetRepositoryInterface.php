<?php

namespace App\Repositories;

interface PetRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca todos os pets de um morador específico.
     *
     * @param int $idMorador
     * @return array
     */
    public function findByMorador(int $idMorador): array;
}