<?php

namespace App\Repositories;

use \Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function all(): Collection;
    public function find($id): mixed;
    public function create(array $data);
    public function update($id, array $data): mixed;
    public function delete($id): mixed;
}
