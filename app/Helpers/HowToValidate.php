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

    public static function getPetStoreRules(): array
    {
        return [
            'nome' => 'nullable|string|max:100',
            'peso' => 'nullable|integer|min:0|max:255',
            'vacinado' => 'required|boolean',
            'cpf' => 'nullable|string|max:11|unique:PETS,cpf',
            'id_morador' => 'required|integer|exists:MORADORES,id_usuario',
        ];
    }

    public static function getPetUpdateRules(int $petId): array
    {
        return [
            'nome' => 'nullable|string|max:100',
            'peso' => 'nullable|integer|min:0|max:255',
            'vacinado' => 'boolean',
            'cpf' => 'nullable|string|max:11|unique:PETS,cpf,' . $petId . ',id_pet',
        ];
    }

    public static function getInvoiceStoreRules(): array
    {
        return [
            'id_morador' => 'required|integer|exists:MORADORES,id_usuario',
            'status_pagamento' => 'required|integer|in:0,1',
            'vencimento' => 'required|date',
            'valor' => 'required|numeric|min:0',
        ];
    }

    public static function getInvoiceUpdateRules(): array
    {
        return [
            'id_morador' => 'nullable|integer|exists:MORADORES,id_usuario',
            'status_pagamento' => 'nullable|integer|in:0,1',
            'vencimento' => 'nullable|date',
            'valor' => 'nullable|numeric|min:0',
        ];
    }
}