<?php /*

namespace MobileStock\model\TransacaoFinanceira\Pagamento\Fila;

/**
 * @property string $conteudo
 * @property string $url_fila
 * @property string $resposta
 * @property string $situacao
 * @property array $respostaArray
 * @property array $conteudoArray
 * @property int|string $id
 *//*
class FilaPagamentos
{
    public string $nome_tabela = 'fila_pagamentos';

    public function extrair(): array
    {
        $objectVars = get_object_vars($this);
        $extrair = [];

        foreach ($objectVars as $objectKey => $objectVar) {
            if ($objectKey === 'conteudoArray') {
                $hash = hash('sha256', json_encode($objectVar));
                unset($objectVar['holderName']);
                unset($objectVar['cardNumber']);
                unset($objectVar['secureCode']);
                unset($objectVar['expirationMonth']);
                unset($objectVar['expirationYear']);
                unset($objectVar['tokenCartao']);
                $objectVar['hash'] = $hash;
            }

            if (in_array($objectKey, ['situacao', 'id', 'conteudo', 'resposta', 'url_fila'])) {
                $extrair[$objectKey] = $objectVar;
            } elseif (in_array($objectKey, ['respostaArray', 'conteudoArray'])) {
                $extrair[str_replace('Array', '', $objectKey)] = json_encode($objectVar);
            }
        }

        return $extrair;
    }
}*/