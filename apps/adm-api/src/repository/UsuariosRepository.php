<?php

namespace MobileStock\repository;
use DateTime;
use DateTimeZone;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\UsuarioService;
use PDO;

class UsuariosRepository extends MobileStockBD
{
    /**
     * Insere Novo Usuário na Tabela de Usuário
     */
    //public function salvar(object $object):object{}
    /**
     * Atualiza Registro através do ID do Usuário destino
     */
    //public function  atualizar(object $object):object{}
    /**
     *  Busca Todos os Usuários da Tabela Usuarios
     */
    //public function listar(array $params):array{}
    /**
     *  Busca Registro por ID do Usuario
     */

    /**
     * Busca Registro por Nome do Usuário
     */
    public function buscaUsuarioNome()
    {
    }
    /**
     * Busca Registro por Telefone do Usuário
     */
    public function buscaIDUsuariobyToken(string $token): array
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('SELECT
        usuarios.id,
        usuarios.id_colaborador,
        usuarios.nome,
        usuarios.nivel_acesso,
        colaboradores.foto_perfil fotoPerfil
      FROM
        usuarios
        LEFT JOIN colaboradores ON (colaboradores.id = usuarios.id_colaborador)
      WHERE
        usuarios.token = :token');
        $query->bindParam(':token', $token);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result === false ? [] : $result;
    }

    public function buscaUsuarioPorId(int $idUsuario): array
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('SELECT
        usuarios.id,
        usuarios.id_colaborador,
        usuarios.nome,
        usuarios.nivel_acesso,
        colaboradores.foto_perfil fotoPerfil
      FROM
        usuarios
        LEFT JOIN colaboradores ON (colaboradores.id = usuarios.id_colaborador)
      WHERE
        usuarios.id = :idUsuario');
        $query->bindParam(':idUsuario', $idUsuario);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result === false ? [] : $result;
    }

    /**
     * @see https://github.com/mobilestock/backend/issues/105
     */
    public static function buscaIDColaboradorComToken(string $token)
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare("SELECT
                                        COALESCE(usuarios.id,0) id_usuario,
                                        COALESCE(usuarios.nome,0) nome,
                                        COALESCE(usuarios.nivel_acesso,0) nivel_acesso,
                                        COALESCE(usuarios.id_colaborador,0) id_colaborador,
                                        colaboradores.regime,
                                        colaboradores_enderecos.uf,
                                        0 qtd_compras
                                    FROM usuarios
                                    INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
                                    LEFT JOIN colaboradores_enderecos ON
                                        colaboradores_enderecos.id_colaborador = colaboradores.id
                                        AND colaboradores_enderecos.eh_endereco_padrao = 1
                                    WHERE usuarios.token=:token");
        $query->bindParam(':token', $token);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @see https://github.com/mobilestock/backend/issues/105
     */
    public static function buscaIDColaboradorComTokenTemporario(string $token_temporario)
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare("SELECT
                                        COALESCE(usuarios.id,0) id_usuario,
                                        COALESCE(usuarios.nome,0) nome,
                                        COALESCE(usuarios.nivel_acesso,0) nivel_acesso,
                                        COALESCE(usuarios.id_colaborador,0) id_colaborador,
                                        usuarios.token_temporario,
                                        usuarios.data_token_temporario,
                                        NOW() BETWEEN usuarios.data_token_temporario
                                            AND usuarios.data_token_temporario + INTERVAL 1 HOUR token_valido,
                                        colaboradores.regime,
                                        0 qtd_compras
                                    FROM usuarios
                                    INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
                                    WHERE usuarios.token_temporario='$token_temporario'");
        $query->execute();
        $resultadoFetch = $query->fetch(PDO::FETCH_ASSOC);

        if (!$resultadoFetch) {
            return false;
        }

        if ((bool) $resultadoFetch['token_valido']) {
            return $resultadoFetch;
        } else {
            $idColaborador = $resultadoFetch['id_colaborador'];
            $notificacoes = new NotificacaoRepository();
            $notificacoes->enviar(
                [
                    'colaboradores' => [1],
                    'titulo' => 'Recuperação de senha',
                    'imagem' => null,
                    'mensagem' => "O colaborador - $idColaborador tentou recuperar sua senha com um link expirado.",
                    'tipoMensagem' => 'Z',
                    'tipoFrete' => 0,
                    'destino' => 'MS',
                ],
                $conexao
            );

            throw new Exception('Link expirado');
        }
    }

    public function buscaUsuarioPhone(int $phone): array
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('SELECT * FROM usuarios WHERE usuarios.telefone=:telefone');
        $query->bindParam(':telefone', $phone);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    /**
     * Busca Registro por CNPJ/CPF do Usuário
     */
    // public function buscaUsuarioCnpj(string $cnpj): Object
    // {
    //     $conexao = Conexao::criarConexao();
    //     $consulta = "SELECT * FROM usuarios WHERE usuarios.cnpj='{$cnpj}'";
    //     $query = $conexao->prepare($consulta);
    //     $query->execute();
    //     $result = $query->fetch(PDO::FETCH_ASSOC);
    //     $usuario = new Usuario();
    //     $usuario->id = (int) $result['id'];
    //     $usuario->nivel_acesso = (int) $result['nivel_acesso'];
    //     $usuario->id_colaborador = (int) $result['id_colaborador'];
    //     $usuario->bloqueado = (int) $result['bloqueado'];
    //     $usuario->online = (int) $result['online'];
    //     $usuario->cnpj = $result['cnpj'];
    //     $usuario->nome = $result['nome'];
    //     $usuario->senha = $result['senha'];
    //     $usuario->telefone = $result['telefone'];
    //     $usuario->email = $result['email'];
    //     $usuario->token = $result['token'];
    //     return $usuario;
    // }
    /**
     * Busca Registro por E-mail do Usuário
     */
    public function buscaUsuarioEmail(string $email): array
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('SELECT * FROM usuarios WHERE usuarios.email=:email');
        $query->bindParam(':email', $email);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Exlui Registro da Tabela Usuario
     */
    public function deletar(object $object): bool
    {
        return true;
    }

    /**
     * Retorna se existe ou não registro com CNPJ/CPF
     */

    public function ExisteCNPJ(string $cnpj): string
    {
        $query = "SELECT usuarios.id FROM usuarios WHERE usuarios.cnpj='{$cnpj}'";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['id'];
        } else {
            return '0';
        }
    }

