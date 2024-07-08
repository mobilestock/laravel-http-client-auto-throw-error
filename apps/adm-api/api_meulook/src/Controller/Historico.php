<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use DomainException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\model\AcompanhamentoTemp;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\EntregaService\EntregasFilaProcessoAlterarEntregadorService;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\IBGEService;
use MobileStock\service\MessageService;
use MobileStock\service\NegociacoesProdutoTempService;
use MobileStock\service\Pedido;
use MobileStock\service\ProdutoService;
use MobileStock\service\RodonavesHttpClient;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Historico extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function buscaHistoricoPedidos(int $pagina)
    {
        $idColaborador = Auth::user()->id_colaborador;

        Pedido::limparCarrinhoSeNecessario();

        $historico = TransacaoConsultasService::buscaPedidosMeuLook($pagina);

        foreach ($historico as &$item) {
            if (!empty($item['ponto']) && !empty($item['endereco_transacao']['id_cidade'])) {
                $acompanhamento = AcompanhamentoTemp::buscarAcompanhamentoDestino(
                    $idColaborador,
                    $item['ponto']['id_tipo_frete'],
                    $item['endereco_transacao']['id_cidade']
                );
                $item['acompanhamento'] = $acompanhamento;
            }
        }

        return $historico;
    }

    public function rastreioTransportadora()
    {
        try {
            Validador::validar($dadosGet = $this->request->query->all(), [
                'cnpj' => [Validador::NAO_NULO],
                'nota_fiscal' => [Validador::NAO_NULO],
            ]);

            $dadosGet = [
                'cnpj' => $this->request->query->get('cnpj'),
                'nota_fiscal' => $this->request->query->get('nota_fiscal'),
            ];

            $rodonaves = new RodonavesHttpClient();
            $rodonaves->listaCodigosPermitidos = [200];
            $resultado = $rodonaves->get(
                'api/v1/tracking?taxIdRegistration=' . $dadosGet['cnpj'] . '&invoiceNumber=' . $dadosGet['nota_fiscal'],
                []
            );

            $this->status = 200;
            $this->retorno['data'] = $resultado->body['Events'];
        } catch (Throwable $e) {
            $this->status = 500;
            $this->retorno['data'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function alteraEnderecoEntregaDaTransacao(
        TransacaoFinanceirasMetadadosService $transacaoMetadadosService,
        int $idTransacao
    ) {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'antigo_endereco' => [Validador::NAO_NULO, Validador::JSON],
            'novo_endereco' => [Validador::NAO_NULO, Validador::JSON],
            'dados_ponto' => [Validador::NAO_NULO, Validador::JSON],
        ]);

        $dadosAntigoEndereco = json_decode($dadosJson['antigo_endereco'], true);
        $dadosNovoEndereco = json_decode($dadosJson['novo_endereco'], true);
        $dadosPonto = json_decode($dadosJson['dados_ponto'], true);

        Validador::validar(array_merge($dadosNovoEndereco, $dadosAntigoEndereco, $dadosPonto), [
            'formatted_address' => [Validador::NAO_NULO],
            'address_components' => [Validador::NAO_NULO, Validador::ARRAY],
            'cidade' => [Validador::NAO_NULO, Validador::OBRIGATORIO],
            'uf' => [Validador::NAO_NULO, Validador::OBRIGATORIO],
            'id_colaborador' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        $idColaborador = Auth::user()->id_colaborador;
        $idColaboradorPagador = TransacaoFinanceiraModel::buscaPagador($idTransacao);
        if ($idColaboradorPagador !== $idColaborador) {
            throw new DomainException('Transação não pertence ao cliente');
        }

        $novoEndereco = $dadosAntigoEndereco;
        foreach ($dadosNovoEndereco['address_components'] as $value) {
            switch (true) {
                case count(array_intersect(['sublocality', 'sublocality_level_1'], $value['types'])):
                    $novoEndereco['bairro'] = $value['long_name'];
                    break;
                case in_array('route', $value['types']):
                    $novoEndereco['endereco'] = $value['long_name'];
                    break;
                case in_array('street_number', $value['types']):
                    $novoEndereco['numero'] = $value['long_name'];
                    break;
            }
        }
        $cidadeTransacao = IBGEService::buscarIDCidade(
            DB::getPdo(),
            $dadosAntigoEndereco['cidade'],
            $dadosAntigoEndereco['uf']
        );
        if (!$cidadeTransacao) {
            throw new NotFoundHttpException('Cidade atual não encontrada, contate o suporte');
        }

        $novoEndereco['latitude'] = $dadosNovoEndereco['geometry']['location']['lat'];
        $novoEndereco['longitude'] = $dadosNovoEndereco['geometry']['location']['lng'];
        $novoEndereco['id_cidade'] = $cidadeTransacao;

        $novoEntregador = TransportadoresRaio::buscaEntregadorMaisProximoDaCoordenada(
            $cidadeTransacao,
            $novoEndereco['latitude'],
            $novoEndereco['longitude']
        );
        $novoEndereco['id_raio'] = $novoEntregador->id;
        $transacaoMetadadosService->id_transacao = $idTransacao;

        $idMetadado = TransacaoFinanceirasMetadadosService::buscaIDMetadadoTransacao(
            $idTransacao,
            'ENDERECO_CLIENTE_JSON'
        );
        $transacaoMetadadosService->id = $idMetadado;
        $transacaoMetadadosService->chave = 'ENDERECO_CLIENTE_JSON';
        $transacaoMetadadosService->valor = $novoEndereco;
        if (!$transacaoMetadadosService->alterar(DB::getPdo())) {
            throw new RuntimeException('Erro ao salvar alterações no endereço.');
        }

        if ($novoEntregador->id_colaborador !== $dadosPonto['id_colaborador']) {
            $tabelas = [
                'transacao_financeiras_metadados',
                'lancamento_financeiro_pendente',
                'pedido_item_meu_look',
                'logistica_item',
            ];

            $itensTransacao = TransacaoFinanceiraItemProdutoService::buscaProdutosTransacao($idTransacao);

            foreach ($tabelas as $tabela) {
                try {
                    EntregasFilaProcessoAlterarEntregadorService::alterarEntregadorEmTabelas(
                        DB::getPdo(),
                        $tabela,
                        $itensTransacao,
                        $novoEntregador->id_colaborador
                    );
                } catch (Throwable $error) {
                    if ($tabela === 'logistica_item') {
                        // Possível pendente de fraude;
                        $colaboradoresService = new ColaboradoresService();
                        $colaboradoresService->id = $idColaborador;
                        $colaboradoresService->buscaSituacaoFraude(DB::getPdo(), ['CARTAO']);
                        if (!in_array($colaboradoresService->situacao_fraude, ['PE', 'FR'])) {
                            throw $error;
                        }
                    }
                }
            }

            EntregasFilaProcessoAlterarEntregadorService::atualizaComissaoEntregaEPontoColeta(
                $itensTransacao,
                $novoEntregador['id_colaborador']
            );
        }
        DB::commit();
    }
    public function buscaNegociacoesAbertas(Authenticatable $usuario, NegociacoesProdutoTempService $negociacoes)
    {
        $negociacoesAbertas = $negociacoes->buscaNegociacoesAbertasPorCliente($usuario->id_colaborador);

        return $negociacoesAbertas;
    }
    public function buscaItensOferecidos(string $uuidProduto, NegociacoesProdutoTempService $negociacoes)
    {
        $negociacoes->uuid_produto = $uuidProduto;
        $itensOferecidos = $negociacoes->buscaInformacoesProdutosOferecidos();

        return $itensOferecidos;
    }
    public function aceitarNegociacao(
        PDO $conexao,
        Request $request,
        NegociacoesProdutoTempService $negociacoes,
        EstoqueGradeService $estoque,
        MessageService $msgService,
        Authenticatable $usuario
    ) {
        try {
            $dadosJson = $request->all();
            Validador::validar($dadosJson, [
                'uuid_produto' => [Validador::OBRIGATORIO],
                'id_produto_substituto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho_escolhido' => [Validador::OBRIGATORIO],
            ]);

            $negociacoes->uuid_produto = $dadosJson['uuid_produto'];
            $existeNegociacao = $negociacoes->buscaNegociacaoAbertaPorProduto();
            if (empty($existeNegociacao)) {
                throw new NotFoundHttpException('Negociação não encontrada');
            }

            $produto = ProdutoService::informacoesDoProdutoNegociado($conexao, $dadosJson['uuid_produto']);
            $conexao->beginTransaction();
            // https://github.com/mobilestock/backend/issues/168
            $negociacoes = app(NegociacoesProdutoTempService::class);
            $negociacoes->uuid_produto = $dadosJson['uuid_produto'];
            $negociacoes->remove();

            $negociacoes->criarLogNegociacao(
                NegociacoesProdutoTempService::SITUACAO_ACEITA,
                Arr::only($produto, ['id_produto', 'nome_tamanho']),
                $existeNegociacao->itens_oferecidos,
                $usuario->id,
                [
                    'id_produto' => $dadosJson['id_produto_substituto'],
                    'nome_tamanho' => $dadosJson['nome_tamanho_escolhido'],
                ]
            );

            $estoque->id_produto = $produto['id_produto'];
            $estoque->nome_tamanho = $produto['nome_tamanho'];
            $estoque->id_responsavel = $produto['id_responsavel_estoque'];
            $estoque->tipo_movimentacao = 'S';
            $estoque->alteracao_estoque = -1;
            $estoque->descricao = "Cliente {$usuario->id_colaborador} aceitou a substituição do produto {$dadosJson['uuid_produto']}";
            $estoque->movimentaEstoque($conexao, $usuario->id);

            $estoque->id_produto = $dadosJson['id_produto_substituto'];
            $estoque->nome_tamanho = $dadosJson['nome_tamanho_escolhido'];
            $estoque->id_responsavel = $produto['id_responsavel_estoque'];
            $estoqueAtual = $estoque->buscaEstoqueEspecifico($conexao);
            if ($estoqueAtual <= 0) {
                throw new ConflictHttpException('Produto não disponível no estoque');
            }
            $estoque->tipo_movimentacao = 'M';
            $estoque->alteracao_estoque = -1;
            $estoque->descricao = "Cliente {$usuario->id_colaborador} aceitou a substituição do produto {$dadosJson['uuid_produto']}";
            $estoque->movimentaEstoque($conexao, $usuario->id);

            $negociacoes->atualizaInformacoesProduto(
                $dadosJson['uuid_produto'],
                $dadosJson['id_produto_substituto'],
                $dadosJson['nome_tamanho_escolhido']
            );
            separacaoService::deletaLogDeImpressaoEspecificoSemVerificacao($conexao, $dadosJson['uuid_produto']);

            $cliente = ColaboradoresService::consultaDadosColaborador($produto['id_cliente']);
            $fornecedor = ColaboradoresService::consultaDadosColaborador($produto['id_responsavel_estoque']);
            $fotoProduto = ProdutoService::buscaFotoDoProduto($conexao, $dadosJson['id_produto_substituto']);
            $mensagem = "O cliente {$cliente['nome']} aceitou a substituição do produto {$produto['id_produto']} ";
            $mensagem .= "tamanho {$produto['nome_tamanho']} pelo produto {$dadosJson['id_produto_substituto']} ";
            $mensagem .= "tamanho {$dadosJson['nome_tamanho_escolhido']}.";
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= 'Imprima a etiqueta do produto novamente e envie para central de distribuição!';
            $msgService->sendImageWhatsApp($fornecedor['telefone'], $fotoProduto, $mensagem);

            $conexao->commit();
        } catch (Throwable $th) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $th;
        }
    }
}
