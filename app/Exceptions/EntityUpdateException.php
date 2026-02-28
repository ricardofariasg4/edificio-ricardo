<?php

namespace App\Exceptions;

use Exception;

class EntityUpdateException extends Exception
{
    public function __construct($entityName = '?', $errorMessage = '')
    {
        parent::__construct("Erro ao atualizar o registro da entidade $entityName: $errorMessage", 500);
    }
}
