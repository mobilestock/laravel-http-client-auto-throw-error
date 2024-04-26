<?php

namespace MobileStock\service;

use Conexao;
use Exception;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\Colaborador;
use MobileStock\model\LogisticaItem;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\repository\ColaboradoresRepository;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../../vendor/autoload.php';

class ProdutoService
{
    public static function verificaExistenciaProduto(int $idProduto, ?string $nomeTamanho): bool
    {
        $query = DB::table('produtos');

        if ($nomeTamanho) {
            $query
                ->join('produtos_grade', 'produtos.id', '=', 'produtos_grade.id_produto')
                ->where('produtos_grade.nome_tamanho', $nomeTamanho);
        }

        return $query->where('produtos.id', $idProduto)->exists();
    }

    public static function buscaIdTamanhoProduto(PDO $conexao, string $pesquisa, ?string $nomeTamanho): array
    {
        $produto = ['id_produto' => null, 'nome_tamanho' => $nomeTamanho];
        $pesquisa_id = (string) (mb_stripos($pesquisa, ' - ') !== false)
            ? mb_substr($pesquisa, 0, mb_stripos($pesquisa, ' - '))
            : $pesquisa;
        $sql = $conexao->prepare(
            "SELECT produtos.id
            FROM produtos
            WHERE (
                produtos.descricao REGEXP :pesquisa
                OR produtos.id = :id_pesquisa
            );"
        );
        $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        $sql->bindValue(':id_pesquisa', $pesquisa_id, PDO::PARAM_STR);
        $sql->execute();
        $produto['id_produto'] = (int) $sql->fetch(PDO::FETCH_ASSOC)['id'];

        if (!$produto['id_produto']) {
            $stmt = $conexao->prepare(
                "SELECT
                    produtos_grade.id_produto,
                    produtos_grade.nome_tamanho
                FROM produtos_grade
                WHERE produtos_grade.cod_barras = :cod_barras;"
            );
            $stmt->bindValue(':cod_barras', $pesquisa, PDO::PARAM_STR);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $produto = ['id_produto' => (int) $resultado['id_produto'], 'nome_tamanho' => $resultado['nome_tamanho']];
        }

        return $produto;
    }

    public static function buscaDetalhesProduto(int $idProduto, ?string $nomeTamanho): array
    {
        $condicao = (string) $nomeTamanho ? ' nome_tamanho = :nome_tamanho ' : ' 1=1 ';

        $sql = "SELECT
            produtos.localizacao,
            produtos.id,
            produtos.nome_comercial,
            produtos.cores AS 'cor',
            (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id LIMIT 1) AS `foto`,
            produtos.descricao,
            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor) fornecedor,
            CONCAT('[',(
                SELECT DISTINCT GROUP_CONCAT(JSON_OBJECT(
                    'nome_tamanho', produtos_grade.nome_tamanho,
                    'qtd', COALESCE((
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                        AND estoque_grade.id_responsavel = 1
                    ), 0),
                    'cod_barras', produtos_grade.cod_barras
                ))
                FROM produtos_grade
                WHERE produtos_grade.id_produto = produtos.id
                ORDER BY produtos_grade.sequencia ASC
            ),']')estoque,
            CONCAT('[',(SELECT GROUP_CONCAT(JSON_OBJECT(
				'new', log_produtos_localizacao.new_localizacao,
                'old', log_produtos_localizacao.old_localizacao,
                'qtd', log_produtos_localizacao.qtd_entrada,
                'usuario', (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = log_produtos_localizacao.usuario),
                'data', DATE_FORMAT(log_produtos_localizacao.data_hora, '%d/%m/%Y'),
                'data_order', log_produtos_localizacao.data_hora
			)) FROM log_produtos_localizacao WHERE log_produtos_localizacao.id_produto = produtos.id GROUP BY log_produtos_localizacao.id_produto),']') historicoLocalizacoes,
			    CONCAT('[',(SELECT GROUP_CONCAT(JSON_OBJECT(
					'data', DATE_FORMAT(log_estoque_movimentacao.data, '%d/%m/%Y %H:%i:%s'),
					'descricao', log_estoque_movimentacao.descricao,
                    'tamanho', log_estoque_movimentacao.nome_tamanho,
					'tipo_movimentacao', log_estoque_movimentacao.tipo_movimentacao,
					'data_hora', log_estoque_movimentacao.data
                ))
				FROM log_estoque_movimentacao
                WHERE log_estoque_movimentacao.id_produto = produtos.id
                    AND DATE(log_estoque_movimentacao.data) = DATE(NOW())
                    AND $condicao
				ORDER BY log_estoque_movimentacao.data DESC),']') historicoMovimentacoes,
			(SELECT COUNT(logistica_item.id)
             FROM logistica_item
             WHERE logistica_item.id_produto = produtos.id
               AND logistica_item.situacao = 'PE'
               AND $condicao) AS qtdSeparacao,
			(SELECT COUNT(logistica_item.id)
             FROM logistica_item
             WHERE logistica_item.id_produto = produtos.id
               AND logistica_item.situacao = 'SE'
               AND $condicao) AS qtdConferencia,
            (
                SELECT produtos_separacao_fotos.nome_tamanho
                FROM produtos_separacao_fotos
                WHERE produtos_separacao_fotos.id_produto = produtos.id
            ) tamanhoFoto
        FROM produtos
            WHERE produtos.id = :id_produto
            GROUP BY produtos.id;";

        $bindings = [
            ':id_produto' => $idProduto,
        ];

        if ($nomeTamanho) {
            $bindings[':nome_tamanho'] = $nomeTamanho;
        }

        $result = DB::select($sql, $bindings);

        if (!$result) {
            return [];
        }

        $consulta = json_decode(json_encode($result), true)[0];

        if (isset($consulta['estoque'])) {
            $consulta['estoque'] = json_decode($consulta['estoque'], true);
        }

        if (isset($consulta['historicoLocalizacoes'])) {
            $consulta['historicoLocalizacoes'] = json_decode($consulta['historicoLocalizacoes'], true);
            usort($consulta['historicoLocalizacoes'], function ($a, $b) {
                return strtotime($a['data_order']) <=> strtotime($b['data_order']);
            });
        }
        if (isset($consulta['historicoMovimentacoes'])) {
            $consulta['historicoMovimentacoes'] = json_decode($consulta['historicoMovimentacoes'], true);
            usort($consulta['historicoMovimentacoes'], function ($a, $b) {
                return strtotime($a['data_hora']) <=> strtotime($b['data_hora']);
            });
        }

