<?php

namespace MobileStock\model;

class Tag implements ModelInterface
{
	public $nome_tabela = 'tags';
	private $id;
	private $nome;

	public function __construct(string $nome)
	{
		$this->nome = $nome;
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

	public function getNome(): string
	{
		return $this->nome;
	}

	public function setNome(string $nome): self
	{
		$this->nome = $nome;
		return $this;
	}

	public static function hidratar(array $dados): ModelInterface
	{
		$categoria = new self();
		foreach ($dados as $parametro => $valor) {

			$categoria->$parametro = $valor;
		}
		return $categoria;
	}

	public function extrair(): array
	{
		$dados = array_filter(get_object_vars($this));
		unset($dados['nome_tabela']);
		return $dados;
	}
}