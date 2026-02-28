<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\EntityCreateException;
use App\Exceptions\EntityUpdateException;
use App\Exceptions\EntityDeleteException;

abstract class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): Model
    {
        try {
            return $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new EntityNotFoundException($this->model::class, $e->getMessage());
        }
    }

    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (\Exception $e) {
            throw new EntityCreateException($this->model::class, $e->getMessage());
        }
    }

    public function update(int $id, array $data): Model
    {
        try {
            $entity = $this->model->findOrFail($id);
            $entity?->update($data);
            return $entity;
        } catch (ModelNotFoundException $e) {
            throw new EntityNotFoundException($this->model::class, $e->getMessage());
        } catch (\Exception $e) {
            throw new EntityUpdateException($this->model::class, $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $entity = $this->model->findOrFail($id);
            return $entity?->delete();
        } catch (ModelNotFoundException $e) {
            throw new EntityNotFoundException($this->model::class, $e->getMessage());
        } catch (\Exception $e) {
            throw new EntityDeleteException($this->model::class, $e->getMessage());
        }
    }
}