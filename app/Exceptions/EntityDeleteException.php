<?php

namespace App\Exceptions;

use Exception;

class EntityDeleteException extends Exception
{
    public function __construct($entityName = '?', $errorMessage = '')
    {
        parent::__construct("Erro ao deletar o registro da entidade $entityName: $errorMessage", 500);
    }
}
