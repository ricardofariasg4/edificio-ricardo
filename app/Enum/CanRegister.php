<?php 

namespace App\Http\Enum;

enum CanRegister: string
{
    case ADMIN = ['admin'];
    case SINDICO = 'sindico';
    case PORTEIRO = 'porteiro';
}

