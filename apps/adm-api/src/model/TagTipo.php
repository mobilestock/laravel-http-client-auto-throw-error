<?php

namespace MobileStock\model;

class TagTipo implements ModelInterface
{
	public $nome_tabela = 'tags_tipos';
	private $id_tag;
	private $tipo;
	const TIPO_MATERIAL = 'MA';
	const TIPO_COR = 'CO';

	public function __construct(int $id_tag, string $tipo)
	{
		$this->id_tag = $id_tag;
		$this->tipo = $tipo;
	}

	public function getTipo(): string
	{
		return $this->tipo;
	}

	public function setTipo(string $tipo): self
	{
		$this->tipo = $tipo;
		return $this;
	}

	public function getIdTag(): int
	{
		return $this->id_tag;
	}

	public function setIdTag(int $id_tag): void
	{
		$this->id_tag = $id_tag;
	}

	public static function hidratar(array $dados): ModelInterface
	{
		$obj = new self(1);
		foreach($dados as $key => $dado) {
			$obj->$key = $dado;
		}
		return $obj;
	}

	public function extrair(): array
	{
		return [
			'id_tag' => $this->id_tag,
			'tipo' => $this->tipo
		];
	}
}