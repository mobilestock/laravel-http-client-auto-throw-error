<?php

namespace MobileStock\model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ReputacaoFornecedoresService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property string $nome
 * @property string $tipo
 * @property bool $esta_ativo
 * @property array $json_produtos
 * @property array $json_plataformas_filtros
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class CatalogoPersonalizado extends Model
{
    protected $table = 'catalogo_personalizado';

    protected $fillable = ['id_colaborador', 'nome', 'tipo', 'esta_ativo', 'json_produtos', 'json_plataformas_filtros'];

    const TIPO_CATALOGO_PUBLICO = 'PUBLICO';

    public static function consultaCatalogoPersonalizadoPorId(int $idCatalogo): self
    {
        $catalogoPersonalizado = self::fromQuery(
            "SELECT
                catalogo_personalizado.id,
                catalogo_personalizado.id_colaborador,
                catalogo_personalizado.nome,
                catalogo_personalizado.tipo,
                catalogo_personalizado.esta_ativo,
                catalogo_personalizado.json_produtos,
                catalogo_personalizado.json_plataformas_filtros
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :id_catalogo",
            ['id_catalogo' => $idCatalogo]
        )->first();
        if (empty($catalogoPersonalizado)) {
            throw new NotFoundHttpException('Catalogo personalizado não encontrado.');
        }

        return $catalogoPersonalizado;
    }

    public static function buscarListaCatalogosColaborador(): array
    {
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.json_produtos
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id_colaborador = :idCliente
            ORDER BY catalogo_personalizado.nome",
            [':idCliente' => Auth::user()->id_colaborador]
        );
        $catalogos = array_map(function ($catalogo) {
            $catalogo['quantidade_produtos'] = count($catalogo['produtos']);
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function buscarTodosCatalogos(): array
    {
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.json_produtos,
                catalogo_personalizado.esta_ativo,
                colaboradores.id `id_colaborador`,
                colaboradores.razao_social,
                catalogo_personalizado.tipo
            FROM catalogo_personalizado
            INNER JOIN colaboradores ON colaboradores.id = catalogo_personalizado.id_colaborador
            ORDER BY catalogo_personalizado.nome"
        );
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['quantidade_produtos'] = count($catalogo['produtos']);
            if ($catalogo['quantidade_produtos'] > 0) {
                $catalogo['link_ms'] = $_ENV['URL_AREA_CLIENTE'] . "?filtro={$catalogo['id']}";
                $catalogo['link_ml'] = $_ENV['URL_MEULOOK'] . "?filtro={$catalogo['id']}";
            }
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public static function adicionarProdutoCatalogo(int $idCatalogo, int $idProduto): void
    {
        $catalogo = self::consultaCatalogoPersonalizadoPorId($idCatalogo);

        if (in_array($idProduto, $catalogo->produtos)) {
            throw new BadRequestHttpException('Produto já existe nesse catálogo');
        }

        $produtos = $catalogo->produtos;
        $produtos[] = $idProduto;
        $catalogo->json_produtos = $produtos;
        $catalogo->save();
    }

    public static function buscarListaCatalogosPublicos(?string $origem = null): array
    {
        $whereOrigem = '';
        $binds = [':tipoCatalogo' => self::TIPO_CATALOGO_PUBLICO];
        if (!empty($origem)) {
            $whereOrigem = 'AND catalogo_personalizado.json_plataformas_filtros REGEXP :origem';
            $binds[':origem'] = $origem;
        }
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.json_produtos
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.tipo = :tipoCatalogo
                AND catalogo_personalizado.esta_ativo = 1
                $whereOrigem
            ORDER BY catalogo_personalizado.nome",
            $binds
        );
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['quantidade_produtos'] = count($catalogo['produtos']);
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
                'origem' => [Validador::ENUM(Origem::MS, Origem::ML)],
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
                LOWER(produtos.nome_comercial) `nome_produto`,
                $chaveValor `valor_venda`,
                IF (produtos.promocao > 0, $chaveValorHistorico, 0) `valor_venda_historico`,
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

        # @issue: https://github.com/mobilestock/backend/issues/397
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
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO_CARTAO,
                'quantidade_vendida' => $item['quantidade_vendida'],
                'foto' => $item['foto_produto'],
                'grades' => $grades,
                'categoria' => $categoria,
            ];
        }, $produtos);

        return $produtos;
    }

    public static function buscaTipoCatalogo(): string
    {
        $porcentagem = ColaboradoresService::calculaTendenciaCompra();

        switch (true) {
            case $porcentagem > 80:
                return CatalogoFixoService::TIPO_MODA_100;
            case $porcentagem > 60:
                return CatalogoFixoService::TIPO_MODA_80;
            case $porcentagem > 40:
                return CatalogoFixoService::TIPO_MODA_60;
            case $porcentagem > 20:
                return CatalogoFixoService::TIPO_MODA_40;
            case $porcentagem > 0:
                return CatalogoFixoService::TIPO_MODA_20;
            default:
                return CatalogoFixoService::TIPO_MODA_GERAL;
        }
    }
}
