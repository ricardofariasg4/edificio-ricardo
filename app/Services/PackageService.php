<?php

namespace App\Services;

use App\Repositories\PackageRepositoryInterface;
use App\Models\Encomenda;

class PackageService
{
    protected PackageRepositoryInterface $packageRepository;

    public function __construct(PackageRepositoryInterface $packageRepository)
    {
        $this->packageRepository = $packageRepository;
    }

    public function getAllPackages(): array
    {
        return $this->packageRepository->all()->all();
    }

    public function getPackageById(int $id): Encomenda
    {
        return $this->packageRepository->find($id);
    }

    public function getPackagesByUsuario(int $idUsuario): array
    {
        return $this->packageRepository->findByUsuario($idUsuario);
    }

    public function createPackage(array $data): array
    {
        $package = $this->packageRepository->create($data)->getAttributes();
        
        // TODO: Implementar lógica de notificação aqui (RF04)
        // NotificationService::sendPackageArrivalNotification($package['id_usuario']);

        return $package;
    }

    public function updatePackage(int $id, array $data): array
    {
        return $this->packageRepository->update($id, $data)->getAttributes();
    }

    public function deletePackage(int $id): bool
    {
        return $this->packageRepository->delete($id);
    }
}
