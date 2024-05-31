<?php

namespace MobileStock\service;

use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorStrings;
use MobileStock\model\LogisticaItemModel;
use PDO;
use Psr\Log\LoggerInterface;
use Throwable;

class NotificacaoService
{
    public array $notificacoesFalhas;
    public LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function verificaErroEstoque(PDO $conexao): void
    {
        // Erro de estoque:
        try {
            $sql = $conexao->prepare(
                "SELECT
                    produtos.id,
                    COALESCE(produtos.descricao, '') descricao,
                    estoque_grade.nome_tamanho,
                    estoque_grade.id_responsavel,
                    estoque_grade.vendido,
                    @reservados := COALESCE((
                        SELECT COUNT(pedido_item.uuid)
                        FROM pedido_item
                        WHERE pedido_item.id_produto = estoque_grade.id_produto
                            AND pedido_item.id_responsavel_estoque = estoque_grade.id_responsavel
                            AND pedido_item.nome_tamanho = estoque_grade.nome_tamanho
                            AND pedido_item.situacao > 1
                    ), 0) + COALESCE((
                        SELECT COUNT(logistica_item.uuid_produto)
                        FROM logistica_item
                        WHERE logistica_item.id_produto = estoque_grade.id_produto
                            AND logistica_item.id_responsavel_estoque = estoque_grade.id_responsavel
                            AND logistica_item.nome_tamanho = estoque_grade.nome_tamanho
                            AND logistica_item.situacao = 'PE'
                    ), 0) + COALESCE((
                        SELECT COUNT(produtos_separacao_fotos.id)
                        FROM produtos_separacao_fotos
                        WHERE produtos_separacao_fotos.id_produto = estoque_grade.id_produto
                            AND produtos_separacao_fotos.nome_tamanho = estoque_grade.nome_tamanho
                            AND produtos_separacao_fotos.separado = 'F'
                            AND produtos_separacao_fotos.tipo_separacao = 'E'
                    ), 0) reservados,
                    estoque_grade.vendido <> @reservados tipo_frete
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                GROUP BY estoque_grade.id
                HAVING tipo_frete = 1
                ORDER BY produtos.id ASC;"
            );
            $sql->execute();
            $produtosEstoqueErrado = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_erro_estoque => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($produtosEstoqueErrado as $index => $produto) {
            try {
                $idProduto = (int) $produto['id'];
                $idResponsavel = (int) $produto['id_responsavel'];
                $vendidos = (int) $produto['reservados'];
                $estoqueVendidos = (int) $produto['vendido'];
                $descricao = $produto['descricao'];
                $nomeTamanho = $produto['nome_tamanho'];
                $mensagem = "Erro de estoque $idProduto - $descricao - $nomeTamanho do responsavel $idResponsavel. Vendidos: $vendidos, mas Estoque_Vendido: $estoqueVendidos";

                $bind[":mensagem_$index"] = $mensagem;
                $bind[":tipo_frete_$index"] = (int) $produto['tipo_frete'];
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem,
                        notificacoes.tipo_frete
                    ) VALUES (
                        1,
                        NOW(),
                        'Erro de estoque',
                        :mensagem_$index,
                        'Z',
                        :tipo_frete_$index
                    );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "erro_mensagem_estoque_$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'ESTOQUE',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_estoque => $mensagemErro";
        }
    }
    public function verificaProdutosCorrigir(): void
    {
        // Produtos que deveriam ser corrigidos mas não foram:
        try {
            if (!DiaUtilService::ehDiaUtil((new DateTime('yesterday'))->format('Y-m-d'))) {
                return;
            }
            $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores(['dias_mensurar_cancelamento']);
            $correcoesFaltantes = DB::select(
                "SELECT
                    logistica_item.id_transacao,
                    logistica_item.uuid_produto,
                    logistica_item.data_criacao,
                    DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) AS `dias_passados`
                FROM logistica_item
                WHERE logistica_item.situacao < :situacao_logistica
                    AND DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) > :dias_cancelamento
                ORDER BY dias_passados DESC;",
                [
                    ':dias_cancelamento' => $fatores['dias_mensurar_cancelamento'],
                    ':situacao_logistica' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA,
                ]
            );
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_correcoes_faltantes => $mensagemErro";
            return;
        }

