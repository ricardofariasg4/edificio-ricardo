<?php

namespace App\Services;

use App\Repositories\PackageRepositoryInterface;
use App\Models\Encomenda;

class PackageService
{
    protected PackageRepositoryInterface $packageRepository;
    protected NotificationService $notificationService;

    public function __construct(PackageRepositoryInterface $packageRepository, NotificationService $notificationService)
    {
        $this->packageRepository = $packageRepository;
        $this->notificationService = $notificationService;
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
        $package = $this->packageRepository->create($data);

        $this->notificationService->notifyPackageArrived($package);

        return $package->getAttributes();
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
