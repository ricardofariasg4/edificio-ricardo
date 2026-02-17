<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use \Illuminate\Database\Eloquent\Collection;

class UserService
{
    protected UserRepositoryInterface $userRepository;
    const UNPAID_INVOICE_STATUS = 0;

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
        // Before deleting the user, verify that the invoices associated with them are paid
        $user = $this->userRepository->find($id);
         
        if (isset($user['error'])) {
            return $user; // return the error response if the user is not found
        }

        if ($user->tipo_usuario === 'morador') {
            $resident = $user->morador()->first();
            $outstandingInvoices = $resident->boletos()->where('status_pagamento', self::UNPAID_INVOICE_STATUS)->exists();
            
            if ($outstandingInvoices) {
                return [
                    'message' => 'O morador não pode ser deletado pois possuí boletos pendentes.'
                ];
            }

            $resident->boletos()->delete();
        }
        
        return $this->userRepository->delete($id);
    }
}