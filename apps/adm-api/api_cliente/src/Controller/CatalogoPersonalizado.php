<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MobileStock\helper\Validador;
use MobileStock\model\Origem;
use MobileStock\service\CatalogoPersonalizadoService;
use MobileStock\service\Estoque\EstoqueGradeService;
use PDO;

class CatalogoPersonalizado extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = 4;
        parent::__construct();
    }

    public function criarCatalogo(PDO $conexao, Request $request, Origem $origem)
    {
        try {
            $conexao->beginTransaction();
            $json = $request->all();
            Validador::validar($json, [
                'nome' => [Validador::OBRIGATORIO],
                'ids_produtos' => [Validador::NAO_NULO],
                'tipo' => [Validador::SE(Validador::NAO_NULO, Validador::ENUM('PUBLICO', 'PRIVADO'))],
                'plataformas' => [Validador::SE($origem->ehAdm(), [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)])],
            ]);

            $catalogoPersonalizado = new CatalogoPersonalizadoService();
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
            $catalogoPersonalizado->salvar($conexao);
            $conexao->commit();
        } catch (\Throwable $throwable) {
            $conexao->rollBack();
            throw $throwable;
        }
    }

    public function buscarListaCatalogos(PDO $conexao)
    {
        $catalogos = CatalogoPersonalizadoService::buscarListaCatalogosColaborador($conexao, $this->idCliente);
        return $catalogos;
    }

    public function buscarListaCatalogosPublicos(PDO $conexao, Origem $origem, Request $request)
    {
        $siglaOrigem = $origem;
        if ($origem->ehMed()) {
            Validador::validar($request->all(), [
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM(Origem::MS, Origem::ML)],
            ]);
            $siglaOrigem = $request->input('origem');
        }
        $catalogos = CatalogoPersonalizadoService::buscarListaCatalogosPublicos($conexao, $origem);

        $idsProdutosTotais = array_reduce(
            $catalogos,
            function (array $idsProdutos, array $catalogo): array {
                return array_merge($idsProdutos, $catalogo['produtos']);
            },
            []
        );
        $idsProdutosComEstoque = EstoqueGradeService::retornarItensComEstoque(
            $conexao,
            $idsProdutosTotais,
            $siglaOrigem
        );

        $catalogos = array_filter($catalogos, function ($catalogo) use ($idsProdutosComEstoque) {
            $idsProdutosCatalogoComEstoque = array_intersect($catalogo['produtos'], $idsProdutosComEstoque);
            return !empty($idsProdutosCatalogoComEstoque);
        });

        return $catalogos;
    }

    public function buscarCatalogoPorId(
        PDO $conexao,
        Origem $origem,
        int $idCatalogo,
        Authenticatable $usuario,
        Request $request
    ) {
        if ($origem->ehMed()) {
            $origem = $request->input('origem');
        } else {
            $origem = (string) $origem;
        }
        Validador::validar(
            ['origem' => $origem],
            [
                'origem' => [Validador::ENUM('MS', 'ML')],
            ]
        );
        $catalogo = CatalogoPersonalizadoService::buscarCatalogoColaborador(
            $conexao,
            $idCatalogo,
            $usuario->id_colaborador
        );
        $catalogo['produtos'] = CatalogoPersonalizadoService::buscarProdutosCatalogoPersonalizadoPorIds(
            $conexao,
            $catalogo['produtos'],
            'EDITAR',
            $origem
        );
        return $catalogo;
    }

    public function editarCatalogo(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();
            $json = $request->all();
            Validador::validar($json, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome' => [Validador::OBRIGATORIO],
                'ids_produtos' => [
                    Validador::SE(Validador::NAO_NULO, [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)]),
                ],
            ]);
            $catalogoPersonalizado = new CatalogoPersonalizadoService();
            $catalogoPersonalizado->id_colaborador = $this->idCliente;
            $catalogoPersonalizado->id = $json['id'];
            $catalogoPersonalizado->nome = $json['nome'];
            $catalogoPersonalizado->produtos = $json['ids_produtos'];
            $catalogoPersonalizado->editar($conexao);
            $conexao->commit();
        } catch (\Throwable $throwable) {
            $conexao->rollBack();
            throw $throwable;
        }
    }

    public function deletarCatalogo(PDO $conexao, int $idCatalogo)
    {
        try {
            $conexao->beginTransaction();
            $catalogoPersonalizado = new CatalogoPersonalizadoService();
            $catalogoPersonalizado->id = $idCatalogo;
            $catalogoPersonalizado->deletar($conexao);
            $conexao->commit();
        } catch (\Throwable $throwable) {
            $conexao->rollBack();
            throw $throwable;
        }
    }

    public function adicionarProdutoCatalogo(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();
            $json = $request->all();
            Validador::validar($json, [
                'id_catalogo' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            CatalogoPersonalizadoService::adicionarProdutoCatalogo(
                $conexao,
                $this->idCliente,
                $json['id_catalogo'],
                $json['id_produto']
            );
            $conexao->commit();
        } catch (\Throwable $throwable) {
            $conexao->rollBack();
            throw $throwable;
        }
    }
}
