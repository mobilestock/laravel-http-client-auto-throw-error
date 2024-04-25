<?php

namespace api_pagamento\Controller;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\model\Lancamento;
use MobileStock\service\Fila\FilaRespostasService;
use MobileStock\service\Fila\FilaService;
use MobileStock\service\Lancamento\LancamentoCrud;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LinksPagamento
{
    public function linkPagamento(Request $request, PDO $conexao, FilaService $fila)
    {
        try {
            $conexao->beginTransaction();

            $token = $request->bearerToken();

            $idCliente = 55919;
            $idUsuario = 55859;
            $hashValida = hash('sha256', "api:$idCliente");

            if ($hashValida !== $token) {
                throw new UnauthorizedHttpException('Bearer', 'Token inválido');
            }

            $dadosJson = $request->all();

            Validador::validar($dadosJson, [
                'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_unico' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            ['valor' => $valor, 'id_unico' => $idUnico] = $dadosJson;

            $fila->conteudoArray = [
                'valor' => $valor,
                'id_unico' => $idUnico,
                'id_usuario' => $idUsuario,
                'id_cliente' => $idCliente,
            ];
            $fila->url_fila = $_ENV['SQS_ENDPOINTS']['PROCESSO_CRIAR_TRANSACAO_CREDITO'];
            $fila->envia();

            $conexao->commit();

            return [
                'status' => true,
                'message' => 'sucesso!',
                'data' => [
                    'id_fila' => $fila->id,
                ],
            ];
        } catch (\Throwable $e) {
            $conexao->rollBack();
            app(Dispatcher::class)->listen(function (RequestHandled $handled) {
                if ($handled->response instanceof JsonResponse) {
                    $handled->response->setData(
                        array_merge(json_decode($handled->response->getContent(), true), [
                            'status' => false,
                            'data' => [],
                        ])
                    );
                }
            });
            throw $e;
        }
    }

    public function infoFila(string $idFila, FilaRespostasService $filaRespostas)
    {
        $resposta = $filaRespostas->consulta($idFila);

        if (empty($resposta)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        return $resposta;
    }

    public function atualizaSaldoLookpay()
    {
        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $lancamento = new Lancamento(
            'P',
            1,
            'CM',
            $dadosJson['id_colaborador'],
            (new \DateTime())->format('Y-m-d H:i:s'),
            $dadosJson['valor'],
            12,
            1
        );
        $lancamento->documento = 7;
        $lancamento->documento_pagamento = 7;
        $lancamento->observacao = 'Crédito de saldo';
        LancamentoCrud::salva(DB::getPdo(), $lancamento);
    }
}
