<?php

namespace App\Services;

use App\Repositories\UsuarioRepositoryInterface;

class UsuarioService {

  protected $usuarioRepository;

    public function __construct(UsuarioRepositoryInterface $usuarioRepository) 
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function create(array $data) 
    {
        // Lógica de validação ou processamento adicional pode ser adicionada aqui
        return $this->usuarioRepository->create($data);
    }
}