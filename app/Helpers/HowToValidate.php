<?php

namespace App\Helpers;

class HowToValidate
{
    public static function getRuleByField(string $field): string
    {
        $rules = [
            'nome' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:USUARIOS,email',
            'senha' => 'required|string|min:8',
            'cpf' => 'required|string|max:14|unique:USUARIOS,cpf',
            'idade' => 'required|integer|min:12',
            'tipo_usuario' => 'required|string|in:sindico,porteiro,morador,prestador,visitante'
        ];

        return $rules[$field] ?? '';
    }
}