<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\MessageService;

class NotificaEntradaEstoque
{
    protected array $grades;

    /**
     * @issue https://github.com/mobilestock/backend/issues/496
     */
    public function __construct(array $grades)
    {
        $this->grades = $grades;
    }

    public function handle(MessageService $messageService)
    {
        $produtosEstocados = array_map(
            fn(array $grade): array => [
                'id_produto' => $grade['id_produto'],
                'tamanho' => $grade['nome_tamanho'],
                'qtd_movimentado' => $grade['qtd_entrada'],
            ],
            $this->grades
        );

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
