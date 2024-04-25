<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\LogisticaItemService;

class EstoqueExterno extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();
    }

    public function buscaDetalhesProdutos()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'transacoes' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'id_responsavel_estoque' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            if (!$dadosJson['id_responsavel_estoque'] || $dadosJson['id_responsavel_estoque'] == 0) {
                $dadosJson['id_responsavel_estoque'] = $this->idCliente;
            }

            $this->retorno['data'] = ProdutosRepository::detalhesProdutos(
                $this->conexao,
                $dadosJson['transacoes'],
                $dadosJson['id_responsavel_estoque'],
                $this->idCliente
            );
            $this->retorno['message'] = 'Informações encontradas com sucesso';
        } catch (\Throwable $e) {
            $this->retorno = [
                'status' => false,
                'message' => $e->getMessage() ?: 'Falha ao encontrar produtos',
                'data' => [],
            ];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
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
        try {
            $dadosJson = [
                'pagina' => (int) $this->request->query->get('pagina', 1),
                'data' => $this->request->query->get('data', ''),
            ];

            Validador::validar($dadosJson, [
                'pagina' => [Validador::NUMERO],
            ]);

            $this->retorno['data'] = LogisticaItemService::buscaUltimosExternosVendidos(
                $this->conexao,
                $dadosJson['pagina'],
                $dadosJson['data']
            );
            $this->retorno['message'] = 'Produtos encontrados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['data'] = null;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}
