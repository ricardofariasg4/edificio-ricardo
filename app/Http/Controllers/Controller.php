<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class Controller
{
    /**
     * Registra o detalhe técnico de uma exceção no canal `critical` (issue #7)
     * e devolve ao cliente apenas a mensagem de negócio já curada por quem
     * chamou, sem vazar a mensagem crua da exceção, classe interna ou stack.
     */
    protected function logCriticalAndRespond(Throwable $e, string $error, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        Log::channel('critical')->error($error, [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json(['error' => $error], $status);
    }
}
