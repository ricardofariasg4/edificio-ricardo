<?php

namespace App\Enum;

enum PeopleBuilding: string
{
    case ADMIN = 'admin';
    case SINDICO = 'sindico';
    case PORTEIRO = 'porteiro';
    case MORADOR = 'morador';
    case VISITANTE = 'visitante';
    case PRESTADOR = 'prestador';
    case PET = 'pet';
}