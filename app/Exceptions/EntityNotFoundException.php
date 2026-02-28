<?php

namespace App\Exceptions;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct($entityName = '?', $errorMessage = '')
    {
        parent::__construct("O registro da entidade $entityName não foi encontrado: $errorMessage", 404);
    }
}