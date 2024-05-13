<?php

namespace MobileStock\model;

use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\service\CatalogoFixoService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property int $regime
 * @property ?string $cnpj
 * @property ?string $cpf
 * @property string $razao_social
 * @property string $telefone
 * @property ?string $email
 * @property string $foto_perfil
 * @property int $id_tipo_entrega_padrao
 * @property string $usuario_meulook
 * @property string $bloqueado_repor_estoque
 * @property ?string $nome_instagram
 * @property bool $porcentagem_compras_moda
 */
class ColaboradorModel extends Model
{
    protected $table = 'colaboradores';
    protected $fillable = [
        'telefone',
        'usuario_meulook',
        'foto_perfil',
        'razao_social',
        'email',
        'id_tipo_entrega_padrao',
        'nome_instagram',
        'bloqueado_repor_estoque',
        'regime',
        'cpf',
        'cnpj',
    ];

    public static function buscaInformacoesColaborador(int $idColaborador): self
    {
        $colaborador = self::fromQuery(
            "SELECT
                colaboradores.id,
                colaboradores.regime,
                colaboradores.cnpj,
                colaboradores.cpf,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores.email,
                colaboradores.foto_perfil,
                colaboradores.id_tipo_entrega_padrao,
                colaboradores.usuario_meulook,
                colaboradores.bloqueado_repor_estoque,
                colaboradores.nome_instagram
            FROM colaboradores
            WHERE colaboradores.id = :id_colaborador",
            ['id_colaborador' => $idColaborador]
        )->first();

        return $colaborador;
    }
    public static function buscaColaboradorPorUsuarioMeulook(string $usuarioMeulook): self
    {
        $colaborador = self::fromQuery(
            "SELECT colaboradores.id
            FROM colaboradores
            WHERE colaboradores.usuario_meulook = :usuario_meulook;",
            ['usuario_meulook' => $usuarioMeulook]
        )->first();
        if (empty($colaborador)) {
            throw new NotFoundHttpException('Colaborador não encontrado');
        }

        return $colaborador;
    }
    public static function existeTelefone(string $telefone): bool
    {
        $existeTelefone = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores
                WHERE colaboradores.telefone = :telefone
            ) AS `existe_telefone`;",
            ['telefone' => $telefone]
        );

        return $existeTelefone;
    }
    public static function existeEmail(string $email): bool
    {
        $existeEmail = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores
                WHERE colaboradores.email = :email
            ) AS `existe_email`;",
            ['email' => $email]
        );

        return $existeEmail;
    }
    public static function existeUsuarioMeulook(string $usuarioMeulook): bool
    {
        $existeUsuarioMeulook = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores
                WHERE colaboradores.usuario_meulook = :usuario_meulook
            ) AS `existe_usuario_meulook`;",
            ['usuario_meulook' => $usuarioMeulook]
        );

        return $existeUsuarioMeulook;
    }
    public static function verificaEhProprioPerfil(string $usuarioMeulook): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $ehMeuPerfil = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores
                WHERE colaboradores.id = :id_colaboradores
                    AND colaboradores.usuario_meulook = :usuario_meulook
            ) AS `eh_meu_perfil`;",
            ['id_colaboradores' => Auth::user()->id_colaborador, 'usuario_meulook' => $usuarioMeulook]
        );

        return $ehMeuPerfil;
    }
    public function buscaOuGeraUsuarioMeulook(int $idColaborador): void
    {
        $usuarioMeulook = DB::selectOneColumn(
            "SELECT colaboradores.usuario_meulook
            FROM colaboradores
            WHERE colaboradores.id = :id_colaborador;",
            ['id_colaborador' => $idColaborador]
        );
        if (empty($usuarioMeulook)) {
            $usuarioMeulook = $this->geraUsuarioMeulook($idColaborador);
        }

        $this->usuario_meulook = $usuarioMeulook;
    }
    private function geraUsuarioMeulook(int $idColaborador): string
    {
        $colaborador = self::buscaInformacoesColaborador($idColaborador);
        $usuarioMeulook = Str::replace(' ', '', $colaborador->razao_social);
        $usuarioMeulook = preg_replace('/(?![a-zA-z ]).{1}/', '', $usuarioMeulook);
        $usuarioMeulook = Str::lower("$usuarioMeulook.$idColaborador");
        $this->validaNomeUsuarioMeuLook($usuarioMeulook);
        $colaborador->usuario_meulook = $usuarioMeulook;
        $colaborador->update();

        return $usuarioMeulook;
    }
    public function validaNomeUsuarioMeuLook(string $usuarioMeulook): void
    {
        $caracteresProibidos = [
            'á',
            'ã',
            'â',
            'à',
            'é',
            'é',
            'í',
            'ì',
            'î',
            'õ',
            'ô',
            'û',
            'ù',
            '~',
            '^',
            '₢',
            'ª',
            '¹',
            '¬',
            'ó',
            'ò',
            'ç',
            'ú',
            '/',
            '|',
            '\\',
            '@',
            ',',
            '*',
            ';',
            ':',
            '#',
            '!',
            'º',
            '²',
            '%',
            '¨',
            '"',
            '&',
            '(',
            ')',
            '=',
            '§',
            '`',
            '}',
            ']',
            '[',
            '³',
            '{',
            '°',
            'Ÿ',
            '–',
            '¤',
            'ð',
            '',
            '˜£',
            'ð',
            '<',
            '>',
            '$',
            '?',
            'DROP DATABASE',
            'ALTER TABLE',
        ];
        $caracteresEncontrados = array_filter(
            $caracteresProibidos,
            fn(string $caracter): bool => str_contains($usuarioMeulook, $caracter)
        );

        if (!empty($caracteresEncontrados)) {
            throw new ValidationException(
                'O nome de usuário não pode conter os seguintes caracteres: ' . implode(', ', $caracteresEncontrados)
            );
        }
    }

    public static function buscaTipoCatalogo(int $idColaborador): string
    {
        $dado = DB::selectOne(
            "
            SELECT
                colaboradores.porcentagem_compras_moda
            FROM colaboradores
            WHERE colaboradores.id = :id_colaborador
        ",
            ['id_colaborador' => $idColaborador]
        );

        switch (true) {
            case $dado['porcentagem_compras_moda'] > 80:
                return CatalogoFixoService::TIPO_MODA_100;
            case $dado['porcentagem_compras_moda'] > 60:
                return CatalogoFixoService::TIPO_MODA_80;
            case $dado['porcentagem_compras_moda'] > 40:
                return CatalogoFixoService::TIPO_MODA_60;
            case $dado['porcentagem_compras_moda'] > 20:
                return CatalogoFixoService::TIPO_MODA_40;
            case $dado['porcentagem_compras_moda'] > 0:
                return CatalogoFixoService::TIPO_MODA_20;
            default:
                return CatalogoFixoService::TIPO_MODA_GERAL;
        }
    }
}
