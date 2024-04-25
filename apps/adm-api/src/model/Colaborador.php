<?php

namespace MobileStock\model;

use Exception;

/**
 * @deprecated
 * @see Usar: MobileStock\model\ColaboradorModel
 */
class Colaborador implements ModelInterface, \JsonSerializable
{
    private $id;
    private $regime;
    private $cpf;
    private $cnpj;
    private $razao_social;
    private $rg;
    private $telefone;
    private $telefone2;
    private $tipo;
    private $usuario;
    private $em_uso;
    private $emite_nota;
    private $contasBancarias;
    private $foto_perfil;
    private $usuario_meulook;
    private $id_tipo_entrega_padrao;
    private $nome_instagram;

    public function __construct(int $id = 0, int $regime = 0, int $bloqueado = 0, int $em_uso = 0, int $emite_nota = 0)
    {
        $this->id = $id;
        $this->regime = $regime;
        $this->bloqueado = $bloqueado;
        // $this->vendedor = $vendedor;
        // $this->tipo_documento = $tipo_documento;
        // $this->cond_pagamento = $cond_pagamento;
        $this->em_uso = $em_uso;
        $this->emite_nota = $emite_nota;
        // $this->tipo_pagamento_frete = $tipo_pagamento_frete;
        // $this->auto_cadastro = $auto_cadastro;
        // $this->total_pontos = $total_pontos;
        // $this->politica_empresa = $politica_empresa;
        // $this->contasBancarias = collect();
    }

    public function addContaBancaria(ContaBancaria $contaBancaria): self
    {
        $this->contasBancarias->add($contaBancaria);
        return $this;
    }

    public function removeContaBancaria(ContaBancaria $contaBancaria): self
    {
        $this->contasBancarias->splice($this->contasBancarias->search($contaBancaria));
        return $this;
    }
    /**
     * @return mixed
     */
    // public function getPercComissao()
    // {
    //     return $this->perc_comissao;
    // }

    public function getFotoPerfil()
    {
        return $this->foto_perfil;
    }

    public function setFotoPerfil($foto_perfil)
    {
        if (!is_null($foto_perfil)) {
            //     $resposta = ColaboradoresService::validaImagemExplicita($foto_perfil);

            //     if ($resposta['rating_index'] === 3) {
            //          ColaboradoresRepository::deletaFotoS3($this->foto_perfil);
            //          throw new \InvalidArgumentException('Imagem inválida');
            //      }

            //// apaga fto antiga
            //if ($this->foto_perfil)
            //    ColaboradoresRepository::deletaFotoS3($this->foto_perfil);
        }

        $this->foto_perfil = $foto_perfil;
    }

    // public function getMensagemLida()
    // {
    //     return $this->mensagem_lida;
    // }
    // public function setMensagemLida($mensagem_lida)
    // {
    //     $this->mensagem_lida = $mensagem_lida;
    // }
    /**
     * @param mixed $perc_comissao
     */
    // public function setPercComissao($perc_comissao): void
    // {
    //     $this->perc_comissao = $perc_comissao;
    // }

    // public function getAlteracao_dados()
    // {
    //     return $this->alteracao_dados;
    // }
    // public function setAlteracao_dados($alteracao_dados): void
    // {
    //     $this->alteracao_dados = $alteracao_dados;
    // }

    // /**
    //  * Get the value of id
    //  */
    // public function getId()
    // {
    //     return $this->id;
    // }

    // /**
    //  * Set the value of id
    //  *
    //  * @return  self
    //  */
    // public function setId($id)
    // {
    //     $this->id = $id;

    //     return $this;
    // }

    /**
     * Get the value of regime
     */
    public function getRegime()
    {
        return $this->regime;
    }

    /**
     * Set the value of regime
     *
     * @return  self
     */
    public function setRegime($regime)
    {
        $this->regime = $regime;

        return $this;
    }

    /**
     * Get the value of cpf
     */
    public function getCpf()
    {
        return $this->cpf;
    }

    /**
     * Set the value of cpf
     *
     * @return  self
     */
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;

