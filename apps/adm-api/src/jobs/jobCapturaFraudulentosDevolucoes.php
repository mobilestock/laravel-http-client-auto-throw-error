<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(\PDO $conexao)
    {
        try {
            $conexao->beginTransaction();
            $valorMinimo = ConfiguracaoService::buscaValorMinimoEntrarFraude($conexao);
            $colaboradoresService = new ColaboradoresService($conexao);

            $pontosFraudulentos = $colaboradoresService->buscaPossiveisPontosFraudulentos($valorMinimo);

            foreach ($pontosFraudulentos as $pontoFraudulento) {
                if ($pontoFraudulento['esta_na_fraude']) {
                    $colaboradoresService->id = $pontoFraudulento['id_colaborador'];
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->alteraSituacaoFraude($conexao, 'DEVOLUCAO');
                } else {
                    $colaboradoresService->id = $pontoFraudulento['id_colaborador'];
                    $colaboradoresService->origem_transacao = null;
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->insereFraude($conexao, 'DEVOLUCAO', $valorMinimo);
                }
            }

            $clientesFraudulentos = $colaboradoresService->buscaPossiveisFraudulentos($valorMinimo);

            foreach ($clientesFraudulentos as $clienteFraudulento) {
                if ($clienteFraudulento['esta_na_fraude']) {
                    $colaboradoresService->id = $clienteFraudulento['id_colaborador'];
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->alteraSituacaoFraude($conexao, 'DEVOLUCAO');
                } else {
                    $colaboradoresService->id = $clienteFraudulento['id_colaborador'];
                    $colaboradoresService->origem_transacao = null;
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->insereFraude($conexao, 'DEVOLUCAO', $valorMinimo);
                }
            }

            $possiveisFraudulentos = $colaboradoresService->buscaPossiveisFraudulentosSaldoNegativo(
                $conexao,
                $valorMinimo
            );

            foreach ($possiveisFraudulentos as $possivelFraudulento) {
                if ($possivelFraudulento['esta_na_fraude']) {
                    $colaboradoresService->id = $possivelFraudulento['id_colaborador'];
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->alteraSituacaoFraude($conexao, 'DEVOLUCAO');
                } else {
                    $colaboradoresService->id = $possivelFraudulento['id_colaborador'];
                    $colaboradoresService->origem_transacao = null;
                    $colaboradoresService->situacao_fraude = 'PE';
                    $colaboradoresService->insereFraude($conexao, 'DEVOLUCAO', $valorMinimo);
                }
            }
            $conexao->commit();
        } catch (Throwable $exception) {
            $conexao->rollBack();
            throw $exception;
        }
    }
};
