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

    public static function getPackageStoreRules(): array
    {
        return [
            'codigo_rastreio' => 'required|string|max:45|unique:ENCOMENDAS,codigo_rastreio',
            'data_recebimento' => 'required|date',
            'id_usuario' => 'required|integer|exists:USUARIOS,id_usuario',
        ];
    }

    public static function getPackageUpdateRules(): array
    {
        return [
            'codigo_rastreio' => 'nullable|string|max:45|unique:ENCOMENDAS,codigo_rastreio',
            'data_recebimento' => 'nullable|date',
            'id_usuario' => 'nullable|integer|exists:USUARIOS,id_usuario',
        ];
    }

    public static function getMoveStoreRules(): array
    {
        return [
            'data' => 'required|date_format:Y-m-d H:i:s|after:today',
            'id_morador' => 'required|integer|exists:MORADORES,id_usuario',
        ];
    }

    public static function getMoveUpdateRules(): array
    {
        return [
            'data' => 'nullable|date_format:Y-m-d H:i:s|after:today',
        ];
    }

    public static function getMoveDecisionRules(): array
    {
        return [
            'decision' => 'required|in:aprovado,recusado',
            'observacao' => 'nullable|string|max:500|required_if:decision,recusado',
        ];
    }

    public static function getDeliveryNotificationRules(): array
    {
        return [
            'id_destinatario' => 'required|integer|exists:USUARIOS,id_usuario',
            'aplicativo' => 'required|string|max:50',
            'observacao' => 'nullable|string|max:255',
        ];
    }

    public static function getMaintenanceNotificationRules(): array
    {
        return [
            'titulo' => 'required|string|max:100',
            'descricao' => 'required|string|max:500',
            'data_agendada' => 'required|date_format:Y-m-d H:i:s|after:today',
        ];
    }
}