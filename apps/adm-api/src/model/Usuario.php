<?php
namespace MobileStock\model;
use PDO;
use Exception;

class Usuario extends Pessoa implements ModelInterface
{
    public const VERIFICA_PERMISSAO_FORNECEDOR = '[[:<:]](3[0-9])[[:>:]]';
    public const VERIFICA_PERMISSAO_ENTREGADOR = '[[:<:]]62[[:>:]]';
    public const VERIFICA_PERMISSAO_ACESSO_APP_ENTREGAS = '20|30|50|51|52|53|54|55|56|57|58|59|60|62';
    public const VERIFICA_PERMISSAO_ACESSO_APP_INTERNO = '50|51|52|53|54|55|56|57|58|59';
    public $nome_tabela = 'usuarios';
    private $id;
    private $nome;
    private $senha;
    //private $email;
    private $nivel_acesso;
    private $id_colaborador;
    //private $bloqueado;
    private $online;
    private $acesso_nome;
    //private $cnpj;
    private $telefone;
    private $token;
    private $tipos;

    public function __construct(int $id, int $nivel_acesso, int $id_colaborador, int $bloqueado, int $online)
    {
        $this->id = $id;
        $this->nivel_acesso = $nivel_acesso;
        $this->id_colaborador = $id_colaborador;
        $this->bloqueado = $bloqueado;
        $this->online = $online;
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
     * Get the value of senha
     */
    public function getSenha()
    {
        return $this->senha;
    }

    /**
     * Set the value of senha
     *
     * @return  self
     */
    public function setSenha($senha)
    {
        $this->senha = $senha;

        return $this;
    }

    /**
     * Get the value of nivel_acesso
     */
    public function getNivel_acesso()
    {
        return $this->nivel_acesso;
    }

    /**
     * Set the value of nivel_acesso
     *
     * @return  self
     */
    public function setNivel_acesso($nivel_acesso)
    {
        $this->nivel_acesso = $nivel_acesso;

        return $this;
    }

    /**
     * Get the value of id_colaborador
     */
    public function getId_colaborador()
    {
        return $this->id_colaborador;
    }

    /**
     * Set the value of id_colaborador
     *
     * @return  self
     */
    public function setId_colaborador($id_colaborador)
    {
        $this->id_colaborador = $id_colaborador;

        return $this;
    }

    public function getOnline()
    {
        return $this->online;
    }

    /**
     * Set the value of online
     *
     * @return  self
     */
    public function setOnline($online)
    {
        $this->online = $online;

        return $this;
    }

    /**
     * Get the value of acesso_nome
     */
    public function getAcesso_nome()
    {
        return $this->acesso_nome;
    }

    /**
     * Set the value of acesso_nome
     *
     * @return  self
     */
    public function setAcesso_nome($acesso_nome)
    {
        $this->acesso_nome = $acesso_nome;

        return $this;
    }

    /**
     * Get the value of telefone
     */
    public function getTelefone()
    {
        return $this->telefone;
    }

    /**
     * Set the value of telefone
     *
     * @return  self
     */
    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;

        return $this;
    }

    /**
     * Get the value of token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the value of token
     *
     * @return  self
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public static function hidratar(array $dados): ModelInterface
    {
        if (empty($dados)) {
            throw new \InvalidArgumentException('Dados inválidos');
        }
        $classe = self::class;

        $notificacao = new self(1, 1, 1, 1, 1);

        foreach ($dados as $key => $dado) {
            $notificacao->$key = $dado;
        }

        return $notificacao;
    }

    public function extrair(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getTipos()
    {
        return $this->tipos;
    }

    /**
     * @param mixed $tipos
     */
    public function setTipos($tipos): void
    {
        $this->tipos = $tipos;
    }
}

?>
