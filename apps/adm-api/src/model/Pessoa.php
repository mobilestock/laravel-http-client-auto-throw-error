<?php 
namespace MobileStock\model;
use PDO;
use Exception;

abstract class Pessoa
{

    private $email;
    private $cnpj;
    private $bloqueado;
    

    public function __construct(string $email, int $cnpj, int $bloqueado)
    {
        $this->cnpj = $cnpj;
        $this->email = $email;
        $this->bloqueado =$bloqueado;
    }


    /**
     * Get the value of email
     */ 
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of cnpj
     */ 
    public function getCnpj()
    {
        return $this->cnpj;
    }

    /**
     * Set the value of cnpj
     *
     * @return  self
     */ 
    public function setCnpj($cnpj)
    {
        $this->cnpj = $cnpj;

        return $this;
    }

    /**
     * Get the value of bloqueado
     */ 
    public function getBloqueado()
    {
        return $this->bloqueado;
    }

    /**
     * Set the value of bloqueado
     *
     * @return  self
     */ 
    public function setBloqueado($bloqueado)
    {
        $this->bloqueado = $bloqueado;

        return $this;
    }
}

?>
