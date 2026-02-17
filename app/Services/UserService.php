<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use \Illuminate\Database\Eloquent\Collection;

class UserService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function listAllUsers(): Collection | array
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
        // Antes de deletar o usuário, verificar se os boletos associados a ele estão pagos
        $user = $this->userRepository->find($id);
         
        if (isset($user['error'])) {
            return $user; // Retorna o erro encontrado ao buscar o usuário
        }

        if ($user->tipo_usuario === 'morador') {
            $morador = $user->morador()->first();
            $boletosPendentes = $morador->boletos()->where('status_pagamento', 0)->exists();
            
            if ($boletosPendentes) {
                return [
                    'error' => 'O morador não pode ser deletado porque possui boletos pendentes.'
                ];
            }
        }
        
        return $this->userRepository->delete($id);
    }
}