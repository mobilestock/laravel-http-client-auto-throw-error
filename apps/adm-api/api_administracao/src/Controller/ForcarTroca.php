<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\Troca\TrocaPendenteCrud;
use MobileStock\service\Troca\TrocasService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ForcarTroca
{
    public function forcaTroca()
    {
        // Faz a separação entre Mobile Stock e Meu Look.
        DB::beginTransaction();
        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'uuid' => [Validador::OBRIGATORIO],
            'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $meuLook = LogisticaItemService::ehMeuLook(DB::getPdo(), $dadosJson['uuid']);

        if ($meuLook) {
            $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
                $dadosJson['uuid'],
                $dadosJson['id_cliente'],
                true
            );

            $trocasService = new TrocasService();
            if (empty($produtoFaturamentoItem)) {
                //Continua
                throw new UnprocessableEntityHttpException('O produto ainda está no ponto');
            }

            if ($produtoFaturamentoItem['situacao'] !== 'disponivel') {
                throw new UnprocessableEntityHttpException(
                    "Produto: {$dadosJson['uuid']} está com situacao {$produtoFaturamentoItem['situacao']}"
                );
            }

            $dadosJson['situacao'] = 'devolucao';
            $trocasService->salvaAgendamento($produtoFaturamentoItem, $dadosJson, true);
            // Troca agendada.

            TrocasService::insereTrocaForcada(DB::getPdo(), $dadosJson['id_cliente'], $dadosJson['uuid']);
            // Insere na tabela transacao_financeiras_produtos_trocas.

            $trocasService->pontoAceitaTroca($dadosJson['uuid']);
            // Atualiza a situação de pendente para paga

            TrocasService::forcaProcessoDevolucaoML(
                DB::getPdo(),
                $dadosJson['uuid'],
                $produtoFaturamentoItem['transacao_origem'],
                $produtoFaturamentoItem['id_produto'],
                $produtoFaturamentoItem['nome_tamanho'],
                Auth::id()
            );
            // Insere na tabela entregas_devolucoes_item como "CO".
        } else {
            $produto = EntregasFaturamentoItemService::consultaInfoProdutoTrocaMS(DB::getPdo(), $dadosJson['uuid']);

            $troca = new TrocaPendenteItem(
                (int) $produto['id_cliente'],
                (int) $produto['id_produto'],
                (string) $produto['nome_tamanho'],
                Auth::id(),
                (float) $produto['preco'],
                $produto['uuid_produto'],
                $produto['cod_barras'],
                $produto['data_base_troca']
            );

            $entregas = new EntregasDevolucoesItemServices();
            $entregas->tipo = 'NO';

            TrocaPendenteCrud::salva($troca, DB::getPdo(), true);
            // Insere na table troca_pendente_item.
            TrocasService::forcaProcessoDevolucaoMS(
                DB::getPdo(),
                $dadosJson['uuid'],
                $produto['id_transacao'],
                $produto['id_produto'],
                $produto['nome_tamanho'],
                Auth::id()
            );
        }

        DB::commit();
    }
}
