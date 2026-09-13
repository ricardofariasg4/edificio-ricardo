<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PackageService;
use App\Helpers\HowToValidate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityCreateException;

class PackageController extends Controller
{
    protected PackageService $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function index(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-packages')) {
                $packages = $this->packageService->getAllPackages();
            } else {
                // Usuário vê apenas suas próprias encomendas
                $user = Auth::user();
                $packages = $this->packageService->getPackagesByUsuario($user->id_usuario);
            }
            return response()->json($packages, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao listar encomendas', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $package = $this->packageService->getPackageById($id);
            Gate::authorize('view-package', $package);
            return response()->json($package, Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar esta encomenda.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Encomenda não encontrada', Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            Gate::authorize('register-package');

            $validatedData = $request->validate(HowToValidate::getPackageStoreRules());

            $package = $this->packageService->createPackage($validatedData);

            return response()->json([
                'message' => 'Encomenda registrada com sucesso',
                'package' => $package
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de registro',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para registrar encomendas.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityCreateException $e) {
            return $this->logCriticalAndRespond($e, 'Erro ao registrar encomenda', Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
             // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            $package = $this->packageService->getPackageById($id);
            Gate::authorize('register-package'); // Reutilizando permissão de funcionários

            $validatedData = $request->validate(HowToValidate::getPackageUpdateRules());

            $package = $this->packageService->updatePackage($id, $validatedData);

            return response()->json([
                'message' => 'Encomenda atualizada com sucesso',
                'package' => $package
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de atualização',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para atualizar esta encomenda.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Encomenda não encontrada', Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
             // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            $package = $this->packageService->getPackageById($id);
            Gate::authorize('register-package'); // Reutilizando permissão de funcionários

            $this->packageService->deletePackage($id);

            return response()->json([
                'message' => 'Encomenda deletada com sucesso'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para deletar esta encomenda.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return $this->logCriticalAndRespond($e, 'Encomenda não encontrada', Response::HTTP_NOT_FOUND);
        }
    }
}
