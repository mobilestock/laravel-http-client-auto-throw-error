<?php

namespace MobileStock\model\TransacaoFinanceira;

use Exception;

/**
 * @property int    $id
 * @property float  $valor_liquido
 * @property float  $valor_credito
 * @property float  $valor_acrescimo
 * @property float  $valor_desconto
 * @property float  $valor_itens
 * @property float  $valor_taxas
 * @property float  $valor_comissao_fornecedor
 * @property float  $valor_total
 * @property float  $juros_pago_split
 * @property int    $numero_parcelas
 * @property string $metodo_pagamento
 * @property string $metodos_pagamentos_disponiveis
 * @property string $status
 * @property int    $pagador
 * @property int    $id_usuario
 * @property array  $dados_cartao
 * @property string $url_boleto
 * @property string $barcode
 * @property string $qrcode_pix
 * @property string $qrcode_text_pix
 * @property string $cod_transacao
 * @property string $url_fatura
 * @property string $origem_transacao
 * @property string $emissor_transacao
 */
class TransacaoFinanceira implements \JsonSerializable
{
    /**
     * @deprecated
     */
	protected $numero_transacao;
    /**
     * @deprecated
     */
	protected $responsavel;
    /**
     * @deprecated
     */
    protected $id_usuario_pagamento;
    /**
     * @deprecated
     */
    protected $id_zoop_pagador;
    /**
     * @deprecated
     */
    protected $razao_social;

    public function __construct()
    {
        $this->responsavel = 1;
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0" || $atrib === 'valor_liquido') {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'status':
                    $this->converteSituacao();
                    break;
                case 'id':
                    $this->validaId();
                    break;
                case 'valor_liquido':
                    $this->validaValorLiquido();
                    break;

            }
        }else{
            $this->$atrib = null;
        }
    }

//    public function __get($atrib)
//    {
//        return $this->$atrib;
//    }

//    protected function converteMetodoPagamento()
//    {
//        $situacao = [
//                        'boleto' =>'BL',
//                        'cartao' =>'CA',
//                        'dinheiro' =>'DE',
//                        'pix' => 'PX',
//                        'BL' =>'BL',
//                        'CA' =>'CA',
//                        'DE' =>'DE',
//                        'PX' => 'PX'];
//        if (array_key_exists($this->metodo_pagamento, $situacao)) {
//            $this->metodo_pagamento = $situacao[$this->metodo_pagamento];
//        }else{
//            throw new Exception("Meio de pagamento invalido", 1);
//        }
//    }

    protected function converteSituacao()
    {
        $situacao = [
                        'new'=>'CR',
                        'link' => 'LK',
                        'pre_authorized'=>'PR',
                        'succeeded'=>'PA',
                        'pending'=>'PE',
                        'failed'=>'FL',
                        'reversed'=>'RV',
                        'dispute'=>'DS',
                        'charged_back'=>'CB',
                        'paid'=>'PA', 
                        'canceled'=>'CA', 
                        'refunded'=>'RE',
                        'partially_paid'=>'PP',
                        'authorized'=>'AT',
                        'in_protest'=>'IP',
                        'chargeback'=>'CH',
                        'expired' => 'CA',
                        'CR'=>'CR',
                        'PR'=>'PR',
                        'PA'=>'PA',
                        'PE'=>'PE',
                        'FL'=>'FL',
                        'RV'=>'RV',
                        'DS'=>'DS',
                        'CB'=>'CB',
                        'CA'=>'CA',
                        'RE'=>'RE',
                        'PP'=>'PP',
                        'AT'=>'AT',
                        'IP'=>'IP',
                        'ES'=>'ES',
                        'CH'=>'CH',
                        'LK'=>'LK'
        ];
        if (array_key_exists($this->status, $situacao)) {
            $this->status = $situacao[$this->status];
        }else{
            throw new Exception("Situacao invalido", 1);
        }
    }
    protected function validaId()
    {
        $this->id = intval($this->id);
    }

    private function validaValorLiquido()
    {
        $this->valor_liquido = (float) $this->valor_liquido;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);

        unset($vars['dados_cartao']);
        return $vars;
    }

    public function extrair(): array
    {
        $objectVars = get_object_vars($this);
        $extrair = [];

        foreach ($objectVars as $objectKey => $objectVar) {
            if (in_array($objectKey, [
                'metodos_pagamentos_disponiveis',
                'status',
                'valor_liquido',
                'emissor_transacao',
                'cod_transacao',
                'valor_taxas',
                'qrcode_pix',
                'qrcode_text_pix',
                'url_fatura',
                'url_boleto',
                'barcode',
                'id'
            ])) {
                $extrair[$objectKey] = $objectVar;
            }
        }

        return $extrair;
    }
}