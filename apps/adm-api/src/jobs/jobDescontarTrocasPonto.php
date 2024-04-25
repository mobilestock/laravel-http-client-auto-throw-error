<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\EntregaService\EntregasDevolucoesServices;
use MobileStock\service\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{

    public function run(\PDO $conexao)
    {
        try {

            $conexao->beginTransaction();

            $devolucoes = new EntregasDevolucoesServices();
            $msgService = new MessageService();

            $trocasAtrasadasParaNotificar = EntregasDevolucoesItemServices::buscarTrocasPendentesAtrasadas($conexao);
            $trocasAtrasadasParaDescontar = EntregasDevolucoesItemServices::buscarTrocasPendentesAtrasadasParaDescontar($conexao);

            if (!empty($trocasAtrasadasParaNotificar)) {
                foreach ($trocasAtrasadasParaNotificar as $troca) {

                    $mensagem = "Olá *{$troca['ponto_coleta_nome']}*. A troca acima foi bipada";

                    if ($troca['usuario_bip_nome'] !== $troca['ponto_coleta_nome']) {
                        $mensagem .= " por *{$troca['usuario_bip_nome']}*,";
                    }

                    $mensagem .= " no dia {$troca['data_bipagem_troca']} e até o presente momento ";
                    $mensagem .= "não foi entrege em nossa central." . PHP_EOL . PHP_EOL;
                    $mensagem .= "O produto *{$troca['produto_nome']} [{$troca['produto_tamanho']}]* precisa ser entregue";
                    $mensagem .= " em nossa central até o dia *{$troca['data_limite']}*, ou então os valores relacionados";
                    $mensagem .= " ao produto e demais encargos serão descontados de sua conta Look Pay.";

                    $msgService->sendImageWhatsApp($troca['ponto_coleta_telefone'], $troca['produto_foto'], $mensagem);
                }
            }

            if (!empty($trocasAtrasadasParaDescontar)) {
                foreach ($trocasAtrasadasParaDescontar as $troca) {

                    $devolucoes->descontar(
                        $conexao,
                        $troca['situacao'],
                        $troca['uuid_produto'],
                        $troca['id_ponto_responsavel'],
                        'ADM',
                        'Ponto',
                        2,
                        $troca['id_transacao'],
                        $troca['id']
                    );

                }
            }

            $conexao->commit();
        } catch (\Throwable $exception) {
            $conexao->rollback();
            throw $exception;
        }
    }
};
