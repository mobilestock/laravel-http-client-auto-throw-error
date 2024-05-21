<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Origem;
use MobileStock\model\Usuario;
use MobileStock\model\UsuarioModel;
use MobileStock\service\Cadastros\CadastrosService;
use PDO;
use RuntimeException;

class UsuarioService
{
    public static function editaAcessoPrincipal(int $novoAcessoPrincipal, int $idUsuario): void
    {
        $linhasAfetadas = DB::update(
            "UPDATE usuarios
            SET usuarios.nivel_acesso = :novo_acesso_principal
            WHERE usuarios.nivel_acesso <> :novo_acesso_principal
                AND usuarios.id = :id_usuario;",
            ['novo_acesso_principal' => $novoAcessoPrincipal, 'id_usuario' => $idUsuario]
        );

        if ($linhasAfetadas !== 1) {
            throw new RuntimeException('Erro ao alterar o acesso principal.');
        }
    }

    public static function buscaNomeUsuario(PDO $conexao, $idUsuario): string
    {
        $stmt = $conexao->prepare('SELECT nome FROM usuarios WHERE id = ?');
        $stmt->execute([$idUsuario]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($dados)) {
            throw new \InvalidArgumentException('Usuário não existe');
        }

        return $dados['nome'];
    }

    public function insereUsuario(PDO $conexao, array $usuario)
    {
        $user = new Usuario(
            $usuario['nome'],
            $usuario['acesso'],
            $usuario['bloqueado'],
            $usuario['id_colaborador'],
            $usuario['email'],
            $usuario['cnpj'],
            $usuario['telefone']
        );
        $user->setSenha($usuario['senha']);

        $senhaMd5 = md5($user->senha);
        $sth = $conexao->prepare("INSERT INTO usuarios (
                nome,
                senha,
                nivel_acesso,
                bloqueado,
                id_colaborador,
                email,
                cnpj,
                telefone)
              VALUES (
                :nome,
                :senha,
                :acesso,
                :bloqueado,
                :id_colaborador,
                :email,
                :cnpj,
                :telefone)");
        $sth->bindValue('nome', $user->nome, PDO::PARAM_STR);
        $sth->bindValue('senha', $senhaMd5, PDO::PARAM_STR);
        $sth->bindValue('acesso', $user->acesso, PDO::PARAM_INT);
        $sth->bindValue('bloqueado', $user->bloqueado, PDO::PARAM_INT);
        $sth->bindValue('id_colaborador', $user->colaborador, PDO::PARAM_INT);
        $sth->bindValue('email', $user->email, PDO::PARAM_STR);
        $sth->bindValue('cnpj', $user->cnpj, PDO::PARAM_STR);
        $sth->bindValue('telefone', $user->telefone, PDO::PARAM_STR);
        $sth->execute();
        return $conexao->lastInsertId();
    }

    public function alteraUsuario(PDO $conexao, array $usuario, int $id)
    {
        $user = new Usuario(
            $usuario['nome'],
            $usuario['acesso'],
            $usuario['bloqueado'],
            $usuario['id_colaborador'],
            $usuario['email'],
            $usuario['cnpj'],
            $usuario['telefone']
        );
        $alteraSenha = false;
        if ($usuario['senha'] != '') {
            $alteraSenha = true;
            $user->setSenha($usuario['senha']);
            $senhaMd5 = md5($user->senha);
        }
        $sql = 'UPDATE usuarios SET nome = :nome,';
        $sql .= $alteraSenha ? ' senha = :senha,' : '';
        $sql .= "nivel_acesso = :acesso,
              bloqueado = :bloqueado,
              id_colaborador = :id_colaborador,
              email = :email,
              cnpj = :cnpj,
              telefone = :telefone
              WHERE id=:id";
        $sth = $conexao->prepare($sql);
        $sth->bindValue('nome', $user->nome, PDO::PARAM_STR);
        if ($alteraSenha) {
            $sth->bindValue('senha', $senhaMd5, PDO::PARAM_STR);
        }
        $sth->bindValue('acesso', $user->acesso, PDO::PARAM_INT);
        $sth->bindValue('bloqueado', $user->bloqueado, PDO::PARAM_INT);
        $sth->bindValue('id_colaborador', $user->colaborador, PDO::PARAM_INT);
        $sth->bindValue('email', $user->email, PDO::PARAM_STR);
        $sth->bindValue('cnpj', $user->cnpj, PDO::PARAM_STR);
        $sth->bindValue('telefone', $user->telefone, PDO::PARAM_STR);
        $sth->bindValue('id', $id, PDO::PARAM_INT);
        return $sth->execute();
    }

