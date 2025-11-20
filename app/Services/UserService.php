<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use \Illuminate\Database\Eloquent\Collection;

class UserService 
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function listAllUsers(): Collection
    {
        return $this->userRepository->all();
    }

    public function listUserById(int $id): mixed
    {
        return $this->userRepository->find($id);
    }

    public function createNewUser(array $data)
    {
        return $this->userRepository->create($data);
    }

    public function updateUserById(array $data, int $id): mixed
    {
        return $this->userRepository->update($id, $data);
    }

    public function deleteUserById(int $id): mixed
    {
        return $this->userRepository->delete($id);
    }
}