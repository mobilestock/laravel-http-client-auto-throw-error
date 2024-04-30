<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado as CatalogoPersonalizadoModel;
use MobileStock\model\Origem;
use MobileStock\service\Estoque\EstoqueGradeService;

class CatalogoPersonalizado extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = 4;
        parent::__construct();
    }

    public function criarCatalogo(Origem $origem)
    {
        try {
            $json = FacadesRequest::all();
            Validador::validar($json, [
                'nome' => [Validador::OBRIGATORIO],
                'ids_produtos' => [Validador::NAO_NULO],
                'tipo' => [Validador::SE(Validador::NAO_NULO, Validador::ENUM('PUBLICO', 'PRIVADO'))],
                'plataformas' => [Validador::SE($origem->ehAdm(), [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)])],
            ]);

            $catalogoPersonalizado = new CatalogoPersonalizadoModel();
            $catalogoPersonalizado->id_colaborador = $this->idCliente;
            $catalogoPersonalizado->nome = $json['nome'];
            if (!empty($json['tipo'])) {
                $catalogoPersonalizado->tipo = $json['tipo'];
            }
            if (!empty($json['ids_produtos'])) {
                $catalogoPersonalizado->produtos = $json['ids_produtos'];
            }
            if (!empty($json['plataformas'])) {
                $catalogoPersonalizado->plataformas_filtros = $json['plataformas'];
            }
            $catalogoPersonalizado->save();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    public function buscarListaCatalogos()
    {
        $catalogos = CatalogoPersonalizadoModel::buscarListaCatalogosColaborador();
        return $catalogos;
    }

    public function buscarListaCatalogosPublicos(Origem $origem)
    {
        $siglaOrigem = $origem;
        if ($origem->ehMed()) {
            Validador::validar(FacadesRequest::all(), [
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM(Origem::MS, Origem::ML)],
            ]);
            $siglaOrigem = FacadesRequest::input('origem');
        }
        $catalogos = CatalogoPersonalizadoModel::buscarListaCatalogosPublicos($origem);

        $idsProdutosTotais = array_reduce(
            $catalogos,
            function (array $idsProdutos, array $catalogo): array {
                return array_merge($idsProdutos, $catalogo['produtos']);
            },
            []
        );
        $idsProdutosComEstoque = EstoqueGradeService::retornarItensComEstoque(
            $idsProdutosTotais,
            $siglaOrigem
        );

        $catalogos = array_filter($catalogos, function ($catalogo) use ($idsProdutosComEstoque) {
            $idsProdutosCatalogoComEstoque = array_intersect($catalogo['produtos'], $idsProdutosComEstoque);
            return !empty($idsProdutosCatalogoComEstoque);
        });

        return $catalogos;
    }

    public function buscarCatalogoPorId(Origem $origem, int $idCatalogo)
    {
        if ($origem->ehMed()) {
            $origem = FacadesRequest::input('origem');
        } else {
            $origem = (string) $origem;
        }
        Validador::validar(
            ['origem' => $origem],
            [
                'origem' => [Validador::ENUM('MS', 'ML')],
            ]
        );
        $catalogo = CatalogoPersonalizadoModel::consultaCatalogoPersonalizadoPorId($idCatalogo);
        $catalogo->produtos = CatalogoPersonalizadoModel::buscarProdutosCatalogoPersonalizadoPorIds(
            $catalogo->produtos,
            'EDITAR',
            $origem
        );
        return $catalogo;
    }

    public function editarCatalogo()
    {
        try {
            $json = FacadesRequest::all();
            Validador::validar($json, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome' => [Validador::OBRIGATORIO],
                'ids_produtos' => [
                    Validador::SE(Validador::NAO_NULO, [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)]),
                ],
            ]);
            $catalogoPersonalizado = CatalogoPersonalizadoModel::consultaCatalogoPersonalizadoPorId($json['id']);
            $catalogoPersonalizado->id_colaborador = Auth::user()->id_colaborador;
            $catalogoPersonalizado->nome = $json['nome'];
            $catalogoPersonalizado->produtos = $json['ids_produtos'];
            $catalogoPersonalizado->save();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    public function deletarCatalogo(int $idCatalogo)
    {
        try {
            $catalogoPersonalizado = CatalogoPersonalizadoModel::consultaCatalogoPersonalizadoPorId($idCatalogo);
            $catalogoPersonalizado->delete();
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    public function adicionarProdutoCatalogo()
    {
        try {
            $json = FacadesRequest::all();
            Validador::validar($json, [
                'id_catalogo' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            CatalogoPersonalizadoModel::adicionarProdutoCatalogo(
                $json['id_catalogo'],
                $json['id_produto']
            );
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }
}
