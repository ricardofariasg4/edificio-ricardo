<?php

namespace App\Repositories;

interface PackageRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca todas as encomendas de um usuário específico.
     *
     * @param int $idUsuario
     * @return array
     */
    public function findByUsuario(int $idUsuario): array;
}