    // public static function UsuarioColaborador(int $id_user)
    // {
    //     $sql = "SELECT id_colaborador FROM usuarios WHERE id={$id_user};";
    //     $conexao = Conexao::criarConexao();
    //     $sth = $conexao->prepare($sql);
    //     $sth->execute();
    //     return $sth->fetch(PDO::FETCH_ASSOC);
    // }

    /**
     * @deprecated usar validaAutenticacaoUsuariosColaborador()
     */
    // public static function logaUsuario(PDO $conexao, string $user, string $pass)
    // {
    //     $passMd5 = md5($pass);

    //     $sql = "SELECT usuarios.id,
    //                 usuarios.nome,
    //                 usuarios.email,
    //                 usuarios.nivel_acesso,
    //                 usuarios.id_colaborador,
    //                 usuarios.cnpj,
    //                 usuarios.telefone,
    //                 usuarios.token,
    //                 usuarios.permissao,
    //                 usuarios.senha,
    //                 (SELECT colaboradores.uf FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) uf,
    //                 (SELECT colaboradores.regime FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) regime
    //             FROM usuarios
    //             WHERE usuarios.bloqueado = 0
    //             AND (
    //                 usuarios.nome = :user
    //                 OR usuarios.email = :user
    //                 OR usuarios.cnpj = :user
    //                 OR usuarios.id_colaborador IN (
    //                     (
    //                         SELECT colaboradores.id FROM colaboradores WHERE colaboradores.cpf = :user
    //                     )
    //                 )
    //             )";

    //     $stm = $conexao->prepare($sql);
    //     $stm->bindValue(":user", $user, PDO::PARAM_STR);
    //     $stm->execute();
    //     $informacoes = $stm->fetchAll(PDO::FETCH_ASSOC);

    //     $usuario = self::verificacaoSenha($conexao, $informacoes, $pass, $passMd5);
    //     if (!empty($usuario)) {
    //         return $usuario;
    //     }
    //     if (empty($informacoes)) $informacoes = self::tentaLoginSenhaTemporaria($conexao, $user, $passMd5);

    //     return $informacoes;
    // }

    // public static function tokenLogin(PDO $conexao, string $token)
    // {

    //     $sql = "SELECT usuarios.id
    //             FROM usuarios
    //             WHERE usuarios.bloqueado = 0 AND token_temporario = :token LIMIT 1";
    //     $sth = $conexao->prepare($sql);
    //     $sth->bindValue(':token', $token, PDO::PARAM_STR);
    //     $sth->execute();
    //     $dados = $sth->fetch(PDO::FETCH_ASSOC);
    //     if (empty($dados)) {
    //         throw new \InvalidArgumentException('Usuário não existe.', 404);
    //     }

    //     $sql = "SELECT usuarios.id,
    //                 usuarios.nome,
    //                 usuarios.email,
    //                 usuarios.nivel_acesso,
    //                 usuarios.id_colaborador,
    //                 usuarios.cnpj,
    //                 usuarios.telefone,
    //                 usuarios.token,
    //                 usuarios.permissao,
    //                 (SELECT colaboradores.uf FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) uf,
    //                 (SELECT colaboradores.regime FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) regime
    //             FROM usuarios WHERE token_temporario = :token LIMIT 1";
    //     $stm = $conexao->prepare($sql);
    //     $stm->bindValue(":token", $token, PDO::PARAM_STR);
    //     $stm->execute();

    //     return $stm->fetch(PDO::FETCH_ASSOC);
    // }