        return $consulta;
    }

    public static function buscaInfoProduto(int $idProduto, ?string $nomeTamanho): array
    {
        $condicao = (string) $nomeTamanho ? ' nome_tamanho = :nome_tamanho ' : ' 1=1 ';

        $sql = "SELECT
            produtos.localizacao,
            produtos.id,
            produtos.nome_comercial,
            produtos.cores AS 'cor',
            (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id LIMIT 1) AS `foto`,
            produtos.descricao,
            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor) fornecedor,
            CONCAT('[',(
                SELECT DISTINCT GROUP_CONCAT(JSON_OBJECT(
                    'nome_tamanho', produtos_grade.nome_tamanho,
                    'qtd', COALESCE((
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                    ), 0),
                    'vendido', COALESCE((
                        SELECT SUM(estoque_grade.vendido)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.nome_tamanho = 'nome_tamanho'
                    ), 0),
                    'cod_barras', produtos_grade.cod_barras
                ))
                FROM produtos_grade
                WHERE produtos_grade.id_produto = produtos.id
                ORDER BY produtos_grade.sequencia ASC
            ),']')estoque,
            CONCAT('[',(SELECT GROUP_CONCAT(JSON_OBJECT(
				'new', log_produtos_localizacao.new_localizacao,
                'old', log_produtos_localizacao.old_localizacao,
                'qtd', log_produtos_localizacao.qtd_entrada,
                'usuario', (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = log_produtos_localizacao.usuario),
                'data', DATE_FORMAT(log_produtos_localizacao.data_hora, '%d/%m/%Y'),
                'data_order', log_produtos_localizacao.data_hora
			)) FROM log_produtos_localizacao WHERE log_produtos_localizacao.id_produto = produtos.id GROUP BY log_produtos_localizacao.id_produto),']') historicoLocalizacoes,
			    CONCAT('[',(SELECT GROUP_CONCAT(JSON_OBJECT(
					'data', DATE_FORMAT(log_estoque_movimentacao.data, '%d/%m/%Y %H:%i:%s'),
					'descricao', log_estoque_movimentacao.descricao,
                    'tamanho', log_estoque_movimentacao.nome_tamanho,
					'tipo_movimentacao', log_estoque_movimentacao.tipo_movimentacao,
					'data_hora', log_estoque_movimentacao.data
                ))
				FROM log_estoque_movimentacao
                WHERE log_estoque_movimentacao.id_produto = produtos.id
                    AND DATE(log_estoque_movimentacao.data) = DATE(NOW())
                    AND $condicao
				ORDER BY log_estoque_movimentacao.data DESC),']') historicoMovimentacoes,
			(SELECT COUNT(logistica_item.id)
             FROM logistica_item
             WHERE logistica_item.id_produto = produtos.id
               AND logistica_item.situacao = 'PE'
               AND $condicao) AS qtdSeparacao,
			(SELECT COUNT(logistica_item.id)
             FROM logistica_item
             WHERE logistica_item.id_produto = produtos.id
               AND logistica_item.situacao = 'SE'
               AND $condicao) AS qtdConferencia,
            (
                SELECT produtos_separacao_fotos.nome_tamanho
                FROM produtos_separacao_fotos
                WHERE produtos_separacao_fotos.id_produto = produtos.id
            ) tamanhoFoto
        FROM produtos
            WHERE produtos.id = :id_produto
            GROUP BY produtos.id;";

        $bindings = [
            ':id_produto' => $idProduto,
        ];

        if ($nomeTamanho) {
            $bindings[':nome_tamanho'] = $nomeTamanho;
        }

        $result = DB::select($sql, $bindings);

        if (!$result) {
            return [];
        }

        $consulta = json_decode(json_encode($result), true)[0];

        if (isset($consulta['estoque'])) {
            $consulta['estoque'] = json_decode($consulta['estoque'], true);
        }

        if (isset($consulta['historicoLocalizacoes'])) {
            $consulta['historicoLocalizacoes'] = json_decode($consulta['historicoLocalizacoes'], true);
            usort($consulta['historicoLocalizacoes'], function ($a, $b) {
                return strtotime($a['data_order']) <=> strtotime($b['data_order']);
            });
        }
        if (isset($consulta['historicoMovimentacoes'])) {
            $consulta['historicoMovimentacoes'] = json_decode($consulta['historicoMovimentacoes'], true);
            usort($consulta['historicoMovimentacoes'], function ($a, $b) {
                return strtotime($a['data_hora']) <=> strtotime($b['data_hora']);
            });
        }

        return $consulta;
    }

    public static function buscaInfoAguardandoEntrada(PDO $conexao, int $idProduto, ?string $nomeTamanho): array
    {
        $condicao = (string) $nomeTamanho ? ' AND produtos_aguarda_entrada_estoque.nome_tamanho = :nome_tamanho' : '';

        $sql = $conexao->prepare(
            "SELECT
            produtos_aguarda_entrada_estoque.id,
            produtos_aguarda_entrada_estoque.nome_tamanho,
            produtos_aguarda_entrada_estoque.tipo_entrada,
            DATE_FORMAT(produtos_aguarda_entrada_estoque.data_hora, '%d/%m/%Y') data_hora,
            (SELECT usuarios.nome FROM usuarios WHERE produtos_aguarda_entrada_estoque.usuario = usuarios.id) usuario
            FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto
                AND produtos_aguarda_entrada_estoque.em_estoque = 'F' $condicao;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        if ($nomeTamanho) {
            $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        }
        $sql->execute();
        $informacoes = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $informacoes;
    }

    public static function buscaFaturamentosDoProduto(PDO $conexao, int $idProduto, ?string $nomeTamanho): array
    {
        $condicaoTransacao = (string) $nomeTamanho
            ? ' AND transacao_financeiras_produtos_itens.nome_tamanho = :nome_tamanho '
            : '';

        $sql = $conexao->prepare(
            "SELECT
            transacao_financeiras.id,
            GROUP_CONCAT(transacao_financeiras_produtos_itens.nome_tamanho)tamanho,
            (SELECT CONCAT(colaboradores.id, ' - ', colaboradores.razao_social) FROM colaboradores WHERE colaboradores.id = transacao_financeiras.pagador) cliente,
            transacao_financeiras.status = 'PA' pago,
            DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y') data_hora
        FROM transacao_financeiras
        INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
            WHERE transacao_financeiras_produtos_itens.id_produto = :id_produto $condicaoTransacao
        GROUP BY transacao_financeiras.id

            ORDER BY data_hora;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        if ($nomeTamanho) {
            $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        }
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_map(function (array $item): array {
            $item['pago'] = (bool) $item['pago'];

            return $item;
        }, $resultado);

        return $resultado;
    }

    public static function buscaTrocasDoProduto(PDO $conexao, int $idProduto, ?string $nomeTamanho): array
    {
        $condicao = (string) $nomeTamanho ? ' AND nome_tamanho = :nome_tamanho' : '';

        $sql = $conexao->prepare(
            "SELECT
                1 confirmada,
                troca_pendente_item.nome_tamanho tamanho,
                troca_pendente_item.uuid,
                (SELECT CONCAT(colaboradores.id, ' - ', colaboradores.razao_social) FROM colaboradores WHERE colaboradores.id = troca_pendente_item.id_cliente) cliente,
                (SELECT logistica_item.preco
                 FROM logistica_item
                 WHERE logistica_item.uuid_produto = troca_pendente_item.uuid) - troca_pendente_item.preco AS taxa,
                (SELECT logistica_item.preco
                 FROM logistica_item
                 WHERE logistica_item.uuid_produto = troca_pendente_item.uuid) AS preco,
                DATE_FORMAT(troca_pendente_item.data_hora, '%d/%m/%Y') data
            FROM troca_pendente_item
            WHERE troca_pendente_item.id_produto = :id_produto $condicao

            UNION ALL

            SELECT
                0 confirmada,
                troca_pendente_agendamento.nome_tamanho tamanho,
                troca_pendente_agendamento.uuid,
                (SELECT CONCAT(colaboradores.id, ' - ', colaboradores.razao_social) FROM colaboradores WHERE colaboradores.id = troca_pendente_agendamento.id_cliente) cliente,
                troca_pendente_agendamento.taxa,
                troca_pendente_agendamento.preco,
                DATE_FORMAT(troca_pendente_agendamento.data_hora, '%d/%m/%Y') data
            FROM troca_pendente_agendamento
            WHERE troca_pendente_agendamento.id_produto = :id_produto $condicao;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        if ($nomeTamanho) {
            $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        }
        $sql->execute();
        $trocas = $sql->fetchAll(PDO::FETCH_ASSOC);

        $trocas = array_map(function ($troca) {
            $troca['confirmada'] = (bool) json_decode($troca['confirmada'], true);
            $troca['taxa'] = (float) $troca['taxa'];
            $troca['preco'] = (float) $troca['preco'];

            return $troca;
        }, $trocas);

        return $trocas;
    }

    public static function buscaReposicoesDoProduto(int $idProduto): array
    {
        $reposicoes = DB::select(
            "SELECT
                        reposicoes.id AS `id_reposicao`,
                        reposicoes_grades.id_produto,
                        reposicoes.id_fornecedor,
                        reposicoes.data_criacao,
                        reposicoes.id_usuario,
                        reposicoes.situacao
                    FROM reposicoes
                        INNER JOIN reposicoes_grades
                        ON reposicoes_grades.id_reposicao = reposicoes.id
                    WHERE reposicoes_grades.id_produto = :id_produto
                        AND (reposicoes.situacao = 'EM_ABERTO' OR reposicoes.situacao = 'PARCIALMENTE_ENTREGUE')
                    GROUP BY reposicoes.id, reposicoes.data_criacao
                    ORDER BY reposicoes.data_criacao DESC",
            [':id_produto' => $idProduto]
        );
        return $reposicoes;
    }

    public static function buscaTodasReposicoesDoProduto(int $idProduto): array
    {
        $reposicoes = DB::select(
            "SELECT
                        reposicoes.id AS `id_reposicao`,
                        reposicoes_grades.id_produto,
                        reposicoes.id_fornecedor,
                        reposicoes.data_criacao,
                        reposicoes.id_usuario,
                        reposicoes.situacao
                    FROM reposicoes
                        INNER JOIN reposicoes_grades
                        ON reposicoes_grades.id_reposicao = reposicoes.id
                    WHERE reposicoes_grades.id_produto = :id_produto
                    GROUP BY reposicoes.id, reposicoes.data_criacao
                    ORDER BY reposicoes.data_criacao DESC",
            [':id_produto' => $idProduto]
        );
        return $reposicoes;
    }

    // public function buscaEstoqueProdutosPorFornecedor(PDO $conexao, int $id)
    // {
    //     $query = "SELECT eg.id_produto, SUM(eg.estoque)estoque,
    //     (SELECT p.valor_custo_produto FROM produtos p WHERE p.id=eg.id_produto)custo, SUM(eg.vendido)vendido FROM estoque_grade eg
    //     INNER JOIN produtos p ON p.id = eg.id_produto WHERE p.id_fornecedor={$id} GROUP BY eg.id_produto ORDER BY eg.id_produto";
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }
    //
    //    public static function buscaTodos()
    //    {
    //        $sql = "SELECT * FROM produtos";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        return $result->fetchAll(PDO::FETCH_ASSOC);
    //    }
    //

    //
    //    public static function buscaProdutoPorId() {
    //        $sql = "SELECT id, descricao FROM produtos";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        $lista = $result->fetchAll(PDO::FETCH_ASSOC);
    //        return $lista;
    //    }
    //

    //
    //    public static function buscaTodosFornecedor($id_fornecedor)
    //    {
    //        $sql = "SELECT produtos.id,produtos.descricao FROM produtos WHERE produtos.id_fornecedor = {$id_fornecedor}";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        return $result->fetchAll(PDO::FETCH_ASSOC);
    //    }
    //

    // public static function buscaProdutosDefasados(PDO $conn, int $dias = 180)
    // {
    //     $sql = " SELECT  produtos.id,
    //                     produtos.descricao,
    //                     (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id AND produtos_foto.sequencia = 1)foto,
    //                     (
    //                         SELECT colaboradores.razao_social
    //                             FROM colaboradores
    //                                 WHERE colaboradores.id = produtos.id_fornecedor
    //                     ) fornecedor,
    //                    produtos.data_ultima_venda,
    //                    DATE_FORMAT(data_ultima_venda, '%d/%m/%Y')data_formatada
    //                     FROM produtos
    //                     WHERE DATEDIFF(DATE(NOW()), DATE(data_ultima_venda)) >= $dias
    // 									  AND  NOT EXISTS (SELECT 1 FROM estoque_grade WHERE estoque_grade.id_produto = produtos.id AND estoque_grade.estoque > 0 )
    //                                       AND produtos.bloqueado = 0
    //                              GROUP BY produtos.id
    // 									ORDER BY data_ultima_venda DESC";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute();
    //     $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $lista;
    // }
    //
    //    public static function blockProdutos(PDO $conn, array $lista_produtos)
    //    {
    //        $start = 1;
    //        $sql = "";
    //        foreach ($lista_produtos as $key => $item) :
    //            $start == 1 ? $start = 0 : $sql .= ",";
    //            $sql .= "'$item'";
    //        endforeach;
    //        $query = "UPDATE produtos SET produtos.bloqueado = 1 where produtos.id IN ({$sql}) ";
    //        $stmt = $conn->prepare($query);
    //        return $stmt->execute();
    //    }
    //

    //
    //    public static function deletPhotoBanco(PDO $conn, int $id)
    //    {
    //        $query = "DELETE FROM produtos_foto WHERE produtos_foto.id = {$id}";
    //        $stmt = $conn->prepare($query);
    //        return $stmt->execute();
    //    }
    //

    //
    //    public static function buscaPhotoBanco(PDO $conn, int $id)
    //    {
    //        $query = "SELECT caminho,nome_foto FROM produtos_foto WHERE produtos_foto.id = {$id}";
    //        $stmt = $conn->prepare($query);
    //        $stmt->execute();
    //        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $lista;
    //    }
    //

    public static function salvaSugestaoDeProduto(PDO $con, string $foto, int $idCliente)
    {
        $query = "INSERT INTO produtos_sugestao (foto_produto, id_cliente, data_criacao) VALUES('{$foto}',{$idCliente},NOW());";
        $stmt = $con->prepare($query);
        return $stmt->execute();
    }

    public static function buscaProdutosParaTroca(): array
    {
        $idCliente = Auth::user()->id_colaborador;
        $auxiliares = ConfiguracaoService::buscaAuxiliaresTroca(DB::getPdo(), 'MS', $idCliente);
        if (!$auxiliares) {
            throw new Exception('Erro ao buscar informações auxiliares');
        }

        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $lista = DB::select(
            "SELECT
        entregas.id AS id_pedido,
        DATE_FORMAT(entregas.data_entrega, '%d/%m/%Y') AS data_pedido,
        COUNT(logistica_item.id) AS pares,
        CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                'id_produto',     logistica_item.id_produto,
                'descricao',      (SELECT produtos.descricao FROM produtos WHERE produtos.id = logistica_item.id_produto),
                'nome_comercial', (SELECT produtos.nome_comercial
                                    FROM produtos
                                    WHERE produtos.id = logistica_item.id_produto),
                'nome_tamanho',   logistica_item.nome_tamanho,
                'preco',          transacao_financeiras_produtos_itens.preco,
                'preco_logistica', logistica_item.preco,
                'valor_estorno', (
                                    SELECT SUM(transacao_financeiras_produtos_itens.preco)
                                    FROM transacao_financeiras_produtos_itens
                                    WHERE transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
                                    AND transacao_financeiras_produtos_itens.sigla_estorno IS NOT NULL
                                ),
                'situacao',       logistica_item.situacao,
                'foto',           COALESCE((SELECT produtos_foto.caminho
                                            FROM produtos_foto
                                            WHERE produtos_foto.id = logistica_item.id_produto
                                            ORDER BY produtos_foto.tipo_foto = 'MD' ASC
                                            LIMIT 1), ''),
                'uuid',           logistica_item.uuid_produto,
                'data_retirada', DATE_FORMAT(entregas.data_entrega, '%d/%m/%Y %H:%i'),
                'situacao_troca', CASE
                    WHEN entregas_devolucoes_item.id IS NOT NULL
                        THEN 'ITEM_TROCADO'
                    WHEN troca_fila_solicitacoes.situacao = 'CANCELADO_PELO_CLIENTE'
                        THEN 'CLIENTE_DESISTIU'
                    WHEN troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO'
                        THEN 'PASSOU_PRAZO'
                    ELSE
                        'TROCA_DISPONIVEL'
                    END,
                'descricao_defeito', COALESCE(troca_fila_solicitacoes.descricao_defeito, ''),
                'foto1', troca_fila_solicitacoes.foto1,
                'foto2', troca_fila_solicitacoes.foto2,
                'foto3', troca_fila_solicitacoes.foto3,
                'data_base_troca', entregas_faturamento_item.data_base_troca,
                'limite_noventa_dias', entregas_faturamento_item.data_base_troca >= DATE_SUB(NOW(), INTERVAL {$auxiliares['dias_defeito']} DAY),
                'data_limite_tarifa', DATE_FORMAT(DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL {$auxiliares['dias_defeito']} DAY), '%d/%m/%Y %H:%i'),
                'data_limite_troca', DATE_FORMAT(DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL 1 YEAR), '%d/%m/%Y %H:%i'),
                'qtd_dias_do_ano' , DAYOFYEAR(CONCAT(YEAR(entregas_faturamento_item.data_base_troca),'-12-31'))
            )),']') produtos
            FROM entregas
            INNER JOIN logistica_item ON logistica_item.id_entrega = entregas.id AND logistica_item.id_entrega <> 40261
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
	            AND transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = logistica_item.uuid_produto
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto
            WHERE logistica_item.situacao >= $situacao
              AND logistica_item.id_cliente = :id_cliente
              AND entregas_faturamento_item.situacao = 'EN'
              AND entregas.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
              AND NOT EXISTS(SELECT 1
                             FROM pedido_item_meu_look
                             WHERE pedido_item_meu_look.uuid = logistica_item.uuid_produto)
              AND NOT EXISTS(
                    SELECT 1
                    FROM troca_pendente_agendamento
                    WHERE troca_pendente_agendamento.uuid = logistica_item.uuid_produto
              )
              AND IF (
                troca_fila_solicitacoes.id,
                troca_fila_solicitacoes.situacao IN ('CANCELADO_PELO_CLIENTE','PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO'),
                TRUE
              )
            GROUP BY entregas.id
            ORDER BY entregas.id DESC;",
            ['id_cliente' => $idCliente]
        );

        $consulta = array_map(function ($item) use ($auxiliares): array {
            $item['produtos'] = json_decode($item['produtos'], true);
            foreach ($item['produtos'] as $index => $produto) {
                $dataBaseTroca = $produto['data_base_troca'];
                $item['produtos'][$index]['situacao_solicitacao'] = TrocaFilaSolicitacoesService::retornaTextoSituacaoTroca(
                    $produto['situacao_troca'],
                    $dataBaseTroca,
                    0,
                    'MS',
                    $auxiliares
                );
            }
            return $item;
        }, $lista);
        return $consulta;
    }

    public static function buscaTrocasAgendadas(): array
    {
        $idCliente = Auth::user()->id_colaborador;
        $auxiliares = ConfiguracaoService::buscaAuxiliaresTroca(DB::getPdo(), 'MS', $idCliente);
        if (!$auxiliares) {
            throw new Exception('Erro ao buscar informações auxiliares');
        }
        $produtos = DB::select(
            "
            SELECT
                produtos.nome_comercial,
                troca_fila_solicitacoes.id id_solicitacao,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.uuid_produto uuid,
                troca_pendente_agendamento.id defeito,
                entregas_faturamento_item.nome_tamanho,
                DATE_FORMAT(entregas_faturamento_item.data_entrega, '%d/%m/%Y') data_retirada,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                    AND produtos_foto.tipo_foto <> 'SM'
                    LIMIT 1
                ) foto,
                troca_fila_solicitacoes.situacao situacao_troca,
                entregas_faturamento_item.data_base_troca,
                troca_fila_solicitacoes.data_atualizacao data_atualizacao_solicitacao,
                CASE
                    WHEN troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO' THEN 'RETORNO_PRODUTO_EXPIRADO'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_NA_DISPUTA' THEN 'DISPUTA_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'EM_DISPUTA' THEN 'DISPUTA'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADO' THEN 'SELLER_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'SOLICITACAO_PENDENTE' THEN 'TROCA_PENDENTE'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_POR_FOTO' THEN 'FOTO_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'PENDENTE_FOTO' THEN 'PENDENTE_FOTO'
                    ELSE 'Troca aprovada'
                END situacao_solicitacao,
                troca_fila_solicitacoes.motivo_reprovacao_seller motivo_reprovacao,
                (
                    SELECT correios_atendimento.numeroColeta
                    FROM correios_atendimento
                    WHERE correios_atendimento.id_cliente = entregas_faturamento_item.id_cliente
                    AND correios_atendimento.status = 'A'
                    AND correios_atendimento.prazo > NOW()
                    ORDER BY correios_atendimento.data_verificacao DESC
                    LIMIT 1
                ) AS numero_coleta,
                troca_fila_solicitacoes.situacao = 'APROVADO' AND logistica_item.situacao = :situacao AS em_processo_troca
            FROM entregas_faturamento_item
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = entregas_faturamento_item.uuid_produto
            LEFT JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = entregas_faturamento_item.uuid_produto
            INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
            WHERE entregas_faturamento_item.id_cliente = :id_cliente
            AND (troca_fila_solicitacoes.id IS NOT NULL OR troca_pendente_agendamento.id IS NOT NULL)
            AND entregas_faturamento_item.origem <> 'ML'
            AND IF (troca_fila_solicitacoes.id,
                troca_fila_solicitacoes.situacao IN (
                    'EM_DISPUTA', 'SOLICITACAO_PENDENTE', 'REPROVADO', 'REPROVADA_NA_DISPUTA', 'REPROVADA_POR_FOTO', 'PENDENTE_FOTO', 'APROVADO'
                    ),
                TRUE
                )
            GROUP BY entregas_faturamento_item.uuid_produto
            ORDER BY situacao_solicitacao = 'SELLER_REPROVOU' DESC,
                    situacao_solicitacao = 'DISPUTA' DESC,
                    situacao_solicitacao = 'TROCA_PENDENTE' DESC,
                    situacao_solicitacao = 'FOTO_REPROVOU' DESC,
                    situacao_solicitacao = 'PENDENTE_FOTO' DESC,
                    situacao_solicitacao = 'Troca aprovada' DESC,
                    entregas_faturamento_item.data_entrega DESC
        ",
            [
                'id_cliente' => $idCliente,
                'situacao' => LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA,
            ]
        );

        $consulta = array_map(function ($item) use ($auxiliares): array {
            $dataBaseTroca = $item['data_base_troca'];
            $dataAtualizacaoSolicitacao = $item['data_atualizacao_solicitacao'];
            $item['situacao_solicitacao'] = TrocaFilaSolicitacoesService::retornaTextoSituacaoTroca(
                $item['situacao_solicitacao'],
                $dataBaseTroca,
                $dataAtualizacaoSolicitacao ?: 0,
                'MS',
                $auxiliares
            );
            return $item;
        }, $produtos);

        return $consulta ?: [];
    }

    // public static function buscaDefeitos(PDO $conexao, int $idCliente)
    // {
    //     $query =
    //         "SELECT
    //         troca_pendente_item.id_produto,
    //         produtos.nome_comercial,
    //         troca_pendente_item.tamanho,
    //         troca_pendente_item.preco,
    //         troca_pendente_item.descricao_defeito,
    //         produtos_foto.caminho AS foto_produto,
    //         colaboradores.razao_social,
    //         DATE_FORMAT(troca_pendente_item.data_hora, '%d/%m/%Y') AS 'data',
    //         IF(entregas_devolucoes_item.situacao IN ('CO', 'RE'),0,1) verifica_devolucao
    //     FROM
    //         troca_pendente_item
    //         JOIN produtos ON produtos.id = troca_pendente_item.id_produto
    //         JOIN produtos_foto on produtos_foto.id = troca_pendente_item.id_produto
    //         JOIN colaboradores ON colaboradores.id = troca_pendente_item.id_cliente
    //         LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = troca_pendente_item.uuid AND entregas_devolucoes_item.situacao_envio = 'NO'
    //     WHERE
    //         produtos.id_fornecedor = $idCliente AND
    //         troca_pendente_item.defeito = 1 AND
    //         troca_pendente_item.descricao_defeito IS NOT NULL AND
    //         troca_pendente_item.data_hora BETWEEN (SELECT DATE_SUB(NOW(), INTERVAL 31 DAY)) AND NOW()
    //     GROUP BY descricao_defeito
    //     HAVING verifica_devolucao = 0
    //     ORDER BY data";
    //     $prepare = $conexao->prepare($query);
    //     $prepare->execute();
    //     $resultado = $prepare->fetchAll(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }
    public static function buscalistaAguardaRetornoEstoque(int $idProduto)
    {
        $conexao = Conexao::criarConexao();
        $sql = $conexao->prepare(
            "SELECT
                produtos_aguarda_entrada_estoque.nome_tamanho tamanho,
                CASE
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'CO' THEN 'Compra'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'FT' THEN 'Foto'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'TR' THEN 'Troca'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'PC' THEN 'Pedido Cancelado'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'SP' THEN 'Separar foto'
                    ELSE 'NAO IDENTIFICADO'
                END tipo_entrada,
                SUM(produtos_aguarda_entrada_estoque.qtd) qtd,
                CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                    'nome_tamanho', produtos_aguarda_entrada_estoque.nome_tamanho,
                    'id', produtos_aguarda_entrada_estoque.id
                )), ']') estoque
            FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto
                AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
            GROUP BY produtos_aguarda_entrada_estoque.tipo_entrada"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $produto): array {
            $produto['estoque'] = (array) json_decode($produto['estoque'], true);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function buscaProdutoPorBarCode(PDO $conexao, string $codigoBarras): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos_grade.id,
                produtos_grade.id_produto,
                produtos_grade.nome_tamanho,
                produtos.localizacao,
                produtos.descricao,
                produtos.descricao
            FROM produtos_grade
            INNER JOIN produtos ON produtos.id = produtos_grade.id_produto
            WHERE produtos_grade.cod_barras = :codigo_barras AND EXISTS(
                SELECT 1
                FROM estoque_grade
                WHERE estoque_grade.id_produto = produtos_grade.id_produto
                    AND estoque_grade.id_responsavel = 1
            );"
        );
        $sql->bindValue(':codigo_barras', $codigoBarras, PDO::PARAM_STR);
        $sql->execute();
        $produto = $sql->fetch(PDO::FETCH_ASSOC);

        return $produto ?: [];
    }
    public static function buscaProdutosPorLocalizacao(PDO $conexao, int $local): array
    {
        $sql = $conexao->prepare(
            "SELECT produtos.id
            FROM produtos
            WHERE produtos.localizacao = :localizacao;"
        );
        $sql->bindValue(':localizacao', $local, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $produtos ?: [];
    }
    public static function consultaProdutosCompradosParametros(
        PDO $conexao,
        int $idUsuario,
        int $idCliente,
        array $parametros,
        int $pagina
    ): array {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $query = "SELECT
            DISTINCT logistica_item.id_transacao id_faturamento,
            CONCAT(
            '[',
            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                'uuid', logistica_item.uuid_produto,
                'id_produto', logistica_item.id_produto,
                'id_faturamento', logistica_item.id_transacao,
                'nome_tamanho', logistica_item.nome_tamanho,
                'data_hora', logistica_item.data_criacao,
                'preco', logistica_item.preco,
                'premio', 0,
                'descricao', produtos.descricao,
                'passou_prazo', NOT (
                                            SELECT
                                                entregas_faturamento_item.data_base_troca
                                            FROM entregas_faturamento_item
                                            WHERE entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                                            LIMIT 1
                                        ) BETWEEN CURDATE() - INTERVAL 1 YEAR AND CURDATE(),
                'fotoProduto', (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ),
                'fornecedor', (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ),
                'cod_barras', (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = logistica_item.id_produto
                        AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                ),
                'linha', (
                    SELECT linha.nome
                    FROM linha
                    WHERE linha.id = produtos.id_linha
                )
            ))
            , ']'
            )produtos
        FROM logistica_item
        INNER JOIN produtos ON produtos.id = logistica_item.id_produto
        WHERE logistica_item.id_cliente = :id_cliente
            AND logistica_item.situacao >= $situacao
            AND logistica_item.id_responsavel_estoque = 1
            AND NOT EXISTS(
                SELECT 1
                FROM troca_pendente_agendamento
                WHERE troca_pendente_agendamento.uuid = logistica_item.uuid_produto
            ) AND NOT EXISTS(
                SELECT 1
                FROM pedido_item_meu_look
                WHERE pedido_item_meu_look.uuid = logistica_item.uuid_produto
            )";

        $bindValues = [':id_cliente' => (int) $idCliente];

        if (!empty($parametros['id_fornecedor'])) {
            Validador::validar($parametros, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND produtos.id_fornecedor = :id_fornecedor';
            $bindValues[':id_fornecedor'] = (int) $parametros['id_fornecedor'];
        }
        if (!empty($parametros['id_faturamento'])) {
            Validador::validar($parametros, [
                'id_faturamento' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND logistica_item.id_transacao = :id_faturamento';
            $bindValues[':id_faturamento'] = (int) $parametros['id_faturamento'];
        }
        if (!empty($parametros['id_categorias'])) {
            Validador::validar($parametros, [
                'id_categorias' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $categorias = (string) implode(',', $parametros['id_categorias']);
            $query .= " AND EXISTS(
                SELECT 1
                FROM produtos_categorias
                WHERE produtos_categorias.id_produto = logistica_item.id_produto
                    AND produtos_categorias.id_categoria IN ($categorias)
            )";
        }
        if (!empty($parametros['id_linha'])) {
            Validador::validar($parametros, [
                'id_linha' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND produtos.id_linha = :id_linha';
            $bindValues[':id_linha'] = (int) $parametros['id_linha'];
        }
        if (!empty($parametros['nome_tamanho'])) {
            Validador::validar($parametros, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $query .= ' AND logistica_item.nome_tamanho = :nome_tamanho';
            $bindValues[':nome_tamanho'] = (string) $parametros['nome_tamanho'];
        }
        if (!empty($parametros['descricao'])) {
            Validador::validar($parametros, [
                'descricao' => [Validador::OBRIGATORIO],
            ]);

            $query .= " AND LOWER(CONCAT_WS(
                    ' - ',
                    logistica_item.id_produto,
                    produtos.descricao
                )) REGEXP LOWER(:descricao) ";
            $bindValues[':descricao'] = (string) $parametros['descricao'];
        }
        if (!empty($parametros['cod_barras'])) {
            Validador::validar($parametros, [
                'cod_barras' => [Validador::OBRIGATORIO],
            ]);

            $query .= " AND EXISTS(
                SELECT 1
                FROM produtos_grade
                WHERE produtos_grade.id_produto = logistica_item.id_produto
                    AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                    AND produtos_grade.cod_barras = :cod_barras
            )";
            $bindValues[':cod_barras'] = (string) $parametros['cod_barras'];
        }
        $query .= ' GROUP BY logistica_item.id_transacao ORDER BY logistica_item.id_transacao DESC';
        if ($pagina !== 0) {
            $offset = ($pagina - 1) * 3;
            $query .= " LIMIT 3 OFFSET $offset;";
        }

        $stmt = $conexao->prepare($query);
        $stmt->execute($bindValues);
        $faturamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /** @var Colaborador $colaborador */
        $colaborador = ColaboradoresRepository::busca([
            'id' => "(SELECT usuarios.id_colaborador FROM usuarios WHERE usuarios.id = $idUsuario)",
        ]);

        foreach ($faturamentos as $key => $faturamento) {
            $faturamento = (array) json_decode($faturamento['produtos'], true);

            foreach ($faturamento as $index => $produto) {
                try {
                    $troca = new TrocaPendenteItem(
                        $idCliente,
                        $produto['id_produto'],
                        $produto['nome_tamanho'],
                        $colaborador->getId(),
                        $produto['preco'],
                        $produto['uuid'],
                        '',
                        $produto['data_hora']
                    );
                    $produto['taxa'] = (float) round($troca->calculaTaxa());
                } catch (\InvalidArgumentException $exception) {
                    $produto['disponivel'] = 0;
                }

                $produto['mensagem_indisponivel'] = '';
                if ($produto['premio']) {
                    $produto['mensagem_indisponivel'] = 'Esse produto é prêmio';
                } elseif ($produto['passou_prazo']) {
                    $produto['mensagem_indisponivel'] = 'Esse produto já passou do prazo de 365 dias';
                }

                $produto['preco'] = (float) $produto['preco'];
                $produto['data_hora'] = (string) (new \DateTime($produto['data_hora']))->format('d/m/y H:i:s');
                $produto['correto'] = true;
                $produto['agendada'] = false;
                $produto['defeito'] = false;

                $faturamento[$index] = $produto;
            }

            $faturamentos[$key] = $faturamento;
        }

        return $faturamentos ?? [];
    }
    public static function buscaProdutosDefeituosos(int $idFornecedor): array
    {
        $produtos = DB::select(
            "SELECT
                    entregas_devolucoes_item.id_produto,
                    entregas_devolucoes_item.uuid_produto,
                    entregas_devolucoes_item.nome_tamanho,
                    DATE_FORMAT(entregas_devolucoes_item.data_atualizacao, '%d/%m/%Y')data,
                    CONCAT(COALESCE(produtos.nome_comercial, produtos.descricao), ' ', COALESCE(produtos.cores, ''))nome_comercial,
                    (
                        SELECT colaboradores.razao_social
                        FROM colaboradores
                        WHERE colaboradores.id = entregas_devolucoes_item.id_cliente
                    )razao_social,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto_produto,
                    (
                        SELECT troca_pendente_item.descricao_defeito
                        FROM troca_pendente_item
                        WHERE troca_pendente_item.uuid = entregas_devolucoes_item.uuid_produto
                    ) descricao_defeito
                FROM entregas_devolucoes_item
                INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                WHERE
                    DATE(entregas_devolucoes_item.data_atualizacao) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND entregas_devolucoes_item.situacao IN ('CO','RE')
                    AND entregas_devolucoes_item.tipo = 'DE'
                    AND produtos.id_fornecedor = :id_fornecedor
                    GROUP BY entregas_devolucoes_item.uuid_produto
                    ORDER BY entregas_devolucoes_item.data_atualizacao DESC",
            [
                ':id_fornecedor' => $idFornecedor,
            ]
        );

        return $produtos;
    }
    public static function listaLinhas(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                linha.id,
                linha.nome
            FROM linha
            ORDER BY linha.nome;"
        );
        $sql->execute();
        $linhas = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $linhas;
    }
    public static function listaTiposGrade(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos_tipos_grades.id,
                produtos_tipos_grades.nome,
                produtos_tipos_grades.grade_json
            FROM produtos_tipos_grades;"
        );
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

        $grades = array_map(function ($grade) {
            if (!is_null($grade['grade_json'])) {
                $grade['grade_json'] = json_decode($grade['grade_json'], true);
            }

            return (array) $grade;
        }, $grades);

        return $grades;
    }
    public static function listaCategorias(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                categorias.id,
                categorias.nome,
                categorias.id_categoria_pai
            FROM categorias
            ORDER BY categorias.id_categoria_pai ASC, categorias.nome;"
        );
        $sql->execute();
        $listaCategorias = $sql->fetchAll(PDO::FETCH_ASSOC);
        $categorias = [];
        $tipos = [];

        foreach ($listaCategorias as $categoria) {
            if (is_null($categoria['id_categoria_pai'])) {
                //Define Categorias
                $categorias[] = $categoria;
            } else {
                //Define Tipos
                $tipos[] = $categoria;
            }
        }
        $listaCategorias = ['categorias' => (array) $categorias, 'tipos' => (array) $tipos];

        return $listaCategorias;
    }
    public static function listaCores(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                tags_tipos.id_tag,
                tags_tipos.tipo,
                (
                    SELECT tags.nome
                    FROM tags
                    WHERE tags.id = tags_tipos.id_tag
                )nome
            FROM tags_tipos
            WHERE tags_tipos.tipo = 'CO'
            ORDER BY tags_tipos.ordem DESC;"
        );
        $sql->execute();
        $cores = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $cores;
    }
    public static function filtraProduto(PDO $conexao, string $pesquisa): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.descricao,
                produtos.localizacao
            FROM produtos
            WHERE produtos.id REGEXP :pesquisa OR produtos.descricao REGEXP :pesquisa
            ORDER BY produtos.id"
        );
        $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public static function buscarDetalhesMovimentacao(PDO $conexao, int $id_movimentacao): array
    {
        $query = "SELECT
            movimentacao_estoque_item.id_produto,
            produtos.descricao produto,
            produtos.id
        FROM movimentacao_estoque_item
        INNER JOIN produtos ON (produtos.id = movimentacao_estoque_item.id_produto)
        WHERE movimentacao_estoque_item.id_mov = :id_movimentacao";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_movimentacao', $id_movimentacao, PDO::PARAM_INT);
        $stmt->execute();
        $busca = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "SELECT SUM(quantidade) historico,
            id_produto
        FROM movimentacao_estoque_item
        WHERE id_produto = :id_produto AND compra > 0";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_produto', $busca['id_produto'], PDO::PARAM_INT);
        $stmt->execute();
        $busca1 = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "SELECT SUM(estoque) estoque,
            SUM(vendido) vendidos
        FROM estoque_grade
        WHERE id_produto = :id_produto";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_produto', $busca['id_produto'], PDO::PARAM_INT);
        $stmt->execute();
        $busca2 = $stmt->fetch(PDO::FETCH_ASSOC);

        $items = [
            'id' => $busca['id'],
            'produto' => $busca['produto'],
            'historico' => $busca1['historico'],
            'estoque' => $busca2['estoque'],
            'vendidos' => $busca2['vendidos'],
        ];

        $estoque = $items['estoque'] + $items['vendidos'];
        $vendidos = $items['historico'] - $estoque;

        $query = "SELECT produtos.valor_venda_ms
        FROM produtos
        WHERE produtos.id = :id";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id', $items['id'], PDO::PARAM_INT);
        $stmt->execute();
        $arr = $stmt->fetch(PDO::FETCH_ASSOC);

        $preco = $arr['valor_venda_ms'];

        $final = [
            'estoque' => $estoque,
            'vendidos' => $vendidos,
            'preco' => $preco,
            'items' => $items,
        ];

        return $final;
    }
    //    public static function buscaEstoqueGradeFornecedor(\PDO $conexao, int $idFornecedor): array
    //    {
    //        $sql = $conexao->prepare(
    //            "SELECT
    //                estoque_grade.id_produto,
    //                estoque_grade.nome_tamanho,
    //                estoque_grade.estoque
    //            FROM estoque_grade
    //            WHERE estoque_grade.id_responsavel = :id_fornecedor;"
    //        );
    //        $sql->bindValue(":id_fornecedor", $idFornecedor, PDO::PARAM_INT);
    //        $sql->execute();
    //        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);
    //
    //        return $grades;
    //    }
    public static function buscaListaPontuacoes(
        PDO $conexao,
        string $pesquisa,
        int $pagina,
        bool $listarTodos,
        int $idCliente
    ): array {
        $interno = ColaboradoresRepository::buscaPermissaoUsuario($conexao, $idCliente);
        $interno = (int) in_array('INTERNO', $interno);

        $where = '';
        if ($pesquisa) {
            $where .= " AND (produtos.id LIKE :pesquisa
                OR produtos.nome_comercial LIKE :pesquisa
                OR produtos.descricao LIKE :pesquisa
                OR colaboradores.razao_social LIKE :pesquisa
                OR colaboradores.usuario_meulook LIKE :pesquisa
            )";
        }

        if (!$listarTodos) {
            $where .= ' AND colaboradores.id = :idCliente';
        }

        $porPagina = 100;
        $offset = $porPagina * ($pagina - 1);

        $stmt = $conexao->prepare(
            "SELECT
                (
                    SELECT publicacoes_produtos.id
                    FROM publicacoes_produtos
                    WHERE publicacoes_produtos.id_produto = produtos_pontos.id_produto
                        AND publicacoes_produtos.situacao = 'CR'
                    ORDER BY RAND()
                    LIMIT 1
                ) id_publicacao,
                produtos_pontos.id_produto,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.pontuacao_avaliacoes, 0) pontuacao_avaliacoes,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.pontuacao_seller, 0) pontuacao_seller,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.pontuacao_fullfillment, 0) pontuacao_fullfillment,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.quantidade_vendas, 0) quantidade_vendas,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.pontuacao_devolucao_normal, 0) pontuacao_devolucao_normal,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.pontuacao_devolucao_defeito, 0) pontuacao_devolucao_defeito,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.cancelamento_automatico, 0) cancelamento_automatico,
                IF($interno OR colaboradores.id = :idCliente, produtos_pontos.atraso_separacao, 0) atraso_separacao,
                produtos_pontos.total,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_pontos.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto,
                colaboradores.razao_social razao_social_seller,
                colaboradores.usuario_meulook usuario_meulook_seller,
                colaboradores.id = :idCliente meu_produto
            FROM produtos_pontos
            INNER JOIN produtos ON produtos.id = produtos_pontos.id_produto
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            WHERE produtos.bloqueado = 0
                AND (produtos.fora_de_linha = 0
                    OR (produtos.fora_de_linha = 1
                        AND EXISTS(
                            SELECT 1
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = produtos.id
                                AND estoque_grade.estoque > 0
                            LIMIT 1
                        )
                    )
                ) $where
            GROUP BY produtos_pontos.id_produto
            ORDER BY produtos_pontos.total DESC
            LIMIT $porPagina OFFSET $offset"
        );
        if ($pesquisa) {
            $stmt->bindValue(':pesquisa', '%' . $pesquisa . '%');
        }
        $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $consulta = array_map(function ($item) {
            $item['id_produto'] = (int) $item['id_produto'];
            if ($item['pontuacao_avaliacoes'] !== null) {
                $item['pontuacao_avaliacoes'] = (int) $item['pontuacao_avaliacoes'];
            }
            if ($item['pontuacao_seller'] !== null) {
                $item['pontuacao_seller'] = (int) $item['pontuacao_seller'];
            }
            if ($item['pontuacao_fullfillment'] !== null) {
                $item['pontuacao_fullfillment'] = (int) $item['pontuacao_fullfillment'];
            }
            if ($item['quantidade_vendas'] !== null) {
                $item['quantidade_vendas'] = (int) $item['quantidade_vendas'];
            }
            if ($item['pontuacao_devolucao_normal'] !== null) {
                $item['pontuacao_devolucao_normal'] = (int) $item['pontuacao_devolucao_normal'];
            }
            if ($item['pontuacao_devolucao_defeito'] !== null) {
                $item['pontuacao_devolucao_defeito'] = (int) $item['pontuacao_devolucao_defeito'];
            }
            if ($item['cancelamento_automatico'] != null) {
                $item['cancelamento_automatico'] = (int) $item['cancelamento_automatico'];
            }
            if ($item['atraso_separacao'] != null) {
                $item['atraso_separacao'] = (int) $item['atraso_separacao'];
            }
            $item['meu_produto'] = (bool) $item['meu_produto'];
            $item['total'] = (int) $item['total'];
            $item['link_produto'] = "{$_ENV['URL_MEULOOK']}produto/{$item['id_produto']}";
            unset($item['id_publicacao']);
            $item['link_seller'] = "{$_ENV['URL_MEULOOK']}{$item['usuario_meulook_seller']}";
            return $item;
        }, $consulta);

        return $consulta;
    }

    // public static function buscaProdutosMobileStock(PDO $conexao, array $idProdutos, array $filtro = [], int $limite = 100, int $offset = 0): array
    // {
    //     $where = '';
    //     $idsLista = [];
    //     $produtoIdWhere = '';
    //     $tamanhoBind = [];
    //     $ordernamento = '';
    //     $tamanhosBindString = '';
    //     $ordenamentoIdsProdutos = '';
    //     $offsetFiltro = '';
    //     $produtosBloqueado = ' AND produtos.bloqueado <> 1 ';

    //     $ordernamento .= ', produtos_ordem_catalogo.id DESC';
    //     if(!empty($filtro['ordenar'])) {
    //         switch ($filtro['ordenar']) {
    //             case 'lancamentos':
    //                 $ordernamento = ', produtos.data_primeira_entrada DESC';
    //             break;
    //             case 'menorPreco':
    //                 $ordernamento = ', produtos.valor_custo_produto ASC';
    //             break;
    //             case 'promocao':
    //                 $ordernamento = ', produtos.preco_promocao DESC';
    //             break;
    //             case 'fotosCalcadas':
    //                 $ordernamento = ', produtos_foto.tipo_foto = "LG" DESC';
    //             break;
    //         }
    //     }

    //     if (empty($filtro['pesquisa']) || !empty($filtro['ordenar'])) {
    //         $where .= " AND estoque_grade.estoque > 0";
    //     }

    //     if (!empty($filtro['tamanho'])) {
    //         [$tamanhoBind, $tamanhosBindString] = ConversorArray::criaBindValues($filtro['tamanho'], 'tamanho');
    //         $tamanhoStrAux = 'AND (';
    //         foreach (array_keys($tamanhosBindString) as $tamanho) {
    //             $tamanhoStrAux .= " estoque_grade.nome_tamanho REGEXP $tamanho OR";
    //         }
    //         $tamanhoStrAux = substr($tamanhoStrAux, 0, -2);
    //         $tamanhoStrAux .= ')';
    //         $where .= " $tamanhoStrAux AND estoque_grade.estoque > 0";
    //     }

    //     if (!empty($filtro['linhas'])) {
    //         $linhasArr = explode('-', $filtro['linhas'][0]);

    //         foreach ($linhasArr as $linha) {
    //             Validador::validar(['linhas' => $linha], [
    //                 'linhas' => [Validador::ENUM('adulto', 'infantil')],
    //             ]);
    //         }

    //         $linhasBuscadas = ProdutosRepository::buscaLinhasPorNome($conexao, $linhasArr);
    //         $linhasSql = implode(',', $linhasBuscadas);
    //         if ($linhasSql) $where .= " AND produtos.id_linha IN ($linhasSql)";
    //     }

    //     if (!empty($idProdutos)) {
    //         [$idsLista, $bindId] = ConversorArray::criaBindValues($idProdutos, 'id_produto');
    //         $produtoIdWhere .= "AND produtos.id IN (" . $idsLista . ")";

    //         foreach ($idProdutos as $id) {
    //             $ordenamentoIdsProdutos .= ", produtos.id = $id DESC ";
    //         }

    //         if (count($idProdutos) === 1) {
    //             $produtosBloqueado = ' ';
    //         }
    //     }

    //     if (empty($idProdutos)) {
    //         $offsetFiltro = " OFFSET $offset ";
    //     }

    //     $query = "SELECT
    //             produtos.id,
    //             LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome,
    //             produtos.valor_venda_ms,
    //             produtos.descricao,
    //             produtos.nome_comercial,
    //             produtos.preco_promocao > 0 situacao_Promocao,
    // 			produtos.preco_promocao = 0 situacao_Normal,
    // 			produtos.posicao_acessado > 0 situacao_Destaque,
    //             DATEDIFF(CURDATE(), produtos.data_primeira_entrada) < 7 situacao_Novidade,
    // 			produtos.preco_promocao promocao,
    //             COALESCE(
    //                 CONCAT(
    //                     '[',
    //                         (
    //                             SELECT
    //                                 GROUP_CONCAT(DISTINCT
    //                                     JSON_OBJECT(
    //                                     'id', produtos_categorias.id_categoria,
    //                                     'nome',categorias.nome
    //                                     )
    //                                 )
    //                             FROM produtos_categorias
    //                             INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
    //                             WHERE produtos.id = produtos_categorias.id_produto
    //                         )
    //                         ,
    //                     ']'
    //                     ),
    //                 '[]'
    //             ) categorias,
    //             CONCAT(
    // 				'[',
    // 				GROUP_CONCAT(DISTINCT JSON_OBJECT(
    // 					'nome_tamanho', estoque_grade.nome_tamanho,
    // 					'estoque', estoque_grade.estoque
    // 				) ORDER BY estoque_grade.sequencia ASC),
    // 				']'
    // 			) estoque,
    //             JSON_OBJECT
    //             (
    //                 'src', produtos_foto.caminho,
    //                 'alt', produtos.nome_comercial,
    //                 'title', produtos.descricao
    //             ) foto,
    //             JSON_OBJECT(
    // 				'valor_venda', produtos.valor_venda_ms,
    // 				'valor_venda_anterior', produtos.valor_venda_ms_historico
    // 			) valores,
    //             produtos.data_entrada,
    //             SUM(estoque_grade.estoque) > 0 tem_estoque
    //         FROM produtos
    //         INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
    //         INNER JOIN produtos_ordem_catalogo ON produtos_ordem_catalogo.id_produto = produtos.id
    //         INNER JOIN produtos_foto
    //             ON produtos_foto.id = produtos.id
    //             AND produtos_foto.tipo_foto <> 'SM'
    //         WHERE
    //             estoque_grade.id_responsavel = 1
    //             $produtosBloqueado
    //             $produtoIdWhere
    //             $where
    //         GROUP BY produtos.id
    //         ORDER BY
    //             SUM(estoque_grade.estoque) > 0 DESC
    //             $ordernamento
    //             $ordenamentoIdsProdutos
    //         LIMIT $limite $offsetFiltro";

    //     $stmt = $conexao->prepare($query);

    //     if (!empty($idProdutos)) {
    //         foreach ($bindId as $key => $value) {
    //             $stmt->bindValue($key, $value, PDO::PARAM_INT);
    //         }
    //     }

    //     if (isset($bindSexos)) {
    //         foreach ($bindSexos as $key => $value) {
    //             $stmt->bindValue($key, $value, PDO::PARAM_STR);
    //         }
    //     }

    //     if (!empty($tamanhoBind)) {
    //         foreach ($tamanhosBindString as $key => $value) {
    //             $stmt->bindValue($key, $value, PDO::PARAM_STR);
    //         }
    //     }

    //     $stmt->execute();
    //     $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $consulta = array_map(function ($item) {
    //         if (isset($item['valores'])) $item['valores'] = json_decode($item['valores'], true);
    //         $item['situacoes'] = ProdutosRepository::calculaSituacoesProdutoCatalogo($item);
    //         $item['foto'] = json_decode($item['foto'], true);
    //         $item['categorias'] = json_decode($item['categorias'], true);
    //         $item['estoque'] = json_decode($item['estoque'], true);
    //         $item['estoque'] = ['grade' => array_values(array_filter($item['estoque'], fn ($grade) => $grade['estoque'] > 0 ))];
    //         return $item;
    //     }, $consulta);

    //     return $consulta;

    // }

    /**
     * @param int[] $idProdutos
     */
    // public static function buscarProdutosSemelhantesMobileStock(\PDO $conexao, int $idProdutoAtual, array $idProdutos): array
    // {

    //     [$idsLista, $bindId] = ConversorArray::criaBindValues($idProdutos);

    //     $query = "SELECT
    //                 produtos.id,
    //                 produtos.nome_comercial,
    //                 produtos.descricao,
    //                 produtos_foto.caminho foto
    //             FROM produtos
    //             INNER JOIN produtos_foto ON produtos_foto.id = produtos.id
    //                 AND produtos_foto.tipo_foto <> 'SM'
    //             INNER JOIN estoque_grade
    //                 ON estoque_grade.id_produto = produtos.id
    //                 AND estoque_grade.estoque > 0
    //             INNER JOIN produtos_categorias ON produtos_categorias.id_produto = produtos.id
    //             INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
    //             INNER JOIN linha ON linha.id = produtos.id_linha
    //             WHERE produtos.id <> :idProdutoAtual
    //                 AND produtos.id IN (" . $idsLista . ")
    //                 AND COALESCE(produtos.nome_comercial, '') <> ''
    //                 AND produtos.bloqueado = 0
    //                 AND produtos.premio = 0
    //                 AND produtos.fora_de_linha = 0
    //                 AND estoque_grade.id_responsavel = 1
    //             GROUP BY produtos.id
    //             ORDER BY
    //                 linha.nome,
    //                 categorias.nome
    //             LIMIT 10";

    //     $stmt = $conexao->prepare($query);
    //     foreach ($bindId as $key => $value) {
    //         $stmt->bindValue($key, $value, PDO::PARAM_INT);
    //     }
    //     $stmt->bindValue(':idProdutoAtual', $idProdutoAtual, PDO::PARAM_INT);
    //     $stmt->execute();
    //     $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     return $produtos;
    // }

    /**
     * @param int[] $idProdutos
     */
    public static function buscarProdutosParaCatalogoPdf(PDO $conexao, array $idProdutos): array
    {
        [$idsLista, $bindId] = ConversorArray::criaBindValues($idProdutos, 'id_produto');

        $query = "SELECT
                    produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id IN ($idsLista)
                    AND produtos_foto.tipo_foto <> 'SM'
                GROUP BY produtos_foto.id
                ORDER BY produtos_foto.tipo_foto = 'MD' DESC";

        $stmt = $conexao->prepare($query);

        foreach ($bindId as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $produtos ?: [];
    }
    public static function buscaFotoDoProduto(PDO $conexao, int $idProduto): string
    {
        $sql = $conexao->prepare(
            "SELECT produtos_foto.caminho
            FROM produtos_foto
            WHERE produtos_foto.tipo_foto <> 'SM'
                AND produtos_foto.id = :id_produto
            GROUP BY produtos_foto.id
            ORDER BY produtos_foto.tipo_foto = 'MD' DESC;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $fotoProduto = $sql->fetchColumn();
        if (empty($fotoProduto)) {
            throw new Exception('Não foi possível encontrar a foto do produto');
        }

        return $fotoProduto;
    }

    // public static function geraCatalogoMobileStock(PDO $conexao): void
    // {

    //     $query = "TRUNCATE TABLE produtos_ordem_catalogo";
    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();

    //     $query = "INSERT INTO produtos_ordem_catalogo (id_produto)
    //                 SELECT produtos.id
    //                 FROM produtos
    //                     INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
    //                 WHERE produtos.data_primeira_entrada IS NOT NULL
    //                     AND estoque_grade.id_responsavel = 1
    //                 GROUP BY produtos.id
    //                 ORDER BY RAND()";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();

    // }
    public static function insereAvisoSeller(int $idProduto, int $idFornecedor, string $nomeTamanho): void
    {
        DB::insert(
            "INSERT INTO alerta_responsavel_estoque_cancelamento (
                alerta_responsavel_estoque_cancelamento.id_produto,
                alerta_responsavel_estoque_cancelamento.nome_tamanho,
                alerta_responsavel_estoque_cancelamento.id_responsavel_estoque
            ) VALUES (
                :id_produto,
                :nome_tamanho,
                :id_responsavel_estoque
            );",
            [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
                ':id_responsavel_estoque' => $idFornecedor,
            ]
        );
    }
    public static function removeAvisoSeller(PDO $conexao, int $idFornecedor, int $idAlerta): void
    {
        $sql = $conexao->prepare(
            "DELETE FROM alerta_responsavel_estoque_cancelamento
            WHERE alerta_responsavel_estoque_cancelamento.id_responsavel_estoque = :id_responsavel_estoque
                AND alerta_responsavel_estoque_cancelamento.id = :id_alerta;"
        );
        $sql->bindValue(':id_responsavel_estoque', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':id_alerta', $idAlerta, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() < 1) {
            throw new Exception('Não foi possível remover alerta, entre em contato com a equipe de T.I.');
        }
    }
    public static function buscaListaProdutosCanceladosSeller(PDO $conexao, int $idFornecedor): array
    {
        $sql = $conexao->prepare(
            "SELECT
                alerta_responsavel_estoque_cancelamento.id,
                alerta_responsavel_estoque_cancelamento.id_produto,
                alerta_responsavel_estoque_cancelamento.nome_tamanho,
                DATE_FORMAT(alerta_responsavel_estoque_cancelamento.data_criacao, '%d/%m/%Y') AS data_cancelamento,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = alerta_responsavel_estoque_cancelamento.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto
            FROM alerta_responsavel_estoque_cancelamento
            WHERE alerta_responsavel_estoque_cancelamento.id_responsavel_estoque = :id_responsavel_estoque;"
        );
        $sql->bindValue(':id_responsavel_estoque', $idFornecedor, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $produto): array {
            $produto['id'] = (int) $produto['id'];
            $produto['id_produto'] = (int) $produto['id_produto'];

            return $produto;
        }, $produtos);

        return $produtos ?: [];
    }
    public static function listaDeProdutosMaisVendidos(PDO $conexao, int $pagina, string $dataIncial): array
    {
        $where = '';
        $itensPorPag = 150;
        $offset = $itensPorPag * ($pagina - 1);
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        if ($dataIncial !== '') {
            $where = ' AND DATE(logistica_item.data_criacao) >= DATE(:data_inicial) ';
        }

        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.id_produto,
                estoque_grade.id_responsavel,
                colaboradores.razao_social,
                colaboradores.telefone,
                COALESCE(reputacao_fornecedores.reputacao, 'NOVATO') AS reputacao,
                (
                    SELECT COALESCE(produtos.nome_comercial, produtos.descricao)
                    FROM produtos
                    WHERE produtos.id = logistica_item.id_produto
                ) AS nome_produto,
                EXISTS(
                    SELECT 1
                    FROM produtos
                    WHERE produtos.id = estoque_grade.id_produto
                        AND produtos.permitido_reposicao = 1
                ) AS possui_permissao,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS foto_produto,
                COUNT(logistica_item.uuid_produto) AS qtd_vendas
            FROM estoque_grade
            INNER JOIN logistica_item ON logistica_item.id_produto = estoque_grade.id_produto
                AND logistica_item.id_responsavel_estoque = estoque_grade.id_responsavel
                AND logistica_item.nome_tamanho = estoque_grade.nome_tamanho
                AND logistica_item.situacao <= :situacao
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE estoque_grade.id_responsavel > 1
                $where
            GROUP BY estoque_grade.id_produto
            ORDER BY qtd_vendas DESC, possui_permissao ASC
            LIMIT :itens_por_pag OFFSET :offset;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        $sql->bindValue(':itens_por_pag', $itensPorPag, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        if ($dataIncial !== '') {
            $sql->bindValue(':data_inicial', $dataIncial, PDO::PARAM_STR);
        }
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $produto): array {
            $idColaborador = (int) $produto['id_responsavel'];
            $razaoSocial = trim($produto['razao_social']);
            $produto['razao_social'] = "($idColaborador) $razaoSocial";
            $produto['id_responsavel'] = $idColaborador;

            $produto['telefone'] = (int) preg_replace('/[^0-9]+/i', '', $produto['telefone']);
            $produto['qr_code'] = Globals::geraQRCODE('https://api.whatsapp.com/send/?phone=55' . $produto['telefone']);
            $produto['telefone'] = ConversorStrings::formataTelefone($produto['telefone']);

            $produto['nome_produto'] = trim($produto['nome_produto']);
            $produto['possui_permissao'] = (bool) $produto['possui_permissao'];
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['qtd_vendas'] = (int) $produto['qtd_vendas'];
            unset($produto['id_responsavel']);

            return $produto;
        }, $produtos);

        $sql = $conexao->prepare(
            "SELECT COUNT(DISTINCT estoque_grade.id_produto) qtd_produtos
            FROM estoque_grade
            INNER JOIN logistica_item ON logistica_item.id_produto = estoque_grade.id_produto
                AND logistica_item.id_responsavel_estoque = estoque_grade.id_responsavel
                AND logistica_item.nome_tamanho = estoque_grade.nome_tamanho
                AND logistica_item.situacao <= :situacao
            WHERE estoque_grade.id_responsavel > 1
                $where;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        if ($dataIncial !== '') {
            $sql->bindValue(':data_inicial', $dataIncial, PDO::PARAM_STR);
        }
        $sql->execute();
        $total = (int) $sql->fetchColumn();

        $resultado = [
            'produtos' => $produtos,
            'total' => $total,
            'mais_pags' => ceil($total / $itensPorPag) - $pagina > 0,
        ];

        return $resultado ?: [];
    }
    public static function listaDeProdutosSemEntrega(PDO $conexao): array
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $sql = $conexao->prepare(
            "SELECT
                logistica_item.id_cliente,
                colaboradores.razao_social,
                colaboradores.tipo_embalagem,
                (
                    SELECT tipo_frete.nome
                    FROM tipo_frete
                    WHERE tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                    LIMIT 1
                ) AS `tipo_frete`,
                SUM(logistica_item.situacao < :situacao) AS `qtd_pendente`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            WHERE logistica_item.id_colaborador_tipo_frete IN (32254, 32257)
                AND logistica_item.id_entrega IS NULL
            GROUP BY logistica_item.id_cliente
            HAVING NOT qtd_pendente
            ORDER BY colaboradores.razao_social ASC;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function (array $produto): array {
            $produto['id_cliente'] = (int) $produto['id_cliente'];
            $produto['tipo_embalagem'] = Colaborador::converteTipoEmbalagem($produto['tipo_embalagem']);
            unset($produto['qtd_pendente']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function dadosMensagemPagamentoAprovado(int $idTransacao): array
    {
        $retorno = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_transacao,
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.nome_tamanho,
                DATE_FORMAT(transacao_financeiras_produtos_itens.data_criacao,'%d/%m/%Y') AS `data_pagamento`,
                colaboradores.telefone,
                (
                    SELECT colaboradores.telefone
                    FROM colaboradores
                    WHERE colaboradores.id = transacao_financeiras_metadados.valor
                ) AS `telefone_entregador`,
                transacao_financeiras_produtos_itens.uuid_produto,
                produtos_transacao_financeiras_metadados.valor AS `json_produtos_metadados`,
                IF(transacao_financeiras_metadados.valor = '32257',
                    'Transportadora',
                    (
                        SELECT tipo_frete.tipo_ponto
                        FROM tipo_frete
                        WHERE tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
                    )
                ) AS `metodo_de_envio`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                (
                    SELECT produtos.nome_comercial
                    FROM produtos
                    WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
                ) AS `nome_comercial`,
                IF(
                    (
                        SELECT 1
                        FROM tipo_frete
                        JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                        WHERE tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
                            AND tipo_frete.tipo_ponto = 'PP'
                            AND tipo_frete.id_colaborador <> 32257
                    ),
                    (
                        SELECT
                            JSON_OBJECT(
                                'endereco', colaboradores_enderecos.logradouro,
                                'numero', colaboradores_enderecos.numero,
                                'bairro', colaboradores_enderecos.bairro,
                                'cidade', colaboradores_enderecos.cidade,
                                'uf', colaboradores_enderecos.uf
                            )
                        FROM colaboradores_enderecos
                        WHERE colaboradores_enderecos.id_colaborador = transacao_financeiras_metadados.valor
                            AND colaboradores_enderecos.eh_endereco_padrao = 1
                        LIMIT 1
                    ), (
                        SELECT
                            transacao_financeiras_metadados.valor
                        FROM transacao_financeiras_metadados
                        WHERE
                            transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                            AND transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                    )
                ) json_endereco
            FROM transacao_financeiras_produtos_itens
            JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
            JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
            JOIN transacao_financeiras_metadados AS `produtos_transacao_financeiras_metadados` ON produtos_transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                AND produtos_transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
            WHERE transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
            AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
            AND transacao_financeiras_produtos_itens.id_transacao = :id_transacao
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto;",
            ['id_transacao' => $idTransacao]
        );

        $respostaTratada = array_map(function (array $item): array {
            $item['produtos_metadados'] = array_filter(
                $item['produtos_metadados'],
                fn (array $produto): bool => $produto['uuid_produto'] === $item['uuid_produto']
            );
            if ($item['produtos_metadados'] = reset($item['produtos_metadados'])) {
                $item['previsao_entrega'] = $item['produtos_metadados']['previsao'] ?? null;
            } else {
                $item['previsao_entrega'] = null;
            }
            unset($item['produtos_metadados']);
            $item['telefone_entregador'] = Str::formatarTelefone($item['telefone_entregador']);
            return $item;
        }, $retorno);

        return $respostaTratada;
    }

    public static function buscaProdutosAtualizarOpensearch(string $timestamp, int $size, int $offset): array
    {
        $binds = [
            'size' => $size,
            'offset' => $offset,
        ];

        $where = '';
        if ($timestamp) {
            $where = "AND (
                produtos.data_qualquer_alteracao > DATE_FORMAT(:timestamp, '%Y-%m-%d %H:%i:%s')
                    AND produtos.data_qualquer_alteracao < NOW()
                )";
            $binds['timestamp'] = $timestamp;
        }

        $retorno = DB::select(
            "SELECT `_produtos`.`id_produto`,
                `_produtos`.`descricao`,
                `_produtos`.`nome_produto`,
                `_produtos`.`valor_venda_ml`,
                `_produtos`.`valor_venda_ms`,
                `_produtos`.`sexo_produto`,
                COALESCE(`_produtos`.`cor_produto`, '') `cor_produto`,
                `_produtos`.`id_fornecedor`,
                COALESCE(linha.nome, '') `linha_produto`,
                COALESCE(`_produtos`.`grade_produto`, '') `grade_produto`,
                COALESCE(`_produtos`.`grade_fullfillment`, '') `grade_fullfillment`,
                `_produtos`.`tem_estoque`,
                `_produtos`.`tem_fullfillment`,
                COALESCE(GROUP_CONCAT(DISTINCT categorias.nome), '') `categoria_produto`,
                colaboradores.razao_social `nome_fornecedor`,
                colaboradores.usuario_meulook `usuario_fornecedor`,
                reputacao_fornecedores.reputacao `reputacao_fornecedor`,
                produtos_pontos.total_normalizado `pontuacao_produto`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 5
                        AND avaliacao_produtos.origem = 'ML'
                ) `5_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 4
                        AND avaliacao_produtos.origem = 'ML'
                ) `4_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 3
                        AND avaliacao_produtos.origem = 'ML'
                ) `3_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 2
                        AND avaliacao_produtos.origem = 'ML'
                ) `2_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 1
                        AND avaliacao_produtos.origem = 'ML'
                ) `1_estrelas`
            FROM (
                SELECT produtos.id `id_produto`,
                    produtos.nome_comercial `nome_produto`,
                    produtos.descricao,
                    produtos.valor_venda_ml,
                    produtos.valor_venda_ms,
                    produtos.sexo `sexo_produto`,
                    produtos.cores `cor_produto`,
                    produtos.id_fornecedor,
                    produtos.id_linha,
                    REGEXP_REPLACE(GROUP_CONCAT(
                        DISTINCT IF(estoque_grade.estoque > 0, estoque_grade.nome_tamanho, NULL)
                        ORDER BY estoque_grade.sequencia
                        SEPARATOR ' '
                    ), ' +', ' ') `grade_produto`,
                    REGEXP_REPLACE(GROUP_CONCAT(
                        DISTINCT IF(estoque_grade.estoque > 0 AND estoque_grade.id_responsavel = 1, estoque_grade.nome_tamanho, NULL)
                        ORDER BY estoque_grade.sequencia
                        SEPARATOR ' '
                    ), ' +', ' ') `grade_fullfillment`,
                    SUM(estoque_grade.estoque) > 0 `tem_estoque`,
                    SUM(estoque_grade.id_responsavel = 1) > 0 `tem_fullfillment`
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                WHERE produtos.bloqueado = 0
                    AND TRUE IN (
                        produtos.fora_de_linha = 0,
                        produtos.fora_de_linha = 1 AND estoque_grade.estoque > 0
                    )
                    $where
                GROUP BY produtos.id
                LIMIT :size OFFSET :offset
            ) _produtos
            INNER JOIN colaboradores ON colaboradores.id = `_produtos`.`id_fornecedor`
            LEFT JOIN linha ON linha.id = `_produtos`.`id_linha`
            LEFT JOIN produtos_categorias ON produtos_categorias.id_produto = `_produtos`.`id_produto`
            LEFT JOIN categorias ON categorias.id = produtos_categorias.id_categoria
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = `_produtos`.`id_fornecedor`
            LEFT JOIN produtos_pontos ON produtos_pontos.id_produto = `_produtos`.`id_produto`
            GROUP BY `_produtos`.`id_produto`",
            $binds
        );

        if (empty($retorno)) {
            return [];
        }

        $categorias = DB::selectOneColumn(
            "SELECT LOWER(
                REGEXP_REPLACE(GROUP_CONCAT(DISTINCT categorias.nome), '[, ]+', '|')
            )
            FROM categorias"
        );
        $categorias = ConversorStrings::tratarTermoOpensearch($categorias);
        $categorias = explode(' ', $categorias);
        foreach ($categorias as $index => $categoria) {
            if (str_ends_with($categoria, 's')) {
                $categorias[] = '\b' . mb_substr($categoria, 0, -1) . '\b';
            }
            $categorias[$index] = "\b$categoria\b";
        }
        $categorias = array_unique($categorias);
        $categorias = implode('|', $categorias);

        $retorno = array_map(function ($item) use ($categorias) {
            $sexoItem = '';
            switch (mb_strtolower($item['sexo_produto'])) {
                case 'fe':
                    $sexoItem = 'feminino';
                    break;
                case 'ma':
                    $sexoItem = 'masculino';
                    break;
                default:
                    $sexoItem = 'feminino masculino';
                    break;
            }

            $fornecedor = ConversorStrings::tratarTermoOpensearch(
                "{$item['nome_fornecedor']} {$item['usuario_fornecedor']}"
            );
            $fornecedor = preg_replace("/$categorias/", '', $fornecedor);

            $item['tem_estoque'] = (int) $item['tem_estoque'];
            $item['tem_fullfillment'] = (int) $item['tem_fullfillment'];

            $item['concatenado'] = implode(' ', [
                $item['id_produto'],
                $item['descricao'],
                $item['nome_produto'],
                $item['grade_produto'],
                $item['linha_produto'],
                $sexoItem,
                $item['cor_produto'],
                $item['categoria_produto'],
                $fornecedor,
            ]);
            $item['concatenado'] = explode(' ', $item['concatenado']);
            $item['concatenado'] = array_unique($item['concatenado']);
            $item['concatenado'] = implode(' ', $item['concatenado']);
            $item['concatenado'] = ConversorStrings::tratarTermoOpensearch($item['concatenado']);
            return $item;
        }, $retorno);

        return $retorno;
    }

    public static function buscaPrecoEResponsavelProduto(PDO $conexao, int $idProduto, string $tamanho): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                produtos.valor_venda_ml preco,
                estoque_grade.id_responsavel
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            WHERE produtos.id = :id_produto
                AND estoque_grade.nome_tamanho = :tamanho
            GROUP BY produtos.id
            ORDER BY estoque_grade.id_responsavel ASC"
        );
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':tamanho', $tamanho, PDO::PARAM_STR);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados;
    }
    public static function buscaInformacoesProduto(PDO $conexao, int $idProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id AS `id_produto`,
                produtos.descricao,
                produtos.id_fornecedor,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
                _estoque_grade.possui_estoque_fulfillment,
                _estoque_grade.possui_estoque_externo
            FROM produtos
            INNER JOIN (
                SELECT
                    estoque_grade.id_produto,
                    SUM(DISTINCT estoque_grade.id_responsavel = 1) AS `possui_estoque_fulfillment`,
                    SUM(DISTINCT estoque_grade.id_responsavel > 1) AS `possui_estoque_externo`
                FROM estoque_grade
                WHERE estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
                GROUP BY estoque_grade.id_produto
            ) AS `_estoque_grade` ON _estoque_grade.id_produto = produtos.id
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $produto = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($produto)) {
            throw new NotFoundHttpException('Não foi possível encontrar as informações do produto');
        }

        $produto['id_produto'] = (int) $produto['id_produto'];
        $produto['id_fornecedor'] = (int) $produto['id_fornecedor'];
        $produto['possui_estoque_fulfillment'] = (bool) $produto['possui_estoque_fulfillment'];
        $produto['possui_estoque_externo'] = (bool) $produto['possui_estoque_externo'];

        return $produto;
    }
    public static function buscaProdutosFornecedorParaNegociar(
        PDO $conexao,
        int $idFornecedor,
        string $pesquisa,
        int $pagina
    ): array {
        $where = '';
        $itensPorPagina = 50;
        $offset = $itensPorPagina * ($pagina - 1);
        if (!empty($pesquisa)) {
            $where = " AND LOWER(CONCAT_WS(
                ' - ',
                produtos.id,
                produtos.nome_comercial,
                GROUP_CONCAT(estoque_grade.nome_tamanho)
            )) REGEXP LOWER(:pesquisa) ";
        }

        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.id_produto,
                produtos.nome_comercial,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`,
                produtos.valor_custo_produto AS `preco`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`,
                GROUP_CONCAT(estoque_grade.nome_tamanho ORDER BY estoque_grade.sequencia ASC) AS `grades`
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.estoque > 0
                AND estoque_grade.id_responsavel > 1
                AND estoque_grade.id_produto = produtos.id
            WHERE produtos.id_fornecedor = :id_fornecedor
	            AND NOT produtos.bloqueado
                AND NOT produtos.fora_de_linha
            GROUP BY produtos.id
            HAVING COALESCE(foto, '') <> '' $where
            ORDER BY produtos.id DESC
            LIMIT :itens_por_pag OFFSET :offset;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':itens_por_pag', $itensPorPagina, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        if (!empty($pesquisa)) {
            $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function (array $produto): array {
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['preco'] = (float) $produto['preco'];
            $produto['grades'] = explode(',', $produto['grades']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function informacoesDoProdutoNegociado(PDO $conexao, string $uuidProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                logistica_item.id_cliente,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.id_transacao,
                logistica_item.id_responsavel_estoque,
                logistica_item.uuid_produto,
                logistica_item.preco,
                produtos.nome_comercial,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE logistica_item.uuid_produto = :uuid_produto;"
        );
        $sql->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $sql->execute();
        $produto = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($produto)) {
            throw new NotFoundHttpException('Não foi possível encontrar as informações do produto');
        }
        $produto['id_cliente'] = (int) $produto['id_cliente'];
        $produto['id_produto'] = (int) $produto['id_produto'];
        $produto['id_transacao'] = (int) $produto['id_transacao'];
        $produto['id_responsavel_estoque'] = (int) $produto['id_responsavel_estoque'];
        $produto['preco'] = (float) $produto['preco'];

        return $produto;
    }
    public static function desativaPromocaoMantemValores(PDO $conexao, int $idProduto, int $idUsuario): void
    {
        $sql = $conexao->prepare(
            "SELECT produtos.valor_custo_produto
            FROM produtos
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $valorCustoProduto = (float) $sql->fetchColumn();

        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.preco_promocao = 0,
                produtos.usuario = :id_usuario
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível desativar a promoção');
        }

        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.valor_custo_produto = :valor_custo_produto
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':valor_custo_produto', $valorCustoProduto, PDO::PARAM_STR);
        $sql->execute();
        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível atualizar o custo do produto');
        }
    }

    public static function buscaLocalizacaoComEstoqueLiberado(): Generator
    {
        $produtos = DB::cursor(
            "SELECT
                estoque_grade.id_produto,
                produtos.localizacao,
                SUM(estoque_grade.estoque) AS soma_estoque,
                SUM(estoque_grade.vendido) AS soma_vendido,
                produtos_aguarda_entrada_estoque.id IS NOT NULL AS tem_aguardando_entrada
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id = estoque_grade.id_produto
            LEFT JOIN produtos_aguarda_entrada_estoque
            ON produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.id_produto = produtos.id
                AND produtos_aguarda_entrada_estoque.localizacao IS NOT NULL
            WHERE produtos.localizacao IS NOT NULL
            AND estoque_grade.id_responsavel = 1
            GROUP BY produtos.id
            HAVING soma_estoque = 0 AND soma_vendido = 0;"
        );

        return $produtos;
    }

    public static function verificaLocalizacao(int $idProduto): ?int
    {
        $sql = "SELECT
                    produtos.localizacao
                FROM produtos
                WHERE produtos.id = :id_produto";

        $localizacaoAtual = DB::selectOneColumn($sql, ['id_produto' => $idProduto]);
        return $localizacaoAtual;
    }
}
