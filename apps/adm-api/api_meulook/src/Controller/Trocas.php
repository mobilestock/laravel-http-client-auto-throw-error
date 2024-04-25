<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use DomainException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\Fila\FilaService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\MessageService;
use MobileStock\service\Troca\TrocasService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Trocas extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function insereTrocaAgendadaNormal(TrocasService $troca)
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'uuid_produto' => [Validador::OBRIGATORIO],
            'motivo_troca' => [Validador::NAO_NULO],
        ]);

        $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
            $dadosJson['uuid_produto'],
            Auth::user()->id_colaborador,
            false
        );
        if (empty($produtoFaturamentoItem)) {
            throw new NotFoundHttpException('Informações do produto não encontradas!');
        }

        if ($produtoFaturamentoItem['situacao'] !== 'disponivel') {
            throw new UnprocessableEntityHttpException(
                "Produto: {$dadosJson['uuid_produto']} está com situacao {$produtoFaturamentoItem['situacao']}"
            );
        }
        if ($produtoFaturamentoItem['situacao_entregas_faturamento_item'] !== 'EN') {
            EntregaServices::forcarEntregaDeProduto($dadosJson['uuid_produto']);

            $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
                $dadosJson['uuid_produto'],
                Auth::user()->id_colaborador,
                false
            );
        }
        if ($produtoFaturamentoItem['passou_prazo_troca_normal']) {
            throw new UnprocessableEntityHttpException(
                "Produto: {$dadosJson['uuid_produto']} já passou prazo troca normal"
            );
        }

        if (!empty($produtoFaturamentoItem['id_troca_fila_solicitacao'])) {
            TrocaPendenteRepository::removeTrocaAgendadadaMeuLook(DB::getPdo(), $dadosJson['uuid_produto']);
        }

        $troca->salvaAgendamento($produtoFaturamentoItem, [
            'uuid' => $dadosJson['uuid_produto'],
            'id_cliente' => Auth::user()->id_colaborador,
            'observacao' => $dadosJson['motivo_troca'],
        ]);

        $dadosAgendamento = $troca->buscaUUIDAgendamentoParaAlerta($dadosJson['uuid_produto']);
        $messageService = new MessageService();
        $messageService->sendMessageWhatsApp(
            $dadosAgendamento['telefone'],
            "*Devolução agendada com sucesso!*\n
                    Foi gerado um crédito no valor de " .
                $dadosAgendamento['preco'] .
                " para comprar um novo produto.\n
                    Utilize o crédito até a data limite de devolução: " .
                $dadosAgendamento['prazo']
        );

        DB::commit();
    }

    public function bipaTrocas(TrocasService $trocasService)
    {
        DB::beginTransaction();
        $uuids = FacadesRequest::all();

        Validador::validar(
            ['trocas' => $uuids],
            [
                'trocas' => [Validador::ARRAY, Validador::OBRIGATORIO],
            ]
        );

        $uuids = $trocasService->filtraSomenteTrocasValidas($uuids);

        if (empty($uuids)) {
            throw new DomainException('Nenhum produto válido para troca');
        }

        foreach ($uuids as $uuid) {
            $trocasService->pontoAceitaTroca($uuid);
            TrocasService::iniciaProcessoDevolucaoML($uuid);
        }
        DB::commit();
    }

    public function removeTrocaAgendada(PDO $conexao, string $uuidProduto)
    {
        try {
            $conexao->beginTransaction();
            if (TrocaPendenteRepository::trocaEstaConfirmada($conexao, $uuidProduto)) {
                throw new UnprocessableEntityHttpException('Não é possivel remover uma troca já confirmada');
            }
            TrocaPendenteRepository::removeTrocaAgendadadaMeuLook($conexao, $uuidProduto);

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }

    public function criaTransacaoEsqueciTrocasPedido(Request $request, FilaService $fila, Authenticatable $usuario)
    {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'id_pagador' => [Validador::NUMERO, Validador::OBRIGATORIO],
        ]);

        $fila->url_fila = $_ENV['SQS_ENDPOINTS']['APPENTREGAS_GERAR_PAGAMENTO_PIX'];
        $fila->conteudoArray = [
            'user' => [
                'id_colaborador' => $dadosJson['id_pagador'],
                'id' => $usuario->id,
            ],
        ];
        $fila->envia();

        return $fila->id;
    }

    public function informacaoSobreTrocaAgendada()
    {
        $resposta = TrocasService::buscaTrocasAgendadas();
        return $resposta;
    }

    public function buscaTransacoesEsqueciTroca(PDO $conexao, Authenticatable $usuario)
    {
        $transacoes = TrocasService::buscaTransacoesEsqueciTroca($conexao, $usuario->id_colaborador);
        return $transacoes;
    }
}
