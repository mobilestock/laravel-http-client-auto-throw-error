<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\ColaboradoresService;

class EstoqueExterno extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();
    }

    public function buscaProdutosFornecedor()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'transacoes' => [Validador::OBRIGATORIO, Validador::JSON],
            'id_responsavel_estoque' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $dadosJson['transacoes'] = json_decode($dadosJson['transacoes'], true);
        $produtos = LogisticaItemModel::buscaProdutosResponsavelTransacoes(
            $dadosJson['id_responsavel_estoque'],
            $dadosJson['transacoes']
        );

        return $produtos;
    }
    public function listaFornecedores(int $pagina)
    {
        $fornecedores = ColaboradoresService::buscaSellerExterno($pagina);

        return $fornecedores;
    }
    public function buscaDetalhesSeller($dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_responsavel_estoque' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            $this->retorno['data'] = ColaboradoresService::buscaPedidosSeller(
                $this->conexao,
                $dadosJson['id_responsavel_estoque']
            );
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
        } catch (\Throwable $e) {
            $this->retorno['message'] = $e->getMessage() ?: 'Falha ao buscar detalhes do seller';
            $this->retorno['data'] = null;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function monitoramentoVendidos()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'data' => [Validador::SE(Validador::OBRIGATORIO, [Validador::DATA])],
        ]);

        $retorno = LogisticaItemModel::buscaUltimosExternosVendidos($dadosJson['pagina'], $dadosJson['data']);

        return $retorno;
    }
}
