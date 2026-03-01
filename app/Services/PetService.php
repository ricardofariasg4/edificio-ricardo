<?php

namespace App\Services;

use App\Repositories\PetRepositoryInterface;
use App\Exceptions\EntityCreateException;
use App\Models\Pet;

class PetService
{
    protected PetRepositoryInterface $petRepository;

    public function __construct(PetRepositoryInterface $petRepository)
    {
        $this->petRepository = $petRepository;
    }

    public function createPet(array $data): array
    {
        // Mandatory vaccination validation
        if (!isset($data['vacinado']) || $data['vacinado'] !== true) {
            throw new EntityCreateException('Pet', 'O pet deve estar vacinado para ser cadastrado.');
        }

        return $this->petRepository->create($data)->getAttributes();
    }

    public function getAllPets(): array
    {
        return $this->petRepository->all()->all();
    }

    public function getPetById(int $id): Pet
    {
        return $this->petRepository->find($id);
    }

    public function getPetsByMorador(int $idMorador): array
    {
        return $this->petRepository->findByMorador($idMorador);
    }

    public function updatePet(int $id, array $data): array
    {
        // If trying to update 'vacinado' to false, do not allow it
        if (isset($data['vacinado']) && $data['vacinado'] === false) {
            throw new EntityCreateException('Pet', 'Não é permitido remover o status de vacinação do pet.');
        }

        return $this->petRepository->update($id, $data)->getAttributes();
    }

    public function deletePet(int $id): bool
    {
        return $this->petRepository->delete($id);
    }
}
