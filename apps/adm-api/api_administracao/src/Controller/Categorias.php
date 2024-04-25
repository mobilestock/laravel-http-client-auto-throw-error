<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\helper\ConversorStrings;
use MobileStock\model\Categoria;
use MobileStock\model\Tag;
use MobileStock\repository\CategoriasRepository;
use MobileStock\repository\FotosRepository;
use MobileStock\repository\TagsRepository;

class Categorias extends Request_m
{
	public function listarCategorias(): void
	{
		$buscaArvoreCategorias = CategoriasRepository::buscaArvoreCategorias();
		$this->respostaJson->setData([
			'status' => true,
			'message' => 'Categorias buscadas com sucesso!',
			'data' => [
				'categorias' => $buscaArvoreCategorias,
				'tags' => TagsRepository::buscaTags()
			]
		])->setStatusCode(200)->send();
	}

	public function salva(): void
	{
		try {
			$this->conexao->beginTransaction();


			$dados = $this->request->request->all();
			Validador::validar($dados, [
				'id' => [Validador::NUMERO],
				'nome' => [Validador::OBRIGATORIO, Validador::STRING],
				'tags' => [Validador::JSON],
				'id_categoria_pai' => [Validador::NUMERO],
			]);
			$dados['tags'] = json_decode($dados['tags'], true);

			$conversorStrings = new ConversorStrings($dados);
			$dados = $conversorStrings->convertePrimeiraLetraMaiusculo();

			$tagsCadastrar = array_filter($dados['tags'], function ($tag) {
				return !is_numeric($tag);
			});

			foreach ($tagsCadastrar as $tag) {
				$dados['tags'] = array_filter($dados['tags'], function ($el) use ($tag) {
					return $el !== $tag;
				});

				$tag = ucfirst($tag);

				$tagObj = new Tag($tag);
				TagsRepository::salva($this->conexao, $tagObj);

				$dados['tags'][] = $tagObj->getId();
			}

			$tags = implode(',', $dados['tags']);
			$categoria = new Categoria($dados['nome'], $tags);
			$categoria->setIdCategoriaPai($dados['id_categoria_pai']);
			$categoria->setId($dados['id']);

			if ($_FILES['foto']) {
				$caminho = CategoriasRepository::insereFoto($_FILES['foto'], $categoria->getNome());
				$categoria->setIconeImagem($caminho);
			}

			CategoriasRepository::salva($this->conexao, $categoria);

			$this->retorno = [
				'status' => true,
				'message' => 'Categoria salva com sucesso!',
				'data' => $categoria->extrair()
			];
			$this->status = 200;
			$this->conexao->commit();
		} catch (\Throwable $ex) {
			$this->conexao->rollBack();
			$this->retorno = [
				'status' => false,
				'message' => $ex->getMessage(),
				'data' => []
			];
			$this->status = 400;
		} finally {
			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
		}
	}

	public function remove(array $dados): void
	{
		try {
			$this->conexao->beginTransaction();

			Validador::validar($dados, [
				'id' => [Validador::OBRIGATORIO]
			]);

			$categoria = Categoria::hidratar(['id' => $dados['id']]);
			CategoriasRepository::deleta($this->conexao, $categoria);

			$this->retorno = [
				'status' => true,
				'message' => 'Categoria removida com sucesso!',
				'data' => []
			];
			$this->status = 200;
			$this->conexao->commit();
		} catch (\Throwable $ex) {
			$this->conexao->rollBack();
			$this->retorno = [
				'status' => false,
				'message' => $ex->getMessage(),
				'data' => []
			];
			$this->status = 400;
		} finally {
			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
		}
	}

	public function listaCategoriasTipos(): void
	{
		try {
			$this->retorno['status'] = true;
			$this->retorno['message'] = 'Tipos das Categorias listadas com sucesso!';
			$this->retorno['data'] = CategoriasRepository::listaCategoriasTipos($this->conexao);
			$this->status = 200;
		} catch (\Throwable $ex) {
			$this->retorno['status'] = false;
			$this->retorno['message'] = $ex->getMessage();
			$this->retorno['data'] = [];
			$this->status = 400;
		} finally {
			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
		}
	}
}
