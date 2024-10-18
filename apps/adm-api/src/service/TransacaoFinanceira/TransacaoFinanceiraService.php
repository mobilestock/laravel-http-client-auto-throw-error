<?php

namespace MobileStock\service\TransacaoFinanceira;

use DomainException;
use Error;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\jobs\Cancelamento;
use MobileStock\jobs\Pagamento;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\Lancamento;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\PedidoItem as PedidoItemModel;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceira;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\CancelamentoProdutos;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\Lancamento\LancamentoService;
use MobileStock\service\Pagamento\LancamentoPendenteService;
use MobileStock\service\PedidoItem\PedidoItem;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @property string $motivo_cancelamento FRAUDE | CLIENTE_DESISTIU
 *
 * @deprecated
 * @issue https://github.com/mobilestock/backend/issues/109
 */
class TransacaoFinanceiraService extends TransacaoFinanceira
{
    /**
     * @issue https://github.com/mobilestock/backend/issues/118
     * @return TransacaoFinanceiraService[]
     */
    public static function consultaTransacoesPendentesSicoob(): array
    {
        $transacoes = DB::select(
            "SELECT
                        transacao_financeiras.id,
                        transacao_financeiras.cod_transacao
                    FROM transacao_financeiras
                    WHERE transacao_financeiras.emissor_transacao = 'Sicoob'
                      AND transacao_financeiras.status = 'PE'"
        );

        $transacoes = array_map(function (array $dados) {
            $transacao = new TransacaoFinanceiraService();
            $transacao->id = $dados['id'];
            $transacao->cod_transacao = $dados['cod_transacao'];

            return $transacao;
        }, $transacoes);

        return $transacoes;
    }

    public static function listaTransacoesApi(
        PDO $conexao,
        int $idCliente,
        string $pesquisa,
        int $pagina,
        string $tipoPesquisa = 'ESTATICA'
    ): array {
        $limit = 10;
        $offset = ($pagina - 1) * $limit;
        $camposSelectEPesquisa = "
                transacao_financeiras.id,
                transacao_financeiras.status,
                transacao_financeiras.valor_liquido,
                transacao_financeiras.qrcode_pix,
                transacao_financeiras.qrcode_text_pix";
        $stmt = $conexao->prepare(
            "SELECT
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d/%m/%Y %H:%i:%s') AS `data_atualizacao`,
                transacao_financeiras_metadados.valor AS `referencia`,
                $camposSelectEPesquisa
             FROM transacao_financeiras
             INNER JOIN transacao_financeiras_metadados ON transacao_financeiras.id = transacao_financeiras_metadados.id_transacao
                AND transacao_financeiras_metadados.chave = 'ID_UNICO'
             WHERE transacao_financeiras.origem_transacao = 'ZA'
               AND transacao_financeiras.pagador = :id_cliente
               AND CONCAT_WS(
                       ' ',
                       transacao_financeiras_metadados.valor,
                       DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s'),
                       DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d/%m/%Y %H:%i:%s'),
                       $camposSelectEPesquisa
                   ) REGEXP :pesquisa
             GROUP BY transacao_financeiras.id
             ORDER BY transacao_financeiras.id DESC
             LIMIT :limite OFFSET :offset"
        );
        $stmt->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        if ($tipoPesquisa === 'ESTATICA') {
            $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

            return $transacao;
        }

        $transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $transacoes = array_map(function ($transacao) {
            $transacao['valor_liquido'] = (float) $transacao['valor_liquido'];

            return $transacao;
        }, $transacoes);

        $stmt = $conexao->prepare(
            "SELECT COUNT(transacao_financeiras.id)
             FROM transacao_financeiras
             INNER JOIN transacao_financeiras_metadados ON transacao_financeiras.id = transacao_financeiras_metadados.id_transacao
                AND transacao_financeiras_metadados.chave = 'ID_UNICO'
             WHERE transacao_financeiras.origem_transacao = 'ZA'
               AND transacao_financeiras.pagador = :id_cliente
               AND CONCAT_WS(
                       ' ',
                       transacao_financeiras_metadados.valor,
                       DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s'),
                       DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d/%m/%Y %H:%i:%s'),
                       $camposSelectEPesquisa
                   ) REGEXP :pesquisa"
        );
        $stmt->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();

        $qtdRegistros = $stmt->fetchColumn();
        $qtdPaginas = max(ceil($qtdRegistros / $limit), 1);

        return ['transacoes' => $transacoes, 'qtd_paginas' => $qtdPaginas];
    }

