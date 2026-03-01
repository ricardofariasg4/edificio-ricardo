<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Helpers\HowToValidate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityCreateException;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(): JsonResponse
    {
        try {
            if (Gate::allows('view-all-invoices')) {
                $invoices = $this->invoiceService->getAllInvoices();
            } else {
                // Morador vê apenas seus próprios boletos
                $user = Auth::user();
                $invoices = $this->invoiceService->getInvoicesByMorador($user->id_usuario);
            }
            return response()->json($invoices, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao listar boletos',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->getInvoiceById($id);
            Gate::authorize('view-invoice', $invoice);
            return response()->json($invoice, Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para visualizar este boleto.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return response()->json([
                'error' => 'Boleto não encontrado',
                'details' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            Gate::authorize('register-invoice');

            $validatedData = $request->validate(HowToValidate::getInvoiceStoreRules());

            $invoice = $this->invoiceService->createInvoice($validatedData);

            return response()->json([
                'message' => 'Boleto criado com sucesso',
                'invoice' => $invoice
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de criação',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para criar boletos.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityCreateException $e) {
            return response()->json([
                'error' => 'Erro ao criar boleto',
                'details' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
             // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            Gate::authorize('update-invoice');

            $validatedData = $request->validate(HowToValidate::getInvoiceUpdateRules());

            $invoice = $this->invoiceService->updateInvoice($id, $validatedData);

            return response()->json([
                'message' => 'Boleto atualizado com sucesso',
                'invoice' => $invoice
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Não foi possível validar os dados de atualização',
                'details' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para atualizar este boleto.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return response()->json([
                'error' => 'Boleto não encontrado',
                'details' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
             // Nota: Middleware EnsureRegistrationByAuthorized já protegeu esta rota
            Gate::authorize('delete-invoice');

            $this->invoiceService->deleteInvoice($id);

            return response()->json([
                'message' => 'Boleto deletado com sucesso'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'error' => 'Acesso negado',
                'details' => 'Você não tem permissão para deletar este boleto.'
            ], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException $e) {
            return response()->json([
                'error' => 'Boleto não encontrado',
                'details' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
