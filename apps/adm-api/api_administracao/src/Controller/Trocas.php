<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\Entrega\EntregasDevolucoesItemModel;
use MobileStock\model\Origem;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\repository\DefeitosRepository;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\ProdutoService;
use MobileStock\service\Troca\TrocaPendenteCrud;
use MobileStock\service\Troca\TrocasService;
use MobileStock\service\TrocaFilaSolicitacoesService;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Trocas extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();
        $this->respostaJson = new JsonResponse();
        $this->conexao = Conexao::criarConexao();
    }
    public function confirmaTroca(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();

            $produtos = $request->all();

            foreach ($produtos as $produto) {
                $produtoInfo = EntregasFaturamentoItemService::consultaInfoProdutoTrocaMS($conexao, $produto['uuid']);

                $troca = new TrocaPendenteItem(
                    (int) $produtoInfo['id_cliente'],
                    (int) $produtoInfo['id_produto'],
                    (string) $produtoInfo['nome_tamanho'],
                    (int) $this->idUsuario,
                    (float) $produtoInfo['preco'],
                    $produtoInfo['uuid_produto'],
                    $produtoInfo['cod_barras'],
                    $produtoInfo['data_base_troca']
                );

                $troca->setClienteEnviouErrado($produto['cliente_enviou_errado']);
                $troca->setPacIndevido($produto['pacIndevido']);
                $troca->setDefeito($produto['defeito']);
                $troca->setDescricaoDefeito($produto['descricao_defeito']);
                $troca->setAgendada($produto['agendada']);

                TrocasService::condicaoSeDefeito($conexao, $produto);
                TrocaPendenteCrud::salva($troca, $conexao, false);
                TrocasService::iniciaProcessoDevolucaoMS(
                    $conexao,
                    $produtoInfo['uuid_produto'],
                    $produtoInfo['id_transacao'],
                    $produtoInfo['id_produto'],
                    $produtoInfo['nome_tamanho'],
                    $this->idUsuario
                );
            }

            $conexao->commit();
        } catch (Throwable $ex) {
            $conexao->rollBack();
            throw $ex;
        }
    }
    public function pesquisaTrocasPendentesConfirmadas(array $dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = TrocaPendenteRepository::buscaTrocasPendentesConfirmadas(
                $this->conexao,
                $dadosJson['id_cliente']
            );
            $this->retorno['mensagem'] = 'trocas pendentes confirmadas buscadas com sucesso';
            $this->retorno['status'] = true;
            $this->status = 200;
        } catch (Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function buscaProdutos(PDO $conexao, Request $request, Authenticatable $usuario, Origem $origem)
    {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'uuid' => [],
            'pesquisa' => [],
        ]);
        $produtos = TrocaPendenteRepository::buscaProdutosTrocaMeuLook(
            $conexao,
            $usuario->id_colaborador,
            $dadosJson['pagina'],
            $dadosJson['uuid'] ?? '',
            $dadosJson['pesquisa'] ?? '',
            $origem->ehMl()
        );

        return $produtos;
    }
    public function buscaProdutosCompradosParametros()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ProdutoService::consultaProdutosCompradosParametros(
                $this->conexao,
                $this->idUsuario,
                $dadosJson['id_cliente'],
                $dadosJson,
                $dadosJson['pagina']
            );
            $this->retorno['mensagem'] = 'Produtos encontrados com sucesso!';
            $this->retorno['status'] = true;
            $this->status = 200;
        } catch (Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function buscaTrocasAgendadas(array $dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = TrocaPendenteRepository::buscaTrocasAgendadasCliente(
                $this->conexao,
                $dadosJson['id_cliente']
            );
            $this->retorno['mensagem'] = 'Produtos encontrados com sucesso!';
            $this->retorno['status'] = true;
            $this->status = 200;
        } catch (Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function aprovarSolicitacaoTroca(
        TrocasService $troca,
        TrocaFilaSolicitacoesService $trocaFilaSolicitacoesService
    ) {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_troca' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $trocaFilaSolicitacoesService->id = $dadosJson['id_troca'];
        $trocaFilaSolicitacoesService->situacao = 'APROVADO';
        $trocaFilaSolicitacoesService->atualizar(DB::getPdo());

        $solicitacaoTroca = TrocaFilaSolicitacoesService::buscaSolicitacaoPorId(DB::getPdo(), $dadosJson['id_troca']);
        $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
            $solicitacaoTroca['uuid_produto'],
            $solicitacaoTroca['id_cliente'],
            true
        );
        if (empty($produtoFaturamentoItem)) {
            throw new NotFoundHttpException('Informações do produto não encontradas!');
        }

        if ($produtoFaturamentoItem['situacao'] !== 'disponivel') {
            throw new UnprocessableEntityHttpException(
                "Produto: {$solicitacaoTroca['uuid_produto']} está com situacao {$produtoFaturamentoItem['situacao']}"
            );
        }

        $troca->idCliente = $solicitacaoTroca['id_cliente'];
        $troca->salvaAgendamento($produtoFaturamentoItem, [
            'uuid' => $solicitacaoTroca['uuid_produto'],
            'situacao' => 'defeito',
            'descricao_defeito' => $solicitacaoTroca['descricao_defeito'],
        ]);
        $pacReverso = null;
        if (!empty($produtoFaturamentoItem['entrega_por_transportadora'])) {
            $pac = app(DefeitosRepository::class);
            $pacReverso = $pac->gerenciarPac(
                $solicitacaoTroca['id_cliente'],
                $produtoFaturamentoItem['transacao_origem']
            );
        }

        DB::commit();

        TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(
            DB::getPdo(),
            $dadosJson['id_troca'],
            $produtoFaturamentoItem['origem'],
            $pacReverso
        );
    }
    public function recusarSolicitacaoTroca()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_troca' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'motivo' => [Validador::OBRIGATORIO],
                'origem' => [Validador::OBRIGATORIO],
            ]);

            $idTroca = $dadosJson['id_troca'];
            $trocaFilaSolicitacoesService = new TrocaFilaSolicitacoesService();
            $trocaFilaSolicitacoesService->id = $idTroca;
            $trocaFilaSolicitacoesService->situacao = 'REPROVADO';
            $trocaFilaSolicitacoesService->motivo_reprovacao_seller = $dadosJson['motivo'];
            $trocaFilaSolicitacoesService->atualizar($this->conexao);

            $this->retorno['data'] = true;
            $this->retorno['message'] = 'Troca recusada com sucesso!';
            $this->codigoRetorno = 200;
            $this->conexao->commit();
            TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp($this->conexao, $idTroca, $dadosJson['origem']);
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function resolverDisputaSolicitacaoTroca(
        TrocasService $troca,
        TrocaFilaSolicitacoesService $trocaFilaSolicitacoesService
    ) {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_troca' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'acao' => [Validador::ENUM('APROVADO', 'REPROVADA_NA_DISPUTA')],
            'motivo' => [Validador::NAO_NULO],
        ]);

        $trocaFilaSolicitacoesService->id = $dadosJson['id_troca'];
        $trocaFilaSolicitacoesService->situacao = $dadosJson['acao'];
        $trocaFilaSolicitacoesService->motivo_reprovacao_disputa = $dadosJson['motivo'];
        $trocaFilaSolicitacoesService->atualizar(DB::getPdo());

        $solicitacaoTroca = TrocaFilaSolicitacoesService::buscaSolicitacaoPorId(DB::getPdo(), $dadosJson['id_troca']);
        $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
            $solicitacaoTroca['uuid_produto'],
            $solicitacaoTroca['id_cliente'],
            true
        );
        if (empty($produtoFaturamentoItem)) {
            throw new NotFoundHttpException('Informações do produto não encontradas!');
        }

        $pacReverso = null;
        if ($dadosJson['acao'] === 'APROVADO') {
            if ($produtoFaturamentoItem['situacao'] !== 'disponivel') {
                throw new UnprocessableEntityHttpException(
                    "Produto: {$solicitacaoTroca['uuid_produto']} está com situacao {$produtoFaturamentoItem['situacao']}"
                );
            }

            $troca->idCliente = $solicitacaoTroca['id_cliente'];
            $troca->salvaAgendamento($produtoFaturamentoItem, [
                'uuid' => $solicitacaoTroca['uuid_produto'],
                'situacao' => 'defeito',
                'descricao_defeito' => $solicitacaoTroca['descricao_defeito'],
            ]);

            if (!empty($produtoFaturamentoItem['entrega_por_transportadora'])) {
                $pac = app(DefeitosRepository::class);
                $pacReverso = $pac->gerenciarPac(
                    $solicitacaoTroca['id_cliente'],
                    $produtoFaturamentoItem['transacao_origem']
                );
            }
        }

        DB::commit();
        TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(
            DB::getPdo(),
            $dadosJson['id_troca'],
            $produtoFaturamentoItem['origem'],
            $pacReverso
        );
    }
    public function buscaDetalhesTrocas(string $uuidProduto)
    {
        $resultado = EntregasDevolucoesItemServices::buscaDetalhesTrocas($uuidProduto);

        return $resultado;
    }

    public function reprovaPorFoto()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $jsonData = json_decode($this->json, true);
            Validador::validar($jsonData, [
                'id_troca' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'motivo' => [Validador::OBRIGATORIO],
                'origem' => [Validador::OBRIGATORIO, Validador::STRING],
            ]);

            $idTroca = $jsonData['id_troca'];
            $trocaFilaSolicitacoesService = new TrocaFilaSolicitacoesService();
            $trocaFilaSolicitacoesService->id = $idTroca;
            $trocaFilaSolicitacoesService->situacao = 'REPROVADA_POR_FOTO';
            $trocaFilaSolicitacoesService->motivo_reprovacao_foto = $jsonData['motivo'];
            $trocaFilaSolicitacoesService->atualizar($this->conexao);

            $this->retorno['data'] = true;
            $this->retorno['message'] = 'Troca reprovada com sucesso!';
            $this->codigoRetorno = 200;
            TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp($this->conexao, $idTroca, $jsonData['origem']);
            $this->conexao->commit();
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function listaTrocas(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $retorno = TrocaPendenteRepository::buscaTrocas($this->conexao, $dados['id_cliente']);

            foreach ($retorno as $key => $item) {
                $retorno[$key]['qrcode'] = Globals::geraQRCODE(
                    'produto/' . $item['id_produto'] . '?w=' . $item['uuid_produto']
                );
            }

            $this->resposta = $retorno;
        } catch (Exception $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_BAD_REQUEST;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function retirarDevolucaoComDefeito(string $uuidProduto)
    {
        $entregasDevolucoesItemModel = new EntregasDevolucoesItemModel();
        $entregasDevolucoesItemModel->exists = true;

        $entregasDevolucoesItemModel->uuid_produto = $uuidProduto;
        $entregasDevolucoesItemModel->situacao = 'PR';
        $entregasDevolucoesItemModel->saveOrFail();
    }
}
