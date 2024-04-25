<?php

namespace MobileStock\model;

class ProdutosCategorias implements ModelInterface
{
	public $nome_tabela = 'produtos_categorias';
	private $id;
	private $id_produto;
	private $id_categoria;

	public function __construct(int $id_produto,int $id_categoria)
	{
		$this->id_produto = $id_produto;
		$this->id_categoria = $id_categoria;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	public function getIdProduto(): int
	{
		return $this->id_produto;
	}

	public function setIdProduto(int $id_produto): self
	{
		$this->id_produto = $id_produto;
		return $this;
	}

	public function getIdCategoria(): int
	{
		return $this->id_categoria;
	}

	public function setIdCategoria(int $id_categoria): self
	{
		$this->id_categoria = $id_categoria;
		return $this;
	}

	public static function hidratar(array $dados): ModelInterface
	{
		$obj = new self(0,0);
		foreach($dados as $key => $dado) {
			$obj->$key = $dado;
		}
		return $obj;
	}

	public function extrair(): array
	{
		$getObjectVars = get_object_vars($this);
		unset($getObjectVars['id']);
		return $getObjectVars;
	}
}