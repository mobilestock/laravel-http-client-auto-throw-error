<?php

use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\EntregaService\EntregasFilaProcessoAlterarEntregadorService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    protected array $middlewares = [
        SetLogLevel::class . ':' . LogLevel::CRITICAL,
    ];

    public function run(PDO $conexao)
    {
        try {
            $conexao->beginTransaction();
            $listaProdutos = EntregasFilaProcessoAlterarEntregadorService::listaProdutos($conexao);
            if (empty($listaProdutos)) return;

            foreach ($listaProdutos as $grupoProdutos) {
                $tabelas = [
                    'transacao_financeiras_produtos_itens',
                    'transacao_financeiras_metadados',
                    'lancamento_financeiro_pendente',
                    'logistica_item',
                ];

                // Tabelas específicas pro meulook.
                if ($grupoProdutos['origem'] === 'ML') {
                    $tabelas[] = 'pedido_item_meu_look';
                }

                foreach ($tabelas as $tabela) {
                    EntregasFilaProcessoAlterarEntregadorService::alterarEntregadorEmTabelas(
                        $conexao,
                        $tabela,
                        $grupoProdutos['lista_produtos'],
                        $grupoProdutos['id_colaborador_tipo_frete']
                    );
                }
            }

            $listaUUIDs = [];
            foreach ($listaProdutos as $produto) {
                $listaUUIDs = array_merge($listaUUIDs, $produto['lista_produtos']);
            }
            EntregasFilaProcessoAlterarEntregadorService::concluirFilaProcessoAlterarEntregador($conexao, $listaUUIDs);
            $conexao->commit();
        } catch (Throwable $exception) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $exception;
        }
    }
};
