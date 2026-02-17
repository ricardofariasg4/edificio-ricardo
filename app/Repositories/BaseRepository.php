<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection | array
    {
        try {
            return $this->model->all();
        } catch (\Exception $e) {
            return [
                'error' => 'Erro ao recuperar os dados',
                'details' => $e->getMessage() // Isso deve ser logado ao invés de retornado em produção
            ];
        }
    }

    public function find(int $id)
    {
        try {
            return $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return [
                'error' => $this->model->getTable() . ' não encontrado(a)',
                'details' => $e->getMessage() // Isso deve ser logado ao invés de retornado em produção
            ];
        }
    }

    public function create(array $data)
    {
        try {
            return $this->model->create($data);
        } catch (\Exception $e) {
            return [
                'error' => 'Erro ao criar o registro na entidade ' . $this->model->getTable(),
                'details' => $e->getMessage() // Isso deve ser logado ao invés de retornado em produção
            ];
        }
    }

    public function update(int $id, array $data)
    {
        try {
            $entity = $this->model->findOrFail($id);
            $entity?->update($data);
            return $entity;
        } catch (ModelNotFoundException $e) {
            return [
                'error' => $this->model->getTable() . ' não encontrado(a) para atualização',
                'details' => $e->getMessage() // Isso deve ser logado ao invés de retornado em produção
            ];
        }
    }

    public function delete(int $id)
    {
        try {
            $entity = $this->model->findOrFail($id);

            $response = [
                'error' => 'Erro ao deletar o registro na entidade ' . $this->model->getTable()
            ];
            
            if ($entity?->delete()) {
                $response = [
                    'message' => 'Usuário deletado(a) com sucesso'
                ];
            }

            return $response;
        } catch (ModelNotFoundException $e) {
            return [
                'error' => 'Usuário não encontrado(a) para deleção',
                'details' => $e->getMessage() // Isso deve ser logado ao invés de retornado em produção
            ];
        }
    }
}