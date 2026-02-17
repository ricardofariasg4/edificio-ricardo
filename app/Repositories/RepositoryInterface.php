<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface RepositoryInterface
{
    public function all(): Collection | array;
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}