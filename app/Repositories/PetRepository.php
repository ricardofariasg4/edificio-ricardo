<?php

namespace App\Repositories;

use App\Models\Pet;

class PetRepository extends BaseRepository implements PetRepositoryInterface
{
    public function __construct(Pet $model)
    {
        parent::__construct($model);
    }
}