    public function criaTransacao(PDO $conexao): int
    {
        $sql =
            'INSERT INTO transacao_financeiras (' .
            implode(',', array_keys(array_filter(get_object_vars($this)))) .
            ') VALUES (';
        foreach ($this as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql .= ":{$key},";
        }

        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1) . ')';
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);

        $this->id = $conexao->lastInsertId();
        return $this->id;
    }

    public function atualizaTransacao(PDO $conexao): int
    {
        $this->nome_tabela = 'transacao_financeiras';
        $gerador = new GeradorSql($this);
        $sql = $gerador->updateSomenteDadosPreenchidos();

        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        $linhas = $stmt->rowCount();

        return $linhas;
    }

    //    public function retornIdTransacao(pdo $conexao)
    //    {
    //        $sql = "SELECT transacao_financeiras.id FROM transacao_financeiras WHERE transacao_financeiras.cod_transacao = " . $this->cod_transacao;
    //
    //        $resultado = $conexao->query($sql);
    //        return $resultado->fetch(PDO::FETCH_ASSOC)['id'];
    //    }
    public function BloqueiaLinhaTransacao(PDO $conexao): void
    {
        $dadosTransacao = $conexao
            ->query(
                'SELECT 1 from transacao_financeiras WHERE transacao_financeiras.id = ' .
                    $this->id .
                    ' LOCK IN SHARE MODE'
            )
            ->fetch(PDO::FETCH_ASSOC);

        if (empty($dadosTransacao)) {
            throw new PagamentoTransacaoNaoExisteException();
        }
    }

    /**
     * @deprecated https://github.com/mobilestock/backend/issues/172
     */
    public function retornaTransacao(PDO $conexao): ?array
    {
        $dados = [];
        $sql = "SELECT transacao_financeiras.id,
                    transacao_financeiras.cod_transacao,
                    transacao_financeiras.data_criacao,
                    transacao_financeiras.data_atualizacao,
                    transacao_financeiras.status,
                    transacao_financeiras.url_boleto,
                    transacao_financeiras.valor_total,
                    transacao_financeiras.valor_credito,
                    transacao_financeiras.valor_credito_bloqueado,
                    transacao_financeiras.valor_desconto,
                    transacao_financeiras.valor_acrescimo,
                    transacao_financeiras.valor_comissao_fornecedor,
                    transacao_financeiras.valor_liquido,
                    transacao_financeiras.valor_itens,
                    transacao_financeiras.valor_taxas,
                    transacao_financeiras.numero_transacao,
                    transacao_financeiras.pagador id_pagador,
                    IF(transacao_financeiras.metodo_pagamento = 'DE', '', (select api_colaboradores.id_zoop from api_colaboradores where api_colaboradores.id_colaborador = transacao_financeiras.pagador LIMIT 1)) id_zoop_pagador,
                    transacao_financeiras.responsavel,
                    transacao_financeiras.metodo_pagamento,
                    transacao_financeiras.id_usuario,
                    transacao_financeiras.id_usuario_pagamento,
                    transacao_financeiras.numero_parcelas,
                    transacao_financeiras.metodos_pagamentos_disponiveis,
                    transacao_financeiras.barcode,
                    transacao_financeiras.origem_transacao,
                    transacao_financeiras.qrcode_pix,
                    transacao_financeiras.qrcode_text_pix,
                    transacao_financeiras.url_fatura,
                    transacao_financeiras.emissor_transacao,
                    transacao_financeiras.juros_pago_split,
                    COALESCE((SELECT IF(transacao_financeiras.origem_transacao = 'ED',configuracoes.num_parcela_limit_meuestoque,configuracoes.num_parcela_limit_mobile) FROM configuracoes LIMIT 1),3) numero_max_parcela,
                    COALESCE (
                        (
                            SELECT transacao_financeiras_metadados.valor
                            FROM transacao_financeiras_metadados
                            WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                                AND transacao_financeiras_metadados.chave = 'VALOR_FRETE'
                        ),
                        0
                    ) valor_frete,
                    COALESCE ((SELECT SUM(transacao_financeiras_produtos_itens.preco) FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                                                                                                    AND transacao_financeiras_produtos_itens.tipo_item = 'AP'), 0) valor_acrescimo_cnpj_nao_possui_qtd_produtos_necessarios,
                    COALESCE(
                        (SELECT
                            SUM(transacao_financeiras_produtos_itens.preco)
                         FROM transacao_financeiras_produtos_itens
                         WHERE transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                            AND transacao_financeiras_produtos_itens.tipo_item IN ('CE', 'CM_ENTREGA')),
                        0) valor_comissao_entrega,
                    COALESCE((SELECT configuracoes.dados_pagamento_padrao FROM configuracoes LIMIT 1),'') dados_pagamento_default,
                    COALESCE((SELECT colaboradores.razao_social FROM colaboradores where colaboradores.id = transacao_financeiras.pagador LIMIT 1),'') razao_social
                FROM transacao_financeiras
                WHERE ";

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if ($key == 'id') {
                $dados[] = 'transacao_financeiras.' . $key . ' = ' . $valor;
            }
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser consultada');
        }

        $sql .= implode(' AND ', $dados);
        $dados = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);

        if (empty($dados)) {
            return null;
        }

        $dados['valor_liquido'] = (float) $dados['valor_liquido'];
        $dados['id'] = (int) $dados['id'];
        $dados['valor_credito'] = (float) $dados['valor_credito'];
        $dados['valor_credito_bloqueado'] = (float) $dados['valor_credito_bloqueado'];
        $dados['valor_itens'] = (float) $dados['valor_itens'];
        $dados['valor_acrescimo'] = (float) $dados['valor_acrescimo'];
        $dados['valor_frete'] = (float) $dados['valor_frete'];
        $dados['valor_comissao_entrega'] = (float) $dados['valor_comissao_entrega'];
        $dados['valor_acrescimo_cnpj_nao_possui_qtd_produtos_necessarios'] =
            (float) $dados['valor_acrescimo_cnpj_nao_possui_qtd_produtos_necessarios'];
        $dados['dados_pagamento_default'] = json_decode($dados['dados_pagamento_default']);

        $this->id = $dados['id'];
        $this->cod_transacao = $dados['cod_transacao'];
        $this->data_criacao = $dados['data_criacao'];
        $this->data_atualizacao = $dados['data_atualizacao'];
        $this->status = $dados['status'];
        $this->url_boleto = $dados['url_boleto'];
        $this->valor_total = $dados['valor_total'];
        $this->valor_credito = $dados['valor_credito'];
        $this->valor_acrescimo = $dados['valor_acrescimo'];
        $this->valor_desconto = $dados['valor_desconto'];
        $this->valor_liquido = $dados['valor_liquido'];
        $this->valor_itens = $dados['valor_itens'];
        $this->valor_taxas = $dados['valor_taxas'];
        $this->juros_pago_split = $dados['juros_pago_split'];
        $this->numero_transacao = $dados['numero_transacao'];
        $this->responsavel = $dados['responsavel'];
        $this->pagador = $dados['id_pagador'];
        $this->metodo_pagamento = $dados['metodo_pagamento'];
        $this->id_usuario = $dados['id_usuario'];
        $this->id_usuario_pagamento = $dados['id_usuario_pagamento'];
        $this->numero_parcelas = $dados['numero_parcelas'];
        $this->razao_social = $dados['razao_social'];
        $this->id_zoop_pagador = $dados['id_zoop_pagador'];
        $this->numero_max_parcela = $dados['id_zoop_pagador'];
        $this->origem_transacao = $dados['origem_transacao'];
        $this->qrcode_pix = $dados['qrcode_pix'];
        $this->qrcode_text_pix = $dados['qrcode_text_pix'];
        $this->emissor_transacao = $dados['emissor_transacao'];
        $this->dados_pagamento_default = $dados['dados_pagamento_default'];

        $dados['metodos_pagamentos_disponiveis'] = explode(',', $dados['metodos_pagamentos_disponiveis']);
        if ($dados['valor_frete'] > 0) {
            $dados['valor_itens_sem_frete'] = (float) $dados['valor_itens'] - $dados['valor_frete'];
        }

        return $dados;
    }

    public function calcularTransacao(PDO $conexao, int $usaCredito)
    {
        $SQL =
            'CALL transacao_financeira_calcula(' .
            $this->id .
            ",'" .
            $this->metodo_pagamento .
            "'," .
            $this->numero_parcelas .
            ', ' .
            $usaCredito .
            ')';
        $conexao->query($SQL);
    }

    public function removeTransacao(PDO $conn, array $arrayId)
    {
        $ids = implode(',', $arrayId);
        $sql = "DELETE FROM transacao_financeiras WHERE transacao_financeiras.id IN ($ids)";

        return $conn->exec($sql);
    }

    public function removeTransacoesEmAberto(PDO $conn)
    {
        $conn->prepare("DELETE FROM transacao_financeiras WHERE pagador = :idCliente AND status = 'CR'")->execute([
            ':idCliente' => $this->pagador,
        ]);
    }

    public function abateCreditoCliente(PDO $conexao)
    {
        $conexao->query("CALL transacao_financeira_pagamento_credito_debito($this->id)");
    }

    public function atualizaSituacaoTransacao(PDO $conexao)
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras.status,
                transacao_financeiras.pagador,
                transacao_financeiras.metodo_pagamento,
                transacao_financeiras.origem_transacao
            FROM transacao_financeiras
            WHERE transacao_financeiras.id = :id;"
        );
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $informacoes = $stmt->fetch(PDO::FETCH_ASSOC);
        $statusAnterior = (string) $informacoes['status'];
        [
            'metodo_pagamento' => $this->metodo_pagamento,
            'origem_transacao' => $this->origem_transacao,
            'pagador' => $this->pagador,
        ] = $informacoes;

        $linhasAtualizadas = $this->atualizaTransacao($conexao);

        if (
            $linhasAtualizadas === 1 &&
            $statusAnterior !== 'PA' &&
            $this->status === 'PA' &&
            $this->origem_transacao === 'ET'
        ) {
            TransacaoFinanceirasProdutosTrocasService::desvinculaPixDeTroca($conexao, $this->id, $this->pagador);
        }

        if ($linhasAtualizadas !== 1 || $statusAnterior !== 'PE' || $this->status !== 'PA') {
            return;
        }
        # Fraude
        $ehFraude = false;
        $temPedidoMobileStock = TransacaoFinanceirasMetadadosService::temPedidoMobileStock($conexao, $this->id);
        if (
            in_array($this->origem_transacao, ['MP', 'ML', 'MC']) &&
            ($this->origem_transacao === 'MP' ? $temPedidoMobileStock : true)
        ) {
            switch ($this->origem_transacao) {
                case 'MP':
                    $origem = 'MS';
                    break;
                case 'ML':
                    $origem = 'ML';
                    break;
                case 'MC':
                    $origem = 'LP';
                    break;
            }
            $colaboradorService = new ColaboradoresService();
            $colaboradorService->id = $this->pagador;
            $colaboradorService->origem_transacao = $origem;
            $colaboradorService->buscaSituacaoFraude($conexao, ['CARTAO']);

            if ($this->metodo_pagamento === 'CA') {
                if (is_null($colaboradorService->situacao_fraude)) {
                    $colaboradorService->insereFraude($conexao, 'CARTAO');
                    $colaboradorService->situacao_fraude = 'PE';
                } elseif ($colaboradorService->situacao_fraude === 'LT') {
                    $colaboradorService->situacao_fraude = 'PE';
                    $colaboradorService->alteraSituacaoFraude($conexao, 'CARTAO');
                }
            }

            $ehFraude = in_array($colaboradorService->situacao_fraude ?: 'LG', ['PE', 'FR']);
        }
        if (in_array($this->origem_transacao, ['MP', 'ML'])) {
            $tipoFreteLogistica = TransacaoFinanceiraItemProdutoService::buscaFreteTransacao($conexao, $this->id);
            $listaProdutos = PedidoItem::buscaItensCarrinho(
                $conexao,
                $this->pagador,
                $this->origem_transacao,
                $this->id
            );

            // atualiza situacao PI
            $temDI = false;
            if (count($listaProdutos) > 0) {
                $pedidoItem = new PedidoItemModel();
                $pedidoItem->id_transacao = $this->id;
                $pedidoItem->situacao = !$ehFraude ? 'DI' : 'FR';

                $produtosAtualizar = array_filter(
                    $listaProdutos,
                    fn(array $produto) => $produto['situacao'] !== $pedidoItem->situacao
                );
                if (count($produtosAtualizar) > 0) {
                    $pedidoItem->atualizaIdTransacaoPI(array_column($produtosAtualizar, 'uuid'));
                }

                $temDI = $pedidoItem->situacao === 'DI';
            }

            if ($this->origem_transacao === 'ML' && count($listaProdutos) > 0) {
                PedidoItemMeuLookService::atualizaInfoPagamentoProduto(
                    $conexao,
                    $this->id,
                    $tipoFreteLogistica,
                    array_column($listaProdutos, 'uuid')
                );
            }

            if ($tipoFreteLogistica && $temDI) {
                $logisticaItem = new LogisticaItemModel();
                $logisticaItem->id_transacao = $this->id;
                $logisticaItem->id_cliente = $this->pagador;
                $logisticaItem->id_colaborador_tipo_frete = $tipoFreteLogistica;
                $logisticaItem->liberarLogistica($this->origem_transacao === Origem::ML ? Origem::ML : Origem::MS);
            }
        }

        $lancamentosPendente = LancamentoService::listaLancamentosPendentesTransacao($conexao, $this->id);
        $qtdLancamentosInseridos = count($lancamentosPendente);

        $listaLancamentosFlip = array_map(function (array $lancamento) {
            $lancamentoObj = new Lancamento(
                $lancamento['tipo'],
                1,
                $lancamento['origem'],
                $lancamento['id_colaborador'],
                null,
                $lancamento['valor'],
                Auth::id(),
                7
            );

            $lancamentoObj->transacao_origem = $this->id;
            $lancamentoObj->numero_documento = $lancamento['numero_documento'];
            $lancamentoObj->id_usuario = $lancamento['id_usuario'];
            $lancamentoObj->observacao = $lancamento['observacao'];
            $lancamentoObj->cod_transacao = $lancamento['cod_transacao'];
            $lancamentoObj->sequencia = $lancamento['sequencia'];
            $lancamentoObj->valor_total = $lancamento['valor_total'];
            $lancamentoObj->valor_pago = $lancamento['valor_pago'];

            return $lancamentoObj;
        }, $lancamentosPendente);

        LancamentoService::insereVarios($conexao, $listaLancamentosFlip);

        if ($qtdLancamentosInseridos > 0) {
            $qtdLancamentosRemovidos = LancamentoPendenteService::removeLancamentos(
                $conexao,
                array_column($listaLancamentosFlip, 'sequencia')
            );

            if ($qtdLancamentosRemovidos !== $qtdLancamentosInseridos) {
                throw new RuntimeException('Quantidade inconsistente de lançamentos alterados.');
            }
        }
        if (!in_array($this->origem_transacao, ['ML', 'ZA', 'ET'])) {
            return;
        }

        $job = new Pagamento([
            'origem_transacao' => $this->origem_transacao,
            'id' => $this->id,
            'pagador' => $this->pagador,
        ]);
        dispatch($job->afterCommit());
    }

    //    public function insereProdutoPagoPainel(PDO $conn, int $id_produto, int $idUsuario, array $grade): void
    //    {
    //         require_once __DIR__ . "/../../../model/estoque.php";
    //        $valor_produto = ProdutosRepository::retornValorProduto($id_produto, $this->pagador);
    //        if (!$valor_produto) throw new \Exception("Erro para identificar valor do produto", 1);
    //
    //        $direitoItem = new PedidoItem();
    //        $direitoItem->id_produto = $id_produto;
    //        $direitoItem->id_cliente = $this->pagador;
    //        $direitoItem->preco = $valor_produto['valor'];
    //        $direitoItem->id_transacao = $this->id;
    //        $direitoItem->situacao = '1';
    //        $direitoItem->grade = $grade;
    //        $direitoItem->adicionaPedidoItem($conn);
    //
    //        $adicionaProduto = new TransacaoFinanceiraItemProdutoService;
    //        foreach ($direitoItem->grade as $key => $value) {
    //            $adicionaProduto->id_transacao = $this->id;
    //            $adicionaProduto->id_produto = $id_produto;
    //            $adicionaProduto->nome_tamanho = $value['nome_tamanho'];
    //            $adicionaProduto->comissao_fornecedor = $valor_produto['valor_custo_produto'];
    //            $adicionaProduto->preco = $valor_produto['valor'];
    //            $adicionaProduto->id_fornecedor = $valor_produto['id_fornecedor'];
    //            $adicionaProduto->uuid_produto = $value['uuid'];
    //            $adicionaProduto->tipo_item = 'PR';
    //            $adicionaProduto->id_responsavel_estoque = $valor_produto['id_responsavel_estoque'];
    //            $adicionaProduto->criaTransacaoItemProduto($conn);
    //
    //            $adicionaProduto->id = null;
    //        }

    //        $qtdProdutos = array_reduce($grade, fn(int $total, array $item) => $total + $item['qtd'], 0);
    //        $direitoItem->situacao = 2;
    //
    //        $listaProdutos = TransacaoFinanceiraItemProdutoService::buscaProdutosTransacao($conn, $this->id);
    //        $direitoItem->atualizaIdTransacaoPI($conn, $listaProdutos);
    //
    //        $this->metodo_pagamento = 'DE';
    //        $this->numero_parcelas = '1';
    //        $this->calcularTransacao($conn, 1);
    //
    //        $this->retornaTransacao($conn);
    //        $processadorPagamentos = new ProcessadorPagamentos($conn, $this, [PagamentoCreditoInterno::class], $idUsuario);
    //        $processadorPagamentos->executa();
    //
    //        $this->retornaTransacao($conn);
    //        if ($this->valor_liquido !== 0.00) {
    //            throw new \Exception("Saldo nao foi suficiente para pagar os itens", 1);
    //        };
    //    }

    /**
     * https://github.com/mobilestock/backend/issues/124
     */
    public function removeTransacaoPaga(PDO $conexao, int $idUsuario)
    {
        switch ($this->status) {
            case 'CR':
                throw new BadRequestHttpException('Transacao do tipo CR nao pode ser removida por esse processo');
            case 'PE':
                LancamentoService::removeLancamentoTemporaria($conexao, $this->id);
                $this->abateLancamentosTransacao($conexao, $idUsuario);
                $this->cancelaTransferenciasDirecionadas($conexao);
                $this->status = 'CA';
                $this->atualizaTransacao($conexao);
                TransacaoFinanceirasProdutosTrocasService::removeProdutosTroca($conexao, $this->id);
                if (
                    $this->emissor_transacao === 'Iugu' &&
                    $this->metodo_pagamento === 'PX' &&
                    debug_backtrace(0, 2)[1]['function'] !== 'transacaoIugu'
                ) {
                    $iugu = new IuguHttpClient();
                    $iugu->put("/invoices/{$this->cod_transacao}/cancel");
                }
                break;
            case 'PA':
                $this->status = 'ES';
                if (!$this->atualizaTransacao($conexao)) {
                    throw new DomainException('Tentativa de cancelamento duplicado.');
                }
                $produtos = TransacaoFinanceiraItemProdutoService::buscaProdutosTransacao($this->id, ['PR', 'RF']);
                (new CancelamentoProdutos($produtos, $this->motivo_cancelamento))->direitosItem();
                break;
            default:
                throw new BadRequestHttpException('O status da transacao nao permite que a mesma seja excluida');
        }

        if (in_array($this->origem_transacao, ['ML', 'MS'])) {
            $this->removeDireitoDeItensSeExistir($conexao);
        }

        if ($this->origem_transacao === 'ZA') {
            $job = new Cancelamento([
                'pagador' => $this->pagador,
                'id' => $this->id,
            ]);

            dispatch($job->afterCommit());
        }
    }

    public function cancelaTransferenciasDirecionadas(PDO $conexao): void
    {
        $conexao->exec(
            "UPDATE transacao_financeira_split
                          SET transacao_financeira_split.situacao = 'CA'
                       WHERE transacao_financeira_split.id_transacao = $this->id
                          AND transacao_financeira_split.situacao = 'NA';"
        );
    }

    // public function geraSaqueTransacaoCancelada(\PDO $conexao, int $pagador, float $valor): void
    // {
    //     $conexao->exec(
    //         "INSERT INTO colaboradores_prioridade_pagamento (id_colaborador, valor_pagamento, valor_pago) VALUES({$pagador}, {$valor}, {$valor})"
    //     );
    //     $idSaque = $conexao->lastInsertId();

    //     ['id' => $idLancamento] = $conexao->query("SELECT lancamento_financeiro.id FROM lancamento_financeiro WHERE lancamento_financeiro.id_prioridade_saque = $idSaque")->fetch(PDO::FETCH_ASSOC);

    //     $recebivel = new RecebivelService();
    //     $recebivel->id_transacao = $this->id;
    //     $recebivel->id_lancamento = $idLancamento;
    //     $recebivel->id_zoop_recebivel = IuguServiceServico::geraIdentificacaoSplitIugu($this->cod_transacao, $pagador);
    //     // $recebivel->id_zoop_split = '';
    //     $recebivel->cod_transacao = $this->cod_transacao;
    //     $recebivel->valor = $valor;
    //     $recebivel->situacao = 'PA';
    //     $recebivel->num_parcela = 1;
    //     $recebivel->id_recebedor = $pagador;
    //     $recebivel->valor = $valor;
    //     $recebivel->valor_pago = $valor;
    //     $recebivel->recebivel_adiciona($conexao);

    // }

    //    public function buscaCalculoComissao(\PDO $conexao)
    //    {
    //        if (!$this->id) throw new Error("Erro interno: Id de transação inválido");
    //        $sql = "SELECT
    //            ((
    //                SELECT SUM(med_venda_produtos_consumidor_final.valor)
    //                FROM med_venda_produtos_consumidor_final
    //                WHERE med_venda_produtos_consumidor_final.id_transacao = transacao_financeiras.id
    //            ) - transacao_financeiras.valor_itens) comissao
    //            FROM transacao_financeiras
    //            WHERE id = {$this->id}";
    //        return $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);
    //    }

    // public function removeTransacoesEmAbertoConsumidorFinal(\PDO $conn, $idConsumidorFinal)
    // {
    //     $selectSQL = "SELECT transacao_financeiras.id
    //         FROM transacao_financeiras
    //         JOIN med_venda_produtos_consumidor_final ON med_venda_produtos_consumidor_final.id_transacao = transacao_financeiras.id
    //         WHERE transacao_financeiras.status = 'LK' AND med_venda_produtos_consumidor_final.id_consumidor_final = {$idConsumidorFinal}";
    //     $selectResults = $conn->query($selectSQL)->fetchAll(PDO::FETCH_ASSOC);

    //     if (sizeof($selectResults) == 0) return;

    //     $ids = implode(',', array_map(function ($id) {
    //         return $id['id'];
    //     }, $selectResults));

    //     $deleteNUpdateSQL = "
    //         DELETE transacao_financeiras
    //         FROM transacao_financeiras
    //         WHERE transacao_financeiras.id IN (" . $ids . ");

    //         UPDATE med_venda_produtos_consumidor_final
    //         SET med_venda_produtos_consumidor_final.id_transacao = NULL
    //         WHERE med_venda_produtos_consumidor_final.id_transacao IN (" . $ids . ");
    //     ";
    //     $conn->exec($deleteNUpdateSQL);
    // }

    public function buscaCreditoDisponivelAbateTroca(PDO $conexao, int $idCliente): float
    {
        return $conexao
            ->query(
                "SELECT (saldo_cliente($idCliente) + COALESCE((
                SELECT SUM(troca_pendente_agendamento.preco)
                FROM troca_pendente_agendamento
                INNER JOIN transacao_financeiras_produtos_trocas ON transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
                WHERE transacao_financeiras_produtos_trocas.id_cliente = $idCliente
            ), 0)) credito"
            )
            ->fetch(PDO::FETCH_ASSOC)['credito'];
    }
    public function buscaTrocasDisponiveisParaDescartePorValor(
        PDO $conexao,
        int $idCliente,
        float $valorDisponivel
    ): array {
        $consulta =
            $conexao
                ->query(
                    "SELECT
                troca_pendente_agendamento.preco,
                troca_pendente_agendamento.uuid
            FROM troca_pendente_agendamento
            WHERE troca_pendente_agendamento.id_cliente = $idCliente
                AND troca_pendente_agendamento.preco <= $valorDisponivel
            LIMIT 1"
                )
                ->fetch(PDO::FETCH_ASSOC) ?? [];

        if (!$consulta) {
            return [];
        }

        return $consulta;
    }
    public function existeTransacaoETPendente(): bool
    {
        $existeTransacaoET = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM transacao_financeiras
                WHERE transacao_financeiras.pagador = :id_cliente
                  AND transacao_financeiras.status = 'PE'
                  AND transacao_financeiras.origem_transacao = 'ET'
            ) AS `existe_transacao_ET_pendente`;",
            [
                ':id_cliente' => Auth::user()->id_colaborador,
            ]
        );

        return $existeTransacaoET;
    }

    public function abateLancamentosTransacao(PDO $conexao, int $idUsuario): void
    {
        $condicao = '';
        if ($this->status === 'PE') {
            $condicao = "AND lancamento_financeiro.origem = 'PC'";
        } elseif ($this->status === 'PA' && $this->motivo_cancelamento === 'FRAUDE') {
            $condicao = "AND lancamento_financeiro.origem NOT IN ('PC', 'FA')";
        }

        $consulta = $conexao
            ->query(
                "SELECT
                        lancamento_financeiro.id_colaborador,
                        SUM(IF(lancamento_financeiro.tipo = 'P', lancamento_financeiro.valor *-1, lancamento_financeiro.valor)) valor,
                        lancamento_financeiro.transacao_origem
                    FROM lancamento_financeiro
                    WHERE lancamento_financeiro.transacao_origem = $this->id
                        AND lancamento_financeiro.origem <> 'AU'
                        $condicao
                    GROUP BY lancamento_financeiro.id_colaborador"
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($consulta as $lancamento) {
            if (abs($lancamento['valor']) == 0) {
                continue;
            }

            $tipo = $lancamento['valor'] > 0 ? 'P' : 'R';
            $lancamentoObj = new Lancamento(
                $tipo,
                1,
                'ES',
                $lancamento['id_colaborador'],
                date('Y-m-d H:i:s'),
                abs($lancamento['valor']),
                $idUsuario,
                15
            );
            $lancamentoObj->transacao_origem = $lancamento['transacao_origem'];

            if (!LancamentoCrud::salva($conexao, $lancamentoObj)) {
                throw new DomainException('Não foi possivel abater lancamento.');
            }
        }
    }

    public function removeDireitoDeItensSeExistir(PDO $conexao): void
    {
        $sql = $conexao->prepare(
            "SELECT logistica_item.uuid_produto
            FROM logistica_item
            WHERE logistica_item.id_cliente = :idCliente
                AND logistica_item.id_transacao = :idTransacao
            UNION
            SELECT pedido_item.uuid
            FROM pedido_item
            WHERE pedido_item.id_cliente = :idCliente
              AND pedido_item.id_transacao = :idTransacao
              AND pedido_item.situacao IN ('DI', 'FR');"
        );
        $sql->bindValue(':idTransacao', $this->id, PDO::PARAM_INT);
        $sql->bindValue(':idCliente', $this->pagador, PDO::PARAM_INT);
        $sql->execute();
        $retorno = $sql->fetchAll(PDO::FETCH_ASSOC);

        $qtdRetornada = $sql->rowCount();

        if (empty($retorno)) {
            return;
        }

        $uuidProdutos = array_column($retorno, 'uuid_produto');

        [$sqlParam, $binds] = ConversorArray::criaBindValues($uuidProdutos, 'uuid_produto');

        $sql = $conexao->prepare(
            "DELETE FROM logistica_item
                WHERE logistica_item.id_cliente = :idCliente
                    AND logistica_item.uuid_produto IN ($sqlParam);

            DELETE FROM pedido_item
                WHERE pedido_item.id_cliente = :idCliente
                    AND pedido_item.uuid IN ($sqlParam)
                    AND pedido_item.situacao IN ('DI', 'FR');"
        );

        $sql->bindValue(':idCliente', $this->pagador, PDO::PARAM_INT);
        foreach ($binds as $key => $uuid) {
            $sql->bindValue($key, $uuid, PDO::PARAM_STR);
        }
        $sql->execute();

        $linhasAfetadas = 0;
        do {
            $linhasAfetadas += $sql->rowCount();
        } while ($sql->nextRowset());

        if ($qtdRetornada !== $linhasAfetadas) {
            throw new Exception('Erro ao fazer a remoção do direito aos itens');
        }
    }

    public function buscaTransacaoCR(PDO $conexao): void
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras.id,
                transacao_financeiras.metodos_pagamentos_disponiveis
            FROM transacao_financeiras
                      WHERE transacao_financeiras.pagador = :idColaborador
                        AND transacao_financeiras.status  = 'CR'"
        );
        $stmt->bindValue(':idColaborador', $this->pagador, PDO::PARAM_INT);
        $stmt->execute();
        $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

        [
            'id' => $this->id,
            'metodos_pagamentos_disponiveis' => $this->metodos_pagamentos_disponiveis,
        ] = $transacao;
    }

    public function verificaSePontoEhMovel(PDO $conexao, int $idTransacao): bool
    {
        $sql = $conexao->prepare(
            "SELECT
                1
            FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.id_transacao = :id_transacao
            AND transacao_financeiras_produtos_itens.tipo_item = 'CM_ENTREGA'"
        );
        $sql->bindValue(':id_transacao', $idTransacao, PDO::PARAM_INT);
        $sql->execute();
        $pontoMovel = !!$sql->fetch(PDO::FETCH_ASSOC);
        return $pontoMovel;
    }

    /**
     * https://github.com/mobilestock/backend/issues/124
     */
    public function consultaTransacaoCancelamento(): void
    {
        $consulta = DB::selectOne(
            "SELECT
                transacao_financeiras.status,
                transacao_financeiras.pagador,
                transacao_financeiras.origem_transacao,
                transacao_financeiras.emissor_transacao,
                transacao_financeiras.metodo_pagamento,
                transacao_financeiras.cod_transacao
             FROM transacao_financeiras
             WHERE transacao_financeiras.id = ?",
            [$this->id]
        );

        foreach ($consulta as $campo => $valor) {
            $this->$campo = $valor;
        }
    }

    public function valorEstornadoTransacao(): float
    {
        $sqlValorEstornado = TransacaoConsultasService::sqlValorEstornado();

        $valorEstornado = DB::selectOneColumn(
            "SELECT
                    $sqlValorEstornado
                 FROM transacao_financeiras
                 WHERE transacao_financeiras.id = ?",
            [$this->id]
        );

        return $valorEstornado;
    }

    public static function criarTransacaoOrigemML(
        array $produtos,
        array $detalhesTransacao,
        array $freteColaborador
    ): array {
        $usuario = Auth::user();

        ColaboradoresService::verificaDadosClienteCriarTransacao();

        $colaboradorEndereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        PedidoItemModel::verificaProdutosEstaoCarrinho($produtos);
        $estoquesDisponiveis = TransacaoPedidoItem::retornaEstoqueDisponivel($produtos);

        TransacaoPedidoItem::reservaEAtualizaPrecosProdutosCarrinho($estoquesDisponiveis);

        $ehFraudatario = ColaboradoresService::colaboradorEhFraudatario();
        $transacaoFinanceiraService = new TransacaoFinanceiraService();
        $transacaoFinanceiraService->id_usuario = $usuario->id;
        $transacaoFinanceiraService->pagador = $usuario->id_colaborador;
        $transacaoFinanceiraService->origem_transacao = 'ML';
        $transacaoFinanceiraService->valor_itens = 0;
        $transacaoFinanceiraService->metodos_pagamentos_disponiveis = $ehFraudatario ? 'CR,PX' : 'CA,CR,PX';
        $transacaoFinanceiraService->removeTransacoesEmAberto(DB::getPdo());
        $transacaoFinanceiraService->criaTransacao(DB::getPdo());

        $produtosReservados = TransacaoPedidoItem::buscaProdutosReservadosMeuLook();

        $transacaoPedidoItem = new TransacaoPedidoItem();
        $transacaoPedidoItem->id_transacao = $transacaoFinanceiraService->id;

        $transacoesProdutosItem = $transacaoPedidoItem->calcularComissoesOrigemTransacaoML(
            $freteColaborador,
            $produtosReservados
        );
        TransacaoFinanceiraItemProdutoService::insereVarios(DB::getPdo(), $transacoesProdutosItem);

        TransacaoFinanceiraLogCriacaoService::criarLogTransacao(
            $transacaoFinanceiraService->id,
            $detalhesTransacao['ip'],
            $detalhesTransacao['user_agent'],
            $colaboradorEndereco->latitude,
            $colaboradorEndereco->longitude
        );

        $transacaoFinanceiraService->metodo_pagamento = 'CA';
        $transacaoFinanceiraService->numero_parcelas = 1;
        $transacaoFinanceiraService->calcularTransacao(DB::getPdo(), 1);

        $enderecoCliente = $colaboradorEndereco->toArray();
        $enderecoCliente['id_raio'] = null;

        $dadosEntregador = TransacaoFinanceirasMetadadosService::buscaDadosEntregadorTransacao(
            $transacaoFinanceiraService->id
        );

        $idColaboradorTipoFrete = $dadosEntregador['tipo_entrega_padrao']['id_colaborador'];
        if ($dadosEntregador['tipo_entrega_padrao']['tipo_ponto'] === 'PM') {
            $entregador = TransportadoresRaio::buscaEntregadorMaisProximoDaCoordenada(
                $enderecoCliente['id_cidade'],
                $enderecoCliente['latitude'],
                $enderecoCliente['longitude']
            );

            $enderecoCliente['id_raio'] = $entregador->id;
        }

        $metadados = new TransacaoFinanceirasMetadadosService();
        $metadados->id_transacao = $transacaoFinanceiraService->id;
        $metadados->chave = 'ID_COLABORADOR_TIPO_FRETE';
        $metadados->valor = $idColaboradorTipoFrete;
        $metadados->salvar(DB::getPdo());

        $metadados = new TransacaoFinanceirasMetadadosService();
        $metadados->id_transacao = $transacaoFinanceiraService->id;
        $metadados->chave = 'VALOR_FRETE';
        $metadados->valor = $dadosEntregador['comissao_fornecedor'];
        $metadados->salvar(DB::getPdo());

        $metadados = new TransacaoFinanceirasMetadadosService();
        $metadados->id_transacao = $transacaoFinanceiraService->id;
        $metadados->chave = 'ENDERECO_CLIENTE_JSON';
        $metadados->valor = $enderecoCliente;
        $metadados->salvar(DB::getPdo());

        $produtos = TransacaoFinanceirasMetadadosService::buscaProdutosTransacao($transacaoFinanceiraService->id);

        return [
            'id_transacao' => $transacaoFinanceiraService->id,
            'produtos' => $produtos,
        ];
    }
}
