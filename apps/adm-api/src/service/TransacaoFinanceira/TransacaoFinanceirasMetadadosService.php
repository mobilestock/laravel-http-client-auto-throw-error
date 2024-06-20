<?php

namespace MobileStock\service\TransacaoFinanceira;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceirasMetadados;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransacaoFinanceirasMetadadosService extends TransacaoFinanceirasMetadados
{
    public static function existeMetadado(PDO $conexao, string $chave, string $valor): bool
    {
        $stmt = $conexao->prepare(
            "SELECT 1
             FROM transacao_financeiras_metadados
             WHERE transacao_financeiras_metadados.chave = :chave
               AND transacao_financeiras_metadados.valor = :valor"
        );
        $stmt->execute([
            ':chave' => $chave,
            ':valor' => $valor,
        ]);

        $existe = (bool) $stmt->fetchColumn();

        return $existe;
    }

    public function salvar(PDO $conexao): int
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->insertSemFilter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Não foi possível criar metadado de transação');
        }
        return $conexao->lastInsertId();
    }

    public static function buscaDadosEntregadorTransacao(int $idTransacao): array
    {
        $dadosEntregador = DB::selectOne(
            "SELECT
                (
                    SELECT JSON_OBJECT(
                        'id_colaborador', tipo_frete.id_colaborador,
                        'tipo_ponto', tipo_frete.tipo_ponto
                    )
                    FROM tipo_frete
                        WHERE tipo_frete.id = colaboradores.id_tipo_entrega_padrao
                ) AS `json_tipo_entrega_padrao`,
                SUM(
                    IF(
                        transacao_financeiras_produtos_itens.tipo_item = 'CM_PONTO_COLETA',
                        transacao_financeiras_produtos_itens.preco - transacao_financeiras_produtos_itens.comissao_fornecedor,
                        transacao_financeiras_produtos_itens.comissao_fornecedor
                    )
                ) AS `comissao_fornecedor`
            FROM transacao_financeiras_produtos_itens
            INNER JOIN colaboradores ON colaboradores.id = :id_cliente
            WHERE transacao_financeiras_produtos_itens.id_transacao = :idTransacao
                AND transacao_financeiras_produtos_itens.tipo_item IN ('CM_ENTREGA', 'CE', 'FR', 'CM_PONTO_COLETA');",
            ['id_cliente' => Auth::user()->id_colaborador, 'idTransacao' => $idTransacao]
        );
        if (empty($dadosEntregador['tipo_entrega_padrao']) || $dadosEntregador['comissao_fornecedor'] === null) {
            throw new Exception("Não foi possível encontrar dados do entregador da transação id: {$idTransacao}");
        }

        return $dadosEntregador;
    }

    public static function buscaProdutosTransacao(int $idTransacao): array
    {
        $produtos = DB::select(
            "SELECT
                produtos.id,
                LOWER(
                    IF(
                    LENGTH(produtos.nome_comercial) > 0,
                    produtos.nome_comercial,
                    produtos.descricao
                    )
                ) AS `nome_comercial`,
                transacao_financeiras_produtos_itens.nome_tamanho,
                (
                    SELECT SUM(calculo_transacao_financeiras_produtos_itens.preco)
                    FROM transacao_financeiras_produtos_itens AS `calculo_transacao_financeiras_produtos_itens`
                    WHERE calculo_transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                        AND calculo_transacao_financeiras_produtos_itens.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                ) AS `preco`,
                transacao_financeiras_produtos_itens.uuid_produto,
                transacao_financeiras_produtos_itens.id_fornecedor,
                transacao_financeiras_produtos_itens.id_responsavel_estoque,
                transacao_financeiras_produtos_itens.comissao_fornecedor
            FROM transacao_financeiras_produtos_itens
            INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
            WHERE transacao_financeiras_produtos_itens.id_transacao = :idTransacao
                AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto;",
            ['idTransacao' => $idTransacao]
        );

        return $produtos;
    }

    public static function buscaChavesTransacao(int $idTransacao): array
    {
        $consulta = DB::select(
            "SELECT
                transacao_financeiras_metadados.id,
                transacao_financeiras_metadados.chave,
                transacao_financeiras_metadados.valor AS `json_valor`
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.id_transacao = :idTransacao;",
            ['idTransacao' => $idTransacao]
        );
        $consulta = array_reduce(
            $consulta,
            function ($acumulador, $item) {
                $acumulador[$item['chave']] = $item;
                return $acumulador;
            },
            []
        );
        return $consulta;
    }

    public static function buscaIDMetadadoTransacao(int $idTransacao, string $chave): int
    {
        $idMetadado = DB::selectOneColumn(
            "SELECT
                transacao_financeiras_metadados.id
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.id_transacao = :idTransacao
                AND transacao_financeiras_metadados.chave = :chave",
            ['idTransacao' => $idTransacao, 'chave' => $chave]
        );
        if (empty($idMetadado)) {
            throw new NotFoundHttpException('Não foi possível encontrar metadado de transação');
        }
        return $idMetadado;
    }

    public function alterar(PDO $conexao): int
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->updateSomenteDadosPreenchidos();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        return $stmt->rowCount();
        // if ($stmt->rowCount() === 0) throw new Exception("Não foi possível atualizar metadado de transação");
    }

    public static function temPedidoMobileStock(PDO $conexao, int $idTransacao): bool
    {
        $stmt = $conexao->prepare(
            "SELECT 1
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.id_transacao = :id_transacao
            AND transacao_financeiras_metadados.chave = 'ID_PEDIDO'"
        );
        $stmt->bindValue(':id_transacao', $idTransacao, PDO::PARAM_INT);
        $stmt->execute();
        $consulta = !!$stmt->fetchColumn();
        return $consulta;
    }

    public static function buscaUuidsMetadadoProdutosTroca(int $idTransacao): ?array
    {
        $uuids = DB::selectOneColumn(
            "SELECT transacao_financeiras_metadados.valor AS json_valor
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.id_transacao = :id_transacao
            AND transacao_financeiras_metadados.chave = 'PRODUTOS_TROCA'",
            ['id_transacao' => $idTransacao]
        );

        return $uuids;
    }

    public static function buscaColaboradoresColetasAnteriores(): array
    {
        $sql = "SELECT
                    COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}/images/avatar-padrao-mobile.jpg') AS `foto_perfil`,
                    colaboradores_enderecos.id AS `id_endereco`,
                    colaboradores_enderecos.id_colaborador,
                    colaboradores_enderecos.nome_destinatario AS `razao_social`,
                    colaboradores_enderecos.telefone_destinatario AS `telefone`,
                    colaboradores_enderecos.logradouro,
                    colaboradores_enderecos.numero,
                    colaboradores_enderecos.bairro,
                    colaboradores_enderecos.cidade,
                    colaboradores_enderecos.uf
                FROM transacao_financeiras
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_COLETA_JSON'
                    AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                INNER JOIN colaboradores ON colaboradores.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_colaborador')
                INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao
                    AND colaboradores_enderecos.esta_verificado
                WHERE transacao_financeiras.pagador = :id_cliente
                GROUP BY JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_colaborador')
                ORDER BY transacao_financeiras_metadados.id DESC
                LIMIT 10";

        $colaboradoresAnteriores = DB::select($sql, ['id_cliente' => Auth::user()->id_colaborador]);

        return $colaboradoresAnteriores;
    }

    public static function buscaRelatorioColetas(array $entregadoresIds): array
    {
        $where = '';
        $binds = [];
        if (!empty($entregadoresIds)) {
            [$referenciasSql, $binds] = ConversorArray::criaBindValues($entregadoresIds, 'id_entregador');
            $where = " AND transportadores_raios.id_colaborador IN ($referenciasSql) ";
        }

        $sql = "SELECT
                    tipo_frete.nome AS `nome_entregador`,
                    transportadores_raios.id_colaborador AS `id_entregador`,
                    transportadores_raios.id AS `id_raio`,
                    transportadores_raios.apelido AS `apelido_raio`,
                    CONCAT(
                        '[',
                            GROUP_CONCAT(DISTINCT
                                JSON_OBJECT(
                                    'destinatario', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.nome_destinatario'),
                                    'telefone', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.telefone_destinatario'),
                                    'cidade', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.cidade'),
                                    'uf', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.uf'),
                                    'logradouro', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.logradouro'),
                                    'numero', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.numero'),
                                    'complemento', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.complemento')
                                )
                            ),
                        ']'
                    ) AS `json_enderecos_coleta`
                FROM transacao_financeiras_metadados
                INNER JOIN logistica_item ON logistica_item.id_transacao = transacao_financeiras_metadados.id_transacao
                    AND logistica_item.situacao = 'PE'
                INNER JOIN transportadores_raios ON transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                WHERE transacao_financeiras_metadados.chave = 'ENDERECO_COLETA_JSON'
                    $where
                    GROUP BY transportadores_raios.id
                ORDER BY logistica_item.id_transacao ASC";

        $coletas = DB::select($sql, $binds);
        $coletas = array_map(function ($coleta) {
            $coleta['entregador'] = "{$coleta['id_entregador']}-{$coleta['nome_entregador']}";
            $coleta['raio'] = empty($coleta['apelido_raio'])
                ? $coleta['id_raio']
                : "{$coleta['id_raio']}-{$coleta['apelido_raio']}";

            unset($coleta['id_entregador'], $coleta['nome_entregador'], $coleta['id_raio'], $coleta['apelido_raio']);

            return $coleta;
        }, $coletas);

        return $coletas;
    }
}
