<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\MessageService;

class NotificaEntradaEstoque
{
    private int $idProduto;
    private array $grades;
    public function __construct(int $idProduto, array $grades)
    {
        $this->idProduto = $idProduto;
        $this->grades = $grades;
    }

    public function handle(MessageService $messageService)
    {
        foreach ($this->grades as $grade) {
            $produtosEstocados[] = [
                'id_produto' => $this->idProduto,
                'tamanho' => $grade['nome_tamanho'],
                'qtd_movimentado' => $grade['qtd_entrada'],
            ];
        }

        $listaColaboradoresNotificacao = EstoqueService::BuscaClientesComProdutosNaFilaDeEspera(
            DB::getPdo(),
            $produtosEstocados
        );

        foreach ($listaColaboradoresNotificacao as $colaborador) {
            $messageService->sendImageWhatsApp(
                $colaborador['telefone'],
                $colaborador['foto'],
                $colaborador['mensagem']
            );
        }
    }
}
