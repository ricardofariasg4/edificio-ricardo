<?php

use App\Exceptions\EntityCreateException;
use App\Exceptions\EntityDeleteException;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityUpdateException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Centraliza o registro de erros "críticos" no canal `critical`
        // (issue #7), consultável via GET /logs. Erros de validação e de
        // autorização são esperados/rotineiros, não "críticos" — não são
        // registrados aqui.
        $exceptions->reportable(function (Throwable $e) {
            if ($e instanceof ValidationException || $e instanceof AuthorizationException) {
                return;
            }

            Log::channel('critical')->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });

        // Rede de segurança: qualquer exceção que escape sem ser tratada por
        // um controller (portanto não coberta por Controller::logCriticalAndRespond)
        // nunca deve devolver mensagem/stack trace crus ao cliente numa
        // requisição JSON, mesmo com APP_DEBUG=true — o detalhe real já foi
        // registrado acima. Exceções HTTP "normais" (404 de rota, 405 etc.) e
        // as já tratadas de forma segura pelo Laravel (validação/autorização)
        // seguem o comportamento padrão.
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException
                || $e instanceof AuthorizationException
                || $e instanceof HttpExceptionInterface
            ) {
                return null;
            }

            $status = match (true) {
                $e instanceof EntityNotFoundException => 404,
                $e instanceof EntityCreateException,
                    $e instanceof EntityUpdateException,
                    $e instanceof EntityDeleteException => 400,
                default => 500,
            };

            return response()->json(['error' => 'Erro interno do servidor.'], $status);
        });
    })->create();
