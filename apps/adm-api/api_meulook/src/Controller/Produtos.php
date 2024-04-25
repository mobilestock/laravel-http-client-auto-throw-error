<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\service\AvaliacaoProdutosService;
use MobileStock\service\ProdutosListaDesejosService;

class Produtos extends Request_m
{
  public function __construct()
  {
    $this->nivelAcesso = '1';
    parent::__construct();
  }

  public function avaliacoesPendentes()
  {
    try {
      $this->retorno['data']['avaliacoes'] = AvaliacaoProdutosService::buscaAvaliacoesPendentesColaborador(
        $this->conexao,
        $this->idCliente
      );
      $this->retorno['message'] = 'Avaliações buscadas com sucesso!';
      $this->status = 200;

    } catch (\PDOException $ex) {
      $this->retorno['status'] = false;
      $this->retorno['message'] = ConversorStrings::trataRetornoBanco($ex->getMessage());
      $this->status = 400;

    } catch (\Throwable $ex) {
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->status = 500;
    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    }
  }

  public function adiarAvaliacao($dados)
  {
    try {
      $this->conexao->beginTransaction();
      Validador::validar($dados, ['id_avaliacao' => [Validador::NUMERO]]);
      $this->retorno['data'] = AvaliacaoProdutosService::adiarAvaliacaoPendente(
        $this->conexao,
        $this->idCliente,
        $dados['id_avaliacao']
      );
      $this->retorno['message'] = 'Avaliação adiada com sucesso!';
      $this->status = 200;
      $this->conexao->commit();

    } catch (\PDOException $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = ConversorStrings::trataRetornoBanco($ex->getMessage());
      $this->status = 400;

    } catch (\Throwable $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->status = 500;
    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    }
  }

  public function avaliarProduto()
  {
    try {
      $this->conexao->beginTransaction();
      Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
      $dados = json_decode($this->json, true);
      Validador::validar($dados, [
        'id_avaliacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
        'nota' => [Validador::OBRIGATORIO, Validador::NUMERO],
        'comentario' => []
      ]);
      $this->retorno['data'] = AvaliacaoProdutosService::avaliarProduto(
        $this->conexao,
        $this->idCliente,
        $dados['id_avaliacao'],
        $dados['nota'],
        $dados['comentario'] ?? ''
      );
      $this->retorno['message'] = 'Produto avaliado com sucesso!';
      $this->status = 200;
      $this->conexao->commit();

    } catch (\PDOException $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = ConversorStrings::trataRetornoBanco($ex->getMessage());
      $this->status = 400;

    } catch (\Throwable $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->status = 500;
    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    }
  }

  public function deletaAvaliacao(array $dados)
  {
    try {
      $this->conexao->beginTransaction();
      Validador::validar($dados, ['id_avaliacao' => [Validador::NUMERO]]);
      $this->retorno['data'] = AvaliacaoProdutosService::deletaAvaliacao(
        $this->conexao,
        $this->idCliente,
        $dados['id_avaliacao']
      );
      $this->retorno['message'] = 'Avaliação deletada com sucesso!';
      $this->status = 200;
      $this->conexao->commit();

    } catch (\PDOException $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = ConversorStrings::trataRetornoBanco($ex->getMessage());
      $this->status = 400;

    } catch (\Throwable $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->status = 500;

    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    }
  }

  public function buscaListaDesejos()
  {
    try {
      $this->retorno['data']['produtos'] = ProdutosListaDesejosService::buscaListaDesejos($this->conexao, $this->idCliente);
      $this->retorno['message'] = 'Produtos buscados com sucesso';
      $this->codigoRetorno = 200;
    } catch (\Throwable $ex) {
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->codigoRetorno = 500;
    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    }
  }

  public function alternaProdutoListaDesejo(array $dados)
  {
    try {
      $this->conexao->beginTransaction();
      Validador::validar($dados, ['id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO]]);
      $idProduto = $dados['id_produto'];
      $existeRegistro = ProdutosListaDesejosService::buscaRegistro($this->conexao, $this->idCliente, $idProduto);
      $produtosListaDesejosService = new ProdutosListaDesejosService();
      $complementoMensagem = '';
      if ($existeRegistro) {
        $produtosListaDesejosService->id = $existeRegistro['id'];
        $produtosListaDesejosService->deletar($this->conexao);
        $complementoMensagem = 'removido da';
      } else {
        $produtosListaDesejosService->id_colaborador = $this->idCliente;
        $produtosListaDesejosService->id_produto = $idProduto;
        $produtosListaDesejosService->salva($this->conexao);
        $complementoMensagem = 'adicionado à';
      }
      $this->conexao->commit();
      $this->retorno['data'] = [];
      $this->retorno['message'] = "Produto $complementoMensagem lista de desejos";
      $this->codigoRetorno = 200;
    } catch (\PDOException $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = ConversorStrings::trataRetornoBanco($ex->getMessage());
      $this->codigoRetorno = 400;
    } catch (\Throwable $ex) {
      $this->conexao->rollBack();
      $this->retorno['status'] = false;
      $this->retorno['message'] = $ex->getMessage();
      $this->codigoRetorno = 500;
    } finally {
      $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    }
  }

}