<?php

namespace App\Services;

use App\Repositories\MoveRepositoryInterface;
use App\Models\Mudanca;
use App\Models\Usuario;
use App\Enum\PeopleBuilding;

class MoveService
{
    protected MoveRepositoryInterface $moveRepository;

    public function __construct(MoveRepositoryInterface $moveRepository)
    {
        $this->moveRepository = $moveRepository;
    }

    public function getAllMoves(): array
    {
        return $this->moveRepository->all()->all();
    }

    public function getMoveById(int $id): Mudanca
    {
        return $this->moveRepository->find($id);
    }

    public function getMovesByMorador(int $idMorador): array
    {
        return $this->moveRepository->findByMorador($idMorador);
    }

    public function getPendingMoves(): array
    {
        return $this->moveRepository->findPending();
    }

    public function createMove(array $data): array
    {
        // Define status inicial como pendente (RF07)
        $data['status'] = 'pendente';
        return $this->moveRepository->create($data)->getAttributes();
    }

    public function updateMove(int $id, array $data): array
    {
        return $this->moveRepository->update($id, $data)->getAttributes();
    }

    public function deleteMove(int $id): bool
    {
        return $this->moveRepository->delete($id);
    }

    public function makeDecision(int $id, string $decision, Usuario $autorizador): array
    {
        $move = $this->moveRepository->find($id);
        $newStatus = $move->status;

        if ($decision === 'aprovado') {
            if ($autorizador->tipo_usuario === PeopleBuilding::SINDICO || $autorizador->tipo_usuario === PeopleBuilding::ADMIN) {
                // Síndico aprova definitivamente
                $newStatus = 'aprovado';
            } elseif ($autorizador->tipo_usuario === PeopleBuilding::PORTEIRO) {
                // Porteiro aprova provisoriamente
                $newStatus = 'em_andamento';
            }
        } elseif ($decision === 'recusado') {
            $newStatus = 'recusado';
        }

        $data = [
            'status' => $newStatus,
            'id_autorizador' => $autorizador->id_usuario
        ];

        return $this->moveRepository->update($id, $data)->getAttributes();
    }
}