        foreach ($correcoesFaltantes as $index => $correcao) {
            $mensagem = "Urgente!! O produto {$correcao['uuid_produto']} da transação {$correcao['id_transacao']} ";
            $mensagem .= "foi liberado para logística dia {$correcao['data_criacao']} se passaram {$correcao['dias_passados']} dias ";
            $mensagem .= 'e não foi corrigido pelo sistema';

            $this->logger->emergency($mensagem, [
                'title' => 'CORRECAO',
            ]);
            sleep(2);
        }
    }
    public function verificaTrocaLancamentoIncorreto(PDO $conexao): void
    {
        // Troca com lançamento incorreto:
        try {
            $sql = $conexao->prepare(
                "SELECT troca_pendente_item.id_cliente
                FROM troca_pendente_item
                LEFT JOIN lancamento_financeiro ON lancamento_financeiro.numero_documento = troca_pendente_item.uuid
                    AND lancamento_financeiro.origem IN ('TR', 'TF')
                WHERE troca_pendente_item.data_hora > DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                    AND lancamento_financeiro.id IS NULL;"
            );
            $sql->execute();
            $trocasLancamentoIncorreto = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_troca_lancamento_incorreto => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($trocasLancamentoIncorreto as $index => $troca) {
            try {
                $idCliente = (int) $troca['id_cliente'];
                $mensagem = "Aviso: Troca não está com lançamentos corretos, cliente $idCliente";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem
                    ) VALUES (
                        1,
                        NOW(),
                        'Erro de lancamento',
                        :mensagem_$index,
                        'Z'
                    );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "troca_lancamento_incorreto_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_troca_lancamento_incorreto => $mensagemErro";
        }
    }
    //    public function verificaProdutosValorZeradoTransacao(\PDO $conexao): void
    //    {
    //        // Produto com valor zerado na transação:
    //        try {
    //            $sql = $conexao->prepare(
    //                "SELECT
    //                    transacao_financeiras_produtos_itens.id_transacao,
    //                    transacao_financeiras_produtos_itens.id_produto,
    //                    transacao_financeiras_produtos_itens.nome_tamanho
    //                FROM transacao_financeiras_produtos_itens
    //                WHERE transacao_financeiras_produtos_itens.preco = 0
    //                    AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
    //                    AND transacao_financeiras_produtos_itens.data_atualizacao > DATE_SUB(CURDATE(), INTERVAL 2 DAY);"
    //            );
    //            $sql->execute();
    //            $transacoesProdutoZerado = $sql->fetchAll(PDO::FETCH_ASSOC);
    //        } catch (Exception $ex) {
    //            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
    //            array_push($this->notificacoesFalhas, "falha_consulta_transacao_produto_zerado => $mensagemErro");
    //            return;
    //        }
    //
    //        $bind = array();
    //        $query = "";
    //        foreach ($transacoesProdutoZerado as $index => $transacao) {
    //            try {
    //                $idTransacao = (int) $transacao["id_transacao"];
    //                $idProduto = (int) $transacao["id_produto"];
    //                $nomeTamanho = $transacao["nome_tamanho"];
    //                $mensagem = "Erro, o produto $idProduto, tamanho $nomeTamanho está com o valor zerado na transação: $idTransacao";
    //
    //                $bind[":mensagem_$index"] = $mensagem;
    //                $query .= "INSERT INTO notificacoes (
    //                        notificacoes.id_cliente,
    //                        notificacoes.data_evento,
    //                        notificacoes.titulo,
    //                        notificacoes.mensagem,
    //                        notificacoes.tipo_mensagem
    //                    ) VALUES (
    //                        1,
    //                        NOW(),
    //                        'Erro em transacao',
    //                        :mensagem_$index,
    //                        'Z'
    //                    );";
    //            } catch (Exception $ex) {
    //                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
    //                array_push($this->notificacoesFalhas, "transacao_produto_zerado_$index => $mensagemErro");
    //            }
    //        }
    //
    //        if ($query === "") return;
    //
    //        try {
    //            $query = $conexao->prepare($query);
    //            $query->execute($bind);
    //        } catch (Exception $ex) {
    //            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
    //            array_push($this->notificacoesFalhas, "erro_insert_transacao_produto_zerado => $mensagemErro");
    //        }
    //    }
    public function verificaTransacoesCRRemanentes(PDO $conexao): void
    {
        // Transação com status CR não removida:
        try {
            $sql = $conexao->prepare(
                "SELECT transacao_financeiras.id
                FROM transacao_financeiras
                WHERE transacao_financeiras.status = 'CR'
                    AND TIMESTAMPDIFF(MINUTE, transacao_financeiras.data_criacao, NOW()) >= 60;"
            );
            $sql->execute();
            $transacoesStatusErrado = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_transacao_CR => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($transacoesStatusErrado as $index => $transacao) {
            try {
                $idTransacao = (int) $transacao['id'];
                $mensagem = "Erro transação $idTransacao com status CR não foi removida";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem
                    ) VALUES (
                        1,
                        NOW(),
                        'Erro em transacao',
                        :mensagem_$index,
                        'Z'
                    );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "transacao_status_errado_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_transacao_status_errado => $mensagemErro";
        }
    }

    public function verificaComissoesErradasTransacao(): void
    {
        // Comissões erradas por transação:
        try {
            $comissoesErradasPorTransacoes = DB::select(
                "SELECT
                    transacao_financeiras.id,
                    transacao_financeiras_produtos_itens.tipo_item,
                    transacao_financeiras_produtos_itens.sigla_lancamento,
                    (
                        SELECT GROUP_CONCAT(DISTINCT JSON_OBJECT(
                            'id_produto', transacao_financeiras_produtos.id_produto,
                            'nome_tamanho', transacao_financeiras_produtos.nome_tamanho
                        ))
                        FROM transacao_financeiras_produtos_itens transacao_financeiras_produtos
                        WHERE transacao_financeiras_produtos.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                            AND transacao_financeiras_produtos.tipo_item = 'PR'
                    ) produto_json,
                    CASE
                        WHEN logistica_item.situacao IN ('DE', 'DF') THEN
                            IF(
                                transacao_financeiras_produtos_itens.sigla_estorno IS NULL OR
                                (logistica_item.situacao = 'DF' AND transacao_financeiras_produtos_itens.tipo_item <> 'PR') OR
                                EXISTS(
                                    SELECT 1
                                    FROM lancamento_financeiro
                                    WHERE lancamento_financeiro.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                        AND lancamento_financeiro.origem = transacao_financeiras_produtos_itens.sigla_estorno
                                        AND lancamento_financeiro.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                                        AND lancamento_financeiro.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
                                        AND lancamento_financeiro.tipo = 'R'
                                ),
                                NULL,
                                'foi trocado, mas a comissão não foi gerada corretamente'
                            )
                        WHEN (logistica_item.id IS NULL
                                  AND NOT EXISTS(SELECT 1
                                                 FROM pedido_item
                                                 WHERE pedido_item.uuid = transacao_financeiras_produtos_itens.uuid_produto
                                                   AND pedido_item.situacao IN ('DI','FR'))
                             ) OR EXISTS(SELECT 1
                                    FROM logistica_item_data_alteracao
                                    WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                                      AND logistica_item_data_alteracao.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto) THEN
                            IF(
                                NOT EXISTS(
                                    SELECT 1
                                    FROM lancamento_financeiro_pendente
                                    WHERE lancamento_financeiro_pendente.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                        AND lancamento_financeiro_pendente.origem IN (transacao_financeiras_produtos_itens.sigla_lancamento, transacao_financeiras_produtos_itens.sigla_estorno)
                                        AND lancamento_financeiro_pendente.numero_documento = transacao_financeiras_produtos_itens.uuid_produto)
                                AND IF (transacao_financeiras_produtos_itens.momento_pagamento = 'PAGAMENTO'
                                            AND transacao_financeiras_produtos_itens.sigla_estorno IS NOT NULL
                                            AND NOT EXISTS(SELECT 1
                                                           FROM colaboradores_suspeita_fraude
                                                           WHERE colaboradores_suspeita_fraude.id_colaborador = transacao_financeiras.pagador
                                                             AND colaboradores_suspeita_fraude.situacao = 'FR'), EXISTS(
                                    SELECT 1
                                    FROM lancamento_financeiro
                                    WHERE lancamento_financeiro.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                      AND lancamento_financeiro.origem IN ('ES', transacao_financeiras_produtos_itens.sigla_estorno)
                                      AND lancamento_financeiro.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
                                      AND (
                                        (lancamento_financeiro.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                                            AND lancamento_financeiro.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor)
                                            OR lancamento_financeiro.valor -
                                               COALESCE((SELECT SUM(outros_produtos_transacao_financeiras_produtos_itens.comissao_fornecedor)
                                                         FROM transacao_financeiras_produtos_itens outros_produtos_transacao_financeiras_produtos_itens
                                                         WHERE outros_produtos_transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                                                           AND outros_produtos_transacao_financeiras_produtos_itens.id_fornecedor = transacao_financeiras_produtos_itens.id_fornecedor
                                                           AND outros_produtos_transacao_financeiras_produtos_itens.id <> transacao_financeiras_produtos_itens.id
                                                        ), 0) = transacao_financeiras_produtos_itens.comissao_fornecedor)
                                          ),
                                        TRUE),
                                    NULL,
                                    'foi cancelado e não houve o estorno da comissão.'
                            )
                        ELSE
                            IF(transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA' AND (
									 	 entregas_faturamento_item.data_entrega IS NULL
                                OR DATE(entregas_faturamento_item.data_entrega) > DATE_SUB(CURDATE(), INTERVAL (
                                    SELECT configuracoes.qtd_dias_disponiveis_troca_normal + 1
                                    FROM configuracoes
                                    LIMIT 1
                                ) DAY)), (
                                IF(
                                    COALESCE(
                                        (
                                            SELECT SUM(lancamento_financeiro_pendente.valor)
                                            FROM lancamento_financeiro_pendente
                                            WHERE lancamento_financeiro_pendente.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                                AND lancamento_financeiro_pendente.origem = transacao_financeiras_produtos_itens.sigla_lancamento
                                                AND lancamento_financeiro_pendente.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                                                AND lancamento_financeiro_pendente.valor = transacao_financeiras_produtos_itens.comissao_fornecedor
                                                AND lancamento_financeiro_pendente.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
                                        ), 0
                                    ) = transacao_financeiras_produtos_itens.comissao_fornecedor,
                                    NULL,
                                    CONCAT('ainda não passou o prazo, comissionado deveria ter um lançamento pendente no valor de: ', transacao_financeiras_produtos_itens.comissao_fornecedor)
                                )
                            ), (
                                IF(
                                    EXISTS(
                                        SELECT 1
                                        FROM lancamento_financeiro
                                        WHERE lancamento_financeiro.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                            AND lancamento_financeiro.origem = transacao_financeiras_produtos_itens.sigla_lancamento
                                            AND lancamento_financeiro.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
                                            AND (
                                                   (lancamento_financeiro.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                                                       AND lancamento_financeiro.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor)
                                                       OR lancamento_financeiro.valor -
                                                          COALESCE((SELECT SUM(outros_produtos_transacao_financeiras_produtos_itens.comissao_fornecedor)
                                                                    FROM transacao_financeiras_produtos_itens outros_produtos_transacao_financeiras_produtos_itens
                                                                    WHERE outros_produtos_transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                                                                      AND outros_produtos_transacao_financeiras_produtos_itens.id_fornecedor = transacao_financeiras_produtos_itens.id_fornecedor
                                                                      AND outros_produtos_transacao_financeiras_produtos_itens.id <> transacao_financeiras_produtos_itens.id
                                                                   ), 0) = transacao_financeiras_produtos_itens.comissao_fornecedor)
                                    ),
                                    NULL,
                                    CONCAT('passou o prazo, comissionado deveria ter um lançamento no valor de: ', transacao_financeiras_produtos_itens.preco)
                                )
                            ))
                    END mensagem
                FROM transacao_financeiras
                INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    AND entregas_faturamento_item.situacao = 'EN'
                WHERE transacao_financeiras.status = 'PA'
                    AND DATE(transacao_financeiras.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 10 DAY)
                    AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
                GROUP BY transacao_financeiras_produtos_itens.id
                HAVING mensagem IS NOT NULL;"
            );
        } catch (Throwable $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_consulta_comissao_errada_transacao => $mensagemErro";
            return;
        }

        foreach ($comissoesErradasPorTransacoes as $comissao) {
            $mensagem = "Transação {$comissao['id']} produto {$comissao['produto']['id_produto']} - ";
            $mensagem .= "{$comissao['produto']['nome_tamanho']} comissao {$comissao['tipo_item']} {$comissao['mensagem']}";

            $this->logger->emergency($mensagem, [
                'title' => 'PAGAMENTO',
            ]);
            sleep(2);
        }
    }
    public function verificaLancamentosDuplicadosTransacao(PDO $conexao): void
    {
        // Lançamento da transação duplicado:
        try {
            $sql = $conexao->prepare(
                "SELECT transacao_financeiras.id
                FROM lancamento_financeiro
                INNER JOIN transacao_financeiras ON transacao_financeiras.id = lancamento_financeiro.transacao_origem
                WHERE transacao_financeiras.data_atualizacao > DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                    AND lancamento_financeiro.origem NOT IN ('TR', 'TF', 'ES', 'TX')
                GROUP BY lancamento_financeiro.transacao_origem,lancamento_financeiro.id_colaborador,lancamento_financeiro.valor, lancamento_financeiro.origem
                HAVING COUNT(lancamento_financeiro.id) > 1;"
            );
            $sql->execute();
            $transacoesLancamentoDuplicado = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_transacao_lancamento_duplicado => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($transacoesLancamentoDuplicado as $index => $transacao) {
            try {
                $idTransacao = (int) $transacao['id'];
                $mensagem = "Urgente!! Transacao $idTransacao está com lançamentos duplicados";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem
                    ) VALUES (
                        1,
                        NOW(),
                        'Urgente!',
                        :mensagem_$index,
                        'Z'
                    );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "transacao_lancamento_duplicado_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_divergencia_split_transacao => $mensagemErro";
        }
    }
    public function verificaProdutosBloqueados(PDO $conexao): void
    {
        // Verifica produtos que estão bloqueados mas tem estoque
        try {
            $sql = $conexao->prepare(
                "SELECT
                    estoque_grade.id_produto,
                    estoque_grade.nome_tamanho,
                    estoque_grade.id_responsavel,
                    estoque_grade.estoque,
                    produtos.localizacao
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                WHERE produtos.bloqueado = 1
                    AND (
                        estoque_grade.estoque > 0
                        OR estoque_grade.vendido > 0
                    )
                GROUP BY estoque_grade.id;"
            );
            $sql->execute();
            $produtosBloqueados = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_bloqueados_estoque => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($produtosBloqueados as $index => $produtoBloqueado) {
            try {
                $idProduto = (int) $produtoBloqueado['id_produto'];
                $idResponsavel = (int) $produtoBloqueado['id_responsavel'];
                $quantidade = (int) $produtoBloqueado['estoque'];
                $localizacao = (int) $produtoBloqueado['localizacao'];
                $nomeTamanho = $produtoBloqueado['nome_tamanho'];
                if ($produtoBloqueado['id_responsavel'] > 1) {
                    $mensagem = "Aviso! O produto externo $idProduto - $nomeTamanho está bloqueado, mas possui $quantidade em estoque no responsável: $idResponsavel";
                } else {
                    $mensagem = "Aviso! O produto $idProduto - $nomeTamanho está bloqueado, mas possui $quantidade em estoque no painel: $localizacao";
                }

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Aviso',
                    :mensagem_$index,
                    'Z'
                );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "erro_mensagem_bloqueados_$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'ESTOQUE',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_bloqueados_estoque => $mensagemErro";
        }
    }
    public function verificaEstoqueForaDeLinha(PDO $conexao): void
    {
        // Produto está fora de linha mas possui estoque externo
        try {
            $sql = $conexao->prepare(
                "SELECT
                    estoque_grade.id_produto,
                    estoque_grade.nome_tamanho,
                    estoque_grade.estoque,
                    estoque_grade.id_responsavel
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                WHERE estoque_grade.id_responsavel <> 1
                    AND estoque_grade.estoque > 0
                    AND produtos.fora_de_linha = 1;"
            );
            $sql->execute();
            $produtosForaDeLinha = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_fora_linha_estoque => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($produtosForaDeLinha as $index => $produto) {
            try {
                $idProduto = (int) $produto['id_produto'];
                $estoque = (int) $produto['estoque'];
                $idResponsavelEstoque = (int) $produto['id_responsavel'];
                $nomeTamanho = $produto['nome_tamanho'];
                $mensagem = "Erro, o produto $idProduto - $nomeTamanho possui $estoque unidades no responsável $idResponsavelEstoque";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Erro fora de linha',
                    :mensagem_$index,
                    'Z'
                );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "erro_mensagem_fora_linha_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_fora_linha_estoque => $mensagemErro";
        }
    }
    public function verificaProdutosSemFotoPub(PDO $conexao): void
    {
        // Verifica se tem produto fulfillment sem foto ou publicação
        try {
            $sql = $conexao->prepare(
                "SELECT
                    estoque_grade.id_produto,
                    DATEDIFF(CURDATE(), produtos.data_entrada) dias_parado,
                    SUM(estoque_grade.estoque) quantidade,
                    produtos_foto.caminho IS NULL sem_foto,
                    (
                        publicacoes_produtos.id IS NULL
                        OR (
                            SUM(DISTINCT
                                COALESCE(
                                    (
                                        SELECT 1
                                        FROM publicacoes
                                        WHERE publicacoes.id = publicacoes_produtos.id_publicacao
                                            AND publicacoes.situacao = 'CR'
                                            AND publicacoes.tipo_publicacao = 'AU'
                                    ), 0
                                )
                            ) = 0
                        )
                    ) AS `sem_pub`
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                    AND estoque_grade.id_responsavel = 1
                    AND estoque_grade.estoque > 0
                LEFT JOIN produtos_foto ON produtos_foto.id = produtos.id
                    AND produtos_foto.tipo_foto IN ('MD', 'LG')
                LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                    AND publicacoes_produtos.situacao = 'CR'
                WHERE produtos.data_entrada IS NOT NULL
                    AND DATE(produtos.data_entrada) < DATE_SUB(CURDATE(), INTERVAL 15 DAY)
                GROUP BY produtos.id
                HAVING sem_foto OR sem_pub
                ORDER BY dias_parado DESC, produtos.id DESC;"
            );
            $sql->execute();
            $produtosSemPubFoto = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_consulta_sem_foto_pub => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($produtosSemPubFoto as $index => $produtoSemPubFoto) {
            try {
                $idProduto = (int) $produtoSemPubFoto['id_produto'];
                $diasParado = (int) $produtoSemPubFoto['dias_parado'];
                $quantidade = (int) $produtoSemPubFoto['quantidade'];
                $semFoto = (bool) $produtoSemPubFoto['sem_foto'];
                $semPub = (bool) $produtoSemPubFoto['sem_pub'];

                $falta = [];
                if (!$semFoto && !$semPub) {
                    throw new Exception("O produto $idProduto possui foto e publicação mais caiu na análise");
                }
                if ($semFoto) {
                    $falta[] = 'foto';
                }
                if ($semPub) {
                    $falta[] = 'publicação';
                }
                $falta = implode(' e ', $falta);

                $mensagem = "Aviso! O produto $idProduto tem $quantidade unidades paradas no estoque a $diasParado dias e está sem $falta";
                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Aviso',
                    :mensagem_$index,
                    'Z'
                );";
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "erro_mensagem_sem_foto_publicacao_$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'ESTOQUE',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_sem_foto_pub => $mensagemErro";
        }
    }
    public function verificaTransacoesGarradasFraude(PDO $conexao): void
    {
        // Transacoes garradas na fraude:
        try {
            $sql = $conexao->prepare(
                "SELECT DISTINCT pedido_item.id_transacao
                FROM pedido_item
                WHERE pedido_item.situacao = 'FR'
                  AND EXISTS(SELECT 1
                             FROM colaboradores_suspeita_fraude
                             WHERE colaboradores_suspeita_fraude.id_colaborador = pedido_item.id_cliente
                               AND colaboradores_suspeita_fraude.situacao IN ('LT', 'LG'))"
            );
            $sql->execute();
            $transacoesGarradas = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "falha_consulta_transacao_garrada_fraude => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($transacoesGarradas as $index => $transacao) {
            try {
                $idTransacao = (int) $transacao['id_transacao'];
                $mensagem = "Urgente!! Transacao $idTransacao está garrada na fraude.";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem
                    ) VALUES (
                        1,
                        NOW(),
                        'Urgente!',
                        :mensagem_$index,
                        'Z'
                    );";

                $this->logger->emergency($mensagem, [
                    'title' => 'FRAUDE',
                ]);
                sleep(2);
            } catch (Exception $ex) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
                $this->notificacoesFalhas[] = "transacao_garrada_fraude_duplicado_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($ex->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_transacao_garrada_fraude => $mensagemErro";
        }
    }

    public function verificaPedidoItemPendenteTransacaoPaga(PDO $conexao): void
    {
        // pedidoItem's pendentes transação paga
        try {
            $sql = $conexao->prepare(
                "SELECT transacao_financeiras_produtos_itens.id_transacao id_transacao
                FROM pedido_item
                    JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
                    JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
                WHERE transacao_financeiras.`status` = 'PA'
                    AND pedido_item.situacao IN ('1', '2', '3')
                GROUP BY transacao_financeiras.id"
            );
            $sql->execute();
            $pedidosPendentes = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($e->getMessage());

            $this->notificacoesFalhas[] = "falha_consulta_pedido_item_pendente_transacao_paga => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($pedidosPendentes as $index => $pedido) {
            try {
                $idTransacao = (int) $pedido['id_transacao'];
                $mensagem = "Urgente!! Transação $idTransacao possui produto(s) pendente(s)!";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Urgente!',
                    :mensagem_$index,
                    'Z'
                );";

                $this->logger->emergency($mensagem, ['PAGAMENTO']);
                sleep(2);
            } catch (Exception $e) {
                $mensagemErro = ConversorStrings::trataRetornoBanco($e->getMessage());

                $this->notificacoesFalhas[] = "transacao_paga_pedido_pendente_duplicado_$index => $mensagemErro";
            }
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $e) {
            $mensagemErro = ConversorStrings::trataRetornoBanco($e->getMessage());
            $this->notificacoesFalhas[] = "erro_insert_pedido_pendente_transacao_paga => $mensagemErro";
        }
    }
    public function verificaSellerBloqueadoComEstoque(PDO $conexao): void
    {
        // Produto de um seller bloqueado tem estoque externo

        try {
            $sql = $conexao->prepare(
                "SELECT
                    estoque_grade.id_produto,
                    estoque_grade.nome_tamanho,
                    estoque_grade.id_responsavel,
                    estoque_grade.estoque
                FROM colaboradores
                INNER JOIN estoque_grade ON estoque_grade.id_responsavel > 1
                    AND estoque_grade.id_responsavel = colaboradores.id
                    AND estoque_grade.estoque > 0
                WHERE colaboradores.bloqueado_repor_estoque = 'T'
                GROUP BY colaboradores.id;"
            );
            $sql->execute();
            $produtosBloqueados = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_consulta_estoque_seller_bloqueado => $mensagemErro";
            return;
        }

        $bind = [];
        $query = '';
        foreach ($produtosBloqueados as $index => $produto) {
            try {
                $idProduto = (int) $produto['id_produto'];
                $idResponsavel = (int) $produto['id_responsavel'];
                $estoque = (int) $produto['estoque'];
                $nomeTamanho = $produto['nome_tamanho'];
                $mensagem = "Urgente!! O tamanho $nomeTamanho do produto $idProduto tem $estoque produtos a venda, mas o responsavel $idResponsavel está bloqueado!";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                        notificacoes.id_cliente,
                        notificacoes.data_evento,
                        notificacoes.titulo,
                        notificacoes.mensagem,
                        notificacoes.tipo_mensagem
                    ) VALUES (
                        1,
                        NOW(),
                        'Urgente!',
                        :mensagem_$index,
                        'Z'
                    );";
            } catch (Exception $ex) {
                $mensagemErro = $ex->getMessage();
                $this->notificacoesFalhas[] = "estoque_seller_bloqueado_$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'ESTOQUE',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "erro_insert_estoque_seller_bloqueado => $mensagemErro";
        }
    }
    public function verificaEntregaDessincronizada(PDO $conexao): void
    {
        // Entrega não está entregue mas os itens já saíram de PE
        try {
            $sql = $conexao->prepare(
                "SELECT
                    entregas.id,
                    entregas.situacao
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                WHERE entregas.situacao <> 'EN'
                    AND entregas_faturamento_item.situacao <> 'PE'
                    AND DATE(entregas.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                GROUP BY entregas.id;"
            );
            $sql->execute();
            $entregasErradas = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_consulta_entrega_dessincronizada => $mensagemErro";
        }

        $bind = [];
        $query = '';
        foreach ($entregasErradas as $index => $entrega) {
            try {
                $idEntrega = (int) $entrega['id'];
                $situacao = $entrega['situacao'];
                $mensagem = "A entrega $idEntrega está na situação $situacao e possui produtos dessincronizados";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Aviso',
                    :mensagem_$index,
                    'Z'
                );";
            } catch (Exception $ex) {
                $mensagemErro = $ex->getMessage();
                $this->notificacoesFalhas[] = "erro_mensagem_entrega_dessincronizada_$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'ENTREGA',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "erro_insert_entrega_dessincronizada => $mensagemErro";
        }
    }

    public function verificaSePontoResponsavelTrocaEstaDiferente(PDO $conexao): void
    {
        // Ponto responsável pela troca está diferente do ponto que bipou a troca
        try {
            $sql = $conexao->prepare(
                "SELECT
                    entregas_devolucoes_item.id_ponto_responsavel,
                    (	SELECT
                            tipo_frete.id
                        FROM usuarios
                        INNER JOIN tipo_frete ON tipo_frete.id_colaborador = usuarios.id_colaborador
                        WHERE
                            lancamento_financeiro.id_usuario = usuarios.id
                    ) AS `usuario_lancamento`,
                    entregas_devolucoes_item.uuid_produto
                FROM entregas_devolucoes_item
                INNER JOIN lancamento_financeiro ON
                    lancamento_financeiro.numero_documento = entregas_devolucoes_item.uuid_produto
                    AND lancamento_financeiro.origem = 'TR'
                WHERE entregas_devolucoes_item.situacao = 'PE'
                GROUP BY entregas_devolucoes_item.uuid_produto
                HAVING `id_ponto_responsavel` <> `usuario_lancamento`"
            );
            $sql->execute();
            $pontosErrados = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_consulta_ponto_responsavel_troca => $mensagemErro";
        }

        $bind = [];
        $query = '';
        foreach ($pontosErrados as $index => $ponto) {
            try {
                $uuidProduto = $ponto['uuid_produto'];
                $idPontoResponsavel = $ponto['id_ponto_responsavel'];
                $idUsuarioLancamento = $ponto['usuario_lancamento'];
                $mensagem = "O ponto responsável pela troca do produto $uuidProduto está diferente do ponto que bipou a troca. Ponto responsável: $idPontoResponsavel. Ponto que bipou: $idUsuarioLancamento";

                $bind[":mensagem_$index"] = $mensagem;
                $query .= "INSERT INTO notificacoes (
                    notificacoes.id_cliente,
                    notificacoes.data_evento,
                    notificacoes.titulo,
                    notificacoes.mensagem,
                    notificacoes.tipo_mensagem
                ) VALUES (
                    1,
                    NOW(),
                    'Aviso',
                    :mensagem_$index,
                    'Z'
                );";
            } catch (Exception $ex) {
                $mensagemErro = $ex->getMessage();
                $this->notificacoesFalhas[] = "erro_mensagem_ponto_responsavel_troca$index => $mensagemErro";
            }

            $this->logger->emergency($mensagem, [
                'title' => 'VERIFICA_PONTO_RESPONSAVEL',
            ]);
            sleep(2);
        }

        if ($query === '') {
            return;
        }

        try {
            $query = $conexao->prepare($query);
            $query->execute($bind);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "erro_insert_ponto_responsavel_troca => $mensagemErro";
        }
    }

    public function verificaValorEstornado(PDO $conexao): void
    {
        // Valor estornado para o cliente está maior que o valor total da transação
        try {
            $sql = $conexao->prepare(
                "SELECT
                    transacao_financeiras.id,
                    SUM(lancamento_financeiro.valor) valor_estorno,
                    transacao_financeiras.valor_total
                FROM lancamento_financeiro
                INNER JOIN transacao_financeiras ON transacao_financeiras.id = lancamento_financeiro.transacao_origem
                    AND transacao_financeiras.pagador = lancamento_financeiro.id_colaborador
                WHERE lancamento_financeiro.origem = 'ES'
                    AND DATE(transacao_financeiras.data_atualizacao) >= CURRENT_DATE() - INTERVAL 1 DAY
                GROUP BY lancamento_financeiro.transacao_origem
                HAVING valor_estorno > transacao_financeiras.valor_total
                ORDER BY transacao_financeiras.id DESC;"
            );
            $sql->execute();
            $estornosErrados = $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $mensagemErro = $ex->getMessage();
            $this->notificacoesFalhas[] = "falha_em_verificar_valor_estornado => $mensagemErro";
        }

        foreach ($estornosErrados as $estorno) {
            $idTransacao = (int) $estorno['id'];
            $valorTotal = (float) $estorno['valor_total'];
            $valorEstornado = (float) $estorno['valor_estorno'];
            $valorTotalFormatado = number_format($valorTotal, 2, ',', '.');
            $valorEstornadoFormatado = number_format($valorEstornado, 2, ',', '.');

            $mensagem = "O valor total da transação $idTransacao foi de R$$valorTotalFormatado , porém, houve um estorno no valor de R$$valorEstornadoFormatado.";

            $this->logger->emergency($mensagem, [
                'title' => 'ESTORNO',
            ]);
            sleep(2);
        }
    }
}
