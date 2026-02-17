<?php

namespace App\Repositories;

use App\Models\Usuario;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(Usuario $model)
    {
        parent::__construct($model);
    }
}