    // public function editUserColaborador(PDO $conexao, array $usuario, int $id)
    // {
    //     $senha = ($usuario['password'] == "true" ? "usuarios.senha = '".md5($usuario['senha'])."', ": "");
    //     $sql = "UPDATE usuarios SET usuarios.nome =  '" . $usuario['nome'] . "',
    //                                 {$senha}
    //                                 usuarios.cnpj =  '" . $usuario['cnpj'] . "',
    //                                 usuarios.email =   '" . $usuario['email'] . "',
    //                                 usuarios.telefone =  '" . $usuario['telefone'] . "',
    //                                 usuarios.id_colaborador = " . $usuario['id_colaborador'] . "
    //                                     WHERE usuarios.id = $id";
    //     $sth = $conexao->prepare($sql);
    //     $sth->execute();
    //      $update = '';
    //     if($usuario['regime'] == 1 && $usuario['registro']=="true"){
    //         $update = ", colaboradores.cnpj = '" . $usuario['cnpj'] . "'";
    //     }else if($usuario['regime'] == 2 && $usuario['registro'] == "true"){
    //         $update = ", colaboradores.cpf = '" . $usuario['cnpj'] . "'";
    //     } else if ($usuario['regime'] == 3) {
    //         throw new Exception("Não foi possível identificar regime Mobile(3)");
    //     }
    //     $query = "UPDATE colaboradores SET colaboradores.regime = " . $usuario['regime'] . " $update WHERE colaboradores.id = " . $usuario['id_colaborador'] . ";";
    //     $sth = $conexao->prepare($query);
    //     return $sth->execute();
    // }

    /**
     * Função auxiliar para buscar os dados do usuário para autenticação
     * @issue https://github.com/mobilestock/backend/issues/120
     * @param PDO $conexao
     * @param string $origem
     * @param string $tipoChaveUsuario TOKEN_TEMPORARIO | ID_COLABORADOR
     * @param int|string $valorChaveUsuario
     * @return array|false
     */
    public static function buscaDadosUsuarioParaAutenticacao(
        PDO $conexao,
        string $origem,
        string $tipoChaveUsuario,
        $valorChaveUsuario
    ) {
        Validador::validar(
            [
                'chave' => $tipoChaveUsuario,
                'valor' => $valorChaveUsuario,
            ],
            [
                'chave' => [Validador::ENUM('TOKEN_TEMPORARIO', 'ID_COLABORADOR')],
                'valor' => [
                    Validador::SE($tipoChaveUsuario === 'TOKEN_TEMPORARIO', Validador::NAO_NULO, Validador::NUMERO),
                ],
            ]
        );

        $colunaWhere = 'usuarios.id_colaborador';
        $tipoValorPDO = PDO::PARAM_INT;
        if ($tipoChaveUsuario === 'TOKEN_TEMPORARIO') {
            $colunaWhere = 'usuarios.token_temporario';
            $tipoValorPDO = PDO::PARAM_STR;
        }

        $caseTipoAutenticacao = ColaboradoresService::caseTipoAutenticacao($origem);

        $stmt = $conexao->prepare(
            "SELECT usuarios.id,
                usuarios.nome,
                usuarios.email,
                usuarios.nivel_acesso,
                usuarios.id_colaborador,
                usuarios.cnpj,
                usuarios.telefone,
                usuarios.token,
                usuarios.permissao,
                colaboradores.regime,
                $caseTipoAutenticacao
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            WHERE $colunaWhere = :valor
            LIMIT 1"
        );
        $stmt->bindValue(':valor', $valorChaveUsuario, $tipoValorPDO);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario;
    }

    // public static function buscaUsuarioAuth(PDO $conn, int $idUser)
    // {
    //     $sql = "SELECT usuarios.id,
    //                 usuarios.nome,
    //                 usuarios.email,
    //                 usuarios.nivel_acesso,
    //                 usuarios.id_colaborador,
    //                 usuarios.cnpj,
    //                 usuarios.telefone,
    //                 usuarios.token,
    //                 usuarios.permissao,
    //                  (SELECT colaboradores.uf FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) uf,
    //                 (SELECT colaboradores.regime FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) regime
    //                 FROM usuarios WHERE usuarios.id = $idUser";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute();
    //     return $stmt->fetch(PDO::FETCH_ASSOC);
    // }
    public static function editIdZoop(PDO $conn, int $idColaborador, string $zoop)
    {
        $sql = "UPDATE api_colaboradores SET api_colaboradores.id_zoop = '{$zoop}' WHERE api_colaboradores.id_colaborador = $idColaborador";
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }
    public static function buscaUsuario(PDO $conn, string $email = '', string $cpf = '', int $idColaborador = 0)
    {
        if ($email != '') {
            $sql = "SELECT
                    usuarios.id,
                    usuarios.cnpj,
                    usuarios.nome,
                    usuarios.token,
                    usuarios.token_temporario,
                    usuarios.email,
                    usuarios.senha
                  FROM
                    usuarios
                  WHERE
                    usuarios.email = '{$email}' LIMIT 1";
        } elseif ($cpf != '') {
            $sql = "SELECT
                        usuarios.id,
                        usuarios.cnpj,
                        usuarios.nome,
                        usuarios.token,
                        usuarios.email,
                        usuarios.senha
                    FROM usuarios
                    INNER JOIN colaboradores ON colaboradores.id=usuarios.id_colaborador
                    WHERE usuarios.cnpj = :cpf OR colaboradores.cpf = :cpf LIMIT 1";
        } elseif ($idColaborador !== 0) {
            $sql = "SELECT
                        usuarios.id,
                        colaboradores.id id_colaborador,
                        usuarios.cnpj,
                        usuarios.nome,
                        usuarios.token,
                        usuarios.email,
                        usuarios.senha,
                        CASE
                            WHEN usuarios.senha IS NULL THEN 'nenhuma'
                            ELSE 'senha'
                        END as `tipo_autenticacao`
                    FROM usuarios
                    INNER JOIN colaboradores ON colaboradores.id=usuarios.id_colaborador
                    WHERE usuarios.id_colaborador = :idColaborador LIMIT 1";
        }
        $stmt = $conn->prepare($sql);
        if ($email !== '') {
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        }
        if ($cpf !== '') {
            $stmt->bindValue(':cpf', $cpf, PDO::PARAM_STR);
        }
        if ($idColaborador !== 0) {
            $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        }
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
        return $retorno;
    }

