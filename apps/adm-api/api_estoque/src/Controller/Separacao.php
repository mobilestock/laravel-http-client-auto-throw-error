<?php

namespace api_estoque\Controller;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\jobs\GerenciarPrevisaoFrete;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\service\Separacao\separacaoService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Separacao
{
    public function buscaItensParaSeparacao(PDO $conexao, Request $request, Origem $origem, Authenticatable $usuario)
    {
        $dadosJson = $request->all();
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
        $resposta = separacaoService::listaItems($conexao, $idColaborador, $dadosJson['pesquisa'] ?? null);

        return $resposta;
    }
    public function buscaEtiquetasFreteDisponiveisDoColaborador(int $idColaborador)
    {
        $etiquetas = separacaoService::consultaEtiquetasFrete($idColaborador);

        return $etiquetas;
    }
    public function buscaEtiquetasParaSeparacao(Origem $origem)
    {
        $dados = FacadesRequest::all();

        Validador::validar($dados, [
            'uuids' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
        ]);

        $respostaFormatada = separacaoService::geraEtiquetaSeparacao(
            $dados['uuids'],
            $origem->ehAplicativoInterno() ? 'ZPL' : 'JSON'
        );

        separacaoService::salvaImpressao($dados['uuids']);

        return $respostaFormatada;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public function separaEConfereItem(string $uuidProduto, Origem $origem)
    {
        $dados = FacadesRequest::all();
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
        $resposta = separacaoService::consultaQuantidadeParaSeparar();

        return $resposta;
    }
    public function buscaEtiquetasSeparacaoProdutosFiltradas()
    {
        $dados = FacadesRequest::all();

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
}
