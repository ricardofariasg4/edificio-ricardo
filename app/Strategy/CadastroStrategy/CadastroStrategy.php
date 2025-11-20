<?php

interface CadastroUsuarioStrategy {
    public function cadastrar(array $dados): void;
}