    public function existeToken(string $token)
    {
        $query = "SELECT usuarios.id, usuarios.id_colaborador FROM usuarios WHERE token='{$token}'";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['id'];
        } else {
            return '0';
        }
    }

    public function existeTokenMaquina(string $token): string
    {
        $conexao = Conexao::criarConexao();

        $query = "SELECT
                usuarios.id
            FROM usuarios
            WHERE usuarios.token = :token";

        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);

        return $retorno['id'] ?? '0';
    }

    public function redefinirSenha(string $cnpj, string $senha)
    {
        $query = "UPDATE usuarios SET usuarios.senha = '{$senha}' WHERE usuarios.cnpj='{$cnpj}';";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['id'];
        } else {
            return '0';
        }
    }
    // public function buscaUsuarioIdColaborador($id_colaborador)
    // {
    //     $query = "SELECT * FROM usuarios WHERE id_colaborador = {$id_colaborador} LIMIT 1";
    //     $conexao = Conexao::criarConexao();
    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();
    //     $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $result;
    // }
    // public function atualizaEmailUsuario($id, $email)
    // {
    //     $query = "UPDATE usuarios SET usuarios.email='{$email}' WHERE usuarios.id={$id}";
    //     $conexao = Conexao::criarConexao();

    //     if ($conexao->query($query)->execute()) {
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }

    public static function usuarioTemEmail(int $idUsuario): bool
    {
        $dados = Conexao::criarConexao()
            ->query('SELECT usuarios.email FROM usuarios WHERE usuarios.id = ' . $idUsuario)
            ->fetch(PDO::FETCH_ASSOC);
        if (!$dados || empty($dados)) {
            return false;
        }

        return (bool) $dados['email'];
    }

    public static function retornaToken(PDO $conexao, int $idUsuario)
    {
        $query = 'SELECT usuarios.token FROM usuarios WHERE usuarios.id=:idUsuario';
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function retornaTokenTemporario(PDO $conexao, int $idUsuario)
    {
        $query =
            'SELECT usuarios.token_temporario, usuarios.data_token_temporario, usuarios.id_colaborador FROM usuarios WHERE usuarios.id=:idUsuario';
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        /**
         * Issue 2664
         * https://github.com/mobilestock/backend/issues/161
         */
        $dataAgora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $dataToken = new DateTime($resultado['data_token_temporario'], new DateTimeZone('America/Sao_Paulo'));
        $tempoDuracaoToken = $dataAgora->diff($dataToken);
        $tempoQueOTokenFoiGerado = $tempoDuracaoToken->h;

        if ($tempoQueOTokenFoiGerado < 1) {
            return $resultado;
        } else {
            return false;
        }
    }

    /**
     * @return array|false
     */
    public static function buscaDiasFaltaParaDesbloquearBotaoAtualizadaDataEntradaProdutos(
        PDO $conexao,
        int $idColaborador
    ) {
        $stmt = $conexao->prepare(
            "SELECT
                IF(
                    reputacao_fornecedores.reputacao = '" .
                CatalogoFixoService::TIPO_MELHOR_FABRICANTE .
                "',
                    COALESCE((SELECT configuracoes.qtd_dias_impulsionar_produtos_melhores_fabricantes FROM configuracoes LIMIT 1), 3),
                    COALESCE((SELECT configuracoes.qtd_dias_impulsionar_produtos_normal FROM configuracoes LIMIT 1), 7)
                ) - (
                    COALESCE(
                        DATEDIFF(CURDATE(), colaboradores.data_botao_atualiza_produtos_entrada),
                        IF(
                            reputacao_fornecedores.reputacao = '" .
                CatalogoFixoService::TIPO_MELHOR_FABRICANTE .
                "',
                            COALESCE((SELECT configuracoes.qtd_dias_impulsionar_produtos_melhores_fabricantes FROM configuracoes LIMIT 1), 3),
                            COALESCE((SELECT configuracoes.qtd_dias_impulsionar_produtos_normal FROM configuracoes LIMIT 1), 7)
                        )
                    )
                ) dias
            FROM colaboradores
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :idColaborador"
        );
        $stmt->bindValue(':idColaborador', $idColaborador);
        $stmt->execute();
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

        $consulta['dias'] = (int) $consulta['dias'];
        return $consulta;
    }

    public static function atualizadaDataEntradaProdutosTodos(PDO $conexao, int $idColaborador): void
    {
        $query = "UPDATE produtos
                    SET produtos.data_up = NOW()
                WHERE produtos.id_fornecedor = :idColaborador
 	                AND EXISTS(SELECT 1 from estoque_grade WHERE estoque_grade.id_produto = produtos.id AND estoque_grade.estoque > 0);

                UPDATE colaboradores
                    SET colaboradores.data_botao_atualiza_produtos_entrada = NOW()
                WHERE colaboradores.id = :idColaborador;";
        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function atualizaAutenticacaoUsuario(
        PDO $conexao,
        int $idUsuario,
        ?string $senha,
        ?string $email
    ): void {
        if (!$senha && !$email) {
            throw new Exception('Email e senha inválidos!');
        }

        $set = '';
        $where = '';
        $params = [':id_usuario' => $idUsuario];
        if ($senha) {
            $set .= ', usuarios.senha = :senha';
            $where .= ' AND usuarios.senha IS NULL';
            $params['senha'] = password_hash($senha, PASSWORD_ARGON2ID);
        }
        if ($email) {
            $set .= ', usuarios.email = :email, colaboradores.email = :email';
            $params['email'] = $email;
        }

        $stmt = $conexao->prepare(
            "UPDATE usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            SET usuarios.nome = usuarios.nome $set
            WHERE usuarios.id = :id_usuario $where"
        );

        $stmt->execute($params);
    }
    public static function buscaCategoriaUsuario(PDO $conexao, int $idUsuario): array
    {
        $dadosDoUsuario = UsuarioService::buscaCategoriaDoUsuarioLogado($conexao, $idUsuario);
        $permissoes = explode(',', $dadosDoUsuario['permissao']);
        $ehAdm = (bool) array_filter($permissoes, fn(int $permissao): bool => $permissao >= 50 && $permissao < 59);
        $ehTransportador = (bool) array_filter(
            $permissoes,
            fn(int $permissao): bool => $permissao >= 20 && $permissao < 29
        );
        $ehPontoMovel = in_array(62, $permissoes);

        $categoriaDoUsuario = 'INDEFINIDO';
        if ($ehAdm) {
            $categoriaDoUsuario = 'ADM';
        }
        if ($ehTransportador) {
            $categoriaDoUsuario = 'TRANSPORTADOR';
        }
        // if ($dadosDoUsuario["categoria"] === 'MS' && !$ehAdm) $categoriaDoUsuario = 'Ponto';
        if ($dadosDoUsuario['categoria'] === 'ML') {
            $categoriaDoUsuario = 'PONTO_RETIRADA';
        }
        if ($dadosDoUsuario['categoria'] === 'ML' && $ehPontoMovel) {
            $categoriaDoUsuario = 'PONTO_MOVEL';
        }

        return [
            'dados_usuario' => $dadosDoUsuario,
            'cateoria_usuario' => $categoriaDoUsuario,
            'id_colaborador' => (int) $dadosDoUsuario['id_colaborador'],
        ];
    }
}
