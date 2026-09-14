<?php

namespace App\Repositories;

interface ReservaRepositoryInterface extends RepositoryInterface
{
    /**
     * Busca reservas ativas (confirmada ou fila_espera) de um ambiente numa data.
     *
     * @param int $idAmbiente
     * @param string $data
     * @return array
     */
    public function findByAmbienteEData(int $idAmbiente, string $data): array;

    /**
     * Busca as datas com reserva confirmada de um ambiente, opcionalmente
     * filtradas por mês (formato "Y-m").
     *
     * @param int $idAmbiente
     * @param string|null $mes
     * @return array
     */
    public function findConfirmedDatesByAmbiente(int $idAmbiente, ?string $mes): array;

    /**
     * Busca todas as reservas de um usuário específico.
     *
     * @param int $idUsuario
     * @return array
     */
    public function findByUsuario(int $idUsuario): array;

    /**
     * Busca o próximo da fila de espera (menor posicao_fila) de um ambiente
     * numa data específica.
     *
     * @param int $idAmbiente
     * @param string $data
     * @return \App\Models\Reserva|null
     */
    public function findProximoDaFila(int $idAmbiente, string $data): ?\App\Models\Reserva;
}
