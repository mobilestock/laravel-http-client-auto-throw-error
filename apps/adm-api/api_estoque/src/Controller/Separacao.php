<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\jobs\GerenciarPrevisaoFrete;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\service\Separacao\separacaoService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Separacao extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

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
        }

        return $etiquetas;
    }

    public function buscaEtiquetasParaSeparacao(Origem $origem)
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'uuids' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
        ]);

        $respostaFormatada = separacaoService::geraEtiquetaSeparacao(
            $dados['uuids'],
            $origem->ehAplicativoInterno() ? 'ZPL' : 'JSON'
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
        try {
            $this->retorno['data'] = separacaoService::consultaQuantidadeParaSeparar(
                $this->conexao,
                $this->idColaborador
            );
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Quantidade para separar encontrada com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Exception $e) {
            $this->retorno['data'] = 0;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
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
}
