<?php

namespace MobileStock\model\Entrega;

use Exception;

class EntregasDevolucoesItem implements \JsonSerializable
{
    protected int $id;
    protected int $id_entrega;
	protected int $id_transacao;
	protected int $id_produto;
	protected int $id_ponto_responsavel;
	protected string $uuid_produto;
    protected string $situacao;
	protected string $tipo;
	protected string $situacao_envio;
	protected string $origem;
	protected string $pac_reverso;
	protected string $data_criacao;
	protected string $data_atualizacao;
    

    public function __set($campo, $valor)
    {
        if ($valor || $valor === "0") {
            $this->$campo = $valor;     
            switch ($campo) {
                case 'id':
                    $this->validaId($campo,$valor);
                    break;
                case 'id_produto':
                    $this->validaId($campo,$valor);
                    break;
                case 'id_entrega':
                    $this->validaId($campo,$valor);
                    break;
                case 'id_transacao':
                    $this->validaId($campo,$valor);
                    break;
                case 'id_ponto_responsavel':
                    $this->validaId($campo,$valor);
                    break;
                case 'pac_reverso':
                    $this->validaId($campo,$valor);
                    break;
                case 'data_atualizacao':
                    $this->$campo = date("Y-m-d H:i:s");
                    break;
                case 'situacao':
                    $this->converteSituacao();
                    break;
            }       
        }else{
            $this->$campo = null;
        }
    } 

    public function __get($campo) 
    {
        return $this->$campo;
    }
    protected function converteOrigem()
    {
        $origem = [
                        'Meu Look'=>'ML',
                        'Mobile Stock'=>'MS',
                        'ML'=>'ML',
                        'MS'=>'MS'];
        if (array_key_exists($this->origem, $origem)) {
            $this->origem = $origem[$this->origem];
        }else{
            throw new Exception("Tipo invalido", 400);
        }
    }
    protected function converteTipo()
    {
        $tipo = [
                    'Defeito'=>'DE',
                    'Normal'=>'NO',
                    'DE'=>'DE',
                    'NO'=>'NO'
                ];
        if (array_key_exists($this->tipo, $tipo)) {
            $this->tipo = $tipo[$this->tipo];
        }else{
            throw new Exception("Tipo invalido", 400);
        }
    }
    protected function converteSituacaoEnvio()
    {
        $tipoEnvio = [
                    'Ausente'=>'AU',
                    'Normal'=>'NO',
                    'AU'=>'AU',
                    'NO'=>'NO'
                ];
        if (array_key_exists($this->situacao_envio, $tipoEnvio)) {
            $this->situacao_envio = $tipoEnvio[$this->situacao_envio];
        }else{
            throw new Exception("Tipo invalido", 400);
        }
    }
    public function buscaNomeOrigem($origenParam = ""){
        $origen = [
            'Meu Look'=>'ML',
            'Mobile Stock'=>'MS',
            'MS'=>'Mobile Stock',
            'ML'=>'Meu Look'
        ];
        if($origenParam){
            return $origen[$origenParam];
        }
        return $origen[$this->origen];
    }
    public function buscaNomeTipo($tipoParam = '')
        $tipo = [
            'Defeito'=>'DE',
            'Normal'=>'NO',
            'DE'=>'Defeito',
            'NO'=>'Normal'
        ];
        if($tipoParam){
            return $tipo[$tipoParam];
        }
        return $tipo[$this->tipo];
    }
    public function buscaNomeSituacao($situacaoParam = ""){
        $situacao = [
            'Pendente'=>'PE',
            'Confirmado'=>'CO',
            'Rejeitado'=>'RE',
            'Vendido'=>'VE',
            'PE'=>'Pendente',
            'RE'=>'Rejeitado',
            'VE'=>'Vendido',
            'CO'=>'Confirmado'
        ];
        if($situacaoParam){
            return $situacao[$situacaoParam];
        }
        return $situacao[$this->situacao];
    }
    protected function converteSituacao()
    {
        $situacao = [
                        'Pendente'=>'PE',
                        'Confirmado'=>'CO',
                        'Rejeitado'=>'RE',
                        'Vendido'=>'VE',
                        'CO'=>'CO',
                        'RE'=>'RE',
                        'PE'=>'PE',
                        'VE'=>'VE',
                    ];
        if (array_key_exists($this->situacao, $situacao)) {
            $this->situacao = $situacao[$this->situacao];
        }else{
            throw new Exception("Situacao invalido", 400);
        }
    }

    protected function validaId($campo,$valor)
    {
        $this->$campo = intval($valor);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public static function ConversorSiglasEntregasDevolucoesItens(string $situacao,string $origem): array 
	{
		
		switch ($situacao) {
			case 'PE':
				$situacao = 'Pendente';
				break;
			case 'CO':
				$situacao = 'Confirmado';
				break;
			case 'RE':
				$situacao = 'Rejeitado';
				break;
            case 'VE':
                $situacao = 'Vendido';
                break;
		}

		switch ($origem) {
			case 'ML':
				$origem = 'Meu Look';
				break;
			case 'MS':
				$origem = 'Mobile Stock';
				break;
		}

		$resultado = ['origem' => $origem,'situacao' => $situacao];

		return $resultado;

	}
}
    