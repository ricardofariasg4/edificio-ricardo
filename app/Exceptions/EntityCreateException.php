<?php

namespace App\Exceptions;

use Exception;

class EntityCreateException extends Exception
{
    public function __construct($entityName = '?', $errorMessage = '')
    {
        parent::__construct("Erro ao criar o registro da entidade $entityName: $errorMessage", 500);
    }
}
