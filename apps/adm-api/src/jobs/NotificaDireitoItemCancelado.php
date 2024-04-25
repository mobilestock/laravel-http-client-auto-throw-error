<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Gate;
use MobileStock\helper\Auth\QueueAuth;
use MobileStock\service\MessageService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;

class NotificaDireitoItemCancelado implements ShouldQueue
{
    use Queueable, QueueAuth;
    protected array $produtos;

    public function __construct(array $produtos)
    {
        $this->authKeys = ['permissao', 'id_colaborador'];
        $this->produtos = $produtos;
    }

    public function handle(MessageService $messageService): void
    {
        $produtos = TransacaoFinanceiraItemProdutoService::buscaInfoProdutoCancelamento($this->produtos);

        $gradesNotificadas = [];
        foreach ($produtos as $produto) {
            $identificadorGrade =
                $produto['id_produto'] . '_' . $produto['nome_tamanho'] . '_' . $produto['id_responsavel_estoque'];

            switch (true) {
                case $produto['sou_cliente']:
                    $messageService->sendImageWhatsApp(
                        $produto['fornecedor']['telefone'],
                        $produto['foto'],
                        "Cliente cancelou o produto {$produto['id_produto']}, pedido {$produto['id_transacao']}, tamanho {$produto['nome_tamanho']}."
                    );
                    break;
                case $produto['sou_responsavel_estoque']:
                    $messageService->sendImageWhatsApp(
                        $produto['telefone_cliente'],
                        $produto['foto'],
                        "O fornecedor {$produto['fornecedor']['razao_social']} cancelou a venda do produto acima.\n" .
                            'Já devolvemos o valor em sua conta Look Pay para que você possa escolher outro produto ou retirar o dinheiro para sua conta bancária.'
                    );
                    break;
                case Gate::allows('ADMIN') && !in_array($identificadorGrade, $gradesNotificadas):
                    $messageService->sendImageWhatsApp(
                        $produto['fornecedor']['telefone'],
                        $produto['foto'],
                        "Venda cancelada por atraso na entrega, zeramos o estoque do tamanho {$produto['nome_tamanho']} do produto {$produto['id_produto']} por segurança."
                    );
                    $messageService->sendImageWhatsApp(
                        $produto['telefone_cliente'],
                        $produto['foto'],
                        "O fornecedor {$produto['fornecedor']['razao_social']} não entregou o produto acima no prazo, então a sua compra foi cancelada.\n" .
                            'Já devolvemos o valor em sua conta Look Pay para que você possa escolher outro produto ou retirar o dinheiro para sua conta bancária.'
                    );
                    $gradesNotificadas[] = $identificadorGrade;
                    break;
            }

            if ($produto['afetou_reputacao']) {
                ProdutoService::insereAvisoSeller(
                    $produto['id_produto'],
                    $produto['id_responsavel_estoque'],
                    $produto['nome_tamanho']
                );
            }
        }
    }
}
