<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado;
use MobileStock\model\Origem;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @deprecated
 * @see Usar: MobileStock\model\CatalogoPersonalizadoModel
 */
class CatalogoPersonalizadoService extends CatalogoPersonalizado
{
    const TIPO_CATALOGO_PUBLICO = 'PUBLICO';

    public function salvar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        $this->id = $conexao->lastInsertId();
    }

    public static function buscarListaCatalogosColaborador(int $idColaborador): array
    {
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id_colaborador = :idCliente
            ORDER BY catalogo_personalizado.nome",
            [':idCliente' => $idColaborador]
        );
        $catalogos = array_map(function ($catalogo) {
            return [
                'id' => $catalogo['id'],
                'nome' => $catalogo['nome'],
                'quantidade_produtos' => sizeof($catalogo['produtos']),
            ];
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarCatalogoColaborador(int $idCatalogo, int $idColaborador): array
    {
        $catalogo = DB::selectOne(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :idCatalogo
                AND catalogo_personalizado.id_colaborador = :idColaborador",
            [':idCatalogo' => $idCatalogo, ':idColaborador' => $idColaborador]
        );
        if (empty($catalogo)) {
            throw new NotFoundHttpException('Catalogo não encontrado');
        }
        return $catalogo;
    }

    public static function buscarListaCatalogosPublicos(?string $origem): array
    {
        $whereOrigem = '';
        $binds = [':tipoCatalogo' => self::TIPO_CATALOGO_PUBLICO];
        if (!empty($origem)) {
            $whereOrigem = 'AND catalogo_personalizado.plataformas_filtros REGEXP :origem';
            $binds[':origem'] = $origem;
        }
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.tipo = :tipoCatalogo
                AND catalogo_personalizado.esta_ativo = 1
                $whereOrigem
            ORDER BY catalogo_personalizado.nome",
            $binds
        );
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['quantidade_produtos'] = sizeof($catalogo['produtos']);
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarTodosCatalogos(): array
    {
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`,
                catalogo_personalizado.esta_ativo,
                colaboradores.id `id_colaborador`,
                colaboradores.razao_social,
                catalogo_personalizado.tipo
            FROM catalogo_personalizado
            INNER JOIN colaboradores ON colaboradores.id = catalogo_personalizado.id_colaborador
            ORDER BY catalogo_personalizado.nome"
        );
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['quantidade_produtos'] = sizeof($catalogo['produtos']);
            if ($catalogo['quantidade_produtos'] > 0) {
                $catalogo['link_ms'] = $_ENV['URL_AREA_CLIENTE'] . "?filtro={$catalogo['id']}";
                $catalogo['link_ml'] = $_ENV['URL_MEULOOK'] . "?filtro={$catalogo['id']}";
            }
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarProdutosCatalogoPersonalizadoPorIds(
        array $idsProdutos,
        string $operacao,
        string $origem
    ): array {
        if (empty($idsProdutos)) {
            return $idsProdutos;
        }

        Validador::validar(
            ['operacao' => $operacao, 'origem' => $origem],
            [
                'operacao' => [Validador::ENUM('CATALOGO', 'EDITAR')],
                'origem' => [Validador::ENUM('MS', 'ML')],
            ]
        );

        $where = ' AND estoque_grade.id_responsavel = 1';
        if ($origem === Origem::ML) {
            $where = '';
        }

        $select = '';
        $join = '';
        if ($operacao === 'CATALOGO') {
            $chaveValor = 'produtos.valor_venda_ms';
            $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
            if ($origem === Origem::ML) {
                $chaveValor = 'produtos.valor_venda_ml';
                $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
            }
            $select .= ",
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) `nome_produto`,
                $chaveValor `valor_venda`,
                IF (produtos.promocao > 0, $chaveValorHistorico, NULL) `valor_venda_historico`,
                reputacao_fornecedores.reputacao,
                produtos.quantidade_vendida";
            $join .=
                'LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor';
            $where .= ' AND produtos.bloqueado = 0';
        }

        [$itens, $bind] = ConversorArray::criaBindValues($idsProdutos);
        $orderBy = implode(',', array_map(fn($id) => "produtos.id=$id DESC", array_keys($bind)));

        $produtos = DB::select(
            "SELECT produtos.id `id_produto`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto_produto`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) `json_grade_estoque`
                $select
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.estoque > 0
            $join
            WHERE produtos.id IN ($itens) $where
            GROUP BY produtos.id
            ORDER BY $orderBy",
            $bind
        );

        $produtos = array_map(function (array $item) use ($operacao): array {
            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grade_estoque']);

            if ($operacao === 'EDITAR') {
                return [
                    'id_produto' => $item['id_produto'],
                    'foto' => $item['foto_produto'],
                    'grades' => $grades,
                ];
            }

            $categoria = (object) ['tipo' => '', 'valor' => ''];
            if ($item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE) {
                $categoria->tipo = $item['reputacao'];
            }

            $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda']);

            return [
                'id_produto' => $item['id_produto'],
                'nome' => $item['nome_produto'],
                'preco' => $item['valor_venda'],
                'preco_original' => $item['valor_venda_historico'],
                'valor_parcela' => $valorParcela,
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                'quantidade_vendida' => $item['quantidade_vendida'],
                'foto' => $item['foto_produto'],
                'grades' => $grades,
                'categoria' => $categoria,
            ];
        }, $produtos);

        return $produtos;
    }

    public function editar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->update();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Nenhum dado foi alterado');
        }
    }

    public function deletar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->deleteSemGetter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
    }

    public static function adicionarProdutoCatalogo(int $idColaborador, int $idCatalogo, int $idProduto): void
    {
        $catalogo = self::buscarCatalogoColaborador($idCatalogo, $idColaborador);

        if (empty($catalogo)) {
            throw new NotFoundHttpException('Catalogo não encontrado');
        }

        if (in_array($idProduto, $catalogo['produtos'])) {
            throw new BadRequestHttpException('Produto já existe nesse catálogo');
        }

        $linhasAfetadas = DB::update(
            "UPDATE catalogo_personalizado
            SET catalogo_personalizado.produtos = JSON_ARRAY_APPEND(catalogo_personalizado.produtos, '$', :idProduto)
            WHERE catalogo_personalizado.id = :idCatalogo
                AND catalogo_personalizado.id_colaborador = :idColaborador",
            [':idProduto' => $idProduto, ':idCatalogo' => $idCatalogo, ':idColaborador' => $idColaborador]
        );

        if ($linhasAfetadas === 0) {
            throw new Exception('Nenhum dado foi alterado');
        }
    }
}
