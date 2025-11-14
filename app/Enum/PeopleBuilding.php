<?php

namespace App\Enum;

enum PeopleBuilding: string
{
    case MORADOR = 'morador';
    case VISITANTE = 'visitante';
    case PRESTADOR = 'prestador';
    case PET = 'pet';
}