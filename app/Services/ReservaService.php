<?php

namespace App\Services;

use App\Exceptions\EntityCreateException;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\ReservaRepositoryInterface;

class ReservaService
{
    protected ReservaRepositoryInterface $reservaRepository;
    protected NotificationService $notificationService;

    public function __construct(ReservaRepositoryInterface $reservaRepository, NotificationService $notificationService)
    {
        $this->reservaRepository = $reservaRepository;
        $this->notificationService = $notificationService;
    }

    public function getReservaById(int $id): Reserva
    {
        return $this->reservaRepository->find($id);
    }

    public function getAllReservas(): array
    {
        return $this->reservaRepository->all()->all();
    }

    public function getReservasByUsuario(int $idUsuario): array
    {
        return $this->reservaRepository->findByUsuario($idUsuario);
    }

    /**
     * Datas com reserva confirmada de um ambiente, opcionalmente filtradas
     * por mês ("Y-m"), para o cliente calcular as datas livres por exclusão.
     */
    public function getAvailability(int $idAmbiente, ?string $mes): array
    {
        return $this->reservaRepository->findConfirmedDatesByAmbiente($idAmbiente, $mes);
    }

    /**
     * Solicita uma reserva. Se não houver reserva confirmada para o
     * ambiente+data, confirma na hora; caso contrário, entra na fila de
     * espera. Um mesmo usuário não pode ter duas solicitações ativas
     * (confirmada ou na fila) para o mesmo ambiente+data.
     */
    public function createReservation(int $idAmbiente, int $idUsuario, string $data): array
    {
        $existentes = $this->reservaRepository->findByAmbienteEData($idAmbiente, $data);

        $jaSolicitou = collect($existentes)->contains(fn (Reserva $reserva) => $reserva->id_usuario === $idUsuario);
        if ($jaSolicitou) {
            throw new EntityCreateException('Reserva', 'Você já tem uma solicitação ativa para este ambiente nesta data.');
        }

        $confirmada = collect($existentes)->first(fn (Reserva $reserva) => $reserva->status === 'confirmada');

        if ($confirmada === null) {
            return $this->reservaRepository->create([
                'id_ambiente' => $idAmbiente,
                'id_usuario' => $idUsuario,
                'data' => $data,
                'status' => 'confirmada',
            ])->getAttributes();
        }

        $posicaoFila = collect($existentes)->where('status', 'fila_espera')->count() + 1;

        return $this->reservaRepository->create([
            'id_ambiente' => $idAmbiente,
            'id_usuario' => $idUsuario,
            'data' => $data,
            'status' => 'fila_espera',
            'posicao_fila' => $posicaoFila,
        ])->getAttributes();
    }

    /**
     * Cancela uma reserva. Se ela estava confirmada, promove o próximo da
     * fila de espera (se houver) e o notifica.
     */
    public function cancelReservation(int $idReserva): array
    {
        $reserva = $this->reservaRepository->find($idReserva);
        $statusAnterior = $reserva->status;

        $reserva = $this->reservaRepository->update($idReserva, ['status' => 'cancelada']);

        if ($statusAnterior === 'confirmada') {
            $proximo = $this->reservaRepository->findProximoDaFila($reserva->id_ambiente, $reserva->data);

            if ($proximo !== null) {
                $proximo = $this->reservaRepository->update($proximo->id_reserva, [
                    'status' => 'confirmada',
                    'posicao_fila' => null,
                ]);

                $usuario = Usuario::find($proximo->id_usuario);
                if ($usuario !== null) {
                    $this->notificationService->notifyReservationPromoted($usuario, $proximo);
                }
            }
        }

        return $reserva->getAttributes();
    }
}
