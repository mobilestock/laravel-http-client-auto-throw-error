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

        $identificacao = [];
        if (isset($dados['identificacao_produto_bipado'])) {
            switch (true) {
                case preg_match(LogisticaItemModel::REGEX_ETIQUETA_SKU_LEGADO, $dados['identificacao_produto_bipado']):
                    $partes = explode('_', $dados['identificacao_produto_bipado']);
                    $identificacao = [
                        'codigo' => $partes[2],
                        'tipo' => 'SKU_LEGADO',
                    ];
                    break;
                case preg_match(LogisticaItemModel::REGEX_ETIQUETA_SKU, $dados['identificacao_produto_bipado']):
                    [$produtoLogistica, $codBarras] = ProdutoLogistica::buscarPorSku(
                        $dados['identificacao_produto_bipado']
                    );
                    $identificacao = [
                        'codigo' => $codBarras,
                        'tipo' => 'SKU',
                    ];
                    break;
                case preg_match(LogisticaItemModel::REGEX_ETIQUETA_COD_BARRAS, $dados['identificacao_produto_bipado']):
                    $identificacao = [
                        'codigo' => $dados['identificacao_produto_bipado'],
                        'tipo' => 'COD_BARRAS',
                    ];
                    break;
                case preg_match(
                    LogisticaItemModel::REGEX_ETIQUETA_UUID_PRODUTO_CLIENTE,
                    $dados['identificacao_produto_bipado']
                ):
                    $identificacao = [
                        'codigo' => $dados['identificacao_produto_bipado'],
                        'tipo' => 'UUID_PRODUTO_CLIENTE',
                    ];
                    break;
            }
        }

        DB::beginTransaction();
        if ($origem->ehAdm() && !empty($dados['id_usuario'])) {
            Auth::setUser(new GenericUser(['id' => $dados['id_usuario']]));
        }

        [$logisticaItem, $codBarras] = LogisticaItemModel::buscaInformacoesLogisticaItem($uuidProduto);

        if (
            isset($identificacao['tipo']) &&
            in_array($identificacao['tipo'], ['SKU_LEGADO', 'SKU', 'COD_BARRAS']) &&
            $identificacao['codigo'] !== $codBarras
        ) {
            throw new BadRequestHttpException('Você bipou a etiqueta errada, os códigos de barras não batem.');
        } elseif (isset($identificacao['tipo']) && $uuidProduto !== $identificacao['codigo']) {
            throw new BadRequestHttpException('Você bipou a etiqueta errada, os códigos de barras não batem.');
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
                'situacao' => 'CONFERIDO',
            ]);
            $produtoLogistica->criarSkuPorTentativas();
        } else {
            $produtoLogistica->situacao = 'CONFERIDO';
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
