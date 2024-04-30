<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use MobileStock\database\Conexao;
use MobileStock\service\Conferencia\ConferenciaItemService;

class Conferencia extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        $this->conexao = Conexao::criarConexao();
        parent::__construct();
    }

    public function buscaItensEntreguesCentral()
    {
        try {
            $this->retorno['data'] = ConferenciaItemService::buscaConferidosDoSeller(
                $this->conexao,
                $this->idColaborador
            );
            $this->retorno['message'] = 'Produtos consultados!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Falha ao buscar produtos.';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function itensDisponiveisParaAdicionarNaEntrega()
    {
        try {
            $pesquisa = (string) $this->request->get('pesquisa', '');
            $this->retorno['data'] = ConferenciaItemService::listaItensDisponiveisParaAdicionarNaEntrega(
                $this->conexao,
                $this->categoriaDoUsuario === 'ADM' ? 1 : $this->idColaborador,
                $pesquisa
            );

            $this->retorno['message'] = 'Pares encontrados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Erro ao buscar itens para conferência';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function descobrirItemParaEntrarNaConferencia(string $uuidProduto)
    {
        $lista = ConferenciaItemService::buscaDetalhesDoItem($uuidProduto);

        return $lista;
    }
}
