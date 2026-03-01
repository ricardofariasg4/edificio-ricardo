<?php

namespace App\Services;

use App\Repositories\InvoiceRepositoryInterface;
use App\Models\Boleto;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    protected InvoiceRepositoryInterface $invoiceRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function getAllInvoices(): array
    {
        return $this->invoiceRepository->all()->all();
    }

    public function getInvoiceById(int $id): Boleto
    {
        return $this->invoiceRepository->find($id);
    }

    public function getInvoicesByMorador(int $idMorador): array
    {
        return $this->invoiceRepository->findByMorador($idMorador);
    }

    public function createInvoice(array $data): array
    {
        // Preenche automaticamente o notificador com o usuário logado
        $data['id_notificador'] = Auth::id();
        
        return $this->invoiceRepository->create($data)->getAttributes();
    }

    public function updateInvoice(int $id, array $data): array
    {
        return $this->invoiceRepository->update($id, $data)->getAttributes();
    }

    public function deleteInvoice(int $id): bool
    {
        return $this->invoiceRepository->delete($id);
    }
}
