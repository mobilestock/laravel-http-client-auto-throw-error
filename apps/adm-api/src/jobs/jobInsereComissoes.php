<?php

namespace MobileStock\jobs;

use Throwable;
use MobileStock\model\Lancamento;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\Lancamento\LancamentoService;
use MobileStock\service\Pagamento\LancamentoPendenteService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $lancamentosPendentes = LancamentoPendenteService::buscaLancamentosPendentes($conexao);

            $qtdLancamentosInseridos = count($lancamentosPendentes);

            $lancamentos = array_map(function (array $lancamento) {
                $lancamentoObj = new Lancamento(
                    $lancamento['tipo'],
                    1,
                    $lancamento['origem'],
                    $lancamento['id_colaborador'],
                    null,
                    $lancamento['valor'],
                    1,
                    7
                );

                $lancamentoObj->sequencia = $lancamento['id'];
                $lancamentoObj->valor_pago = 0;
                $lancamentoObj->tipo = $lancamento['tipo'];
                $lancamentoObj->documento = $lancamento['documento'];
                $lancamentoObj->situacao = $lancamento['situacao'];
                $lancamentoObj->origem = $lancamento['origem'];
                $lancamentoObj->id_colaborador = $lancamento['id_colaborador'];
                $lancamentoObj->valor = $lancamento['valor'];
                $lancamentoObj->valor_total = $lancamento['valor_total'];
                $lancamentoObj->id_usuario = $lancamento['id_usuario'];
                $lancamentoObj->id_usuario_pag = $lancamento['id_usuario_pag'];
                $lancamentoObj->observacao = $lancamento['observacao'];
                $lancamentoObj->tabela = $lancamento['tabela'];
                $lancamentoObj->pares = $lancamento['pares'];
                $lancamentoObj->transacao_origem = $lancamento['transacao_origem'];
                $lancamentoObj->cod_transacao = $lancamento['cod_transacao'];
                $lancamentoObj->bloqueado = $lancamento['bloqueado'];
                $lancamentoObj->id_split = $lancamento['id_split'];
                $lancamentoObj->parcelamento = $lancamento['parcelamento'];
                $lancamentoObj->juros = $lancamento['juros'];
                $lancamentoObj->numero_documento = $lancamento['numero_documento'];

                return $lancamentoObj;
            }, $lancamentosPendentes);

            LancamentoService::insereVarios($conexao, $lancamentos);

            if ($qtdLancamentosInseridos > 0) {
                $qtdLancamentosRemovidos = LancamentoPendenteService::removeLancamentos($conexao, array_column($lancamentosPendentes, 'id'));

                if ($qtdLancamentosRemovidos !== $qtdLancamentosInseridos) {
                    throw new \RuntimeException("Quantidade inconsistente de lançamentos alterados.");
                }
            }

            $conexao->commit();
        } catch (Throwable $exception) {
            $conexao->rollback();
            throw $exception;
        }
    }
};