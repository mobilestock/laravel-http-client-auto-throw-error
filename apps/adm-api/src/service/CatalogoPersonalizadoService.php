<?php

namespace MobileStock\service;

use Exception;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public static function buscarListaCatalogosColaborador(PDO $conexao, int $idColaborador): array
    {
        $stmt = $conexao->prepare(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id_colaborador = :idCliente
            ORDER BY catalogo_personalizado.nome"
        );
        $stmt->bindValue(':idCliente', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $catalogos = array_map(function ($catalogo) {
            return [
                'id' => (int) $catalogo['id'],
                'nome' => $catalogo['nome'],
                'quantidade_produtos' => sizeof(json_decode($catalogo['produtos'], true)),
            ];
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarCatalogoPorId(PDO $conexao, int $idCatalogo): array
    {
        $stmt = $conexao->prepare(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos,
                catalogo_personalizado.ativo
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :idCatalogo"
        );
        $stmt->bindValue(':idCatalogo', $idCatalogo, PDO::PARAM_INT);
        $stmt->execute();
        $catalogo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($catalogo)) {
            throw new NotFoundHttpException('Catalogo não encontrado');
        }
        $catalogo['id'] = (int) $catalogo['id'];
        $catalogo['produtos'] = json_decode($catalogo['produtos'], true);
        $catalogo['ativo'] = (bool) $catalogo['ativo'];
        return $catalogo;
    }

    public static function buscarCatalogoColaborador(PDO $conexao, int $idCatalogo, int $idColaborador): array
    {
        $stmt = $conexao->prepare(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :idCatalogo
                AND catalogo_personalizado.id_colaborador = :idColaborador"
        );
        $stmt->bindValue(':idCatalogo', $idCatalogo, PDO::PARAM_INT);
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $catalogo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($catalogo)) {
            throw new NotFoundHttpException('Catalogo não encontrado');
        }
        $catalogo['id'] = (int) $catalogo['id'];
        $catalogo['produtos'] = json_decode($catalogo['produtos'], true);
        return $catalogo;
    }

    public static function buscarListaCatalogosPublicos(PDO $conexao, ?string $origem): array
    {
        $whereOrigem = empty($origem) ? '' : 'AND catalogo_personalizado.plataformas_filtros REGEXP :origem';
        $stmt = $conexao->prepare(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.tipo = :tipoCatalogo
                AND catalogo_personalizado.ativo = 1
                $whereOrigem
            ORDER BY catalogo_personalizado.nome"
        );
        $stmt->bindValue(':tipoCatalogo', self::TIPO_CATALOGO_PUBLICO, PDO::PARAM_STR);
        if (!empty($origem)) {
            $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
        }
        $stmt->execute();
        $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['id'] = (int) $catalogo['id'];
            $catalogo['produtos'] = json_decode($catalogo['produtos'], true);
            $catalogo['quantidade_produtos'] = sizeof($catalogo['produtos']);
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarTodosCatalogos(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos,
                catalogo_personalizado.ativo,
                colaboradores.id `id_colaborador`,
                colaboradores.razao_social,
                catalogo_personalizado.tipo
            FROM catalogo_personalizado
            INNER JOIN colaboradores ON colaboradores.id = catalogo_personalizado.id_colaborador
            ORDER BY catalogo_personalizado.nome"
        );
        $stmt->execute();
        $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['id'] = (int) $catalogo['id'];
            $catalogo['produtos'] = json_decode($catalogo['produtos'], true);
            $catalogo['quantidade_produtos'] = sizeof($catalogo['produtos']);
            $catalogo['id_colaborador'] = (int) $catalogo['id_colaborador'];
            $catalogo['ativo'] = (bool) $catalogo['ativo'];
            if ($catalogo['quantidade_produtos'] > 0) {
                $catalogo['link_ms'] = $_ENV['URL_AREA_CLIENTE'] . "?filtro={$catalogo['id']}";
                $catalogo['link_ml'] = $_ENV['URL_MEULOOK'] . "?filtro={$catalogo['id']}";
            }
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarProdutosCatalogoPersonalizadoPorIds(
        PDO $conexao,
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
        if ($origem === 'ML') {
            $where = '';
        }

        $select = '';
        $join = '';
        if ($operacao === 'CATALOGO') {
            $chaveValor = 'produtos.valor_venda_ms';
            $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
            if ($origem === 'ML') {
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

        $stmt = $conexao->prepare(
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
                ) `grade_estoque`
                $select
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.estoque > 0
            $join
            WHERE produtos.id IN ($itens) $where
            GROUP BY produtos.id
            ORDER BY $orderBy"
        );
        $stmt->execute($bind);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $item) use ($operacao): array {
            $item['id_produto'] = (int) $item['id_produto'];
            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo(json_decode($item['grade_estoque'], true));

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

            return [
                'id_produto' => (int) $item['id_produto'],
                'nome' => $item['nome_produto'],
                'preco' => (float) $item['valor_venda'],
                'preco_original' => (float) $item['valor_venda_historico'],
                'quantidade_vendida' => (int) $item['quantidade_vendida'],
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

    public static function adicionarProdutoCatalogo(
        PDO $conexao,
        int $idColaborador,
        int $idCatalogo,
        int $idProduto
    ): void {
        $catalogo = self::buscarCatalogoColaborador($conexao, $idCatalogo, $idColaborador);

        if (empty($catalogo)) {
            throw new NotFoundHttpException('Catalogo não encontrado');
        }

        if (in_array($idProduto, $catalogo['produtos'])) {
            throw new NotFoundHttpException('Produto já existe nesse catálogo');
        }

        $stmt = $conexao->prepare(
            "UPDATE catalogo_personalizado
            SET catalogo_personalizado.produtos = JSON_ARRAY_APPEND(catalogo_personalizado.produtos, '$', :idProduto)
            WHERE catalogo_personalizado.id = :idCatalogo
                AND catalogo_personalizado.id_colaborador = :idColaborador"
        );
        $stmt->bindValue(':idProduto', $idProduto, PDO::PARAM_STR);
        $stmt->bindValue(':idCatalogo', $idCatalogo, PDO::PARAM_INT);
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception('Nenhum dado foi alterado');
        }
    }
}
