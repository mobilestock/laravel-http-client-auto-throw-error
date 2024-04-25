<?php

namespace MobileStock\model;

class Linha implements ModelInterface
{
    private $id;
    private $nome;
    private $icone_imagem;

    public static function hidratar(array $dados): self
    {
        $linha = new self();
        foreach ($dados as $parametro => $valor) {

            $linha->$parametro = $valor;
        }
        return $linha;
    }

    public function extrair(): array
    {
        return get_object_vars($this);
    }

    /**
     * Get the value of icone_imagem
     */
    public function getIcone_imagem()
    {
        return $this->icone_imagem;
    }

    /**
     * Set the value of icone_imagem
     *
     * @return  self
     */
    public function setIcone_imagem($icone_imagem)
    {
        $this->icone_imagem = $icone_imagem;

        return $this;
    }

    /**
     * Get the value of nome
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     * Set the value of nome
     *
     * @return  self
     */
    public function setNome($nome)
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
