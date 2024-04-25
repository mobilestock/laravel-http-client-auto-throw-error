<?php

namespace MobileStock\helper\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    public function retrieveByCredentials(array $credentials): ?GenericUser
    {
        $ehApiEstoque = mb_stripos(Request::getBasePath(), '/api_estoque') !== false;

        $join = '';
        $where = '';
        if ($ehApiEstoque) {
            $join = 'LEFT JOIN usuarios_tokens_maquinas ON usuarios_tokens_maquinas.id_usuario = usuarios.id';
            $where = 'OR usuarios_tokens_maquinas.token = :token';
        }

        $usuario = DB::selectOne(
            "SELECT
                 usuarios.id,
                 usuarios.nome,
                 usuarios.id_colaborador,
                 usuarios.permissao
             FROM usuarios
             $join
             WHERE usuarios.bloqueado = 0
               AND (usuarios.token = :token $where)",
            [
                'token' => $credentials['api_token'],
            ]
        );

        if (empty($usuario)) {
            return null;
        }

        Log::withContext(['user' => $usuario]);
        $usuario = new GenericUser($usuario);

        return $usuario;
    }

    public function retrieveById($identifier)
    {
        // TODO: Implement retrieveById() method.
    }

    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: Implement validateCredentials() method.
    }
}
