<?php

namespace App\Services;

use App\Repositories\PetRepositoryInterface;

class PetService
{
    protected $petRepository;

    public function __construct(PetRepositoryInterface $petRepository)
    {
        $this->petRepository = $petRepository;
    }

    public function createPet(array $data)
    {
        return $this->petRepository->create($data);
    }

    public function getAllPets()
    {
        return $this->petRepository->all();
    }

    public function getPetById($id)
    {
        return $this->petRepository->find($id);
    }

    public function updatePet($id, array $data)
    {
        return $this->petRepository->update($id, $data);
    }

    public function deletePet($id)
    {
        return $this->petRepository->delete($id);
    }
}
