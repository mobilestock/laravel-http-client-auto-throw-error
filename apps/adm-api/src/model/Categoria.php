<?php

namespace MobileStock\model;

class Categoria implements ModelInterface
{
	public $nome_tabela = 'categorias';
    private $id;
    private $nome;
    private $mostrar_altura_salto;
	private $icone_imagem;
	private $id_categoria_pai;
	private $tags;

	public function __construct(string $nome,string $tags)
	{
		$this->nome = $nome;
		$this->tags = $tags;
	}

	public function getIdCategoriaPai(): int
	{
		return $this->id_categoria_pai;
	}

	public function setIdCategoriaPai(int $id_categoria_pai)
	{
		$this->id_categoria_pai = $id_categoria_pai;
		return $this;
	}

	public function getTags(): string
	{
		return $this->tags;
	}

	public function setTags(string $tags): self
	{
		$this->tags = $tags;
		return $this;
	}

    public static function hidratar(array $dados): self
    {
        $categoria = new self('', '');
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

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;

        return $this;
    }

    public function getMostrar_altura_salto()
    {
        return $this->mostrar_altura_salto;
    }

    public function setMostrar_altura_salto($mostrar_altura_salto)
    {
        $this->mostrar_altura_salto = $mostrar_altura_salto;

        return $this;
    }

    public function getIconeImagem()
    {
        return $this->icone_imagem;
    }

    public function setIconeImagem($icone_imagem)
    {
        $this->icone_imagem = $icone_imagem;

        return $this;
    }
}
