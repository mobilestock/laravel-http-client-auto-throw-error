<?php

namespace MobileStock\model;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;

/**
 * https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property string $nome
 * @property ?string $senha
 * @property string $email
 * @property int $nivel_acesso
 * @property int $id_colaborador
 * @property ?string $cnpj
 * @property string $telefone
 * @property string $tipos
 * @property string $permissao
 */
class UsuarioModel extends User
{
    public const ID_USUARIO_SISTEMA = 2;
    protected $table = 'usuarios';
    public $timestamps = false;
    protected $fillable = [
        'nome',
        'senha',
        'email',
        'nivel_acesso',
        'id_colaborador',
        'cnpj',
        'telefone',
        'tipos',
        'permissao',
    ];
    public static function buscaInformacoesUsuario(int $idUsuario): self
    {
        $usuario = self::fromQuery(
            "SELECT
                usuarios.id,
                usuarios.nome,
                usuarios.senha,
                usuarios.email,
                usuarios.nivel_acesso,
                usuarios.id_colaborador,
                usuarios.cnpj,
                usuarios.telefone,
                usuarios.tipos,
                usuarios.permissao
            FROM usuarios
            WHERE usuarios.id = :idUsuario",
            ['idUsuario' => $idUsuario]
        )->first();

        return $usuario;
    }

    public static function buscaIdColaboradorPorIdUsuario(int $idUsuario): int
    {
        $binds = [':id_usuario' => $idUsuario];
        $sql = "SELECT usuarios.id_colaborador
            FROM usuarios
            WHERE usuarios.id = :id_usuario;";
        $idColaborador = DB::selectOneColumn($sql, $binds);

        return $idColaborador;
    }

    public static function adicionarPermissao(int $idUsuario, int $permissao): void
    {
        $usuario = self::buscaInformacoesUsuario($idUsuario);

        $permissoesAtuais = empty($usuario->permissao) ? [] : explode(',', $usuario->permissao);
        $permissoesAtuais[] = $permissao;
        $permissao = array_unique($permissoesAtuais);
        asort($permissao, SORT_NUMERIC);
        $usuario->permissao = implode(',', $permissao);

        if ($usuario->isDirty()) {
            $usuario->update();
        }
    }
}