    //  public static function buscaTelefoneUsuario(PDO $conn, int $idUsuario){
    //     $sql = "SELECT
    //                 COALESCE(colaboradores.telefone, colaboradores.telefone2, usuarios.telefone) AS telefone
    //             FROM usuarios
    //             JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
    //             WHERE usuarios.id = $idUsuario";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute();
    //     $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $retorno['telefone'];
    //  }

    public function recuperaUsuarioPorToken(PDO $conn, string $token)
    {
        $sql = "SELECT usuarios.id, usuarios.token, usuarios.nivel_acesso, usuarios.nome, usuarios.bloqueado, usuarios.id_colaborador,
         usuarios.email, usuarios.cnpj, usuarios.telefone,(SELECT colaboradores.regime
         FROM colaboradores WHERE usuarios.id_colaborador = colaboradores.id) regime
         FROM usuarios WHERE usuarios.token ='{$token}'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function buscaUsuarioEAutenticacaoPorTelefone(PDO $conexao, string $telefone): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                usuarios.id,
                colaboradores.email,
                CASE
                    WHEN usuarios.senha IS NULL THEN 'nenhuma'
                    ELSE 'senha'
                END tipo_autenticacao
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            WHERE colaboradores.telefone = :telefone OR usuarios.telefone = :telefone
            LIMIT 1"
        );

