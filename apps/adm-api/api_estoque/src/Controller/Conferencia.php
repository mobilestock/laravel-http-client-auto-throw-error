<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\jobs\GerenciarPrevisaoFreteConferido;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoLogistica;
use MobileStock\service\Conferencia\ConferenciaItemService;
use MobileStock\service\Separacao\separacaoService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Conferencia extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        $this->conexao = Conexao::criarConexao();
        parent::__construct();
    }
    public function buscaItensEntreguesCentral()
    {
        try {
            $this->retorno['data'] = ConferenciaItemService::buscaConferidosDoSeller(
                $this->conexao,
                $this->idColaborador
            );
            $this->retorno['message'] = 'Produtos consultados!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Falha ao buscar produtos.';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function itensDisponiveisParaAdicionarNaEntrega()
    {
        try {
            $pesquisa = (string) $this->request->get('pesquisa', '');
            $this->retorno['data'] = ConferenciaItemService::listaItensDisponiveisParaAdicionarNaEntrega(
                $this->conexao,
                $this->categoriaDoUsuario === 'ADM' ? 1 : $this->idColaborador,
                $pesquisa
            );

            $this->retorno['message'] = 'Pares encontrados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Erro ao buscar itens para conferência';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function descobrirItemParaEntrarNaConferencia(string $uuidProduto)
    {
        $lista = ConferenciaItemService::buscaDetalhesDoItem($uuidProduto);

        return $lista;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public function conferir(string $uuidProduto, Origem $origem)
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_usuario' => [Validador::SE(Validador::OBRIGATORIO, [Validador::NUMERO])],
            'identificacao_produto_bipado' => [Validador::SE($origem->ehAplicativoInterno(), [Validador::OBRIGATORIO])],
        ]);

        $logisticaItem = LogisticaItemModel::buscaInformacoesLogisticaItem($uuidProduto);
        if (!empty($dados['identificacao_produto_bipado'])) {
            $identificacao = null;
            switch (true) {
                case preg_match(
                    LogisticaItemModel::REGEX_ETIQUETA_PRODUTO_SKU_LEGADO,
                    $dados['identificacao_produto_bipado']
                ):
                    $partes = explode('_', $dados['identificacao_produto_bipado']);
                    $identificacao = $partes[2];
                    break;
                case preg_match(LogisticaItemModel::REGEX_ETIQUETA_PRODUTO_SKU, $dados['identificacao_produto_bipado']):
                    $produtoLogistica = ProdutoLogistica::buscarPorSku(
                        explode('SKU', $dados['identificacao_produto_bipado'])[1]
                    );
                    $identificacao = $produtoLogistica->cod_barras;
                    break;
                case preg_match(
                    LogisticaItemModel::REGEX_ETIQUETA_PRODUTO_COD_BARRAS,
                    $dados['identificacao_produto_bipado']
                ):
                    $identificacao = $dados['identificacao_produto_bipado'];
                    break;
                case preg_match(
                    LogisticaItemModel::REGEX_ETIQUETA_UUID_PRODUTO_CLIENTE,
                    $dados['identificacao_produto_bipado']
                ):
                    $produtoLogistica = ProdutoLogistica::buscarPorUuid($dados['identificacao_produto_bipado']);
                    $identificacao = $dados['identificacao_produto_bipado'];
                    break;
            }

            if (!in_array($identificacao, [$logisticaItem->cod_barras, $uuidProduto])) {
                throw new BadRequestHttpException('Você bipou a etiqueta errada, as etiquetas não batem.');
            }
        }

        DB::beginTransaction();
        if ($origem->ehAdm() && !empty($dados['id_usuario'])) {
            Auth::setUser(new GenericUser(['id' => $dados['id_usuario']]));
        }

        if ($logisticaItem->situacao === 'CO') {
            throw new BadRequestHttpException('Este produto já foi conferido!');
        } elseif ($logisticaItem->situacao === 'PE') {
            separacaoService::separa(DB::getPdo(), $uuidProduto, Auth::user()->id);
        }

        if (empty($produtoLogistica)) {
            $produtoLogistica = new ProdutoLogistica([
                'id_produto' => $logisticaItem->id_produto,
                'nome_tamanho' => $logisticaItem->nome_tamanho,
                'origem' => 'REPOSICAO',
                'situacao' => 'EM_ESTOQUE',
            ]);
            $produtoLogistica->criarSkuPorTentativas();
        } elseif ($logisticaItem->id_responsavel_estoque > 1) {
            $produtoLogistica->situacao = 'EM_ESTOQUE';
            $produtoLogistica->update();
        }
        $logisticaItem->sku = $produtoLogistica->sku;
        $logisticaItem->situacao = 'CO';
        $logisticaItem->uuid_produto = $uuidProduto;
        $logisticaItem->update();

        DB::commit();
        dispatch(new GerenciarAcompanhamento([$uuidProduto]));
        if (in_array($logisticaItem->id_produto, Produto::IDS_PRODUTOS_FRETE)) {
            dispatch(new GerenciarPrevisaoFreteConferido($uuidProduto));
        }
    }
}
