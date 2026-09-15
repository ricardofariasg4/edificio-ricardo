<?php

namespace App\Services;

use App\Models\Ambiente;
use App\Repositories\AmbienteRepositoryInterface;

class AmbienteService
{
    protected AmbienteRepositoryInterface $ambienteRepository;

    public function __construct(AmbienteRepositoryInterface $ambienteRepository)
    {
        $this->ambienteRepository = $ambienteRepository;
    }

    public function getAllAmbientes(): array
    {
        return $this->ambienteRepository->all()->all();
    }

    public function getAmbienteById(int $id): Ambiente
    {
        return $this->ambienteRepository->find($id);
    }

    public function createAmbiente(array $data): array
    {
        return $this->ambienteRepository->create($data)->getAttributes();
    }

    public function updateAmbiente(int $id, array $data): array
    {
        return $this->ambienteRepository->update($id, $data)->getAttributes();
    }

    public function deleteAmbiente(int $id): bool
    {
        return $this->ambienteRepository->delete($id);
    }
}
