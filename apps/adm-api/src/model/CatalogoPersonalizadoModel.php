<?php

namespace MobileStock\model;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 * @property int $id
 * @property int $id_colaborador
 * @property string $nome
 * @property string $tipo
 * @property bool $esta_ativo
 * @property string $produtos
 * @property string $plataformas_filtros
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class CatalogoPersonalizadoModel extends Model
{
    protected $table = 'catalogo_personalizado';

    protected $fillable = ['id_colaborador', 'nome', 'tipo', 'esta_ativo', 'produtos', 'plataformas_filtros'];

    public static function consultaCatalogoPersonalizadoPorId(int $idCatalogo): self
    {
        $catalogoPersonalizado = self::fromQuery(
            "SELECT
                catalogo_personalizado.id,
                catalogo_personalizado.id_colaborador,
                catalogo_personalizado.nome,
                catalogo_personalizado.tipo,
                catalogo_personalizado.esta_ativo,
                catalogo_personalizado.produtos `json_produtos`,
                catalogo_personalizado.plataformas_filtros
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :id_catalogo",
            ['id_catalogo' => $idCatalogo]
        )->first();
        if (empty($catalogoPersonalizado)) {
            throw new NotFoundHttpException('Catalogo personalizado não encontrado.');
        }

        return $catalogoPersonalizado;
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
