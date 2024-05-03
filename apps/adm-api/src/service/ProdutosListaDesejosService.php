<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\model\ProdutosListaDesejos;
use PDO;

class ProdutosListaDesejosService extends ProdutosListaDesejos
{
    public static function buscaListaDesejos(): array
    {
        $produtos = DB::select(
            "SELECT
                produtos_lista_desejos.id id_lista_desejo,
                CASE
                    WHEN SUM(estoque_grade.estoque) = 0 AND produtos.fora_de_linha THEN 'FORA_DE_LINHA'
                    WHEN SUM(estoque_grade.estoque) = 0 AND NOT produtos.fora_de_linha THEN 'ESGOTADO'
                    WHEN SUM(estoque_grade.estoque) > 0 AND produtos.fora_de_linha THEN 'ULTIMAS_UNIDADES'
                    ELSE 'NORMAL'
                END situacao_lista_desejo,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) grades,
                produtos_lista_desejos.id_produto,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) `nome`,
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) valor_venda_ml_historico,
                produtos.quantidade_vendida,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_lista_desejos.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto`
            FROM produtos_lista_desejos
            INNER JOIN produtos ON produtos.id = produtos_lista_desejos.id_produto
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos_lista_desejos.id_produto
            WHERE produtos_lista_desejos.id_colaborador = :idColaborador
            GROUP BY produtos_lista_desejos.id_produto
            ORDER BY produtos_lista_desejos.data_criacao DESC",
            [':idColaborador' => Auth::user()->id_colaborador]
        );

        $produtos = array_map(function ($item) {
            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo(json_decode($item['grades'], true));

            $categoria = (object) [ 'tipo' => 'SITUACAO_ITEM_LISTA_DESEJO', 'valor' => $item['situacao_lista_desejo'] ];

            $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda_ml']);

            return [
                'id_produto' => (int) $item['id_produto'],
                'nome' => $item['nome'],
                'preco' => (float) $item['valor_venda_ml'],
                'preco_original' => (float) $item['valor_venda_ml_historico'],
                'valor_parcela' => $valorParcela,
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                'quantidade_vendida' => (int) $item['quantidade_vendida'],
                'foto' => $item['foto'],
                'grades' => $grades,
                'categoria' => $categoria
            ];
        }, $produtos);

        return $produtos;
    }

    public function salva(PDO $conexao): int
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        if ($stmt->rowCount() === 0) throw new Exception("Não foi possível criar registro");
        return $conexao->lastInsertId();
    }

    /**
     * @return array|false
     */
    public static function buscaRegistro(PDO $conexao, int $idColaborador, int $idProduto)
    {
        $stmt = $conexao->prepare(
            "SELECT produtos_lista_desejos.id,
                produtos_lista_desejos.id_colaborador,
                produtos_lista_desejos.id_produto,
                produtos_lista_desejos.data_criacao
            FROM produtos_lista_desejos
            WHERE produtos_lista_desejos.id_colaborador = :idColaborador
                AND produtos_lista_desejos.id_produto = :idProduto"
        );
        $stmt->bindValue(':idColaborador', $idColaborador);
        $stmt->bindValue(':idProduto', $idProduto);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro;
    }

    public function deletar(PDO $conexao)
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->deleteSemGetter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        if ($stmt->rowCount() === 0) throw new Exception("Não foi possível deletar registro");
    }
}
