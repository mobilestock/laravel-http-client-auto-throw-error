<?php

namespace App\Providers\Auth;

use App\Models\Establishment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    public function retrieveByCredentials(array $credentials): Establishment
    {
        $userData = DB::selectOne(
            "SELECT
                 establishments.id,
                 establishments.token,
                 establishments.iugu_token_live,
                 establishments.fees
             FROM establishments
               WHERE (establishments.token = :token)",
            [
                'token' => $credentials['api_token'],
            ]
        );

        if (empty($userData)) {
            return null;
        }

        $user = new Establishment($userData);

        return $user;
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
