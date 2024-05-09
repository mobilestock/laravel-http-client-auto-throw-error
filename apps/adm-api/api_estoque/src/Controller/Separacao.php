<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\jobs\GerenciarPrevisaoFrete;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\service\Separacao\separacaoService;
use PDO;
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

        if (Gate::allows('FORNECEDOR') && !Gate::allows('FORNECEDOR.CONFERENTE_INTERNO')) {
            separacaoService::salvaImpressao($dados['uuids']);
        }

        return $respostaFormatada;
    }

    public function separaEConfereItem(string $uuidProduto)
    {
        $dados = FacadesRequest::all();
        Validador::validar($dados, [
            'id_usuario' => [Validador::SE(Validador::OBRIGATORIO, [Validador::NUMERO])],
        ]);

        DB::beginTransaction();
        $situacao = LogisticaItemModel::buscaSituacaoItem($uuidProduto);
        if ($situacao === 'CO') {
            throw new BadRequestHttpException('Este produto já foi conferido!');
        } elseif ($situacao === 'PE') {
            separacaoService::separa(DB::getPdo(), $uuidProduto, Auth::user()->id);
        }

        LogisticaItemModel::confereItens([$uuidProduto], $dados['id_usuario']);
        DB::commit();
        dispatch(new GerenciarAcompanhamento([$uuidProduto]));
        dispatch(new GerenciarPrevisaoFrete($uuidProduto));
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
