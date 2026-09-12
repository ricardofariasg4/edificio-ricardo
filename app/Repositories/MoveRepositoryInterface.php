<?php

namespace App\Repositories;

interface MoveRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca todas as mudanças de um morador específico.
     *
     * @param int $idMorador
     * @return array
     */
    public function findByMorador(int $idMorador): array;

    /**
     * Busca todas as mudanças pendentes de aprovação.
     *
     * @return array
     */
    public function findPending(): array;

    /**
     * Busca todas as mudanças recusadas com observação preenchida.
     *
     * @return array
     */
    public function findRejected(): array;

    /**
     * Busca mudanças recusadas com observação preenchida de um morador específico.
     *
     * @param int $idMorador
     * @return array
     */
    public function findRejectedByMorador(int $idMorador): array;
}
