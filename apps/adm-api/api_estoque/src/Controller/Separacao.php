<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\service\Conferencia\ConferenciaItemService;
use MobileStock\service\Separacao\separacaoService;
use PDO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MobileStock\model\Origem;
use MobileStock\service\LogisticaItemService;
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
    public function buscaEtiquetasParaSeparacao(Origem $origem)
    {
        $dados = \Illuminate\Support\Facades\Request::all();

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
    public function separaEConfereItem(PDO $conexao, Authenticatable $usuario, string $uuidProduto)
    {
        try {
            $conexao->beginTransaction();

            $situacao = LogisticaItemService::buscaSituacaoItem($conexao, $uuidProduto);

            if (empty($situacao)) {
                throw new BadRequestHttpException(
                    'Este produto não foi encontrado. Verifique se o mesmo foi cancelado ou chame a T.I.'
                );
            }

            if ($situacao->situacao === 'CO') {
                throw new BadRequestHttpException('Este produto já foi conferido!');
            }

            separacaoService::separa($conexao, $uuidProduto, $usuario->id);
            ConferenciaItemService::confere($conexao, [$uuidProduto], $usuario->id);

            $conexao->commit();
            dispatch(new GerenciarAcompanhamento([$uuidProduto]));
        } catch (\Throwable $th) {
            $conexao->rollBack();
            throw $th;
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
        $dados = \Illuminate\Support\Facades\Request::all();

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