        $stmt->execute([':telefone' => $telefone]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function buscaCategoriaDoUsuarioLogado(PDO $conexao, int $id_usuario)
    {
        $prepare = $conexao->prepare("  SELECT
                                            usuarios.permissao,
                                            usuarios.id_colaborador,
                                            IF(tipo_frete.categoria = 'PE',
                                            IF(EXISTS(SELECT 1 FROM entregas WHERE entregas.id_tipo_frete = tipo_frete.id),'ML','PE')
                                            ,tipo_frete.categoria) categoria
                                        FROM
                                            usuarios
                                            LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = usuarios.id_colaborador
                                        WHERE usuarios.id = :id_usuario;");
        $prepare->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetch(PDO::FETCH_ASSOC);
        return $dados;
    }
    public static function buscaPermissaoColaborador(PDO $conexao, int $id_cliente)
    {
        $query = $conexao->prepare(
            "SELECT usuarios.permissao,
            colaboradores.razao_social
        FROM usuarios
        INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
        WHERE usuarios.id_colaborador = :idCliente
        GROUP BY usuarios.id"
        );
        $query->bindValue(':idCliente', $id_cliente, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * @issue https://github.com/mobilestock/backend/issues/120
     */
    public static function validaAutenticacaoUsuariosColaborador(int $idColaborador, ?string $senha): ?array
    {
        $origem = app(Origem::class);
        $sqlCaseTipoAutenticacao = ColaboradoresService::caseTipoAutenticacao($origem);

        $usuario = DB::selectOne(
            "SELECT
                usuarios.id,
                usuarios.senha,
                usuarios.nome,
                usuarios.nivel_acesso,
                usuarios.permissao,
                colaboradores.regime,
                colaboradores.id id_colaborador,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                $sqlCaseTipoAutenticacao
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :id_colaborador
            GROUP BY usuarios.id;",
            [':id_colaborador' => $idColaborador]
        );
        if (empty($usuario)) {
            return null;
        }

        if ($usuario['tipo_autenticacao'] === 'SENHA' && empty($senha)) {
            throw new Exception('Senha é obrigatória para autenticação.');
        }

        $senhaBateComMd5 = $usuario['senha'] === md5($senha);
        if (
            (in_array($usuario['permissao'], ['10', '10,13']) && $senha === null && !$origem->ehLp()) ||
            $usuario['senha'] === null ||
            password_verify($senha, $usuario['senha']) ||
            $senhaBateComMd5
        ) {
            if ($senhaBateComMd5) {
                CadastrosService::editPassword(DB::getPdo(), $senha, $usuario['id']);
            }

            return $usuario;
        }

        $informacoes = self::tentaLoginSenhaTemporaria($idColaborador, $senha);

        return $informacoes;
    }

    public static function usuarioPossuiSenha(): bool
    {
        $possuiSenha = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM usuarios
                WHERE usuarios.id = :id_usuario
                    AND usuarios.senha IS NOT NULL
            ) AS `possui_senha`;",
            ['id_usuario' => Auth::user()->id]
        );

        return $possuiSenha;
    }
    public static function verificaSeEstaBloqueado(PDO $conexao, int $idCliente)
    {
        $sql = $conexao->prepare(
            "SELECT colaboradores.bloqueado_criar_look
            FROM colaboradores
            WHERE colaboradores.id = $idCliente"
        );
        $sql->execute();
        $consulta = $sql->fetch(PDO::FETCH_ASSOC)['bloqueado_criar_look'];
        return $consulta;
    }
    public static function bloqueiaDeCriarLook(PDO $conexao, int $idCliente)
    {
        return $conexao->exec(
            "UPDATE colaboradores
            SET colaboradores.bloqueado_criar_look = 'T'
            WHERE colaboradores.id = $idCliente"
        );
    }
    public static function desbloqueiaDeCriarLook(PDO $conexao, int $idCliente)
    {
        return $conexao->exec(
            "UPDATE colaboradores
            SET colaboradores.bloqueado_criar_look = 'F'
            WHERE colaboradores.id = $idCliente"
        );
    }
    private static function tentaLoginSenhaTemporaria(int $idColaborador, string $senha): ?array
    {
        $informacoes = DB::selectOne(
            "SELECT
                usuarios.senha_temporaria,
                usuarios.data_senha_temporaria >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) esta_na_validade,
                usuarios.id
            FROM usuarios
            WHERE usuarios.id_colaborador = :id_colaborador;",
            [':id_colaborador' => $idColaborador]
        );

        if (
            empty($informacoes) ||
            !$informacoes['esta_na_validade'] ||
            !password_verify($senha, $informacoes['senha_temporaria'])
        ) {
            return null;
        }

        $sqlCaseTipoAutenticacao = ColaboradoresService::caseTipoAutenticacao(app(Origem::class));

        $informacoes = DB::selectOne(
            "SELECT
                usuarios.id,
                usuarios.nome,
                usuarios.email,
                usuarios.nivel_acesso,
                usuarios.id_colaborador,
                usuarios.cnpj,
                usuarios.telefone,
                usuarios.permissao,
                COALESCE(
                    colaboradores.foto_perfil,
                    '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'
                ) foto_perfil,
                colaboradores.regime,
                $sqlCaseTipoAutenticacao
            FROM usuarios
            LEFT JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            WHERE usuarios.id = :id_usuario;",
            ['id_usuario' => $informacoes['id']]
        );

        return $informacoes;
    }

    public static function buscaPermissoes(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                nivel_permissao.nome,
                nivel_permissao.nivel_value
            FROM nivel_permissao"
        );
        $stmt->execute();
        $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $permissoes;
    }

