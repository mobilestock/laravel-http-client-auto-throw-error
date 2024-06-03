<?php

namespace MobileStock\service\Separacao;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaCliente;
use MobileStock\model\LogisticaItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\Separacao\Separacao;
use MobileStock\model\TipoFrete;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\MessageService;
use PDO;

class separacaoService extends Separacao
{
    public static function sqlExistePendente(bool $ehRetirada): string
    {
        $where = '';
        $situacaoLogistica = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        if ($ehRetirada) {
            $colaboradoresEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;
            $where = " AND _logistica_item.id_colaborador_tipo_frete IN ($colaboradoresEntregaCliente) ";
        }
        $sql = " EXISTS(
            SELECT 1
            FROM logistica_item AS `_logistica_item`
            WHERE _logistica_item.id_responsavel_estoque > 1
                AND _logistica_item.situacao < $situacaoLogistica
                $where
                AND _logistica_item.id_cliente = logistica_item.id_cliente
        ) ";

        return $sql;
    }

    public static function listaItems(int $idColaborador, ?string $pesquisa): array
    {
        $where = '';

        if (!empty($pesquisa)) {
            $where = " AND (
                logistica_item.id_cliente = :pesquisa
                OR logistica_item.id_produto = :pesquisa
                OR logistica_item.id_transacao = :pesquisa
                OR logistica_item.nome_tamanho regexp :pesquisa
                OR colaboradores.razao_social regexp :pesquisa
                OR produtos.localizacao = :pesquisa
                OR produtos.cores regexp :pesquisa
            ) ";
        }

        $binds = [
            'id_colaborador' => $idColaborador,
        ];
        if (!empty($pesquisa)) {
            $binds['pesquisa'] = $pesquisa;
        }

        $sql = "SELECT
                    produtos.localizacao,
                    produtos.id id_produto,
                    COALESCE(produtos.nome_comercial,produtos.descricao) nome_produto,
                    produtos.cores,
                    transacao_financeiras_produtos_itens.comissao_fornecedor valor_recebido_seller,
                    colaboradores.id id_cliente,
                    colaboradores.razao_social nome_cliente,
                    colaboradores.telefone,
                    logistica_item.uuid_produto uuid,
                    DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y às %h:%i') AS `data_pagamento`,
                    logistica_item.nome_tamanho tamanho,
                    logistica_item.situacao,
                    logistica_item.id_transacao,
                    EXISTS(
                        SELECT 1
                        FROM negociacoes_produto_temp
                        WHERE negociacoes_produto_temp.uuid_produto = logistica_item.uuid_produto
                    ) AS `eh_negociacao_aberta`,
                    (
                        SELECT negociacoes_produto_log.mensagem
                        FROM negociacoes_produto_log
                        WHERE negociacoes_produto_log.uuid_produto = logistica_item.uuid_produto
                            AND negociacoes_produto_log.situacao = 'ACEITA'
                        ORDER BY negociacoes_produto_log.id DESC
                        LIMIT 1
                    ) negociacao_aceita,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto,
                    COALESCE(
                        (
                            SELECT
                                publicacoes_produtos.id
                            FROM publicacoes_produtos
                            WHERE
                                publicacoes_produtos.uuid = logistica_item.uuid_produto
                                AND publicacoes_produtos.id_produto = logistica_item.id_produto
                            LIMIT 1
                        ),
                        0
                    ) id_publicacao,
                    DATE_FORMAT(DATEADD_DIAS_UTEIS(3, logistica_item.data_criacao), '%d/%m') ultimo_dia_separar,
                    DATEDIFF_DIAS_UTEIS(NOW(), logistica_item.data_criacao) dias_na_separacao,
                    (
                        SELECT
                            produtos_grade.cod_barras
                        FROM produtos_grade
                        WHERE
                            produtos_grade.id_produto = logistica_item.id_produto
                            AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                        LIMIT 1
                    ) cod_barras,
                    EXISTS (
                        SELECT 1
                        FROM logistica_item_impressos_temp
                        WHERE logistica_item_impressos_temp.uuid_produto = logistica_item.uuid_produto
                        LIMIT 1
                    ) AS `eh_etiqueta_impressa`,
                    JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.nome_destinatario') AS `nome_destinatario`
                FROM logistica_item
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                INNER JOIN produtos ON logistica_item.id_produto = produtos.id
                INNER JOIN colaboradores ON logistica_item.id_cliente = colaboradores.id
                JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.`uuid_produto` = logistica_item.`uuid_produto`
                    AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
                WHERE logistica_item.id_responsavel_estoque = :id_colaborador
                    AND logistica_item.situacao IN ('PE', 'SE')
                    AND logistica_item.id_entrega IS NULL
                    $where
                GROUP BY logistica_item.uuid_produto
                ORDER BY transacao_financeiras_produtos_itens.data_atualizacao ASC;";
        $dados = DB::select($sql, $binds);

        if (!$dados) {
            return [];
        }

        $dadosFormatados = array_map(function ($item) {
            $item['nome_produto'] = ConversorStrings::sanitizeString($item['nome_produto']);
            $item['nome_cliente'] = ConversorStrings::sanitizeString($item['nome_cliente']);
            if (!empty($item['negociacao_aceita'])) {
                $item['negociacao_aceita'] = json_decode($item['negociacao_aceita'], true);
                $item['negociacao_aceita']['produtos_oferecidos'] = json_decode(
                    $item['negociacao_aceita']['produtos_oferecidos'],
                    true
                );
            }

            return $item;
        }, $dados);

        return $dadosFormatados;
    }

    public static function consultaEtiquetasFrete(int $idColaborador): array
    {
        [$binds, $valores] = ConversorArray::criaBindValues(
            [ProdutoModel::ID_PRODUTO_FRETE, ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO],
            'id_produto'
        );
        $valores['id_colaborador'] = $idColaborador;

        $etiquetas = DB::select(
            "SELECT
                logistica_item.id_produto,
                logistica_item.uuid_produto AS `uuid`,
                colaboradores.razao_social AS `nome_cliente`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`,
                transacao_financeiras_metadados.valor AS `json_destino`,
                DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) AS `dias_na_separacao`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            WHERE logistica_item.situacao = 'PE'
                AND logistica_item.id_produto IN ($binds)
            AND logistica_item.id_cliente = :id_colaborador
            GROUP BY logistica_item.uuid_produto
            ORDER BY logistica_item.data_criacao ASC;",
            $valores
        );
        $etiquetas = array_map(function (array $etiqueta): array {
            $etiqueta['telefone'] = Str::formatarTelefone($etiqueta['destino']['telefone_destinatario']);
            $etiqueta['destinatario'] = trim($etiqueta['destino']['nome_destinatario']);
            $etiqueta['destinatario'] .= ': ';
            $etiqueta['destinatario'] .= implode(' - ', Arr::only($etiqueta['destino'], ['logradouro', 'numero']));
            $cidade = implode(' - ', Arr::only($etiqueta['destino'], ['cidade', 'uf']));
            if (!empty($cidade)) {
                $etiqueta['destinatario'] .= " ($cidade)";
            }

            unset($etiqueta['destino']);

            return $etiqueta;
        }, $etiquetas);

        return $etiquetas;
    }
    /**
     * @deprecated
     * @see https://github.com/mobilestock/backend/issues/147
     */
    public static function separa(PDO $conexao, string $uuidProduto, int $idUsuario): void
    {
        $sql = "UPDATE
                    logistica_item
                SET
                    logistica_item.situacao = 'SE',
                    logistica_item.id_usuario = :id_usuario
                WHERE logistica_item.uuid_produto = :uuid_produto;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindValue(':uuid_produto', $uuidProduto);
        $prepare->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $prepare->execute();

        //if ($prepare->rowCount() !== 1) {
        //    throw new RuntimeException('Falha ao separa item');
        //}
    }

    //    public function setConsulta($consulta)
    //    {
    //        $this->consulta = $consulta;
    //
    //        return $this;
    //    }

    public static function alertarSepararProdutoExterno(int $idTransacao): void
    {
        $temExterno = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM transacao_financeiras_produtos_itens
                WHERE transacao_financeiras_produtos_itens.id_transacao = :idTransacao
                    AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                    AND transacao_financeiras_produtos_itens.id_responsavel_estoque <> 1
                LIMIT 1
            )tem_externo;",
            ['idTransacao' => $idTransacao]
        );

        if ($temExterno) {
            $produtos = DB::select(
                "SELECT
                    colaboradores.telefone,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        GROUP BY produtos_foto.id
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    )foto_produto,
                    CONCAT(
                        'Você vendeu no Meu Look, ',
                        produtos.descricao,
                        ' tamanho: ',
                        logistica_item.nome_tamanho
                    )mensagem
                FROM logistica_item
                INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                WHERE logistica_item.id_transacao = :idTransacao
                    AND logistica_item.id_responsavel_estoque <> 1;",
                ['idTransacao' => $idTransacao]
            );

            $messageService = app(MessageService::class);

            foreach ($produtos as $produto) {
                $messageService->sendImageWhatsApp(
                    $produto['telefone'],
                    $produto['foto_produto'],
                    $produto['mensagem']
                );
            }
        }
    }
    public static function consultaQuantidadeParaSeparar(PDO $conexao, int $idResponsavelEstoque): int
    {
        $sql = $conexao->prepare(
            "SELECT COUNT(logistica_item.uuid_produto) quantidade
            FROM logistica_item
            WHERE logistica_item.id_responsavel_estoque = :id_responsavel_estoque
                AND logistica_item.situacao = 'PE';"
        );
        $sql->bindValue(':id_responsavel_estoque', $idResponsavelEstoque, PDO::PARAM_INT);
        $sql->execute();
        $quantidade = $sql->fetchColumn();

        return $quantidade;
    }

    public static function geraEtiquetaSeparacao(array $uuids, string $tipoRetorno): array
    {
        $resposta = LogisticaItemService::buscaItensForaDaEntregaParaImprimir($uuids);
        $retorno = array_map(function ($item) use ($tipoRetorno) {
            $destinatario = '';
            $cidade = '';
            $ponto = '';
            $entregador = '';
            $dataLimiteTrocaMobile = 'Troca 7 dias';
            if ($item['eh_ponto_movel']) {
                if ($item['nome_destinatario'] !== '') {
                    $item['nome_cliente'] = $item['nome_destinatario'];
                    $item['nome_destinatario'] = '';
                }
                $destinatario = $item['nome_destinatario'] !== '' ? $item['nome_destinatario'] . PHP_EOL : '';
                $destinatario .= "{$item['logradouro']}" . PHP_EOL;
                $destinatario .= "Nº: {$item['numero']}   Bairro: {$item['bairro']}" . PHP_EOL;
                $destinatario .= "{$item['complemento']}" . PHP_EOL;
                !empty($item['observacao']) && ($destinatario .= $item['observacao']['nome']);
                $cidade .= "{$item['cidade']}, {$item['uf']}";
                if (!empty($item['apelido_raio'])) {
                    $entregador = $item['apelido_raio'];
                } else {
                    $entregador = Str::retornaSigla($item['nome_remetente']) . '-' . $item['id_remetente'];
                }
            } else {
                $ponto = Str::retornaSigla($item['nome_remetente']) . '-' . $item['id_remetente'];
                !empty($item['observacao']) && ($ponto .= ' - ' . $item['observacao']['nome']);
            }

            if ($item['origem'] === 'MS') {
                $data = new \DateTime();
                $data->add(new \DateInterval('P90D'));
                $dataLimiteTrocaMobile = 'Troca ate ' . $data->format('d/m/Y');
            }

            switch ($tipoRetorno) {
                case 'JSON':
                    $item = [
                        'consumidor_final' => trim(mb_substr($item['nome_cliente'], 0, 25)),
                        'produto' => $item['nome_produto'],
                        'tamanho' => $item['nome_tamanho'],
                        'remetente' => $destinatario,
                        'cidade' => $cidade,
                        'ponto' => $ponto,
                        'entregador' => $entregador,
                        'vendedor_qrcode' => 'produto/' . $item['id_produto'] . '?w=' . $item['uuid_produto'],
                        'data_limite_troca' => $dataLimiteTrocaMobile,
                    ];
                    break;
                case 'ZPL':
                    $imagem = new ImagemEtiquetaCliente(
                        $item['nome_cliente'],
                        $item['nome_produto'],
                        $item['nome_tamanho'],
                        ($item['categoria'] = 'produto/' . $item['id_produto'] . '?w=' . $item['uuid_produto']),
                        $destinatario,
                        $cidade,
                        $ponto,
                        $entregador,
                        $dataLimiteTrocaMobile
                    );
                    $item = $imagem->criarZpl();
                    break;
            }

            return $item;
        }, $resposta);
        return $retorno;
    }

    public static function listarEtiquetasSeparacao(string $tipoLogistica): array
    {
        $colaboradoresEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;
        $sqlExistePendente = self::sqlExistePendente($tipoLogistica === 'PRONTAS');

        $sql = "SELECT
                    logistica_item.id_cliente,
                    (
                        SELECT
                            colaboradores.razao_social
                        FROM colaboradores
                        WHERE colaboradores.id = IF(
                            logistica_item.id_colaborador_tipo_frete IN ($colaboradoresEntregaCliente),
                            logistica_item.id_cliente,
                            logistica_item.id_colaborador_tipo_frete
                        )
                    ) AS `cliente`,
                    DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                    COUNT(logistica_item.id) AS `qtd_item`,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'uuid_produto',logistica_item.uuid_produto,
                                'localizacao',produtos.localizacao
                            )
                        ),
                        ']'
                    ) AS `json_produtos`
                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                WHERE
                    logistica_item.situacao = 'PE'
                    AND logistica_item.id_responsavel_estoque = 1
                    AND if(:condicaoLogistica = 'PRONTAS',
                        (
                            logistica_item.id_colaborador_tipo_frete IN ($colaboradoresEntregaCliente)
                            AND NOT $sqlExistePendente
                        ),
                        TRUE
                    )
                GROUP BY IF(
                    logistica_item.id_colaborador_tipo_frete IN ($colaboradoresEntregaCliente),
                    logistica_item.id_cliente,
                    logistica_item.id_colaborador_tipo_frete
                )
                ORDER BY cliente ASC";
        $resultado = DB::select($sql, ['condicaoLogistica' => $tipoLogistica]);

        return $resultado;
    }
    /**
     * @param 'TODAS'|'PRONTAS'|null $tipoLogistica
     * @param 'SEGUNDA'|'TERCA'|'QUARTA'|'QUINTA'|'SEXTA'|null $diaDaSemana
     * @return array
     */
    public static function produtosProntosParaSeparar(?string $tipoLogistica, ?string $diaDaSemana): array
    {
        $bind['id_produto_frete'] = ProdutoModel::ID_PRODUTO_FRETE;
        $bind['id_produto_frete_expresso'] = ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO;
        $where = '';
        $colaboradoresEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;
        if (empty($tipoLogistica)) {
            $where .= " AND logistica_item.id_colaborador_tipo_frete NOT IN ($colaboradoresEntregaCliente) ";
        } else {
            $where .= " AND logistica_item.id_colaborador_tipo_frete IN ($colaboradoresEntregaCliente) ";
        }
        if ($tipoLogistica === 'PRONTAS') {
            $sqlExistePendente = self::sqlExistePendente(true);
            $where .= " AND NOT $sqlExistePendente ";
        }
        if (!empty($diaDaSemana)) {
            $where .= " AND EXISTS(
                SELECT 1
                FROM tipo_frete_grupos
                INNER JOIN tipo_frete_grupos_item ON tipo_frete_grupos_item.id_tipo_frete_grupos = tipo_frete_grupos.id
                WHERE tipo_frete_grupos.dia_fechamento = :dia_fechamento
                    AND tipo_frete_grupos_item.id_tipo_frete = tipo_frete.id
            ) ";
            $bind['dia_fechamento'] = $diaDaSemana;
        }

        $uuids = DB::selectColumns(
            "SELECT logistica_item.uuid_produto
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            WHERE logistica_item.situacao = 'PE'
                AND logistica_item.id_produto NOT IN (:id_produto_frete, :id_produto_frete_expresso)
                AND logistica_item.id_responsavel_estoque = 1
                $where
            GROUP BY logistica_item.uuid_produto;",
            $bind
        );

        return $uuids;
    }

    public static function salvaImpressao(array $uuids): void
    {
        [$bind, $valores] = ConversorArray::criaBindValues($uuids, 'uuid_produto');
        $bind = str_replace(',', '),(', $bind);
        DB::insert(
            "INSERT INTO logistica_item_impressos_temp (
                logistica_item_impressos_temp.uuid_produto
            ) VALUES ($bind);",
            $valores
        );
    }

    public static function deletaLogsSeparacao(): void
    {
        $sql = "DELETE FROM logistica_item_impressos_temp
            WHERE logistica_item_impressos_temp.data_criacao < NOW() - INTERVAL 7 DAY;";

        DB::delete($sql);
    }
    public static function deletaLogDeImpressaoEspecificoSemVerificacao(PDO $conexao, string $uuidProduto): void
    {
        $sql = $conexao->prepare(
            "DELETE FROM logistica_item_impressos_temp
            WHERE logistica_item_impressos_temp.uuid_produto = :uuid_produto;"
        );
        $sql->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $sql->execute();
    }
}
