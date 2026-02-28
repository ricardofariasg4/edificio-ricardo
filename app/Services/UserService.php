<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use App\Exceptions\EntityDeleteException;

class UserService
{
    protected UserRepositoryInterface $userRepository;
    const UNPAID_INVOICE_STATUS = 0;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function listAllUsers(): array
    {
        return $this->userRepository->all()->all();
    }

    public function listUserById(int $id)
    {
        return $this->userRepository->find($id)->getAttributes();
    }

    public function createNewUser(array $data)
    {
        return $this->userRepository->create($data);
    }

    public function updateUserById(array $data, int $id)
    {
        return $this->userRepository->update($id, $data);
    }

    public function deleteUserById(int $id)
    {
        // Before deleting the user, verify that the invoices associated with them are paid
        $user = $this->userRepository->find($id);

        if ($user->tipo_usuario === 'morador') {
            $resident = $user->morador()->first();
            $outstandingInvoices = $resident->boletos()->where('status_pagamento', self::UNPAID_INVOICE_STATUS)->exists();
            
            if ($outstandingInvoices) {
                throw new EntityDeleteException('Usuário', 'O morador não pode ser deletado pois possuí boletos pendentes.');
            }

            $resident->boletos()->delete();
        }
        
        return $this->userRepository->delete($id);
    }
}