<?php

namespace api_estoque\Controller;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\jobs\GerenciarPrevisaoFrete;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\service\Separacao\separacaoService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Separacao
{
    public function buscaItensParaSeparacao(Origem $origem, Authenticatable $usuario)
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::SE($origem->ehAdm(), [Validador::OBRIGATORIO, Validador::NUMERO])],
            'pesquisa' => [],
        ]);

        if ($origem->ehAdm()) {
            $idColaborador = $dadosJson['id_colaborador'];
        } elseif ($origem->ehAplicativoInterno()) {
            $idColaborador = 1;
        } else {
            $idColaborador = $usuario->id_colaborador;
        }
        $resposta = separacaoService::listaItems($idColaborador, $dadosJson['pesquisa'] ?? null);

        return $resposta;
    }

    public function buscaEtiquetasFreteDisponiveisDoColaborador()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_colaborador' => [
                Validador::SE(empty($dados['numero_frete']), [Validador::OBRIGATORIO, Validador::NUMERO]),
            ],
            'numero_frete' => [
                Validador::SE(empty($dados['id_colaborador']), [Validador::OBRIGATORIO, Validador::NUMERO]),
            ],
        ]);

        if (isset($dados['id_colaborador'])) {
            $etiquetas = separacaoService::consultaEtiquetasFrete($dados['id_colaborador']);
        } elseif (isset($dados['numero_frete'])) {
            $etiquetas = separacaoService::consultaEtiquetasFrete($dados['numero_frete'], true);
            if (empty($etiquetas)) {
                throw new NotFoundHttpException(
                    "Nenhuma etiqueta disponível para o frete de número {$dados['numero_frete']}!"
                );
            }
        }

        return $etiquetas;
    }

    public function buscaEtiquetasParaSeparacao(Origem $origem)
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'uuids' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
            'tipo_etiqueta' => [
                Validador::SE(isset($dados['tipo_etiqueta']), [Validador::ENUM('TODAS', 'PRONTAS', 'COLETAS')]),
            ],
        ]);

        $respostaFormatada = separacaoService::geraEtiquetaSeparacao(
            $dados['uuids'],
            $origem->ehAplicativoInterno() ? 'ZPL' : 'JSON',
            $dados['tipo_etiqueta'] ?? ''
        );

        if (Gate::allows('FORNECEDOR') && !Gate::allows('FORNECEDOR.CONFERENTE_INTERNO')) {
            separacaoService::salvaImpressao($dados['uuids']);
        }

        return $respostaFormatada;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public function separaEConfereItem(string $uuidProduto, Origem $origem)
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_usuario' => [Validador::SE(Validador::OBRIGATORIO, [Validador::NUMERO])],
        ]);

        DB::beginTransaction();

        if ($origem->ehAdm() && !empty($dados['id_usuario'])) {
            Auth::setUser(new GenericUser(['id' => $dados['id_usuario']]));
        }

        $logisticaItem = LogisticaItemModel::buscaInformacoesLogisticaItem($uuidProduto);
        if ($logisticaItem->situacao === 'CO') {
            throw new BadRequestHttpException('Este produto já foi conferido!');
        } elseif ($logisticaItem->situacao === 'PE') {
            separacaoService::separa(DB::getPdo(), $uuidProduto, Auth::user()->id);
        }

        LogisticaItemModel::confereItens([$uuidProduto]);
        DB::commit();
        dispatch(new GerenciarAcompanhamento([$uuidProduto]));
        if (
            in_array($logisticaItem->id_produto, [
                ProdutoModel::ID_PRODUTO_FRETE,
                ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
            ])
        ) {
            dispatch(new GerenciarPrevisaoFrete($uuidProduto));
        }
    }

    public function buscaQuantidadeDemandandoSeparacao()
    {
        $resposta = LogisticaItemModel::consultaQuantidadeParaSeparar();

        return $resposta;
    }
    public function buscaEtiquetasSeparacaoProdutosFiltradas()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'dia_da_semana' => [
                Validador::SE(Validador::OBRIGATORIO, [
                    Validador::ENUM('SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA'),
                ]),
            ],
            'tipo_logistica' => [Validador::ENUM('TODAS', 'PRONTAS')],
        ]);

        $produtosNaoEntregaCliente = separacaoService::produtosProntosParaSeparar(
            null,
            $dados['dia_da_semana'] ?? null
        );
        $produtosEntregaCliente = separacaoService::produtosProntosParaSeparar($dados['tipo_logistica'], null);
        $produtos = array_merge($produtosNaoEntregaCliente, $produtosEntregaCliente);

        $retorno = separacaoService::geraEtiquetaSeparacao($produtos, 'JSON');
        return $retorno;
    }

    public function defineEtiquetaImpressa()
    {
        $dados = FacadesRequest::all();

        Validador::validar($dados, [
            'uuids' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
        ]);

        separacaoService::salvaImpressao($dados['uuids']);
    }
}
