<?php

namespace MobileStock\service\EntregaService;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\Entrega\EntregasDevolucoesItemModel;
use MobileStock\model\Lancamento;
use MobileStock\model\PacReverso;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\MessageService;
use MobileStock\service\TrocaFilaSolicitacoesService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EntregasDevolucoesServices extends EntregasDevolucoesItemServices
{
    public function listaDevolucoesPonto(string $pesquisa, bool $pesquisaEspecificaUuid): array
    {
        $sql = "SELECT
                    entregas_devolucoes_item.id AS `id_devolucao`,
                    entregas_devolucoes_item.id_produto,
                    entregas_devolucoes_item.situacao,
                    entregas_devolucoes_item.nome_tamanho,
                    entregas_devolucoes_item.tipo,
                    entregas_devolucoes_item.uuid_produto,
                    entregas_devolucoes_item.origem,
                    entregas_devolucoes_item.data_criacao,
                    entregas_devolucoes_item.data_atualizacao,
                    produtos.nome_comercial AS `nome_produto`,
                    produtos.localizacao,
                    (
                        SELECT
                            JSON_OBJECT(
                               'nome',tipo_frete.nome,
                               'telefone',colaboradores.telefone
                            )
                        FROM colaboradores
                        WHERE
                        colaboradores.id = tipo_frete.id_colaborador
                    ) json_ponto,
                    (
                        SELECT JSON_OBJECT(
                            'nome',colaboradores.razao_social,
                            'telefone',colaboradores.telefone
                            )
                        FROM colaboradores
                        WHERE colaboradores.id = produtos.id_fornecedor
                    ) json_fornecedor,
                    (
                        SELECT
                            colaboradores.razao_social
                        FROM colaboradores
                        WHERE
                            colaboradores.id =  (
                                SELECT
                                    logistica_item.id_cliente
                                FROM logistica_item
                                WHERE
                                    logistica_item.uuid_produto = entregas_devolucoes_item.uuid_produto
                                LIMIT 1
                            )
                        LIMIT 1
                    ) nome_cliente_final,
                    JSON_VALUE(
                        transacao_financeiras_metadados.valor,
                        '$.nome_destinatario'
                    ) nome_destinatario,
                    IF(
                        entregas_devolucoes_item.tipo = 'DE',
                        (
                            SELECT
                                troca_pendente_item.descricao_defeito
                            FROM troca_pendente_item
                            WHERE troca_pendente_item.uuid = entregas_devolucoes_item.uuid_produto
                            LIMIT 1
                        ),
                        NULL
                    ) descricao_defeito,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM
                            produtos_foto
                        WHERE
                            produtos_foto.id = entregas_devolucoes_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY
                            produtos_foto.tipo_foto = 'MD' DESC,
                            produtos_foto.tipo_foto = 'LG' DESC
                        LIMIT 1
                    ) foto_produto,
                    (
                        SELECT troca_fila_solicitacoes.descricao_defeito
                        FROM troca_fila_solicitacoes
                        WHERE troca_fila_solicitacoes.uuid_produto = entregas_devolucoes_item.uuid_produto
                    ) descricao_defeito
            FROM tipo_frete
            INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
            INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = entregas_devolucoes_item.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            WHERE
                1 = 1";

        if ($pesquisaEspecificaUuid) {
            $sql .= ' AND entregas_devolucoes_item.uuid_produto = :pesquisa ';
        } else {
            $sql .= " AND entregas_devolucoes_item.situacao = 'PE' ";
        }
        if ($pesquisa && !$pesquisaEspecificaUuid) {
            $sql .= " HAVING CONCAT_WS(
                        ' ',
                        entregas_devolucoes_item.id_produto,
                        entregas_devolucoes_item.nome_tamanho,
                        nome_cliente_final,
                        produtos.nome_comercial
                    ) REGEXP :pesquisa";
        }

        $sql .= " ORDER BY entregas_devolucoes_item.situacao = 'PE' DESC ";
        $bind = [];
        if ($pesquisa) {
            $bind['pesquisa'] = $pesquisa;
        }

        $matriz = DB::select($sql, $bind);

        return $matriz;
    }

    function listaDevolucoesQueNaoChegaramACentral(int $idColaborador): array
    {
        $sql = "SELECT
                    entregas_devolucoes_item.uuid_produto,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = entregas_devolucoes_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                        LIMIT 1
                    ) foto,
                    produtos.nome_comercial,
                    entregas_devolucoes_item.nome_tamanho,
                    entregas_devolucoes_item.id_produto,
                    entregas_devolucoes_item.id_cliente,
                    entregas_devolucoes_item.id_usuario,
                    DATE_FORMAT(entregas_devolucoes_item.data_atualizacao,'%d/%m/%Y %H:%i:%s') data_atualizacao,
                    JSON_OBJECT(
                        'id', tipo_frete.id_colaborador,
                        'nome', colaboradores.razao_social
                    ) json_ponto_responsavel,
                    (
                        SELECT entregas_devolucoes_item.id_usuario = usuarios.id
                        FROM usuarios
                        WHERE usuarios.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                    ) esta_entregue_ao_ponto_de_coleta,
                    transacao_financeiras_metadados.valor json_endereco_metadado,
                    JSON_OBJECT(
                        'bairro', colaboradores_enderecos.bairro,
                        'logradouro', colaboradores_enderecos.logradouro,
                        'numero', colaboradores_enderecos.numero,
                        'complemento', colaboradores_enderecos.complemento,
                        'ponto_de_referencia', colaboradores_enderecos.ponto_de_referencia,
                        'cidade',colaboradores_enderecos.cidade,
                        'uf', colaboradores_enderecos.uf
                    ) json_endereco_colaborador,
                    (
                        SELECT colaboradores.razao_social
                        FROM colaboradores
                        WHERE colaboradores.id = entregas_devolucoes_item.id_cliente
                        LIMIT 1
                    ) nome_cliente,
                    (
                        SELECT colaboradores.telefone
                        FROM colaboradores
                        WHERE colaboradores.id = entregas_devolucoes_item.id_cliente
                        LIMIT 1
                    ) telefone_cliente
                FROM entregas_devolucoes_item
                INNER JOIN transacao_financeiras_metadados ON
                    entregas_devolucoes_item.id_transacao = transacao_financeiras_metadados.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                INNER JOIN produtos ON entregas_devolucoes_item.id_produto = produtos.id
                INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                WHERE
                    (
                        tipo_frete.id_colaborador  = :idColaborador
                        OR tipo_frete.id_colaborador_ponto_coleta = :idColaborador
                    )
                    AND entregas_devolucoes_item.situacao = 'PE'
                ORDER BY esta_entregue_ao_ponto_de_coleta ASC";

        $dados = DB::select($sql, [
            ':idColaborador' => $idColaborador,
        ]);

        $retorno = array_map(function ($item) {
            $item['telefone_destinatario'] =
                isset($item['endereco_metadado']['telefone_destinatario']) &&
                $item['endereco_metadado']['telefone_destinatario'] !== $item['telefone_cliente']
                    ? $item['endereco_metadado']['telefone_destinatario']
                    : $item['telefone_cliente'];

            $item['nome_destinatario'] = $item['endereco_metadado']['nome_destinatario'] ?? null;

            unset($item['endereco_metadado']['telefone_destinatario'], $item['endereco_metadado']['nome_destinatario']);

            $endereco = [];
            foreach ($item['endereco_metadado'] as $campo => $valor) {
                switch (true) {
                    case empty($valor) && !empty($item['endereco_colaborador'][$campo]):
                        $endereco[$campo] = $item['endereco_colaborador'][$campo];
                        break;
                    case empty($valor) && empty($item['endereco_colaborador'][$campo]):
                        $endereco[$campo] = null;
                        break;

                    default:
                        $endereco[$campo] = $valor;
                        break;
                }
            }

            $item['endereco'] = "{$endereco['logradouro']}, {$endereco['numero']} - {$endereco['bairro']}";
            $item['endereco'] .= ", {$endereco['cidade']} - {$endereco['uf']}";
            if (!empty($endereco['complemento'])) {
                $item['endereco'] .= ", {$endereco['complemento']}";
            }
            if (!empty($endereco['ponto_de_referencia'])) {
                $item['endereco'] .= ", {$endereco['ponto_de_referencia']}";
            }

            return $item;
        }, $dados);

        return $retorno;
    }

    public function recebiProdutoDoEntregador(PDO $conexao, string $uuidProduto, int $idUsuario)
    {
        $sql = "UPDATE entregas_devolucoes_item
                SET entregas_devolucoes_item.id_usuario = :id_usuario
                WHERE entregas_devolucoes_item.uuid_produto = :uuid_produto;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $prepare->bindParam(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $prepare->execute();
        if ($prepare->rowCount() === 0) {
            throw new UnprocessableEntityHttpException(
                'Não foi possível atualizar pois você já é o responsável por este produto'
            );
        }
    }
    public function bip(string $uuidProduto): void
    {
        $resultado = EntregasDevolucoesItemModel::fromQuery(
            "SELECT
                entregas_devolucoes_item.uuid_produto
                FROM
                    entregas_devolucoes_item
                WHERE
                    entregas_devolucoes_item.uuid_produto = :uuid_produto
                    AND entregas_devolucoes_item.situacao = 'PE'
            ",
            [':uuid_produto' => $uuidProduto]
        )->first();

        if (empty($resultado)) {
            throw new BadRequestHttpException('Bipagem inválida, Este produto já foi bipado ou não existe.');
        }

        $resultado->situacao = 'CO';
        $resultado->situacao_envio = 'NO';

        $resultado->save();

        $idSolicitacaoTroca = TrocaFilaSolicitacoesService::buscaIdDaFilaDeTrocasPorUuid(DB::getPdo(), $uuidProduto);

        if ($idSolicitacaoTroca <= 0) {
            return;
        }

        $solicitacaoDefeito = TrocaFilaSolicitacoesService::buscaSolicitacaoPorId($idSolicitacaoTroca);

        if (explode(':', $solicitacaoDefeito['descricao_defeito'])[0] === 'PRODUTO_ERRADO') {
            $detalhesDaTroca = EntregasDevolucoesItemServices::buscaDetalhesTrocas($uuidProduto);
            $taxa = ConfiguracaoService::buscarTaxaProdutoErrado();

            $debito = new Lancamento(
                'R',
                1,
                'DT',
                $detalhesDaTroca['id_fornecedor'],
                null,
                $taxa,
                Auth::user()->id,
                12
            );
            $debito->numero_documento = $uuidProduto;
            $debito->transacao_origem = $detalhesDaTroca['id_transacao'];

            LancamentoCrud::salva(DB::getPdo(), $debito);
        }
    }
    public function descontar(
        PDO $conexao,
        string $situacao,
        string $uuidProduto,
        int $idPonto,
        string $categoriaDoUsuario,
        string $descontar,
        int $idUsuario,
        int $idTransacao,
        int $idDevolucao
    ) {
        if (
            $categoriaDoUsuario !== 'ADM' ||
            !in_array($situacao, ['PE', 'VE']) ||
            !in_array($descontar, ['ADM', 'Ponto'])
        ) {
            throw new Exception('Voce não tem permissão para bipar esta devolucao', 400);
        }

        $sql = "SELECT
                tipo_frete.id_colaborador_ponto_coleta,
                (
                    SELECT usuarios.id
                    FROM usuarios
                    WHERE usuarios.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                ) AS `id_usuario`
                FROM tipo_frete
                WHERE
                    tipo_frete.id = :id_ponto;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindValue(':id_ponto', $idPonto, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            throw new Exception('Erro ao identificar o usuario, entre em contato com a equipe de TI.', 400);
        }

        $query = "SELECT
                        SUM(
                            IF(
                                transacao_financeiras_produtos_itens.id_fornecedor = :id_colaborador_ponto_coleta,
                                transacao_financeiras_produtos_itens.preco - transacao_financeiras_produtos_itens.comissao_fornecedor,
                                transacao_financeiras_produtos_itens.preco
                            )
                        )
                FROM transacao_financeiras_produtos_itens
                WHERE transacao_financeiras_produtos_itens.uuid_produto = :uuid_produto";

        $prepare = $conexao->prepare($query);
        $prepare->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $prepare->bindValue(':id_colaborador_ponto_coleta', $dados['id_colaborador_ponto_coleta'], PDO::PARAM_INT);
        $prepare->execute();
        $valorPontos = (float) $prepare->fetchColumn();
        if (!$valorPontos) {
            throw new Exception('Não foi possivel buscar os valores dos produtos.', 400);
        }

        $lancamentoFinanceiro = new Lancamento(
            'R',
            1,
            'DR',
            (int) $dados['id_colaborador_ponto_coleta'],
            date('Y-m-d H:i:s'),
            $valorPontos,
            (int) $dados['id_usuario'],
            1
        );

        $observacaoDesconto =
            $idUsuario === 2 ? 'processo automático para descontar devoluções' : "usuário {$idUsuario}";

        $lancamentoFinanceiro->transacao_origem = $idTransacao;
        $lancamentoFinanceiro->numero_documento = $uuidProduto;
        $lancamentoFinanceiro->observacao = 'Devolucao descontada pelo ' . $observacaoDesconto;
        $lancamento = LancamentoCrud::salva($conexao, $lancamentoFinanceiro);
        if (!$lancamento->id) {
            throw new Exception('Erro ao processar desconto', 400);
        }

        $sql = "SELECT
                    transacao_financeiras_produtos_itens.id_fornecedor,
                    transacao_financeiras_produtos_itens.comissao_fornecedor AS `valor`,
                    transacao_financeiras_produtos_itens.tipo_item
                FROM entregas_devolucoes_item
                INNER JOIN transacao_financeiras_produtos_itens ON entregas_devolucoes_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                WHERE
                    entregas_devolucoes_item.uuid_produto = :uuid_produto
                    AND entregas_devolucoes_item.situacao IN ('PE','VE')
                    AND transacao_financeiras_produtos_itens.id_fornecedor <> :id_colaborador_ponto_coleta
                    AND transacao_financeiras_produtos_itens.sigla_estorno IS NOT NULL
                GROUP BY transacao_financeiras_produtos_itens.id";
        $PrepareDadosDoFornecedor = $conexao->prepare($sql);
        $PrepareDadosDoFornecedor->bindParam(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $PrepareDadosDoFornecedor->bindParam(
            ':id_colaborador_ponto_coleta',
            $dados['id_colaborador_ponto_coleta'],
            PDO::PARAM_INT
        );
        $PrepareDadosDoFornecedor->execute();
        $comissoes = $PrepareDadosDoFornecedor->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comissoes as $comissao) {
            $lancamentoFinanceiro = new Lancamento(
                'P',
                1,
                'DR',
                (int) $comissao['id_fornecedor'],
                date('Y-m-d H:i:s'),
                (float) $comissao['valor'],
                (int) $idUsuario,
                1
            );

            $observacaoDesconto =
                $idUsuario === 2 ? 'processo automático para descontar devoluções' : "usuário {$idUsuario}";

            $lancamentoFinanceiro->observacao =
                'Comissão ' . $comissao['tipo_item'] . ' paga pelo ' . $observacaoDesconto;
            $lancamentoFinanceiro->transacao_origem = $idTransacao;
            $lancamentoFinanceiro->numero_documento = $uuidProduto;

            $lancamento = LancamentoCrud::salva($conexao, $lancamentoFinanceiro);
            if (!$lancamento->id) {
                throw new Exception('Erro ao processar desconto', 400);
            }
        }

        $this->id = $idDevolucao;
        $this->situacao = 'RE';

        if (!$this->atualiza($conexao)) {
            throw new Exception("Tentativa de debito duplicado. UUID: {$uuidProduto}");
        }
    }

    public function relacaoPontoDevolucoes(int $idPonto = 0)
    {
        $somaValores = fn($acc, $item) => ($acc += $item['valor']);
        $retorno = [];
        $binds = [];

        $sql = "SELECT
                    (
                        SELECT MAX(entregas.data_atualizacao)
                        FROM entregas
                        WHERE entregas.id IN (GROUP_CONCAT(entregas_devolucoes_item.id_entrega))
                    ) data_ultimo_envio,
                    tipo_frete.id,
                    tipo_frete.nome,
                    tipo_frete.id_colaborador,
                    tipo_frete.categoria,
                    colaboradores_enderecos.cidade,
                    colaboradores.telefone,
                    CONCAT(
                        '[',
                        GROUP_CONCAT( DISTINCT
                            JSON_OBJECT(
                                'id_devolucao', entregas_devolucoes_item.id,
                                'id',entregas_devolucoes_item.id_produto,
                                'pac_reverso',entregas_devolucoes_item.pac_reverso,
                                'foto',COALESCE((
                                    SELECT
                                            produtos_foto.caminho
                                        FROM produtos_foto
                                    WHERE
                                            produtos_foto.id = entregas_devolucoes_item.id_produto
                                            ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                        LIMIT 1
                                    ),''),
                                'tamanho', entregas_devolucoes_item.nome_tamanho,
                                'consumidor', consumidor.razao_social,
                                'valor',logistica_item.preco,
                                'nome_produto', produtos.nome_comercial,
                                'situacao',entregas_devolucoes_item.situacao,
                                'dias_devolucao',DATEDIFF(NOW(),entregas_devolucoes_item.data_criacao),
                                'uuid', entregas_devolucoes_item.uuid_produto,
                                'data_criacao_ts', UNIX_TIMESTAMP(entregas_devolucoes_item.data_criacao),
                                'data_criacao', entregas_devolucoes_item.data_criacao,
                                'id_usuario_bip', usuarios.nome
                            )
                        ),
                        ']'
                        ) devolucoes

                FROM entregas_devolucoes_item
                INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_devolucoes_item.uuid_produto
                INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                INNER JOIN colaboradores consumidor ON consumidor.id = logistica_item.id_cliente
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id AND
                    colaboradores_enderecos.eh_endereco_padrao = 1
                INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                INNER JOIN usuarios ON usuarios.id_colaborador = tipo_frete.id_colaborador
                WHERE
                        tipo_frete.categoria IN ( 'ML' ,'PE')
                        AND entregas_devolucoes_item.situacao NOT IN ('RE', 'CO', 'PR')";

        if ($idPonto) {
            $sql .= ' AND tipo_frete.id = :id_ponto ';
            $binds[':id_ponto'] = $idPonto;
        }

        $sql .= ' GROUP BY tipo_frete.id;';

        $data = DB::select($sql, $binds);

        $verificaDataEmProdutos = function ($item) {
            return $item['dias_devolucao'] > 30;
        };

        foreach ($data as $ponto) {
            $ponto['data_ultimo_envio_ts'] = strtotime($ponto['data_ultimo_envio']);
            $ponto['data_ultimo_envio'] = date('d/m/Y H:i', strtotime($ponto['data_ultimo_envio']));

            $ponto['produtos'] = [];

            $listaDeProdutosAEnviar = $this->formataListaDetalhesDeProdutos($ponto['devolucoes']);

            $ponto['devolucoes'] = count($listaDeProdutosAEnviar['produtos']) | 0;

            if (count($listaDeProdutosAEnviar['produtos']) > 0) {
                $ponto['pac_reverso'] = $listaDeProdutosAEnviar['pac_reverso'];
            }

            $ponto['alerta_devolucoes'] = !!array_filter($listaDeProdutosAEnviar['produtos'], $verificaDataEmProdutos);

            $itemsAEnviar = $listaDeProdutosAEnviar['produtos'];

            usort($itemsAEnviar, fn($a, $b) => $a['dias_devolucao'] > $b['dias_devolucao'] ? -1 : 1);

            $ponto['produtos'] = [
                'a_enviar' => [
                    'items' => $itemsAEnviar,
                    'faturamentos' => [],
                    'total' => array_reduce($listaDeProdutosAEnviar['produtos'], $somaValores, 0),
                ],
            ];
            $retorno[] = $ponto;
        }
        usort(
            $retorno,
            fn($a, $b) => $a['devolucoes'] > $b['devolucoes'] || $a['devolucoes'] > $b['devolucoes'] ? -1 : 1
        );

        return $retorno;
    }

    private function formataListaDetalhesDeProdutos(string $listaDeProdutosString): array
    {
        $retorno = [];
        $pac_reverso = null;

        $data = json_decode($listaDeProdutosString, true) ?: [];

        foreach ($data as $ponto) {
            $ponto['id'] = (int) $ponto['id'];
            $ponto['valor'] = (float) $ponto['valor'];
            $ponto['uuid'] = $ponto['uuid'];
            $ponto['data_criacao_ts'] = (int) $ponto['data_criacao_ts'];
            $ponto['data_criacao'] = date('d/m/Y H:i', strtotime($ponto['data_criacao']));

            if ($ponto['pac_reverso']) {
                $pac_reverso = $ponto['pac_reverso'];
            }
            $retorno[] = $ponto;
        }
        usort($retorno, fn($a, $b) => $a['dias_devolucao'] > $b['dias_devolucao'] ? -1 : 1);

        return [
            'produtos' => $retorno,
            'pac_reverso' => $pac_reverso,
        ];
    }

    public function geraPacReversoParaPontoDeEntrega(int $idCliente): array
    {
        $valorDeclarado = '100.00'; // A pedido do fabio este valor sera fixo para evitar custos
        $pac = app(PacReverso::class, [
            'id_cliente' => $idCliente,
            'id_transacao' => 0,
            'valor_devolucao' => $valorDeclarado,
        ]);
        $result = $pac->solicitarPac();
        $numeroColetaSplit = preg_split('<numero_coleta>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $numeroColetaArray = $numeroColetaSplit[1][0];
        $numeroColeta = trim($numeroColetaArray, '< / >');

        $prazoSplit = preg_split('<prazo>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $prazoArray = $prazoSplit[1][0];
        $prazo = trim($prazoArray, '< / >');

        $erroSplit = preg_split('<msg_erro>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $erroArray = $erroSplit[1][0];
        $erro = trim($erroArray, '< / >');
        //<descricao_erro></descricao_erro>
        $descricaoErroSplit = preg_split('<descricao_erro>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $descricaoErroArray = $descricaoErroSplit[1][0];
        $descricaoErro = trim($descricaoErroArray, '< / >');

        return [
            'numero_coleta' => $numeroColeta,
            'prazo' => $prazo,
            'erro' => mb_strlen($descricaoErro) > 0 ? $erro . ' ' . $descricaoErro : false,
        ];
    }
    public function salvaNumeroPacReversoNoPonto(array $listaDeIds, string $numeroDeColeta): void
    {
        [$binds, $valores] = ConversorArray::criaBindValues($listaDeIds, 'id_entregas_devolucoes_item');
        $valores[':pac_reverso'] = $numeroDeColeta;
        $linhasAlteradas = DB::update(
            "UPDATE entregas_devolucoes_item
            SET entregas_devolucoes_item.pac_reverso = :pac_reverso
            WHERE entregas_devolucoes_item.id IN ($binds);",
            $valores
        );

        if ($linhasAlteradas === 0) {
            throw new Exception('Erro ao salvar o numero do Pac no ponto');
        }
    }

    /**
     * Para evitar complexidade no sistema, foi decidido, junto com o Fábio, que a devolução será configurada como normal
     * e em seguida, alterado para defeito. As comissões de devolução não mudarão para comissões de defeitos.
     * https://github.com/mobilestock/web/pull/2820#discussion_r1355571675
     */
    public function mudaTipoDeDevolucao(PDO $conexao, int $id_devolucao, int $id_usuario)
    {
        $query = "SELECT
                entregas_devolucoes_item.uuid_produto,
                entregas_devolucoes_item.tipo
            FROM entregas_devolucoes_item
            WHERE entregas_devolucoes_item.id = :id_devolucao LIMIT 1";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_devolucao', $id_devolucao, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!isset($resultado['uuid_produto'])) {
            throw new Exception('Essa alteração é inválida.', 400);
        }

        $update = "UPDATE
                        troca_pendente_item
                    SET
                        troca_pendente_item.defeito = :defeito
                    WHERE
                        troca_pendente_item.uuid = :uuid_produto";
        $prepare = $conexao->prepare($update);
        $prepare->bindValue(':uuid_produto', $resultado['uuid_produto'], PDO::PARAM_STR);
        $prepare->bindValue(':defeito', $resultado['tipo'] === 'NO' ? 1 : 0, PDO::PARAM_INT);
        $prepare->execute();
        // Colocar essa logica na trigger de `troca_pendente_item`
        $update = "UPDATE
                        entregas_devolucoes_item
                    SET
                        entregas_devolucoes_item.id_usuario = :id_usuario
                    WHERE
                        entregas_devolucoes_item.id = :id_devolucao";
        $prepare = $conexao->prepare($update);
        $prepare->bindValue(':id_devolucao', $id_devolucao, PDO::PARAM_INT);
        $prepare->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $prepare->execute();
    }
    public static function enviarMensagemInteresse(int $idColaboradorPonto, int $idProduto, string $nomeTamanho): void
    {
        $msgService = new MessageService();
        $sql = "SELECT
                entregas_devolucoes_item.id_produto,
                entregas_devolucoes_item.nome_tamanho,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto_produto`,
                (
                    SELECT produtos.valor_venda_ml
                    FROM produtos
                    WHERE produtos.id = entregas_devolucoes_item.id_produto
                ) + COALESCE((
                    SELECT transportadores_raios.preco_entrega
                    FROM transportadores_raios
                    WHERE transportadores_raios.id_colaborador =  :id_colaborador_ponto
                    AND transportadores_raios.id_cidade = @idCidade
                ),0)AS `valor_ml`,
                JSON_OBJECT(
					'nome', entregador_colaboradores.razao_social,
					'telefone', entregador_colaboradores.telefone
				) AS `json_entregador`,
                (
                    SELECT JSON_OBJECT(
                        'nome', cliente_colaboradores.razao_social,
                        'telefone', cliente_colaboradores.telefone,
                        'id_cidade', @idCidade := colaboradores_enderecos.id_cidade
                    )
                    FROM colaboradores_enderecos
                    WHERE colaboradores_enderecos.id_colaborador = :id_cliente
                        AND colaboradores_enderecos.eh_endereco_padrao = 1
                ) AS `json_cliente`
            FROM publicacoes_produtos
            INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.id_produto = publicacoes_produtos.id_produto
                AND entregas_devolucoes_item.situacao = 'PE'
                AND entregas_devolucoes_item.situacao_envio = 'NO'
                AND entregas_devolucoes_item.origem = 'ML'
                AND NOT entregas_devolucoes_item.tipo = 'DE'
            INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
            INNER JOIN colaboradores AS `cliente_colaboradores` ON cliente_colaboradores.id = :id_cliente
            INNER JOIN colaboradores AS `entregador_colaboradores` ON entregador_colaboradores.id = tipo_frete.id_colaborador
            WHERE publicacoes_produtos.id_produto = :id_produto
                AND tipo_frete.id_colaborador = :id_colaborador_ponto
                AND entregas_devolucoes_item.nome_tamanho = :nome_tamanho
            LIMIT 1;";

        $binds = [
            ':id_colaborador_ponto' => $idColaboradorPonto,
            ':id_cliente' => Auth::user()->id_colaborador,
            ':id_produto' => $idProduto,
            ':nome_tamanho' => $nomeTamanho,
        ];

        $informacao = DB::selectOne($sql, $binds);
        if (empty($informacao)) {
            throw new NotFoundHttpException("O tamanho $nomeTamanho não está mais disponível");
        }

        $valorVenda = number_format($informacao['valor_ml'], 2, ',', '.');

        if (mb_strlen(trim($informacao['cliente']['nome'])) > 20) {
            $cliente = preg_replace('/\s.*/im', '', $informacao['cliente']['nome']);
        } else {
            $cliente = trim($informacao['cliente']['nome']);
        }

        if (mb_strlen(trim($informacao['entregador']['nome'])) > 20) {
            $entregador = preg_replace('/\s.*/im', '', $informacao['entregador']['nome']);
        } else {
            $entregador = trim($informacao['entregador']['nome']);
        }

        $mensagem = "Olá $entregador." . PHP_EOL;
        $mensagem .=
            "O(a) cliente $cliente está interessado(a) no produto $idProduto, tamanho $nomeTamanho, valor R$$valorVenda." .
            PHP_EOL;
        $mensagem .=
            'Entre em contato pelo link abaixo caso esse produto esteja disponível para pronta entrega em seu ponto!' .
            PHP_EOL;
        $mensagem .= 'https://api.whatsapp.com/send/?phone=55' . $informacao['cliente']['telefone'];
        $msgService->sendImageWhatsApp($informacao['entregador']['telefone'], $informacao['foto_produto'], $mensagem);
    }

    public function gerenciarProntaEntrega(
        PDO $conexao,
        int $idColaboradorPonto,
        int $idProduto,
        string $nomeTamanho,
        string $movimentacao
    ): void {
        $sql = $conexao->prepare(
            "SELECT MIN(entregas_devolucoes_item.id) AS `id_entregas_devolucoes_item`
            FROM entregas_devolucoes_item
            INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
            WHERE entregas_devolucoes_item.id_produto = :id_produto
                AND tipo_frete.id_colaborador = :id_colaborador_ponto
                AND entregas_devolucoes_item.nome_tamanho = :nome_tamanho
                AND entregas_devolucoes_item.situacao = 'PE'
                AND entregas_devolucoes_item.situacao_envio = 'NO'
                AND entregas_devolucoes_item.origem = 'ML'
                AND NOT entregas_devolucoes_item.tipo = 'DE'
            LIMIT 1;"
        );
        $sql->bindValue(':id_colaborador_ponto', $idColaboradorPonto, PDO::PARAM_INT);
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->execute();
        $idDevolucaoItem = (int) $sql->fetchColumn();
        if (empty($idDevolucaoItem)) {
            throw new Exception('Produto não mais disponível para venda Pronta Entrega');
        }

        $this->id = $idDevolucaoItem;
        if ($movimentacao === 'VENDIDO') {
            $this->situacao = 'VE';
        }
        if (!$this->atualiza($conexao)) {
            throw new Exception('Situação atualizada incorretamente, consulte a equpe de T.I.');
        }
    }
}
