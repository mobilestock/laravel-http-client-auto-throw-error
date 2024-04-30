<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\database\Conexao;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaDevolucao;
use MobileStock\helper\Validador;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\EntregaService\EntregasDevolucoesServices;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\Item\DevolucaoAgendadaService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasProdutosTrocasService;
use MobileStock\service\Troca\TrocaPendenteCrud;
use MobileStock\service\Troca\TrocasService;
use MobileStock\service\TrocaFilaSolicitacoesService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class Devolucao extends Request_m
{
    private $conexao;

    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
        if ($this->categoriaDoUsuario === 'indefinido') {
            throw new Exception('Este usuario não é um ponto', 403);
        }
    }

    public function listaDevolucoesPonto(PDO $conexao, Request $request)
    {
        $pesquisa = $request->query->get('pesquisa') ?: '';

        $devolucao = new EntregasDevolucoesServices();

        $ehUuidProduto = !!preg_match('/[0-9]+_[0-9A-z]+.[0-9]+/', $pesquisa);

        $resultado = $devolucao->listaDevolucoesPonto($conexao, $pesquisa, $ehUuidProduto);

        if (!empty($resultado) && $ehUuidProduto) {
            if ($resultado[0]['situacao'] !== 'Pendente') {
                $data = new \DateTime($resultado[0]['data_atualizacao']);
                throw new UnprocessableEntityHttpException(
                    "Essa devolução consta como {$resultado[0]['situacao']} no dia {$data->format('d/m/Y H:i:s')}"
                );
            }

            $diaAtual = new \DateTime();
            $dataAtualizacao = new \DateTime($resultado[0]['data_criacao']);

            $diasAposBipagemPonto = $dataAtualizacao->diff($diaAtual)->days;

            if ($diasAposBipagemPonto >= 60) {
                $resultado[0]['tipo_problema'] = 'PRAZO_EXPIRADO_60';
                $resultado[0]['uuid_produto'] = $pesquisa;
                $resultado[0]['mensagem'] =
                    'Esse produto é de uma compra do meulook que já passou do prazo de 60 dias para que o ponto faça a devolução.';
            }
        }

        return $resultado;
    }

    public function descontarDevolucoes()
    {
        try {
            $this->conexao->beginTransaction();
            $devolucao = new EntregasDevolucoesServices();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'uuid_produto' => [Validador::OBRIGATORIO],
            ]);

            $dados = EntregasDevolucoesItemServices::buscarTrocasPendentesAtrasadasParaDescontar(
                $this->conexao,
                $dadosJson['uuid_produto']
            );
            if (empty($dados)) {
                throw new Exception('Não foi possível encontrar a devolução');
            }
            $dados = $dados[0];

            $devolucao->descontar(
                $this->conexao,
                $dados['situacao'],
                $dados['uuid_produto'],
                $dados['id_ponto_responsavel'],
                $this->categoriaDoUsuario,
                $dadosJson['descontar'] ?: 'Ponto',
                $this->idUsuario,
                $dados['id_transacao'],
                $dados['id']
            );
            $this->conexao->commit();
            $this->retorno['message'] = 'Devolucao concluida!';
            $this->retorno['status'] = true;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->retorno['message'] = $e->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function bipaDevolucao(EntregasDevolucoesServices $devolucao, string $uuidProduto)
    {
        DB::beginTransaction();

        $devolucao->bip($uuidProduto);

        DB::commit();
    }

    public function alteraTipoDeDevolucao()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_devolucao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $devolucao = new EntregasDevolucoesServices();
            $devolucao->mudaTipoDeDevolucao($this->conexao, $dadosJson['id_devolucao'], $this->idUsuario);

            $this->retorno['message'] = 'A devolucao foi alterada';
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\PDOException $exception) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['message'] = 'Erro ao recuperar lista';
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->retorno['message'] = 'Lista não encontrada';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function buscaRelacaoPontoDevolucoes()
    {
        $devolucao = new EntregasDevolucoesServices();
        $resultado = $devolucao->relacaoPontoDevolucoes();

        return $resultado;
    }

    public function geraPacReversoParaDevolucaoDePonto()
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'gerarEmTransito' => [Validador::SE(Validador::NAO_NULO, [Validador::BOOLEANO])],
        ]);

        $devolucao = new EntregasDevolucoesServices();

        [$resultado] = $devolucao->relacaoPontoDevolucoes($dadosJson['id_ponto']);

        $indice = $dadosJson['gerarEmTransito'] ? 'em_transito' : 'a_enviar';
        $produtos = $resultado['produtos'][$indice]['items'];
        $listaDeIds = array_unique(array_column($produtos, 'id_devolucao'));
        $resultado = $devolucao->geraPacReversoParaPontoDeEntrega($dadosJson['id_cliente']);
        $verificaStringErro = explode('Número do pedido ', $resultado['erro']);
        if (count($verificaStringErro) > 0 && $verificaStringErro[0] !== '') {
            $resultado['numero_coleta'] = $verificaStringErro[1];
        }

        if (mb_strlen($resultado['erro']) > 1 && count($verificaStringErro) > 0 && $verificaStringErro[0] !== '') {
            throw new Exception($resultado['erro'], 400);
        }

        $devolucao->salvaNumeroPacReversoNoPonto($listaDeIds, $resultado['numero_coleta']);

        DB::commit();

        return $resultado['numero_coleta'];
    }

    public function buscarProdutoSemAgendamento(string $uuidProduto)
    {
        $resultado = EntregasDevolucoesItemServices::buscarProdutoSemAgendamento(DB::getPdo(), $uuidProduto);

        TransacaoFinanceirasProdutosTrocasService::converteDebitoPendenteParaNormalSeNecessario(
            $resultado['id_cliente']
        );

        $devolucoesPendentesPonto = EntregasDevolucoesItemServices::buscarProdutosBipadosPontoPorCliente(
            $resultado['id_cliente'],
            $resultado['id_produto']
        );
        if (!empty($devolucoesPendentesPonto['pontos'])) {
            $resultado['devolucoes_pendentes'] = $devolucoesPendentesPonto;
            $quantidadeDevolucoes = count($resultado['devolucoes_pendentes']['pontos']);
            $resultado['tipo_problema'] = 'PRODUTO_PENDENTE_PONTO';
            $resultado['mensagem'] = 'Foi identificado que este cliente possui ';
            $resultado['mensagem'] .=
                $quantidadeDevolucoes > 1 ? $quantidadeDevolucoes . ' trocas pendentes' : 'uma troca pendente';
            $resultado['mensagem'] .= ' do mesmo produto.';
        }

        $idSolicitacaoTroca = TrocaFilaSolicitacoesService::buscaIdDaFilaDeTrocasPorUuid(DB::getPdo(), $uuidProduto);

        $agendado = TrocasService::verificaSeEstaAgendado($uuidProduto) || (bool) $idSolicitacaoTroca;

        if ($idSolicitacaoTroca > 0) {
            $solicitacaoDefeito = TrocaFilaSolicitacoesService::buscaSolicitacaoPorId(
                DB::getPdo(),
                $idSolicitacaoTroca
            );

            if (!in_array($solicitacaoDefeito['situacao'], ['APROVADO', 'CANCELADO_PELO_CLIENTE'])) {
                $resultado['tipo_problema'] = 'SOLICITACAO_PENDENTE';
                $resultado[
                    'mensagem'
                ] = "Esse produto pertence à uma devolução que não pode ser confirmada.\n\nSituação da devolução: {$solicitacaoDefeito['situacao']}";
                return $resultado;
            } elseif ($solicitacaoDefeito['situacao'] === 'CANCELADO_PELO_CLIENTE') {
                $agendado = false;
                unset($solicitacaoDefeito['descricao_defeito']);
            }

            $resultado['descricao_defeito'] = $solicitacaoDefeito['descricao_defeito'] ?? '';
        }

        $diasDisponiveisTroca = ConfiguracaoService::buscaAuxiliaresTroca($resultado['origem']);

        if ($resultado['origem'] === 'MS' && $resultado['situacao'] !== 'EN') {
            throw new BadRequestHttpException(
                'Esse produto é de uma compra do Mobile Stock que ainda não foi entregue'
            );
        }

        if (
            $resultado['origem'] === 'ML' &&
            !$agendado &&
            $resultado['dias_apos_entrega'] > $diasDisponiveisTroca['dias_normal']
        ) {
            $resultado['tipo_problema'] = "PRAZO_EXPIRADO_{$diasDisponiveisTroca['dias_normal']}";
            $resultado[
                'mensagem'
            ] = "Esse produto é de uma compra do meulook que foi entregue há mais de {$diasDisponiveisTroca['dias_normal']} dias.";
            return $resultado;
        }

        $resultado['data_base_troca'] = (new \DateTime($resultado['data_base_troca']))->format('d/m/Y H:i:s');

        $resultado['bloquear_defeito'] =
            ($resultado['origem'] === 'MS' &&
                $resultado['dias_apos_entrega'] > $diasDisponiveisTroca['dias_defeito']) ||
            !empty($resultado['descricao_defeito']);

        if (
            $resultado['origem'] === 'MS' &&
            $idSolicitacaoTroca > 0 &&
            $resultado['dias_apos_entrega'] > $diasDisponiveisTroca['dias_defeito']
        ) {
            if ($solicitacaoDefeito['situacao'] !== 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO') {
                $trocaFilaSolicitacoesService = new TrocaFilaSolicitacoesService();
                $trocaFilaSolicitacoesService->id = $idSolicitacaoTroca;
                $trocaFilaSolicitacoesService->situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO';
                $trocaFilaSolicitacoesService->motivo_reprovacao_seller = 'PASSOU DO PRAZO DE TROCA';
                $trocaFilaSolicitacoesService->atualizar(DB::getPdo());
            }

            $mensagemInterno = EntregasDevolucoesItemServices::notificarAtrasoDevolucaoMS(
                $resultado['id_cliente'],
                'DEFEITO',
                [
                    'uuid_produto' => $resultado['uuid_produto'],
                    'nome_produto' => $resultado['dados_produto']['nome_comercial'],
                    'nome_tamanho' => $resultado['nome_tamanho'],
                    'dias_troca' => $diasDisponiveisTroca['dias_defeito'],
                    'foto_produto' => $resultado['dados_produto']['produto_foto'],
                ]
            );

            throw new BadRequestHttpException($mensagemInterno);
        } elseif (
            $resultado['origem'] === 'MS' &&
            $resultado['dias_apos_entrega'] > $diasDisponiveisTroca['dias_normal']
        ) {
            $mensagemInterno = EntregasDevolucoesItemServices::notificarAtrasoDevolucaoMS(
                $resultado['id_cliente'],
                'NORMAL',
                [
                    'uuid_produto' => $resultado['uuid_produto'],
                    'nome_produto' => $resultado['dados_produto']['nome_comercial'],
                    'nome_tamanho' => $resultado['nome_tamanho'],
                    'dias_troca' => $diasDisponiveisTroca['dias_normal'],
                    'foto_produto' => $resultado['dados_produto']['produto_foto'],
                ]
            );

            throw new BadRequestHttpException($mensagemInterno);
        }

        return $resultado;
    }

    public function confirmarTrocaMobileStock(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();

            $dadosJson = $request->all();

            Validador::validar($dadosJson, [
                'uuid_produto' => [Validador::OBRIGATORIO],
                'defeito' => [Validador::BOOLEANO],
                'cliente_enviou_errado' => [Validador::BOOLEANO],
                'pac_indevido' => [Validador::BOOLEANO],
                'descricao_defeito' => [],
            ]);

            $produto = EntregasDevolucoesItemServices::buscarProdutoSemAgendamento(
                $conexao,
                $dadosJson['uuid_produto']
            );

            $produto['id_solicitacao_troca'] = TrocaFilaSolicitacoesService::buscaIdDaFilaDeTrocasPorUuid(
                $conexao,
                $dadosJson['uuid_produto']
            );

            if ((bool) $produto['id_solicitacao_troca']) {
                $solicitacao = TrocaFilaSolicitacoesService::buscaSolicitacaoPorId(
                    $conexao,
                    $produto['id_solicitacao_troca']
                );

                if (
                    !in_array($solicitacao['situacao'], [
                        'APROVADO',
                        'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO',
                        'CANCELADO_PELO_CLIENTE',
                    ])
                ) {
                    $dadosJson['cliente_enviou_errado'] = true;

                    $trocaFilaSolicitacoesService = new TrocaFilaSolicitacoesService();
                    $trocaFilaSolicitacoesService->id = $produto['id_solicitacao_troca'];
                    $trocaFilaSolicitacoesService->situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO';
                    $trocaFilaSolicitacoesService->motivo_reprovacao_seller =
                        'TROCA ENVIADA ANTES DA CONFIRMAÇÃO DO FORNECEDOR';
                    $trocaFilaSolicitacoesService->atualizar($conexao);
                }
            }

            DevolucaoAgendadaService::salvaProdutoTrocaAgendada(
                $conexao,
                $dadosJson['uuid_produto'],
                $produto['id_cliente']
            );

            $troca = new TrocaPendenteItem(
                $produto['id_cliente'],
                $produto['id_produto'],
                $produto['nome_tamanho'],
                $this->idUsuario,
                $produto['preco'],
                $produto['uuid_produto'],
                $produto['cod_barras'],
                $produto['data_base_troca']
            );

            $descricaoDefeito = !empty($dadosJson['descricao_defeito']) ? $dadosJson['descricao_defeito'] : '';

            $troca->setClienteEnviouErrado($dadosJson['cliente_enviou_errado']);
            $troca->setDefeito((bool) $dadosJson['defeito']);
            $troca->setDescricaoDefeito($descricaoDefeito);
            $troca->setAgendada(true);
            $troca->setPacIndevido((bool) $dadosJson['pac_indevido']);

            TrocaPendenteCrud::salva($troca, $conexao, false);
            TrocasService::condicaoSeDefeito($conexao, [
                'defeito' => $dadosJson['defeito'],
                'uuid' => $dadosJson['uuid_produto'],
            ]);
            TrocasService::iniciaProcessoDevolucaoMS(
                $conexao,
                $produto['uuid_produto'],
                $produto['id_transacao'],
                $produto['id_produto'],
                $produto['nome_tamanho'],
                $this->idUsuario
            );

            $conexao->commit();
        } catch (\Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }

    public function confirmarTrocaMeuLookSemAgendamento()
    {
        DB::beginTransaction();
        $dados = FacadesRequest::all();

        Validador::validar($dados, [
            'uuid_produto' => [Validador::OBRIGATORIO],
            'situacao' => [Validador::OBRIGATORIO],
            'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'defeito' => [Validador::BOOLEANO],
        ]);

        if ($dados['situacao'] !== 'EN') {
            EntregaServices::forcarEntregaDeProduto($dados['uuid_produto']);
        }

        $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
            $dados['uuid_produto'],
            $dados['id_cliente'],
            true
        );

        $trocasService = new TrocasService();
        $devolucao = new EntregasDevolucoesServices();

        $trocaAgendada = TrocasService::verificaSeEstaAgendado($dados['uuid_produto']);

        if (!$trocaAgendada) {
            $dados['situacao'] = $dados['defeito'] ? 'defeito' : 'devolucao';
            $dados['uuid'] = $dados['uuid_produto'];
            $trocasService->salvaAgendamento($produtoFaturamentoItem, $dados, true);
        }

        TrocasService::insereTrocaForcada(DB::getPdo(), $dados['id_cliente'], $dados['uuid_produto']);

        $trocasService->pontoAceitaTroca($dados['uuid_produto']);

        TrocasService::iniciaProcessoDevolucaoML($dados['uuid_produto']);

        $dados['id_solicitacao_troca'] = TrocaFilaSolicitacoesService::buscaIdDaFilaDeTrocasPorUuid(
            DB::getPdo(),
            $dados['uuid_produto']
        );

        $detalhesDaTroca = EntregasDevolucoesItemServices::buscaDetalhesTrocas($dados['uuid_produto']);

        if (!($detalhesDaTroca['tipo'] === 'NO' xor $dados['defeito'])) {
            $devolucao->mudaTipoDeDevolucao(DB::getPdo(), $detalhesDaTroca['id_devolucao'], Auth::id());
        }

        $devolucao->bip($dados['uuid_produto']);

        DB::commit();
    }

    public function gerarEtiquetaDevolucao(PDO $conexao, Request $request)
    {
        $dados = $request->all();

        Validador::validar($dados, [
            'uuid_produto' => [Validador::OBRIGATORIO],
            'tipo_problema' => [
                Validador::OBRIGATORIO,
                Validador::ENUM('PRAZO_EXPIRADO_7', 'PRAZO_EXPIRADO_60', 'SOLICITACAO_PENDENTE'),
            ],
        ]);

        $dadosConsulta = EntregasDevolucoesItemServices::buscarDadosEtiquetaDevolucao($conexao, $dados['uuid_produto']);

        $dataEtiqueta = '';

        switch ($dados['tipo_problema']) {
            case 'PRAZO_EXPIRADO_7':
                $dataEtiqueta = $dadosConsulta['data_entrega'];
                break;
            case 'PRAZO_EXPIRADO_60':
                $dataEtiqueta = $dadosConsulta['data_devolucao'];
                break;
        }

        $imagem = new ImagemEtiquetaDevolucao($dataEtiqueta, $dados['tipo_problema']);
        $final = $imagem->criarZpl();

        return $final;
    }

    public function listaDevolucoesQueNaoChegaramACentral(EntregasDevolucoesServices $devolucoes, int $idColaborador)
    {
        $resultado = $devolucoes->listaDevolucoesQueNaoChegaramACentral($idColaborador);
        return $resultado;
    }

    public function recebiProdutoDoEntregador(
        PDO $conexao,
        EntregasDevolucoesServices $devolucoes,
        Authenticatable $usuario,
        string $uuidProduto
    ) {
        $devolucoes->recebiProdutoDoEntregador($conexao, $uuidProduto, $usuario->id);
    }
}
