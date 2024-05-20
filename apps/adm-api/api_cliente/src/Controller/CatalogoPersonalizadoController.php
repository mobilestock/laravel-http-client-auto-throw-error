<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado;
use MobileStock\model\Origem;
use MobileStock\service\Estoque\EstoqueGradeService;

class CatalogoPersonalizadoController
{
    public function criarCatalogo(Origem $origem)
    {
        $json = Request::all();
        Validador::validar($json, [
            'nome' => [Validador::OBRIGATORIO],
            'ids_produtos' => [Validador::NAO_NULO],
            'tipo' => [Validador::SE(Validador::NAO_NULO, Validador::ENUM('PUBLICO', 'PRIVADO'))],
            'plataformas' => [Validador::SE($origem->ehAdm(), [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)])],
        ]);

        $catalogoPersonalizado = new CatalogoPersonalizado();
        $catalogoPersonalizado->id_colaborador = Auth::user()->id_colaborador;
        $catalogoPersonalizado->nome = $json['nome'];
        if (!empty($json['tipo'])) {
            $catalogoPersonalizado->tipo = $json['tipo'];
        }
        if (!empty($json['ids_produtos'])) {
            $produtos = json_encode($json['ids_produtos']);
            $catalogoPersonalizado->produtos = $produtos;
        }
        if (!empty($json['plataformas'])) {
            $plataformas = json_encode($json['plataformas']);
            $catalogoPersonalizado->plataformas_filtros = $plataformas;
        }
        $catalogoPersonalizado->save();
    }

    public function buscarListaCatalogos()
    {
        $catalogos = CatalogoPersonalizado::buscarListaCatalogosColaborador();
        return $catalogos;
    }

    public function buscarListaCatalogosPublicos(Origem $origem)
    {
        $siglaOrigem = $origem;
        if ($origem->ehMed()) {
            Validador::validar(Request::all(), [
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM(Origem::MS, Origem::ML)],
            ]);
            $siglaOrigem = Request::input('origem');
        }
        $catalogos = CatalogoPersonalizado::buscarListaCatalogosPublicos($origem);

        $idsProdutosTotais = array_reduce(
            $catalogos,
            function (array $idsProdutos, array $catalogo): array {
                return array_merge($idsProdutos, $catalogo['produtos']);
            },
            []
        );
        $idsProdutosComEstoque = EstoqueGradeService::retornarItensComEstoque($idsProdutosTotais, $siglaOrigem);

        $catalogos = array_filter($catalogos, function ($catalogo) use ($idsProdutosComEstoque) {
            $idsProdutosCatalogoComEstoque = array_intersect($catalogo['produtos'], $idsProdutosComEstoque);
            return !empty($idsProdutosCatalogoComEstoque);
        });

        return $catalogos;
    }

    public function buscarCatalogoPorId(Origem $origem, int $idCatalogo)
    {
        if ($origem->ehMed()) {
            $origem = Request::input('origem');
        } else {
            $origem = (string) $origem;
        }
        Validador::validar(
            ['origem' => $origem],
            [
                'origem' => [Validador::ENUM(Origem::MS, Origem::ML)],
            ]
        );
        $catalogo = CatalogoPersonalizado::consultaCatalogoPersonalizadoPorId($idCatalogo);
        $catalogo->produtos = CatalogoPersonalizado::buscarProdutosCatalogoPersonalizadoPorIds(
            $catalogo->produtos,
            'EDITAR',
            $origem
        );
        return $catalogo;
    }

    public function editarCatalogo()
    {
        $json = Request::all();
        Validador::validar($json, [
            'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome' => [Validador::OBRIGATORIO],
            'ids_produtos' => [Validador::SE(Validador::NAO_NULO, [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)])],
        ]);
        $catalogoPersonalizado = CatalogoPersonalizado::consultaCatalogoPersonalizadoPorId($json['id']);
        $catalogoPersonalizado->id_colaborador = Auth::user()->id_colaborador;
        $catalogoPersonalizado->nome = $json['nome'];
        $catalogoPersonalizado->produtos = $json['ids_produtos'];
        $catalogoPersonalizado->save();
    }

    public function deletarCatalogo(int $idCatalogo)
    {
        $catalogoPersonalizado = CatalogoPersonalizado::consultaCatalogoPersonalizadoPorId($idCatalogo);
        $catalogoPersonalizado->delete();
    }

    public function adicionarProdutoCatalogo()
    {
        $json = Request::all();
        Validador::validar($json, [
            'id_catalogo' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
        CatalogoPersonalizado::adicionarProdutoCatalogo($json['id_catalogo'], $json['id_produto']);
    }
}
