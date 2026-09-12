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

    public function getRejectedMoves(): array
    {
        return $this->moveRepository->findRejected();
    }

    public function getRejectedMovesByMorador(int $idMorador): array
    {
        return $this->moveRepository->findRejectedByMorador($idMorador);
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

    /**
     * Aprova ou recusa automaticamente mudanças que continuam sem decisão
     * definitiva do síndico/admin a menos de 24h do acontecimento:
     * - 'em_andamento' (já com aprovação provisória do porteiro) vira 'aprovado';
     * - 'pendente' (sem nenhuma aprovação) vira 'recusado', com observação padrão.
     *
     * @return array Lista das mudanças decididas automaticamente.
     */
    public function autoDecidePendingMoves(): array
    {
        $threshold = now()->addHours(24);
        $dueMoves = $this->moveRepository->findDueForAutoDecision($threshold);

        $decided = [];
        foreach ($dueMoves as $move) {
            $data = $move->status === 'em_andamento'
                ? ['status' => 'aprovado', 'observacao' => null]
                : ['status' => 'recusado', 'observacao' => 'Ausência de aprovação'];

            $decided[] = $this->moveRepository->update($move->id_mudanca, $data)->getAttributes();
        }

        return $decided;
    }

    public function makeDecision(int $id, string $decision, Usuario $autorizador, ?string $observacao = null): array
    {
        $move = $this->moveRepository->find($id);
        $newStatus = $move->status;
        $normalizedObservation = $observacao !== null ? trim($observacao) : null;

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
            'id_autorizador' => $autorizador->id_usuario,
            'observacao' => $decision === 'recusado' ? $normalizedObservation : null,
        ];

        return $this->moveRepository->update($id, $data)->getAttributes();
    }
}
