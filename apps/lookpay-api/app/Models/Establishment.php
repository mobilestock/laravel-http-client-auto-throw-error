<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Establishment
 *
 * @property string $id
 * @property string $password
 * @property string $token
 * @property string $iugu_token_live
 * @property ?Carbon $created_at
 *
 */
class Establishment extends Model
{
    use Authenticatable;

    protected $primaryKey = 'id';
    protected $fillable = ['id', 'password', 'token', 'iugu_token_live', 'fees'];

    public static function getEstablishmentByPhoneNumber(string $phoneNumber): array
    {
        $establishment = DB::select(
            "SELECT
                establishments.id,
                establishments.name
            FROM establishments
            WHERE establishments.phone_number = :phone_number",
            [
                'phone_number' => $phoneNumber,
            ]
        );

        return $establishment;
    }

    public static function authentication(string $establishmentId, string $password): array
    {
        $user = DB::selectOne(
            "SELECT
                establishments.id,
                establishments.token,
                establishments.password,
                establishments.name
            FROM establishments
            WHERE establishments.id = :establishment_id",
            [
                'establishment_id' => $establishmentId,
            ]
        );

        if (empty($user) || !password_verify($password, $user['password'])) {
            return [];
        }

        return $user;
    }
}
