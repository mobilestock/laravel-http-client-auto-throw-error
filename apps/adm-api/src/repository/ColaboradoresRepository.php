<?php

namespace MobileStock\repository;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\DB;
use MobileStock\helper\Globals;
use MobileStock\model\Colaborador;
use MobileStock\model\ModelInterface;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class ColaboradoresRepository implements RepositoryInterface
{
    public static function salvaFotoS3(array $foto_perfil, int $id_colaborador)
    {
        require_once __DIR__ . '/../../controle/produtos-insere-fotos.php';
        $img_extensao = ['jpg', 'JPG', 'jpeg', 'JPEG'];
        $extensao = explode('/', $foto_perfil['type'])[1];

        if ($foto_perfil['name'] == '' && !$foto_perfil['name']) {
            throw new InvalidArgumentException('Imagem inválida');
        }

        if (!in_array($extensao, $img_extensao)) {
            throw new InvalidArgumentException("Sistema permite apenas imagens com extensão '.jpg'.");
        }

        $nomeimagem =
            PREFIXO_LOCAL . 'imagem_perfil_' . rand(0, 100) . '_' . $id_colaborador . '_' . date('dmYhms') . '.webp';
        $caminhoImagens = 'https://cdn-fotos.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;

        upload($foto_perfil['tmp_name'], $nomeimagem, 800, 800);

        try {
            $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
        } catch (Exception $e) {
            throw new \DomainException('Erro ao conectar com o servidor');
        }

        try {
            $s3->putObject([
                'Bucket' => 'mobilestock-fotos',
                'Key' => $nomeimagem,
                'SourceFile' => __DIR__ . '/../../downloads/' . $nomeimagem,
            ]);
        } catch (S3Exception $e) {
            throw new \DomainException('Erro ao enviar imagem');
        }

        unlink(__DIR__ . '/../../downloads/' . $nomeimagem);
        return $caminhoImagens;
    }

    public static function deletaFotoS3(string $nomeFoto)
    {
        $offset = mb_strlen($_ENV['URL_CDN']) + 19;
        $nomeimagem = mb_substr($nomeFoto, $offset);
        $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
        $s3->deleteObject([
            'Bucket' => 'mobilestock-fotos',
            'Key' => $nomeimagem,
        ]);
    }

    // public function buscaColaborador(int $id_colaborador): Object
    // {
    //     $consulta = "SELECT * FROM colaboradores WHERE colaboradores.id={$id_colaborador} ";
    //     $conexao = Conexao::criarConexao();
    //     $query = $conexao->prepare($consulta);
    //     $query->execute();
    //     $result = $query->fetch(PDO::FETCH_ASSOC);
    //     $colaborador = new Colaborador(
    //         $result['id'],
    //         $result['regime'],
    //         $result['bloqueado'],
    //         $result['vendedor'],
    //         $result['tipo_tabela'],
    //         $result['tipo_documento'],
    //         $result['cond_pagamento'],
    //         $result['em_uso'],
    //         $result['transportadora'],
    //         $result['emite_nota'],
    //         $result['tipo_envio'],
    //         $result['tipo_pagamento_frete'],
    //         $result['auto_cadastro'],
    //         $result['total_pontos'],
    //         $result['politica_empresa']
    //     );
    //     $colaborador->setRazao_social($result['razao_social']);
    //     $colaborador->setBairro($result['bairro']);
    //     $colaborador->setCep($result['cep']);
    //     $colaborador->setCidade($result['cidade']);
    //     $colaborador->setCnpj($result['cnpj']);
    //     $colaborador->setCpf($result['cpf']);
    //     $colaborador->setComplemento($result['complemento']);
    //     $colaborador->setPonto_de_referencia($result['ponto_de_referencia']);
    //     $colaborador->setData_cadastro($result['data_cadastro']);
    //     $colaborador->setData_painel_ilimitado($result['data_painel_ilimitado']);
    //     $colaborador->setEmail($result['email']);
    //     $colaborador->setNumero($result['numero']);
    //     $colaborador->setInscricao($result['inscricao']);
    //     $colaborador->setEndereco($result['endereco']);
    //     $colaborador->setObservacao($result['observacao']);
    //     $colaborador->setUf($result['uf']);
    //     $colaborador->setAlteracao_dados($result['alteracao_dados']);
    //     return $colaborador;
    // }

    /**
     * @deprecated
     */
    public static function busca($params, $limitador = '', PDO $conexao = null)
    {
        $conn = $conexao ?? Conexao::criarConexao();

        $sql = 'SELECT * FROM colaboradores WHERE 1 = 1';
        foreach ($params as $key => $param) {
            $sql .= " AND $key = $param ";
        }

        $sql .= ' ' . $limitador;
        $arrayColaboradores = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (sizeof($arrayColaboradores) === 1) {
            $arrColaborador = $arrayColaboradores[0];
            $colaborador = Colaborador::hidratar($arrColaborador);
            return $colaborador;
        }

        $listaObjetosColaboradores = [];
        foreach ($arrayColaboradores as $colaborador) {
            $listaObjetosColaboradores[] = Colaborador::hidratar($colaborador);
        }
        return $listaObjetosColaboradores;
    }

    public static function consultaSaldoColaboradorMeuLook(): array
    {
        $consulta = FacadesDB::selectOne(
            "SELECT saldo_cliente(:id_cliente) saldo,
              saldo_cliente_bloqueado(:id_cliente) saldo_bloqueado,
              (SELECT SUM(lancamento_financeiro_pendente.valor)
               FROM lancamento_financeiro_pendente
               WHERE lancamento_financeiro_pendente.id_colaborador = :id_cliente
                 AND lancamento_financeiro_pendente.tipo = 'P'
		         AND lancamento_financeiro_pendente.origem NOT IN ('TR', 'PC', 'ES')) saldo_comissao;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        $saldo_total = $consulta['saldo'] + $consulta['saldo_comissao'] + $consulta['saldo_bloqueado'];
        return [
            'saldo' => $consulta['saldo'],
            'saldo_bloqueado' => $consulta['saldo_bloqueado'],
            'saldo_comissao' => $consulta['saldo_comissao'],
            'saldo_total' => $saldo_total,
        ];
    }

    /**
     * @param Colaborador $colaborador
     */
    public static function salva(ModelInterface $colaborador): void
    {
    }

    public static function formataCpfCnpjParaBanco($value)
    {
        return str_replace('.', '', str_replace(',', '', str_replace('/', '', str_replace('-', '', $value))));
    }

    /**
     * @deprecated
     * @param Colaborador $colaborador
     * @param array $params
     * @return ModelInterface
     */
    public static function atualiza(ModelInterface $colaborador, $params = [], PDO $conexao = null): ModelInterface
    {
        $conexao = $conexao ?? Conexao::criarConexao();
        $campos = $colaborador->extrair();
        unset($campos['contasBancarias'], $campos['id']);

        $pontoHorarioFuncionamento = $campos['horarioDeFuncionamento'] ?? null;
        $pontoNome = $campos['nomePonto'] ?? null;
        unset($campos['horarioDeFuncionamento'], $campos['nomePonto']);

        $sql = 'UPDATE colaboradores SET tipo = tipo, ';
        $bindValues = [];
        foreach ($campos as $campo => $value) {
            if ($campo === 'cnpj' || $campo === 'cpf' || $campo === 'cep') {
                $value = self::formataCpfCnpjParaBanco($value);
            }

            $sql .= "$campo = :$campo,";
            $bindValues = array_merge($bindValues, [":$campo" => $value]);
        }
        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1) . ' WHERE id = :id_colaborador;';

        $sql .=
            'UPDATE usuarios SET cnpj = :cnpj_ou_cpf, email = :email' .
            (isset($campos['telefone']) ? ', telefone = :telefone ' : ' ') .
            'WHERE id_colaborador = :id_colaborador';
        if (isset($campos['telefone'])) {
            $bindValues[':telefone'] = $campos['telefone'];
        }
        $bindValues['id_colaborador'] = $colaborador->getId();
        $bindValues['cnpj_ou_cpf'] =
            $colaborador->getRegime() == 1
                ? self::formataCpfCnpjParaBanco($colaborador->getCnpj())
                : self::formataCpfCnpjParaBanco($colaborador->getCpf());
        DB::exec($sql, $bindValues, $conexao);

        if ($pontoNome) {
            DB::exec(
                'UPDATE tipo_frete SET nome = :nome, horario_de_funcionamento = :horario_de_funcionamento WHERE id_colaborador = :id_colaborador',
                [
                    ':nome' => $pontoNome,
                    ':horario_de_funcionamento' => $pontoHorarioFuncionamento,
                    ':id_colaborador' => $colaborador->getId(),
                ],
                $conexao
            );
        }

        return $colaborador;
    }

    public static function listar(
        array $parametros = ['where' => [], 'group' => '', 'order' => [], 'pagina' => 1, 'limit' => 100],
        $transacao = ''
    ): array {
        $conexao = $transacao instanceof PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof PDO) {
            throw new \PDOException('Opa faltou a conexao com o banco de dados.', 500);
        }

        $paramentrosPermitidos = ['where', 'group', 'order', 'pagina', 'limit'];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos)) {
                throw new \PDOException(
                    'Parametro de busca não permitido, use where, group, order, pagina ou limite para efetuar sua busca',
                    400
                );
            }
        }

        $query = 'SELECT * FROM colaboradores WHERE 1 = 1';
        $WHERE = $parametros['where'] ?? [];
        $GROUP = $parametros['group'] ?? '';
        $ORDER = $parametros['order'] ?? [];
        $PAGINA = $parametros['pagina'] ?? 1;
        $LIMIT = $parametros['limit'] ?? 100;

        if (!!$WHERE && Count($parametros['where']) !== 0) {
            foreach ($WHERE as $campo => $busca) {
                if (is_array($busca)) {
                    $condicao = mb_strtoupper($busca[0]);

                    switch ($condicao) {
                        case 'LIKE':
                            $query .= " AND $campo $condicao '%$busca[1]%' ";
                            break;

                        case '>=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '<=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '<>':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        default:
                            break;
                    }
                } else {
                    $query .= " AND $campo = '$busca'";
                }
            }
        }

        if (!!$GROUP) {
            $query .= " GROUP BY {$GROUP} ";
        }

        if (!!$ORDER && Count($ORDER) >= 1) {
            $query .= ' ORDER BY ';

            foreach ($ORDER as $ordem => $sentido) {
                $ordenador = mb_strtoupper($sentido);

                if ($ordem) {
                    $query .= " $ordem $ordenador, ";
                } else {
                    $query .= " $sentido, ";
                }
            }

            $query .= ' 1';
        }

        $offset = $PAGINA ? $PAGINA * $LIMIT - $LIMIT : 0;

        $query .= " LIMIT $LIMIT OFFSET $offset";

        if ($resultado = $conexao->query($query)) {
            $listaNotificacoes = $resultado->fetchAll(PDO::FETCH_ASSOC);

            $listaObjetosNotificacao = [];

            foreach ($listaNotificacoes as $notificacao) {
                $listaObjetosNotificacao[] = Colaborador::hidratar($notificacao);
            }

            return $listaObjetosNotificacao;
        }
    }

    public static function deleta(ModelInterface $model): void
    {
        // TODO: Implement deletar() method.
    }

    /**
     * @deprecated
     *  Esse método foi depreciado devido à sua estrutura e à quantidade de lugares onde é chamado.
     *  Favor criar um novo método se precisar fazer alguma alteração na busca destas informações.
     */
    public static function buscaColaboradorPorID(int $id_cliente): array
    {
        $query = "SELECT
                    colaboradores.id,
                    colaboradores.cpf,
                    colaboradores.cnpj,
                    colaboradores.razao_social nome,
                    colaboradores.usuario_meulook,
                    COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                    colaboradores_enderecos.logradouro,
                    colaboradores_enderecos.numero,
                    colaboradores_enderecos.complemento,
                    colaboradores_enderecos.ponto_de_referencia,
                    colaboradores_enderecos.bairro,
                    colaboradores_enderecos.id_cidade,
                    colaboradores_enderecos.cidade,
                    colaboradores_enderecos.uf,
                    colaboradores_enderecos.cep,
                    colaboradores_enderecos.esta_verificado,
                    colaboradores.telefone,
                    colaboradores.telefone2,
                    colaboradores.email
                  FROM colaboradores
                  LEFT JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                  WHERE colaboradores.id = $id_cliente;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query)->fetch(PDO::FETCH_ASSOC);
        return $resultado;
    }

    public static function buscaFotoPerfil(int $idCliente)
    {
        $sql = "SELECT COALESCE(foto_perfil,'') foto FROM colaboradores WHERE id={$idCliente};";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($sql);
        return $resultado->fetch(PDO::FETCH_ASSOC);
    }

    public static function qtdPedidosEntregues(PDO $conexao, int $idCliente): int
    {
        $stmt = $conexao->prepare(
            "SELECT
                         COUNT(entregas_faturamento_item.id_entrega)
                     FROM entregas_faturamento_item
                     WHERE entregas_faturamento_item.id_cliente = ?
                       AND entregas_faturamento_item.situacao = 'EN'"
        );
        $stmt->execute([$idCliente]);
        $resultado = $stmt->fetchColumn();

        return $resultado;
    }

    public function validaIdUsuarioNomeMeulook(PDO $conexao, ?string $idUsuario, ?string $nomeUsuario)
    {
        $consulta = $conexao
            ->query(
                "SELECT
            colaboradores.id,
            colaboradores.usuario_meulook
        FROM colaboradores
        WHERE
            colaboradores.id = $idUsuario AND
            colaboradores.usuario_meulook = '$nomeUsuario'"
            )
            ->fetch(PDO::FETCH_ASSOC);

        return !empty($consulta);
    }

    /**
     * Pega os níveis de permissão do $idCliente e compara com a tabela
     * 'nivel_permissao'.
     *
     * @param int $idUsuario ID de colaborador do usuário
     * @param PDO $conexao Conexão com o banco de dados
     *
     * @return array Array com os níveis de permissão do usuário no formato ex: ['CLIENTE', 'INTERNO', 'PONTO']
     */
    public static function buscaPermissaoUsuario(PDO $conexao, int $idCliente)
    {
        $permissoes = [];
        $permissoesUsuario = $conexao
            ->query(
                "SELECT
                                            usuarios.permissao
                                       FROM colaboradores
                                       JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                                       WHERE colaboradores.id = $idCliente"
            )
            ->fetch(PDO::FETCH_ASSOC);
        $permissoesUsuario = explode(',', $permissoesUsuario['permissao']);

        $permissoesSistema = $conexao
            ->query('SELECT nivel_value, categoria FROM nivel_permissao')
            ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($permissoesSistema as $permissao) {
            if (in_array($permissao['nivel_value'], $permissoesUsuario)) {
                $permissoes[] = $permissao['categoria'];
            }
        }
        return $permissoes;
    }

    /**
     * Pega o id_colaborador do dono de algum id de publicação.
     *
     * @param int $idPublicacao ID da publicação na tabela 'publicacoes'
     * @param PDO $conexao Conexão com o banco de dados
     *
     * @return int id_colaborador
     */
    public static function buscaDonoPublicacao(PDO $conexao, int $idPublicacao)
    {
        $ownerID = $conexao
            ->query("SELECT id_colaborador FROM publicacoes WHERE id = $idPublicacao")
            ->fetch(PDO::FETCH_ASSOC)['id_colaborador'];
        return $ownerID;
    }

    /**
     * @deprecated
     * @see Usar: MobileStock\model\ColaboradorModel::buscaOuGeraUsuarioMeulook()
     */
    public static function buscaNomeUsuarioMeuLook(PDO $conexao, int $idCliente)
    {
        $consultaSql = "SELECT
                            colaboradores.usuario_meulook,
                            COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil
                        FROM colaboradores
                        WHERE colaboradores.id = $idCliente";

        $consulta = $conexao->query($consultaSql)->fetch(PDO::FETCH_ASSOC);

        if (empty($consulta)) {
            throw new BadRequestHttpException('Usuário inexistente');
        }

        if (!$consulta['usuario_meulook']) {
            self::gerarNomeUsuarioMeuLook($conexao, $idCliente);
        }

        return $conexao->query($consultaSql)->fetch(PDO::FETCH_ASSOC);
    }

    public static function gerarNomeUsuarioMeuLook(PDO $conexao, int $idCliente)
    {
        return $conexao->exec("UPDATE colaboradores
        SET colaboradores.usuario_meulook = LOWER(CONCAT(
            REGEXP_REPLACE(REPLACE(colaboradores.razao_social, ' ', '-'), '(?![a-zA-z ]).{1}', ''),
            '.',
            colaboradores.id
        ))
        WHERE
            colaboradores.id = $idCliente AND
            colaboradores.usuario_meulook IS NULL");
    }

    public static function consultaColaboradoresAutocomplete(PDO $conexao, string $pesquisa)
    {
        $pesquisa = mb_strtolower(trim($pesquisa));
        $pesquisa = str_replace(' ', '', $pesquisa);

        $consulta = $conexao->prepare(
            "SELECT
                colaboradores.id,
                colaboradores.usuario_meulook,
                colaboradores.razao_social,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil
            FROM colaboradores
            WHERE LENGTH(COALESCE(colaboradores.usuario_meulook, '')) > 2
                AND (
                    LOCATE(:pesquisa, REPLACE(LOWER(colaboradores.usuario_meulook), ' ', '')) OR
                    LOCATE(:pesquisa, REPLACE(LOWER(colaboradores.razao_social), ' ', '')) OR
                    LOCATE(:pesquisa, colaboradores.id)
                )
            GROUP BY colaboradores.id
            ORDER BY colaboradores.id = :pesquisa DESC
            LIMIT 25"
        );
        $consulta->bindValue(':pesquisa', $pesquisa);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function buscaTipoFretePadrao(): ?array
    {
        $resultado = FacadesDB::selectOne(
            "
            SELECT
                tipo_frete.tipo_ponto,
                tipo_frete.id_colaborador,
                colaboradores.id_tipo_entrega_padrao,
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf,
                tipo_frete_colaboradores.foto_perfil,
                tipo_frete.nome,
                tipo_frete_colaboradores.usuario_meulook
            FROM colaboradores
            JOIN tipo_frete ON tipo_frete.id = colaboradores.id_tipo_entrega_padrao
            JOIN colaboradores tipo_frete_colaboradores ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
            JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE colaboradores.id = :idCliente
                AND tipo_frete.categoria <> 'PE'
            ",
            ['idCliente' => Auth::user()->id_colaborador]
        );

        if (empty($resultado)) {
            return null;
        }

        if ($resultado['id_tipo_entrega_padrao'] === 2) {
            $resultado['tipo_ponto'] = 'ENVIO_TRANSPORTADORA';
        }

        return $resultado;
    }

    public static function atualizaEnderecoPonto(PDO $conexao, int $idCliente, string $mensagemNova): void
    {
        $conexao
            ->prepare(
                "UPDATE tipo_frete
                SET mensagem = :mensagemNova
            WHERE id_colaborador = :idCliente"
            )
            ->execute([
                ':idCliente' => $idCliente,
                ':mensagemNova' => $mensagemNova,
            ]);
    }

    /**
     * Adiciona novas permissões para o usuário.
     * usar nivel_value da tabela 'nivel_permissao' no parametro $permissoes
     *
     * @param PDO $conexao Conexão com o banco de dados
     * @param int $idUsuario ID de usuario para adicionar permissões
     * @param array $permissoes Array com as permissões a serem adicionadas
     * @throws Exception Caso a permissão não seja um número de permissão
     * @see issue: https://github.com/mobilestock/backend/issues/129
     * @issue https://github.com/mobilestock/backend/issues/516
     */
    public static function adicionaPermissaoUsuario(PDO $conexao, int $idUsuario, array $permissoes)
    {
        foreach ($permissoes as $permissao) {
            if (!is_numeric($permissao)) {
                throw new Exception('Permissão inválida');
            }
        }
        $consultaPermissoes = $conexao->prepare('SELECT permissao FROM usuarios WHERE id = :id');
        $consultaPermissoes->execute([':id' => $idUsuario]);
        $permissoesAtuais = $consultaPermissoes->fetch(PDO::FETCH_ASSOC)['permissao'];

        $permissoesAtuais = empty($permissoesAtuais) ? [] : explode(',', $permissoesAtuais);

        $permissoes = array_unique(array_merge($permissoesAtuais, $permissoes));
        asort($permissoes, SORT_NUMERIC);
        $permissoes = implode(',', $permissoes);
        if ($permissoes === implode(',', $permissoesAtuais)) {
            return false;
        }
        $stmt = $conexao->prepare('UPDATE usuarios SET permissao = :permissao WHERE id = :id');
        $stmt->execute([
            ':permissao' => $permissoes,
            ':id' => $idUsuario,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Já possui a permissão!');
        }
        return true;
    }

    /**
     * Remove permissões do usuário.
     *
     * @param PDO $conexao Conexão com o banco de dados
     * @param int $idUsuario ID de usuario para adicionar permissões
     * @param array $permissoes Array com as permissões a serem removidas
     * @throws InvalidArgumentException Caso a dê erro ao tentar remover permissões do usuário
     */
    public static function removePermissaoUsuario(int $idUsuario, array $permissoes): bool
    {
        foreach ($permissoes as $permissao) {
            if (!is_numeric($permissao)) {
                throw new NotAcceptableHttpException('Permissão inválida');
            }
        }

        $binds = [':id_usuario' => $idUsuario];

        $sqlPermissoes = "SELECT CONCAT('[', usuarios.permissao, ']') AS `json_permissao`
                FROM usuarios
                WHERE usuarios.id = :id_usuario";
        $permissoesAtuais = FacadesDB::selectOneColumn($sqlPermissoes, $binds);
        $seraoRemovidas = array_intersect($permissoesAtuais, $permissoes);
        if (empty($seraoRemovidas)) {
            return true;
        }

        $permissoes = array_diff($permissoesAtuais, $permissoes);
        if (empty($permissoes)) {
            throw new InvalidArgumentException('Usuário não pode ficar sem permissão');
        }

        asort($permissoes, SORT_NUMERIC);
        $permissoes = implode(',', $permissoes);
        $binds[':permissao'] = $permissoes;
        $sqlUpdate = 'UPDATE usuarios
                      SET usuarios.permissao = :permissao
                      WHERE usuarios.id = :id_usuario';
        $rowCount = FacadesDB::update($sqlUpdate, $binds);

        if ($rowCount !== 1) {
            throw new InvalidArgumentException('Erro ao tentar remover permissões do usuário.');
        }
        return true;
    }

    // public static function pesquisaColaborador(
    //     \PDO $conexao,
    //     ?string $nome,
    //     ?int $idProduto,
    //     ?int $situacao,
    //     ?int $tipo,
    //     ?string $ordenar,
    //     ?string $dataInicio,
    //     ?string $dataFim
    // ) {
    //     $where = '';
    //     $bind = [];
    //     if($nome) {
    //         $where .= " AND cliente.razao_social REGEXP :nome";
    //         $bind[':nome'] = $nome;
    //     }

    //     if ($idProduto) {
    //         $where .= " AND atendimento_cliente.id_produto REGEXP :id_produto";
    //         $bind[':id_produto'] = $idProduto;
    //     }

    //     if($situacao) {
    //         $where .= " AND atendimento_cliente.situacao = :situacao";
    //         $bind[':situacao'] = $situacao;
    //     }

    //     if($tipo) {
    //         if ($tipo) $where .= " AND atendimento_cliente.id_tipo_atendimento = :tipo";
    //         $bind[':tipo'] = $tipo;
    //     }

    //     if ($dataInicio && $dataFim) {
    //         $where .= " AND (
    //             DATE(atendimento_cliente.data_inicio) BETWEEN :data_inicio AND :data_fim OR
    //             DATE(atendimento_cliente.data_final) BETWEEN :data_inicio AND :data_fim
    //         )";
    //         $bind[':data_inicio'] = $dataInicio;
    //         $bind[':data_fim'] = $dataFim;
    //     } else if ($dataInicio) {
    //         $where .= " AND (
    //             DATE(atendimento_cliente.data_inicio) = :data_inicio OR
    //             DATE(atendimento_cliente.data_final) = :data_inicio
    //         )";
    //         $bind['data_inicio'] = $dataInicio;
    //     } else if ($dataFim) {
    //         $where .= " AND (
    //             DATE(atendimento_cliente.data_inicio) = :data_fim OR
    //             DATE(atendimento_cliente.data_final) = :data_fim
    //         )";
    //         $bind['data_fim'] = $dataFim;
    //     }

    //     $order = 'ORDER BY ';
    //     switch ($ordenar) {
    //         case '1':
    //             $order .= 'atendimento_cliente.id ASC';
    //             break;
    //         case '2':
    //             $order .= 'atendimento_cliente.situacao = 4 DESC, atendimento_cliente.id ASC';
    //             break;
    //         case '3':
    //             $order .= 'atendimento_cliente.id DESC';
    //             break;
    //         case '4':
    //             $order .= 'atendimento_cliente.situacao = 4 DESC, atendimento_cliente.id DESC';
    //             break;
    //         default:
    //             $order = '';
    //     }

    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             atendimento_cliente.id,
    //             cliente.id id_cliente,
    //             cliente.razao_social nome_cliente,
    //             tipo_atendimento.nome problema,
    //             DATE_FORMAT(atendimento_cliente.data_inicio, '%d/%m/%Y %H:%i:%s') data_inicio,
    //             CASE situacao
    //                 WHEN 0 THEN 'Fila'
    //                 WHEN 1 THEN 'Aceito'
    //                 WHEN 2 THEN 'Reprovado'
    //                 WHEN 3 THEN 'Aprovado'
    //                 WHEN 4 THEN 'Finalizado'
    //                 ELSE 'Aberto'
    //             END situacao,
    //             DATE_FORMAT(atendimento_cliente.data_final, '%d/%m/%Y %H:%i:%s') data_atualizacao,
    //             atendente.nome nome_atendente,
    //             produtos.id id_produto,
    //             CONCAT(
    //                 produtos.id,
    //                 ' - ',
    //                 IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)
    //             ) nome_produto
    //         FROM atendimento_cliente
    //         INNER JOIN tipo_atendimento ON tipo_atendimento.id = atendimento_cliente.id_tipo_atendimento
    //         INNER JOIN colaboradores cliente ON cliente.id = atendimento_cliente.id_cliente
    //         LEFT JOIN usuarios atendente ON atendente.id = atendimento_cliente.id_colaborador
    //         LEFT JOIN produtos ON produtos.id = atendimento_cliente.id_produto
    //         WHERE 1=1 $where
    //         $order"
    //     );
    //     $stmt->execute($bind);
    //     $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $consulta;
    // }
    public static function atualizaNumeroTelefone(PDO $conexao, int $idColaborador, int $telefone): void
    {
        $sql = $conexao->prepare(
            "UPDATE colaboradores
            SET colaboradores.telefone = :telefone
            WHERE colaboradores.id = :id_colaborador;"
        );
        $sql->bindValue(':telefone', $telefone, PDO::PARAM_INT);
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new Exception('Telefone atualizado errado');
        }
    }

    /**
     * @deprecated
     * @see Usar: \model\ColaboradorEndereco::buscaEnderecoPadraoColaborador()
     */
    public static function enderecoParaTransferencia(PDO $conexao, int $idColaborador): array
    {
        $sql = $conexao->prepare(
            "SELECT
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.cep,
                colaboradores_enderecos.cidade
            FROM colaboradores_enderecos
            WHERE colaboradores_enderecos.id_colaborador = :id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LIMIT 1;"
        );
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();
        $endereco = $sql->fetch(PDO::FETCH_ASSOC);

        return $endereco;
    }
}

//}
