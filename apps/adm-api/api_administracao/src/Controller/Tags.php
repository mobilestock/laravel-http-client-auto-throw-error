<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\helper\ConversorStrings;
use MobileStock\model\Tag;
use MobileStock\repository\TagsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class Tags extends Request_m
{
	public function __construct()
	{
		parent::__construct();
		$this->respostaJson = new JsonResponse();
		$this->conexao = Conexao::criarConexao();
	}
	public function listarTagsTipos()
	{
		try {
			$this->retorno["data"] = TagsRepository::buscaTagsTipos($this->conexao);
			$this->retorno["message"] = "Tags tipo encontrados com sucesso!";
			$this->retorno["status"] = true;
			$this->codigoRetorno = 200;
		} catch (\Throwable $ex) {
			$this->retorno["status"] = false;
			$this->retorno["message"] = $ex->getMessage();
			$this->codigoRetorno = 400;
		} finally {
			$this->respostaJson
				->setData($this->retorno)
				->setStatusCode($this->codigoRetorno)
				->send();
		}
	}

	public function salva()
	{
		try {
			$this->conexao->beginTransaction();

			Validador::validar(['json' => $this->json], [
				'json' => [Validador::OBRIGATORIO, Validador::JSON]
			]);

			$dados = json_decode($this->json, true);
			Validador::validar($dados, [
				'nome' => [Validador::OBRIGATORIO, Validador::SANIZAR],
				'tipo' => [Validador::OBRIGATORIO, Validador::STRING]
			]);
			if (!in_array($dados["tipo"], array("CO", "MA"))) throw new Exception("Não existe esse tipo de tag");

			$dados["nome"] = ConversorStrings::sanitizeString($dados["nome"]);
			$conversorStrings = new ConversorStrings($dados);
			$dados = $conversorStrings->convertePrimeiraLetraMaiusculo();

			if ($dados["tipo"] === "CO") {
				$dados["nome"] = (string) preg_replace("/ /", "_", $dados["nome"]);
			}

			$tag = new Tag($dados['nome']);

			TagsRepository::salvaComTipo($this->conexao, $tag, $dados['tipo']);
			$this->retorno['message'] = 'Tag cadastrada com sucesso!';
			$this->retorno['data'] = $tag->extrair();
			$this->conexao->commit();
		} catch (\Throwable $ex) {
			$this->retorno['status'] = false;
			$this->retorno['message'] = $ex->getMessage();
			$this->codigoRetorno = 400;
			$this->conexao->rollBack();
		} finally {
			$this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
		}
	}

	public function removeTipo(array $dados)
	{
		try {
			$this->conexao->beginTransaction();

			Validador::validar($dados, [
				'id' => [Validador::OBRIGATORIO]
			]);

			TagsRepository::removeTiposTag($this->conexao, $dados['id']);

			$this->retorno['message'] = 'Tag removida com sucesso!';
			$this->conexao->commit();
		} catch (\Throwable $ex) {
			$this->conexao->rollBack();
			$this->retorno['status'] = false;
			$this->retorno['message'] = $ex->getMessage();
			$this->codigoRetorno = 400;
		} finally {
			$this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
		}
	}
}
