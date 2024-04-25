<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Illuminate\Http\Request;
use MobileStock\helper\Validador;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraLink;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraLinksService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;

class LinksPagamento extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = 2;
        parent::__construct();
    }

    public function credito(Request $request, PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $dadosJson = $request->all();

            Validador::validar($dadosJson, [
                'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_consumidor_final' => []
            ]);

            ['valor' => $valor] = $dadosJson;

            $transacao = new TransacaoFinanceiraService();
            $transacao->status = 'link';
            $transacao->origem_transacao = 'MP';
            $transacao->pagador = $this->idCliente;
            $transacao->removeTransacoesEmAberto($conexao);
            $transacao->id_usuario = $this->idUsuario;
            $transacao->valor_itens = $valor;
	        $transacao->metodos_pagamentos_disponiveis = ColaboradoresRepository::qtdPedidosEntregues(
                $conexao,
                $this->idCliente
            ) > 2 ? 'CA,PX' : 'PX';;
	        $transacao->criaTransacao($conexao);

            $adicionItem = new TransacaoFinanceiraItemProdutoService;
            $adicionItem->id_transacao = $transacao->id;
            $adicionItem->comissao_fornecedor = $transacao->valor_itens; 
            $adicionItem->preco = $transacao->valor_itens;
            $adicionItem->id_fornecedor = $transacao->pagador;
            $adicionItem->tipo_item = 'AC';
            $adicionItem->criaTransacaoItemProduto($conexao);
            $transacao->metodo_pagamento = 'CA';
            $transacao->numero_parcelas = 5;
            $transacao->calcularTransacao($conexao, 0);
            
            $link = new TransacaoFinanceiraLink($this->idCliente, $valor, $transacao->id);

            if (isset($dadosJson['nome_consumidor_final'])) {
                $link->nome_consumidor_final = $dadosJson['nome_consumidor_final'];
            }

            $service = new TransacaoFinanceiraLinksService($conexao, $link);
            $service->insere();

            $conexao->commit();

            return $link;
        } catch (\Throwable $exception) {
            $conexao->rollBack();

            throw $exception;
        }
    }
}