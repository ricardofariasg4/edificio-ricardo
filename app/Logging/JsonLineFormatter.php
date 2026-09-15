<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

/**
 * "Tap" do canal de log `critical`: troca o formatter padrão do Monolog
 * (texto) por um formatter que grava cada registro como uma linha JSON,
 * permitindo que LogService leia o arquivo de volta sem parsing frágil.
 *
 * Recebe o wrapper `Illuminate\Log\Logger`, não o `Monolog\Logger` cru — é
 * assim que o LogManager invoca as classes listadas em `tap`.
 */
class JsonLineFormatter
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
