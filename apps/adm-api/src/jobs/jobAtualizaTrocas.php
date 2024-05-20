<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ExceptionHandler;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\DefeitosRepository;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\Troca\TrocasService;
use MobileStock\service\TrocaFilaSolicitacoesService;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(ExceptionHandler $exceptionHandler)
    {
        $erros = [];

        $trocasDeletar = TrocaFilaSolicitacoesService::buscaTrocasParaDeletar();
        foreach ($trocasDeletar as $troca) {
            try {
                DB::beginTransaction();
                TrocaPendenteRepository::removeTrocaAgendadaMeuLook($troca['uuid']);
                DB::commit();
            } catch (Throwable $exception) {
                DB::rollBack();
                $erros[] = $exception;
            }
        }
        try {
            DB::beginTransaction();
            TrocaPendenteRepository::removeTrocaPendenteAgendamentoMobileStock();
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            $erros[] = $exception;
        }

        $solicitacoesAprovar = TrocaFilaSolicitacoesService::buscaSolicitacoesParaAprovar();
        foreach ($solicitacoesAprovar as $solicitacao) {
            try {
                DB::beginTransaction();

                $id = $solicitacao['id'];
                $solicitacaoTroca = new TrocaFilaSolicitacoesService();
                $solicitacaoTroca->id = $id;
                $solicitacaoTroca->situacao = 'APROVADO';
                $solicitacaoTroca->atualizar(DB::getPdo());

                $uuid = $solicitacao['uuid_produto'];
                $idCliente = $solicitacao['id_cliente'];
                $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca($uuid, $idCliente, true);

                if (empty($produtoFaturamentoItem)) {
                    throw new \InvalidArgumentException("Produto: {$uuid} não existe");
                }
                if ($produtoFaturamentoItem['situacao'] !== 'disponivel') {
                    DB::rollBack();
                    continue;
                }

                $trocasService = new TrocasService();
                $trocasService->idCliente = $idCliente;
                $trocasService->salvaAgendamento($produtoFaturamentoItem, [
                    'uuid' => $uuid,
                    'situacao' => 'defeito',
                    'descricao_defeito' => $solicitacao['descricao_defeito'],
                ]);

                $pacReverso = null;
                if (!empty($produtoFaturamentoItem['entrega_por_transportadora'])) {
                    $pac = app(DefeitosRepository::class);
                    $pacReverso = $pac->gerenciarPac($idCliente, $produtoFaturamentoItem['transacao_origem']);
                }

                TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(
                    DB::getPdo(),
                    $id,
                    $produtoFaturamentoItem['origem'],
                    $pacReverso
                );
                DB::commit();
            } catch (Throwable $exception) {
                DB::rollBack();
                $erros[] = $exception;
            }
        }

        $trocasAExpirarFoto = TrocaFilaSolicitacoesService::buscaTrocasPraExpirarFoto();
        foreach ($trocasAExpirarFoto as $value) {
            try {
                DB::beginTransaction();
                $model = new TrocaFilaSolicitacoesService();
                $model->id = $value['id'];
                $model->situacao = 'REPROVADO';
                $model->atualizar(DB::getPdo());
                DB::commit();
            } catch (Throwable $exception) {
                DB::rollBack();
                $erros[] = $exception;
            }
        }

        foreach ($erros as $erro) {
            $exceptionHandler->report($erro);
        }
    }
};
