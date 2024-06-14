<?php

namespace MobileStock\model\TransacaoFinanceira;

use Exception;

/**
 * @property int $id
 * @property int $id_transacao
 * @property string $chave
 * @property string $valor
 */
class TransacaoFinanceirasMetadados
{
    public string $nome_tabela = 'transacao_financeiras_metadados';

    public function __set($key, $value)
    {
        if ($key === 'chave') {
            $chaveMetadado = $value;
            if (isset($this->valor)) {
                $valorMetadado = $this->valor;
            }
        } elseif ($key === 'valor') {
            if (isset($this->chave)) {
                $chaveMetadado = $this->chave;
            }
            $valorMetadado = $value;
        }

        if (isset($chaveMetadado, $valorMetadado)) {
            switch ($chaveMetadado) {
                case 'ID_COLABORADOR_TIPO_FRETE':
                    $this->valor = (int) $valorMetadado;
                    break;
                case 'ENDERECO_CLIENTE_JSON':
                    $this->valor = json_encode($valorMetadado, JSON_UNESCAPED_UNICODE);
                    break;
                case 'ENDERECO_COLETA_JSON':
                    $this->valor = json_encode($valorMetadado, JSON_UNESCAPED_UNICODE);
                    break;
                case 'PRODUTOS_JSON':
                    $this->valor = json_encode($valorMetadado, JSON_UNESCAPED_UNICODE);
                    break;
                case 'VALOR_FRETE':
                    $this->valor = (float) $valorMetadado;
                    break;
                case 'ID_PEDIDO':
                case 'ID_UNICO':
                    $this->valor = (string) $valorMetadado;
                    break;
                case 'PRODUTOS_TROCA':
                    $this->valor = json_encode($valorMetadado);
                    break;
                default:
                    throw new Exception('Chave de transação financeira metadado inválida!');
            }

            if ($key === 'chave') {
                $this->chave = $chaveMetadado;
            }
        } else {
            $this->$key = $value;
        }
    }

    public function extrair()
    {
        return [
            'id' => $this->id ?? '',
            'id_transacao' => $this->id_transacao ?? '',
            'chave' => $this->chave ?? '',
            'valor' => $this->valor ?? '',
        ];
    }
}