    public static function atualizaUsuario(array $novasInformacoes): void
    {
        $usuario = UsuarioModel::buscaInformacoesUsuario($novasInformacoes['id_usuario']);
        $usuario->nome = $novasInformacoes['nome'];
        $usuario->email = $novasInformacoes['email'];
        if ($novasInformacoes['senha_alterada'] === true) {
            if (empty($novasInformacoes['senha'])) {
                $usuario->senha = null;
            } else {
                $usuario->senha = password_hash($novasInformacoes['senha'], PASSWORD_ARGON2ID);
            }
        }
        if (!empty($novasInformacoes['cnpj'])) {
            $usuario->cnpj = $novasInformacoes['cnpj'];
        }
        if (!empty($novasInformacoes['cpf'])) {
            $usuario->cnpj = $novasInformacoes['cpf'];
        }
        $usuario->update();

        $colaborador = ColaboradorModel::buscaInformacoesColaborador($novasInformacoes['id_colaborador']);
        $colaborador->email = $novasInformacoes['email'];
        $colaborador->telefone = $novasInformacoes['telefone'];
        $colaborador->razao_social = $novasInformacoes['colaborador'];
        if (!empty($novasInformacoes['cpf'])) {
            $colaborador->cpf = $novasInformacoes['cpf'];
        }
        if (!empty($novasInformacoes['cnpj'])) {
            $colaborador->cnpj = $novasInformacoes['cnpj'];
        }
        if (!empty($novasInformacoes['regime'])) {
            $colaborador->regime = $novasInformacoes['regime'];
        }
        if (!empty($novasInformacoes['usuario_meulook'])) {
            $colaborador->usuario_meulook = $novasInformacoes['usuario_meulook'];
        }
        $colaborador->update();
    }

    public static function limpaItokenComBaseNoIdColaborador(PDO $conexao, int $idColaborador): void
    {
        $sql = "UPDATE usuarios
				SET usuarios.password_pay = NULL
				WHERE usuarios.id_colaborador = :id_colaborador";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception(
                'Ocorreu um erro ao limpar Itoken do cliente. ' . $stmt->rowCount() . ' linhas atualizadas.'
            );
        }
    }

    public static function filtraUsuariosRedefinicaoSenha(
        PDO $conexao,
        string $email,
        string $telefone,
        string $cpf
    ): array {
        if ($email) {
            $where = 'AND usuarios.email = :email';
        } elseif ($telefone) {
            $where = 'AND (usuarios.telefone = :telefone OR colaboradores.telefone = :telefone)';
        } elseif ($cpf) {
            $where = 'AND colaboradores.cpf = :cpf';
        }

        $stmt = $conexao->prepare(
            "SELECT
                colaboradores.id,
                usuarios.id id_usuario,
                usuarios.nome,
                colaboradores.usuario_meulook,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                usuarios.email,
                usuarios.telefone,
                colaboradores.razao_social
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            WHERE TRUE
            AND usuarios.senha IS NOT NULL
            $where
            GROUP BY usuarios.id_colaborador"
        );
        if ($email) {
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        } elseif ($telefone) {
            $stmt->bindValue(':telefone', $telefone, PDO::PARAM_INT);
        } elseif ($cpf) {
            $stmt->bindValue(':cpf', $cpf);
        }
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    public static function calculaTendenciaCompra(): void
    {
        $clientesTransacoes = DB::select(
            "SELECT
                logistica_item.id_cliente,
                colaboradores.porcentagem_compras_moda,
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        DISTINCT
                        logistica_item.id_transacao
                        ORDER BY logistica_item.id_transacao DESC
                        LIMIT 10
                    ),
                    ']'
                ) AS `json_transacoes`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            GROUP BY logistica_item.id_cliente
            "
        );

        foreach ($clientesTransacoes as $cliente) {
            [$binds, $valores] = ConversorArray::criaBindValues($cliente['transacoes'], 'id_transacao');
            $produtos = DB::select(
                "SELECT
                    logistica_item.id_produto,
                    produtos.eh_moda
                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                WHERE logistica_item.id_transacao IN ($binds)
                GROUP BY logistica_item.id_produto",
                $valores
            );
            $totalProdutos = count($produtos);
            $produtosModa = array_filter($produtos, fn(array $produto): bool => $produto['eh_moda']);
            $porcentagemCompra = $totalProdutos > 0 ? round((count($produtosModa) / $totalProdutos) * 100) : 0;
            if ($cliente['porcentagem_compras_moda'] === $porcentagemCompra) {
                continue;
            }

            $colaborador = new ColaboradorModel();
            $colaborador->exists = true;
            $colaborador->id = $cliente['id_cliente'];
            $colaborador->porcentagem_compras_moda = $porcentagemCompra;
            $colaborador->update();
        }
    }
}
