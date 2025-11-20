<?php

namespace App\Repositories;

use App\Models\Usuario;
use \Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
    	return Usuario::all();
    }

    public function find($id): mixed
    {
    	return Usuario::find($id);
    }

    public function create(array $data)
    {
    	return Usuario::create($data);
    }

    public function update($id, array $data): mixed
    {
		$usuario = Usuario::find($id);
		if ($usuario) {
			$usuario->update($data);
			return $usuario;
		}
		return null;
    }

    public function delete($id): mixed
    {
		$usuario = Usuario::find($id);
		if ($usuario) {
			return $usuario->delete();
		}
		return false;
    }
}