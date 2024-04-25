<?php

namespace MobileStock\model\TransacaoFinanceira;

/**
 * @property int $id_transacao
 * @property int $id_produto
 * @property int $id_fornecedor
 * @property int $id_responsavel_estoque
 * @property string $nome_tamanho
 * @property string $uuid_produto
 * @property string $tipo_item
 * @property float $preco
 * @property float $comissao_fornecedor
 * @property string $momento_pagamento
 */
class TransacaoFinanceiraProdutosItens
{
    public string $nome_tabela = 'transacao_financeiras_produtos_itens';

    /**
     * @var float
     * @deprecated NÃO UTILIZAR.
     */
    public float $valor_custo_produto;

    public function extrair(): array
    {
        $objectVars = get_object_vars($this);
        $extrair = [];

        foreach ($objectVars as $objectKey => $objectVar) {
            if (in_array(
                $objectKey,
                [
                    'id_transacao',
                    'id_produto',
                    'id_fornecedor',
                    'nome_tamanho',
                    'uuid_produto',
                    'tipo_item',
                    'preco',
                    'comissao_fornecedor',
                    'id_responsavel_estoque',
                    'momento_pagamento'
                ]
            )) {
                $extrair[$objectKey] = $objectVar;
            }
        }

        return $extrair;
    }
}
