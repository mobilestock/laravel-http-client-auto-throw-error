<?php

namespace App\Models;

use App\Casts\OnlyNumbers;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableInterface;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\Usuario
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $data_criacao
 * @property \Illuminate\Support\Carbon|null $data_atualizacao
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario query()
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario whereDataAtualizacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario whereDataCriacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario whereId($value)
 *
 * @property string $nome
 * @property string $telefone
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Usuario whereTelefone($value)
 *
 * @mixin \Eloquent
 */
class Usuario extends Model implements JWTSubject, AuthenticatableInterface
{
    use Authenticatable;

    protected $fillable = [
        'nome',
        'telefone',
    ];

    protected $casts = [
        'telefone' => OnlyNumbers::class,
    ];

    public function getJWTIdentifier()
    {
        return $this->{$this->primaryKey};
    }

    public function getJWTCustomClaims()
    {
        $dados = $this->only('nome', 'telefone');
        $dados['permissao'] = 'CLIENTE';

        return $dados;
    }
}
