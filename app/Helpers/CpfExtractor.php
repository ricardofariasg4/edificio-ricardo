<?php

namespace App\Helpers;

class CpfExtractor
{
    /**
     * Extrai apenas os números de um CPF formatado.
     *
     * @param string $cpf O CPF formatado (ex: 123.456.789-00)
     * @return string O CPF contendo apenas números (ex: 12345678900)
    */
    public static function extractNumbers($cpf)
    {
        return preg_replace('/\D/', '', $cpf);
    }
}