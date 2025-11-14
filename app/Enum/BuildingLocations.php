<?php

namespace App\Enum;

enum BuildingLocations: string
{
    case GARAGEM = 'garagem';
    case VAGA = 'vaga';
    case PORTARIA = 'portaria';
    case TERRACO = 'terraco';
}