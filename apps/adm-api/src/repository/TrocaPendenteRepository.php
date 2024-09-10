<?php

namespace MobileStock\repository;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Gate;
use MobileStock\database\Conexao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\DB;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\Entrega\EntregasDevolucoesItem;
use MobileStock\model\Origem;
use MobileStock\model\Produto;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Troca\TrocaPendenteCrud;
use MobileStock\service\TrocaFilaSolicitacoesService;
use PDO;

require_once __DIR__ . '/../../classes/troca-pendente.php';

/**
 * Buscar itens da troca pendente
 */
class TrocaPendenteRepository
{
    private $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::criarConexao();
    }

    public static function removeTrocaAgendadaPorUuid(TrocaPendenteItem $troca, PDO $conexao): void
    {
        $agendada = TrocaPendenteCrud::busca(
            [
                'uuid' => $troca->getUuid(),
            ],
            true,
            $conexao
        );

        if ($agendada) {
            TrocaPendenteCrud::deleta($agendada[0], $conexao);
        }
    }

    // public static function buscaTrocasPendentesConfirmadasCliente(int $id): array
    // {
    //     return array_map(function ($item) {
    //         $item['preco'] = (float)$item['preco'];
    //         $item['json_taxa'] = json_decode($item['json_taxa'], true);
    //         $item['detalhes_taxa'] = $item['json_taxa']['detalhes_taxa'];
    //         $item['detalhes_taxa'] = implode('<br>', explode('|', $item['detalhes_taxa']));
    //         $item['taxa'] = (float) $item['json_taxa']['taxa'];
    //         unset($item['json_taxa']);

    //         return $item;
    //     }, DB::select("SELECT
    //         lancamento_financeiro.id,
    //         lancamento_financeiro.tipo,
    //         CASE lancamento_financeiro.origem
    //             WHEN 'PC' THEN 'Crédito usado'
    //             WHEN 'CP' THEN 'Correção de Par'
    //             WHEN 'TR' THEN COALESCE((SELECT produtos.descricao FROM produtos WHERE troca_pendente_item.id_produto = produtos.id), 'Produtos Indefinido')
    //         END produto,
    //         lancamento_financeiro.origem,
    //         lancamento_financeiro.valor preco,
    //         lancamento_financeiro.pedido_origem,
    //         DATE_FORMAT(troca_pendente_item.data_hora, '%d/%m/%Y') data_compra,
    //         (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = troca_pendente_item.id_produto LIMIT 1) caminho,
    //         lancamento_financeiro.observacao json_taxa,
    //         troca_pendente_item.tamanho
    //     FROM lancamento_financeiro
    //     LEFT OUTER JOIN troca_pendente_item ON (lancamento_financeiro.numero_documento = troca_pendente_item.uuid)
    //     WHERE lancamento_financeiro.id_colaborador = :id
    //         AND lancamento_financeiro.origem IN ('TR', 'PC', 'CP')
    //         ORDER BY lancamento_financeiro.id DESC
    //         ", [':id' => $id]));
    // }

    // public function buscaItensCompradosParametro(int $id_cliente, $params, int $pagina = 0)
    // {
    //     $regrasPesquisa = [
    //         'fornecedor' => [
    //             'campo' => 'produtos.id_fornecedor',
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  true,
    //             ')' =>  false
    //         ],
    //         'fornecedor_origem' => [
    //             'campo' => 'produtos.id_fornecedor_origem',
    //             'regex' => false,
    //             'OR' => true,
    //             '(' =>  false,
    //             ')' =>  true
    //         ],
    //         'id_faturamento' => [
    //             'campo' => 'faturamento_item.id_faturamento',
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //         'categoria' => [
    //             'campo' => 'produtos_categorias.id_categoria',
    //             'subquery' => true,
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //         'linha' => [
    //             'campo' => 'produtos.id_linha',
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //         'tamanho' => [
    //             'campo' => 'faturamento_item.tamanho',
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //         'descricao' => [
    //             'campo' => 'LOWER(tags.nome)',
    //             'subquery' => true,
    //             'regex' => false,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //         //            'id_produto' => [
    //         //                'campo' => 'produtos.id',
    //         //                'regex' => false,
    //         //                'OR' => true,
    //         //                '(' =>  false,
    //         //                ')' =>  true
    //         //            ],
    //         'codigo' => [
    //             'campo' => 'produtos_grade_cod_barras.cod_barras',
    //             'regex' => false,
    //             'subquery' => true,
    //             'OR' => false,
    //             '(' =>  false,
    //             ')' =>  false
    //         ],
    //     ];
    //     $bindValues = [
    //         ':id_cliente' => $id_cliente,
    //     ];
    //     $porPagina = 3;
    //     $query = "select
    //     DISTINCT faturamento_item.id_faturamento,
    //     CONCAT('[',
    //         GROUP_CONCAT(DISTINCT
    //         JSON_OBJECT(
    //             'uuid', faturamento_item.uuid,
    //             'id_faturamento', faturamento_item.id_faturamento,
    //             'nome_tamanho', faturamento_item.nome_tamanho,
    //             'data_hora', faturamento.data_emissao,
    //             'preco', faturamento_item.preco,
    //             'fornecedor', (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor),
    //             'fornecedor_origem', (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor_origem),
    //             'linha', (SELECT linha.nome FROM linha WHERE linha.id = produtos.id_linha) ,
    //             'descricao', produtos.descricao,
    //             'id_produto', produtos.id,
    //             'fotoProduto', (SELECT produtos_foto.caminho from produtos_foto where produtos_foto.id = produtos.id LIMIT 1),
    //             'premio', faturamento_item.premio,
    //             'passou_prazo', NOT date(faturamento.data_emissao) BETWEEN CURRENT_DATE - INTERVAL 1 YEAR AND CURRENT_DATE,
    //             'cod_barras', (SELECT GROUP_CONCAT(produtos_grade_cod_barras.cod_barras) FROM produtos_grade_cod_barras WHERE produtos_grade_cod_barras.id_produto = faturamento_item.id_produto AND produtos_grade_cod_barras.tamanho = faturamento_item.tamanho)
    //                 ))
    //     ,']') items
    //     from faturamento
    //     INNER JOIN faturamento_item on (faturamento.id = faturamento_item.id_faturamento)
    //     INNER JOIN produtos on (faturamento_item.id_produto = produtos.id)
    //     where faturamento_item.id_cliente = :id_cliente
    //             and faturamento.situacao = 2
    //             and faturamento.separado = 1
    //             and faturamento_item.situacao = 6
    //             and NOT EXISTS(SELECT 1 FROM troca_pendente_agendamento WHERE faturamento_item.uuid = troca_pendente_agendamento.uuid)
    //             and NOT EXISTS(SELECT 1 FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = faturamento_item.uuid)";

    //     if (isset($params['codigo']) && $params['codigo']) {
    //         $regra = $regrasPesquisa['codigo'];
    //         $query .= " AND EXISTS(SELECT 1 FROM produtos_grade_cod_barras
    // 				WHERE produtos_grade_cod_barras.id_produto = faturamento_item.id_produto
    // 					AND produtos_grade_cod_barras.tamanho = faturamento_item.tamanho
    // 					AND {$regra['campo']} = :codigo) ";
    //         $bindValues = array_merge($bindValues, [":codigo" => $params['codigo']]);
    //     }

    //     if (isset($params['categoria']) && $params['categoria']) {
    //         $categorias = implode(',', $params['categoria']);
    //         $regra = $regrasPesquisa['categoria'];
    //         $query .= " AND produtos.id IN(SELECT produtos_categorias.id_produto
    //     	    FROM produtos_categorias
    //     	    WHERE produtos_categorias.id_categoria IN ({$categorias}))";
    //     }

    //     if (isset($params['descricao']) && $params['descricao']) {
    //         $query .= " AND produtos.id IN(SELECT produtos_tags.id_produto FROM produtos_tags WHERE produtos_tags.id_tag IN(SELECT tags.id FROM tags WHERE LOWER(tags.nome) regexp :descricao))";
    //         $bindValues = array_merge($bindValues, [':descricao' => strtolower($params['descricao'])]);
    //     }

    //     foreach ($params as $key => $value) {
    //         if (!$value) continue;
    //         $regra = $regrasPesquisa[$key];
    //         if (isset($regra['subquery']) || is_null($regra)) continue;

    //         $campo = $regra['campo'];

    //         $andOrStr = $regra['OR'] === true ? 'OR' : "AND";
    //         $sinalInicial = $regra['('] === true ? '(' : '';
    //         $sinalFinal = $regra[')'] === true ? ')' : '';

    //         if ($regra['regex']) {
    //             $query .= "$andOrStr $sinalInicial $campo like :$key $sinalFinal";
    //             $value = "%$value%";
    //         } else {

    //             $query .= "{$andOrStr} $sinalInicial $campo = :$key $sinalFinal";
    //         }

    //         $bindValues = array_merge($bindValues, [":$key" => $value]);
    //     }

    //     $query .= "
    //     group by
    //         faturamento.id
    //     order by `faturamento`.`data_emissao` DESC";

    //     if ($pagina !== 0) {

    //         $offset = ($pagina - 1) * $porPagina;
    //         $query .= " LIMIT {$porPagina} OFFSET {$offset}";
    //     }
    //                         //    echo '<pre>';
    //                         //    echo $query;
    //                         //    var_dump($bindValues);
    //                         //    exit;
    //     $resultado = DB::select($query, $bindValues);

    //     /** @var Colaborador $colaborador */
    //     $colaborador = ColaboradoresRepository::busca([
    //         'id' => "(SELECT id_colaborador FROM usuarios WHERE id = " . idUsuarioLogado() . ' )'
    //     ]);

    //     $resultado = array_map(function ($faturamento) use ($colaborador, $id_cliente) {
    //         $faturamento = json_decode($faturamento['items'], true);

    //         $faturamento = array_map(function ($item) use ($colaborador, $id_cliente) {

    //             try {

    //                 $troca = new TrocaPendenteItem(
    //                     $id_cliente,
    //                     $item['id_produto'],
    //                     $item['tamanho'],
    //                     $colaborador->getId(),
    //                     $item['preco'],
    //                     $item['uuid'],
    //                     '',
    //                     $item['data_hora']
    //                 );
    //                 $item['taxa'] = round($troca->calculaTaxa(), 2);
    //             } catch (\InvalidArgumentException $exception) {
    //                 $item['disponivel'] = 0;
    //             }

    //             $item['mensagem_indisponivel'] = '';
    //             if ($item['premio']) {
    //                 $item['mensagem_indisponivel'] = 'Esse produto é premio';
    //             } else if ($item['passou_prazo']) {
    //                 $item['mensagem_indisponivel'] = 'Esse produto já passou do prazo de 365 dias';
    //             }

    //             $item['preco'] = (float) $item['preco'];
    //             $item['taxa'] = (float) $item['taxa'];
    //             $item['data_hora'] = (new \DateTime($item['data_hora']))->format('d/m/y H:i:s');

    //             // campos validos apenas para a troca interna
    //             $item['correto'] = true;
    //             $item['agendada'] = false;
    //             $item['defeito'] = false;
    //             $item['cod_barras'] = explode(',', $item['cod_barras']);
    //             return $item;
    //         }, $faturamento);

    //         return $faturamento;
    //     }, $resultado);

    //     if (empty($resultado)) {
    //         return false;
    //     }

    //     return $resultado;
    // }

    public function buscaFornecedores()
    {
        $query = "SELECT razao_social, id FROM colaboradores WHERE TIPO='F' ORDER BY RAZAO_SOCIAL";
        return DB::select($query);
    }

    public function buscaCategorias()
    {
        return DB::select('SELECT * FROM categorias;');
    }

    public function buscaLinhas(): array
    {
        return DB::select('SELECT * FROM linha');
    }

    //    public static function removerItemTrocaPendenteConfirmada($uuid, PDO $conn)
    //    {
    //        $troca = TrocaPendenteCrud::busca([
    //            'uuid' => $uuid
    //        ], false, $conn)[0];
    //        TrocaPendenteCrud::deleta($troca, $conn);
    //    }

    //    private static function perda_valor_troca($dias_compra_troca, $item)
    //    {
    //        $valor_diferenca['novo_preco'] = $item['preco'];
    //        $valor_diferenca['desconto'] = "-";
    //        if ($item['descricao_defeito'] == null) { // Verififica se não é defeito a requisição de troca
    //            if ($dias_compra_troca['diferenca'] < 60) {
    //                $valor_diferenca['novo_preco'] = $item['preco'];
    //                $valor_diferenca['desconto'] = "0";
    //            } else {
    //                if ($dias_compra_troca['diferenca'] < 120) { // Se a data da compra - data devolução forem menor de 120
    //                    $valor_diferenca['novo_preco'] = $item['preco'] - (($item['preco'] * 30) / 100);
    //                    $valor_diferenca['desconto'] = "30";
    //                    // Desconta os 30% no preço
    //                } else {
    //                    if ($dias_compra_troca['diferenca'] < 180) { // Se a data da compra - data devolução forem menor de 180
    //                        $valor_diferenca['novo_preco'] = $item['preco'] - (($item['preco'] * 50) / 100);
    //                        $valor_diferenca['desconto'] = "50";
    //                        $valor_diferenca['desconto'] = "-";
    //                        // Desconta os 50% no preço
    //                    } else {
    //                        if ($dias_compra_troca['diferenca'] < 360) { //Se a data da compra - data devolução forem menor de 360
    //                            $valor_diferenca['novo_preco'] = $item['preco'] - (($item['preco'] * 70) / 100);
    //                            $valor_diferenca['desconto'] = "70";
    //                            // Desconta os 70% no preço
    //                        }
    //                    }
    //                }
    //            }
    //        } else {
    //            $valor_diferenca['novo_preco'] = $item['preco'];
    //            $valor_diferenca['desconto'] = "-";
    //        }
    //        return $valor_diferenca;
    //    }

    public static function buscaTrocasAgendadasCliente(PDO $conexao, int $idCliente): array
    {
        $sql = $conexao->prepare(
            "SELECT
        troca_pendente_agendamento.id_produto,
        (SELECT produtos.descricao FROM produtos WHERE produtos.id = troca_pendente_agendamento.id_produto) produto,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = troca_pendente_agendamento.id_produto
                    ORDER BY produtos_foto.nome_foto = 'MD' DESC
                    LIMIT 1
                ) caminho,
                (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = troca_pendente_agendamento.id_produto
                        AND produtos_grade.nome_tamanho = troca_pendente_agendamento.nome_tamanho
                ) cod_barras,
        troca_pendente_agendamento.preco,
        troca_pendente_agendamento.taxa,
        troca_pendente_agendamento.nome_tamanho,
        troca_pendente_agendamento.uuid,
        troca_pendente_agendamento.defeito = 'T' defeito,
        IF (troca_pendente_agendamento.defeito = 'T', 'Esse produto é defeito', '')descricao_defeito,
        DATE_FORMAT(troca_pendente_agendamento.data_hora, '%d/%m/%Y %H:%i:%s') data_hora,
                (
                    SELECT
                        DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y %H:%i:%s')
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = troca_pendente_agendamento.uuid
                ) data_compra
        FROM troca_pendente_agendamento
        WHERE troca_pendente_agendamento.id_cliente = :id_cliente
        AND troca_pendente_agendamento.tipo_agendamento = 'MS'
            ORDER BY troca_pendente_agendamento.data_hora DESC;"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function ($produto) {
            $produto['preco'] = (float) $produto['preco'];
            $produto['taxa'] = (float) $produto['taxa'];
            $produto['taxa_real'] = (float) $produto['taxa'];
            $produto['alerta_defeito'] = (bool) json_decode($produto['defeito'], true);
            $produto['agendada'] = (bool) true;
            $produto['defeito'] = (bool) false;

            return $produto;
        }, $produtos);

        return $produtos ?? [];
    }

    // public static function buscaProdutosParaTroca(int $id_cliente)
    // {
    //     $sql = "SELECT
    //     f.id,
    //     f.data_fechamento,
    //     CONCAT('[',GROUP_CONCAT(JSON_OBJECT(
    //     'id_produto',fi.id_produto,
    //     'tamanho',fi.tamanho,
    //     'preco',fi.preco,
    //     'taxa',''
    //     )),']')produtos
    //     from faturamento f
    //     INNER JOIN faturamento_item fi ON fi.id_faturamento=f.id
    //     WHERE f.id_cliente=63
    //     AND fi.uuid NOT IN (SELECT tpa.uuid FROM troca_pendente_agendamento tpa)
    //     AND DATE(f.data_fechamento) between current_date() - interval 1 YEAR AND curdate()
    //     group by f.id
    //     ORDER BY f.id DESC;";
    // }

    public static function buscaProdutosTrocaMeuLook(int $pagina = 1, string $uuid = '', string $pesquisa = ''): array
    {
        $origem = app(Origem::class);
        $auxiliares = ConfiguracaoService::buscaAuxiliaresTroca(Origem::ML);

        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues(Produto::IDS_PRODUTOS_FRETE, 'id_produto_frete');

        $binds[':idColaborador'] = Auth::user()->id_colaborador;

        $where = '';
        if ($origem->ehMl()) {
            $situacaoExpedicao = Entregas::SITUACAO_EXPEDICAO;
            $whereInterno = " AND entregas.situacao > $situacaoExpedicao AND entregas_faturamento_item.id_cliente = :idColaborador AND entregas_faturamento_item.origem = 'ML' ";
            $order = ' tab.id_entregas_faturamento_item DESC ';
            if ($uuid) {
                $where .= 'AND (
                    tab.uuid_produto = :uuid
                )';
                $binds[':uuid'] = $uuid;
            }
        } else {
            $where .= ' AND troca_fila_solicitacoes.id IS NOT NULL';
            $whereInterno = " AND entregas_faturamento_item.situacao = 'EN' ";
            if ($pesquisa) {
                $where .= ' AND (
                    tab.id_produto REGEXP :pesquisa OR
                    tab.nome_comercial REGEXP :pesquisa OR
                    tab.id_cliente REGEXP :pesquisa OR
                    tab.nome_cliente REGEXP :pesquisa OR
                    tab.razao_social_cliente REGEXP :pesquisa OR
                    tab.id_vendedor REGEXP :pesquisa OR
                    tab.nome_vendedor REGEXP :pesquisa OR
                    tab.razao_social_vendedor REGEXP :pesquisa
                )';
                $binds[':pesquisa'] = $pesquisa;
            }
            if (Gate::allows('FORNECEDOR')) {
                $whereInterno .= ' AND produtos.id_fornecedor = :idColaborador';
                $order = " situacao_solicitacao = 'TROCA_PENDENTE' DESC,
                    tab.data_retirada DESC";
            } else {
                unset($binds[':idColaborador']);
                $order = " situacao_solicitacao = 'DISPUTA' DESC,
                    situacao_solicitacao = 'TROCA_PENDENTE' DESC,
                    tab.data_retirada DESC";
            }
        }

        $porPagina = 100;
        $limit = "LIMIT $porPagina OFFSET " . ($pagina - 1) * $porPagina;

        $consulta = FacadesDB::select(
            "SELECT
                # Solicitação
                troca_fila_solicitacoes.id id_solicitacao,
                troca_fila_solicitacoes.descricao_defeito,
                COALESCE(
                    troca_fila_solicitacoes.motivo_reprovacao_disputa,
                    troca_fila_solicitacoes.motivo_reprovacao_seller,
                    troca_fila_solicitacoes.motivo_reprovacao_foto
                ) motivo_reprovacao,
                troca_fila_solicitacoes.foto1,
                troca_fila_solicitacoes.foto2,
                troca_fila_solicitacoes.foto3,
                troca_fila_solicitacoes.foto4,
                troca_fila_solicitacoes.foto5,
                troca_fila_solicitacoes.foto6,
                IF (
                    troca_fila_solicitacoes.data_atualizacao,
                    troca_fila_solicitacoes.data_atualizacao,
                    troca_pendente_agendamento.data_hora
                ) data_atualizacao_solicitacao,
                troca_pendente_agendamento.data_vencimento,
                CASE
                    WHEN troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO' THEN 'RETORNO_PRODUTO_EXPIRADO'
                    WHEN troca_fila_solicitacoes.situacao = 'CANCELADO_PELO_CLIENTE' THEN 'CLIENTE_DESISTIU'
                    WHEN (
                        (entregas_devolucoes_item.id IS NOT NULL AND troca_fila_solicitacoes.situacao = 'ITEM_TROCADO') OR
                        (entregas_devolucoes_item.id IS NOT NULL)
                    ) THEN 'ITEM_TROCADO'
                    WHEN (
                        (troca_pendente_agendamento.id IS NOT NULL AND troca_fila_solicitacoes.situacao = 'APROVADO') OR
                        (troca_pendente_agendamento.id IS NOT NULL AND troca_pendente_agendamento.defeito = 'F')
                    ) THEN 'TROCA_AGENDADA'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_NA_DISPUTA' THEN 'DISPUTA_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'EM_DISPUTA' THEN 'DISPUTA'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADO' THEN 'SELLER_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'SOLICITACAO_PENDENTE' THEN 'TROCA_PENDENTE'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_POR_FOTO' THEN 'FOTO_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'PENDENTE_FOTO' THEN 'PENDENTE_FOTO'
                    WHEN (
                        (CURRENT_DATE > DATE(tab.data_base_troca) + INTERVAL {$auxiliares['dias_normal']} DAY OR
                            `tab`.id_tipo_frete = 2) AND
                        CURRENT_DATE <= DATE(tab.data_base_troca) + INTERVAL {$auxiliares['dias_defeito']} DAY
                    ) THEN 'DISPONIVEL_SO_TROCA'
                    WHEN (
                        CURRENT_DATE <= DATE(tab.data_base_troca) + INTERVAL {$auxiliares['dias_normal']} DAY AND
                            `tab`.id_tipo_frete <> 2
                    ) THEN 'DISPONIVEL_TROCA_E_DEFEITO'
                    WHEN (
                        CURRENT_DATE > DATE(tab.data_base_troca) + INTERVAL {$auxiliares['dias_defeito']} DAY
                    ) THEN 'PASSOU_PRAZO'
                    ELSE 'INDISPONIVEL'
                END situacao_solicitacao,
                IF (
                    tab.data_base_troca,
                    CURRENT_DATE <= DATE(tab.data_base_troca) + INTERVAL {$auxiliares['dias_normal']} DAY,
                    FALSE
                ) `esta_no_periodo_troca_normal`,
                # Pagamento
                transacao_financeiras.data_atualizacao data_pagamento,
                # Produto, Entrega, Cliente, Vendedor
                tab.*
            FROM (
                SELECT
                    # Produto
                    entregas_faturamento_item.id id_entregas_faturamento_item,
                    entregas_faturamento_item.origem,
                    entregas_faturamento_item.id_produto,
                    entregas.id_tipo_frete,
                    LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome_comercial,
                    entregas_faturamento_item.nome_tamanho,
                    entregas_faturamento_item.uuid_produto,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto_produto,
                    # Entrega
                    entregas_faturamento_item.data_entrega data_retirada,
                    COALESCE(entregas_faturamento_item.data_base_troca, NOW()) data_base_troca,
                    # Cliente
                    cliente_colaboradores.id id_cliente,
                    cliente_colaboradores.usuario_meulook nome_cliente,
                    COALESCE(cliente_colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_cliente,
                    cliente_colaboradores.razao_social razao_social_cliente,
                    cliente_colaboradores.telefone telefone_cliente,
                    (
                        SELECT correios_atendimento.numeroColeta
                        FROM correios_atendimento
                        WHERE correios_atendimento.id_cliente = cliente_colaboradores.id
                            AND correios_atendimento.status = 'A'
                            AND correios_atendimento.prazo > NOW()
                        ORDER BY correios_atendimento.data_verificacao DESC
                        LIMIT 1
                    ) ultimo_numero_coleta,
                    # Vendedor
                    vendedor_colaboradores.id id_vendedor,
                    vendedor_colaboradores.usuario_meulook nome_vendedor,
                    COALESCE(vendedor_colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_vendedor,
                    vendedor_colaboradores.razao_social razao_social_vendedor,
                    vendedor_colaboradores.telefone telefone_vendedor,
                    entregas_faturamento_item.id_transacao
                FROM entregas_faturamento_item
                INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
                INNER JOIN colaboradores cliente_colaboradores ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
                INNER JOIN colaboradores vendedor_colaboradores ON vendedor_colaboradores.id = produtos.id_fornecedor
                INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                WHERE produtos.id NOT IN ($produtosFreteSql) $whereInterno
            ) tab
            # Data compra
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = tab.id_transacao
            # Solicitações
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = tab.uuid_produto
            # Trocas agendadas
            LEFT JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = tab.uuid_produto
            # Trocas devolvidas
            LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = tab.uuid_produto
            WHERE TRUE $where
            ORDER BY $order
            $limit;",
            $binds
        );
        if (empty($consulta)) {
            return [];
        }

        $consulta = array_map(function ($item) use ($auxiliares, $origem) {
            $situacao = $item['situacao_solicitacao'];
            $dataBaseTroca = $item['data_base_troca'];
            $dataAtualizacaoSolicitacao = $item['data_atualizacao_solicitacao'] ?? '';
            $item['observacao'] = TrocaFilaSolicitacoesService::retornaTextoSituacaoTroca(
                $situacao,
                $dataBaseTroca,
                $dataAtualizacaoSolicitacao,
                $origem,
                $auxiliares
            );

            $camposData = ['data_pagamento', 'data_base_troca', 'data_retirada', 'data_vencimento'];
            foreach ($camposData as $campo) {
                if (!empty($item[$campo])) {
                    $item[$campo] = (new DateTime($item[$campo]))->format('d/m/Y H:i:s');
                }
            }

            unset($item['data_atualizacao_solicitacao']);
            return $item;
        }, $consulta);

        return $consulta;
    }

    // --Commented out by Inspection START (18/08/2022 17:28):
    //    public static function verificaTrocaTotal(\PDO $conexao, string $uuid)
    //    {
    //        $sqlTotal = "SELECT COUNT(1) qte
    //            FROM transacao_financeiras_produtos_itens
    //            WHERE EXISTS(SELECT 1 FROM faturamento_item WHERE faturamento_item.uuid = transacao_financeiras_produtos_itens.uuid AND faturamento_item.situacao IN (6,8,9,12)) AND
    //                transacao_financeiras_produtos_itens.tipo_item = 'PR' AND
    //                transacao_financeiras_produtos_itens.id_transacao = (SELECT transacao_financeiras_produtos_itens.id_transacao
    //                                                                    FROM transacao_financeiras_produtos_itens
    //                                                                    WHERE transacao_financeiras_produtos_itens.uuid = :uuid);";
    //        $preparaTotal = $conexao->prepare($sqlTotal);
    //        $preparaTotal->bindValue(':uuid', $uuid);
    //        $preparaTotal->execute();
    //        $total = intval($preparaTotal->fetch(PDO::FETCH_ASSOC)["qte"]);
    //
    //        $sqlTrocas = "SELECT COUNT(1) qte
    //            FROM transacao_financeiras_produtos_itens
    //            WHERE (EXISTS (SELECT 1
    //                    FROM troca_pendente_agendamento
    //                    WHERE troca_pendente_agendamento.uuid = transacao_financeiras_produtos_itens.uuid
    //                ) OR EXISTS (SELECT 1
    //                    FROM faturamento_item
    //                    WHERE faturamento_item.uuid = transacao_financeiras_produtos_itens.uuid AND
    //                    faturamento_item.situacao IN (8,9,12,19)
    //                )) AND
    //                transacao_financeiras_produtos_itens.id_transacao IN (SELECT transacao_financeiras_produtos_itens.id_transacao
    //                                                                    FROM transacao_financeiras_produtos_itens
    //                                                                    WHERE transacao_financeiras_produtos_itens.uuid = :uuid)";
    //        $preparaTroca = $conexao->prepare($sqlTrocas);
    //        $preparaTroca->bindValue(':uuid', $uuid);
    //        $preparaTroca->execute();
    //        $troca = intval($preparaTroca->fetch(PDO::FETCH_ASSOC)["qte"]);
    //
    //        if ($troca == $total) {
    //            return true;
    //        } else {
    //            return false;
    //        }
    //    }
    // --Commented out by Inspection STOP (18/08/2022 17:28)

    // --Commented out by Inspection START (18/08/2022 17:28):
    //    public static function updateValorSemPonto(\PDO $conexao, string $uuid)
    //    {
    //        $sqlLancamento = "UPDATE lancamento_financeiro_pendente
    //                SET lancamento_financeiro_pendente.valor = (SELECT faturamento_item.preco FROM faturamento_item WHERE faturamento_item.uuid = lancamento_financeiro_pendente.numero_documento),
    //                    lancamento_financeiro_pendente.valor_total = (SELECT faturamento_item.preco FROM faturamento_item WHERE faturamento_item.uuid = lancamento_financeiro_pendente.numero_documento)
    //                    WHERE lancamento_financeiro_pendente.origem = 'TR' AND
    //                        lancamento_financeiro_pendente.numero_documento = (SELECT lancamento_financeiro_pendente.numero_documento
    //                                                                        FROM lancamento_financeiro_pendente
    //                                                                        WHERE lancamento_financeiro_pendente.origem = 'TE' AND
    //                                                                        lancamento_financeiro_pendente.transacao_origem IN (SELECT lancamento_financeiro_pendente.transacao_origem
    //                                                                                                                            FROM lancamento_financeiro_pendente
    //                                                                                                                            WHERE lancamento_financeiro_pendente.numero_documento = '$uuid'))";
    //        $stmtLancamento = $conexao->prepare($sqlLancamento);
    //        $stmtLancamento->execute();
    //
    //        $sqlTrocaAgendada = "UPDATE troca_pendente_agendamento
    //                SET troca_pendente_agendamento.preco = (SELECT faturamento_item.preco FROM faturamento_item WHERE faturamento_item.uuid = troca_pendente_agendamento.uuid)
    //                WHERE troca_pendente_agendamento.uuid = (SELECT lancamento_financeiro_pendente.numero_documento
    //                                                                    FROM lancamento_financeiro_pendente
    //                                                                    WHERE lancamento_financeiro_pendente.origem = 'TE' AND
    //                                                                    lancamento_financeiro_pendente.transacao_origem IN (SELECT lancamento_financeiro_pendente.transacao_origem
    //                                                                                                                        FROM lancamento_financeiro_pendente
    //                                                                                                                        WHERE lancamento_financeiro_pendente.numero_documento = '$uuid'))";
    //        $stmtTrocaAgendada = $conexao->prepare($sqlTrocaAgendada);
    //        $stmtTrocaAgendada->execute();
    //    }
    // --Commented out by Inspection STOP (18/08/2022 17:28)

    // --Commented out by Inspection START (18/08/2022 17:28):
    //    public static function deletePontoLancamento(\PDO $conexao, string $uuid)
    //    {
    //        $sql = "DELETE FROM lancamento_financeiro_pendente
    //                WHERE lancamento_financeiro_pendente.origem = 'TE' AND
    //                lancamento_financeiro_pendente.transacao_origem IN (SELECT lancamento_financeiro_pendente.transacao_origem
    //                                                                    FROM lancamento_financeiro_pendente
    //                                                                    WHERE lancamento_financeiro_pendente.numero_documento = :uuid)";
    //        $stmt = $conexao->prepare($sql);
    //        $stmt->execute([
    //            'uuid' => $uuid
    //        ]);
    //    }
    // --Commented out by Inspection STOP (18/08/2022 17:28)

    public static function removeTrocaAgendadaMeuLook(string $uuid): void
    {
        $linhasAlteradas = FacadesDB::delete(
            "DELETE FROM troca_fila_solicitacoes
            WHERE troca_fila_solicitacoes.uuid_produto = :uuid;",
            ['uuid' => $uuid]
        );

        if ($linhasAlteradas !== 1) {
            self::removeTrocaAgendadadaNormalMeuLook($uuid);
        }
    }
    public static function removeTrocaPendenteAgendamentoMobileStock(): void
    {
        FacadesDB::delete(
            "DELETE FROM troca_pendente_agendamento
            WHERE
                troca_pendente_agendamento.tipo_agendamento = 'MS'
                AND troca_pendente_agendamento.data_criacao < DATE_SUB(NOW(), INTERVAL 90 DAY);"
        );
    }

    public static function removeTrocaAgendadadaNormalMeuLook(string $uuid): void
    {
        $linhasAlteradas = FacadesDB::delete(
            "DELETE FROM troca_pendente_agendamento
            WHERE troca_pendente_agendamento.uuid = :uuid;",
            ['uuid' => $uuid]
        );

        if ($linhasAlteradas !== 1) {
            throw new Exception("Erro ao remover troca agendada $uuid");
        }
    }

    public static function atualizarDataVencimentoCorrecoes(PDO $conexao): void
    {
        $stmt = $conexao->prepare(
            "UPDATE troca_pendente_agendamento
                SET troca_pendente_agendamento.data_vencimento = NOW() + INTERVAL (SELECT qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) DAY
                WHERE troca_pendente_agendamento.id_cliente IN (
                    SELECT lancamento_financeiro_pendente.id_colaborador
                    FROM lancamento_financeiro_pendente
                    WHERE lancamento_financeiro_pendente.origem = 'CP'
                        AND DATE(lancamento_financeiro_pendente.data_emissao) = CURDATE() - INTERVAL 1 DAY
                    GROUP BY lancamento_financeiro_pendente.id_colaborador
                )"
        );
        $stmt->execute();
    }

    public static function buscaTrocas(PDO $conexao, int $idCliente): array
    {
        $sql = $conexao->prepare(
            "SELECT
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.uuid_produto,
                produtos.descricao,
                entregas_faturamento_item.nome_tamanho,
                DATE_FORMAT(COALESCE(troca_fila_solicitacoes.data_criacao,troca_pendente_agendamento.data_hora), '%d/%m/%Y %H:%i') data_hora,
                UNIX_TIMESTAMP(COALESCE(troca_fila_solicitacoes.data_criacao,troca_pendente_agendamento.data_hora)) data_nao_formatada,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto,
                'AGENDADO' situacao,
                troca_pendente_agendamento.defeito
            FROM entregas_faturamento_item
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = entregas_faturamento_item.uuid_produto
            LEFT JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = entregas_faturamento_item.uuid_produto
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            WHERE
                entregas_faturamento_item.id_cliente = :id_cliente
                AND
                    IF(troca_pendente_agendamento.tipo_agendamento = 'ML',
                        (troca_fila_solicitacoes.situacao = 'APROVADO' OR troca_pendente_agendamento.defeito = 'F'),
                        troca_pendente_agendamento.tipo_agendamento = 'MS'
                    )
                AND NOT EXISTS(SELECT 1 FROM entregas_devolucoes_item WHERE entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto)

            UNION
            SELECT
                entregas_devolucoes_item.id_produto,
                entregas_devolucoes_item.uuid_produto,
                produtos.descricao,
                entregas_devolucoes_item.nome_tamanho,
                DATE_FORMAT(entregas_devolucoes_item.data_criacao, '%d/%m/%Y %H:%i') data_hora,
                UNIX_TIMESTAMP(entregas_devolucoes_item.data_criacao) data_nao_formatada,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto,
                entregas_devolucoes_item.situacao,
                entregas_devolucoes_item.tipo
            FROM entregas_devolucoes_item
            INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
            WHERE entregas_devolucoes_item.id_cliente = :id_cliente
            ORDER BY data_nao_formatada DESC
        "
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
        $resultado = array_map(function ($item) {
            $conversor = EntregasDevolucoesItem::ConversorSiglasEntregasDevolucoesItens($item['situacao'], '');
            $item['situacao'] = $conversor['situacao'];
            return $item;
        }, $resultado);
        return $resultado;
    }
}