        return $this;
    }

    /**
     * Get the value of razao_social
     */
    public function getRazao_social()
    {
        return $this->razao_social;
    }

    /**
     * Set the value of razao_social
     *
     * @return  self
     */
    public function setRazao_social($razao_social)
    {
        $this->razao_social = $razao_social;

        return $this;
    }

    /**
     * Get the value of inscricao
     */
    // public function getInscricao()
    // {
    //     return $this->inscricao;
    // }

    /**
     * Set the value of inscricao
     *
     * @return  self
     */
    // public function setInscricao($inscricao)
    // {
    //     $this->inscricao = $inscricao;

    //     return $this;
    // }

    /**
     * Get the value of rg
     */
    public function getRg()
    {
        return $this->rg;
    }

    /**
     * Set the value of rg
     *
     * @return  self
     */
    public function setRg($rg)
    {
        $this->rg = $rg;

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
     * Get the value of telefone2
     */
    public function getTelefone2()
    {
        return $this->telefone2;
    }

    /**
     * Set the value of telefone2
     *
     * @return  self
     */
    public function setTelefone2($telefone2)
    {
        $this->telefone2 = $telefone2;

        return $this;
    }

    /**
     * Get the value of observacao
     */
    // public function getObservacao()
    // {
    //     return $this->observacao;
    // }

    /**
     * Set the value of observacao
     *
     * @return  self
     */
    // public function setObservacao($observacao)
    // {
    //     $this->observacao = $observacao;

    //     return $this;
    // }

    /**
     * Get the value of tipo
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * Set the value of tipo
     *
     * @return  self
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;

        return $this;
    }

    /**
     * Get the value of vendedor
     */
    // public function getVendedor()
    // {
    //     return $this->vendedor;
    // }

    /**
     * Set the value of vendedor
     *
     * @return  self
     */
    // public function setVendedor($vendedor)
    // {
    //     $this->vendedor = $vendedor;

    //     return $this;
    // }

    /**
     * Get the value of tipo_tabela
     */
    // public function getTipo_tabela()
    // {
    //     return $this->tipo_tabela;
    // }

    /**
     * Set the value of tipo_tabela
     *
     * @return  self
     */
    // public function setTipo_tabela($tipo_tabela)
    // {
    //     $this->tipo_tabela = $tipo_tabela;

    //     return $this;
    // }

    /**
     * Get the value of tipo_documento
     */
    // public function getTipo_documento()
    // {
    //     return $this->tipo_documento;
    // }

    /**
     * Set the value of tipo_documento
     *
     * @return  self
     */
    // public function setTipo_documento($tipo_documento)
    // {
    //     $this->tipo_documento = $tipo_documento;

    //     return $this;
    // }

    /**
     * Get the value of cond_pagamento
     */
    // public function getCond_pagamento()
    // {
    //     return $this->cond_pagamento;
    // }

    /**
     * Set the value of cond_pagamento
     *
     * @return  self
     */
    // public function setCond_pagamento($cond_pagamento)
    // {
    //     $this->cond_pagamento = $cond_pagamento;

    //     return $this;
    // }

    /**
     * Get the value of usuario
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * Set the value of usuario
     *
     * @return  self
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get the value of em_uso
     */
    public function getEm_uso()
    {
        return $this->em_uso;
    }

    /**
     * Set the value of em_uso
     *
     * @return  self
     */
    public function setEm_uso($em_uso)
    {
        $this->em_uso = $em_uso;

        return $this;
    }

    /**
     * Get the value of emite_nota
     */
    public function getEmite_nota()
    {
        return $this->emite_nota;
    }

    /**
     * Set the value of emite_nota
     *
     * @return  self
     */
    public function setEmite_nota($emite_nota)
    {
        $this->emite_nota = $emite_nota;

        return $this;
    }

    /**
     * Coisas da tabela tipo_frete
     */
    public function getHorarioDeFuncionamento()
    {
        return $this->horarioDeFuncionamento;
    }
    public function setHorarioDeFuncionamento($horarioDeFuncionamento)
    {
        $this->horarioDeFuncionamento = $horarioDeFuncionamento;
        return $this;
    }

    public function getNomePonto()
    {
        return $this->nomePonto;
    }
    public function setNomePonto($nomePonto)
    {
        $this->nomePonto = $nomePonto;
        return $this;
    }

    /**
     * @return mixed
     */

    public function getCnpj()
    {
        return $this->cnpj;
    }

    /**
     * @param mixed $cnpj
     */
    public function setCnpj($cnpj): void
    {
        $this->cnpj = $cnpj;
    }
    public function getRazaoSocial()
    {
        return $this->razao_social;
    }

    /**
     * @param mixed $razao_social
     */
    public function setRazaoSocial($razao_social): void
    {
        $this->razao_social = $razao_social;
    }

    /**
     * @return mixed
     */
    public function getIncricao()
    {
        return $this->incricao;
    }

    /**
     * @param mixed $incricao
     */
    public function setIncricao($incricao): void
    {
        $this->incricao = $incricao;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getBloqueado()
    {
        return $this->bloqueado;
    }

    /**
     * @param mixed $bloqueado
     */
    public function setBloqueado($bloqueado): void
    {
        $this->bloqueado = $bloqueado;
    }

    /**
     * @return mixed
     */
    public function getEmUso()
    {
        return $this->em_uso;
    }

    /**
     * @param mixed $em_uso
     */
    public function setEmUso($em_uso): void
    {
        $this->em_uso = $em_uso;
    }

    /**
     * @return mixed
     */
    public function getEmiteNota()
    {
        return $this->emite_nota;
    }

    /**
     * @param mixed $emite_nota
     */
    public function setEmiteNota($emite_nota): void
    {
        $this->emite_nota = $emite_nota;
    }

    /**
     * @return mixed
     */
    public function getTipoEnvio()
    {
        return $this->tipo_envio;
    }

    /**
     * @param mixed $tipo_envio
     */
    public function setTipoEnvio($tipo_envio): void
    {
        $this->tipo_envio = $tipo_envio;
    }

    /**
     * @return mixed
     */
    public function getLinkRastreio()
    {
        return $this->link_rastreio;
    }

    /**
     * @param mixed $link_rastreio
     */
    public function setLinkRastreio($link_rastreio): void
    {
        $this->link_rastreio = $link_rastreio;
    }

    /**
     * @return mixed
     */
    // public function getTotalPontos()
    // {
    //     return $this->total_pontos;
    // }

    /**
     * @param mixed $total_pontos
     */
    // public function setTotalPontos($total_pontos): void
    // {
    //     $this->total_pontos = $total_pontos;
    // }

    /**
     * @return mixed
     */
    // public function getDataPainelIlimitado()
    // {
    //     return $this->data_painel_ilimitado;
    // }

    public function setUsuarioMeulook($usuario_meulook)
    {
        return $this->usuario_meulook = $usuario_meulook;
    }

    public function getUsuarioMeuLook()
    {
        return $this->usuario_meulook;
    }

    public function setIdTipoEntregaPadrao(int $tipoEntrega)
    {
        $this->id_tipo_entrega_padrao = $tipoEntrega;
    }

    public function getIdTipoEntregaPadrao()
    {
        return $this->id_tipo_entrega_padrao;
    }

    public function getNomeInstagram()
    {
        return $this->nome_instagram;
    }

    public function setNomeInstagram(string $nomeInstagram)
    {
        $this->nome_instagram = $nomeInstagram;
    }

    /**
     * Colaborador constructor.
     * @param $regime
     * @param $cnpj
     * @param $cpf
     * @param $razao_social
     * @param $incricao
     * @param $rg
     * @param $cep
     * @param $telefone
     * @param $tipo
     */
    public static function buscaListaDeAnos()
    {
        return [2020, 2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028, 2029, 2030];
    }

    public static function buscaListaDeMeses()
    {
        return $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro',
        ];
    }

    public static function hidratar(array $dados): ModelInterface
    {
        if (empty($dados)) {
            throw new \InvalidArgumentException('Dados inválidos');
        }
        $colaborador = new self();
        foreach ($dados as $key => $dado) {
            $colaborador->$key = $dado;
        }
        return $colaborador;
    }

    public function extrair(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
    public static function converteTipoEmbalagem(string $tipoEmbalagem): string
    {
        $tiposDeEmbalagem = [
            'CA' => 'Caixa',
            'SA' => 'Sacola',
            'Caixa' => 'CA',
            'Sacola' => 'SA',
        ];
        if (!array_key_exists($tipoEmbalagem, $tiposDeEmbalagem)) {
            throw new Exception('Tipo de embalagem inválido');
        }

        return $tiposDeEmbalagem[$tipoEmbalagem];
    }
}
