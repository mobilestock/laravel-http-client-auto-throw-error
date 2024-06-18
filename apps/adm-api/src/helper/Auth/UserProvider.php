<?php

namespace MobileStock\helper\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    public function retrieveByCredentials(array $credentials): ?GenericUser
    {
        $usuario = DB::selectOne(
            "SELECT
                 usuarios.id,
                 usuarios.nome,
                 usuarios.id_colaborador,
                 usuarios.permissao
             FROM usuarios
             WHERE usuarios.bloqueado = 0
               AND MATCH(usuarios.token) AGAINST(:token IN BOOLEAN MODE)",
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
