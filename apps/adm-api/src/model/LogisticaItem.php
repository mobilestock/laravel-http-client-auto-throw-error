<?php

namespace MobileStock\model;

use Exception;

/**
 * @property int    $id_cliente
 * @property int    $id_usuario
 * @property int    $id_produto
 * @property string $nome_tamanho
 * @property int    $id_transacao
 * @property string $situacao
 * @property string $uuid_produto
 * @property int    $id_colaborador_tipo_frete
 * @property int    $id_responsavel_estoque
 *
 * Propriedades não relacionadas ao banco de dados
 * @property string $origem
 *
 * @deprecated
 */
class LogisticaItem
{
    public string $nome_tabela = 'logistica_item';
    /**
     * Representa atualmente a situação 'CO' - Conferencia.
     * @deprecated
     * @see LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA
     */
    public const SITUACAO_FINAL_PROCESSO_LOGISTICA = 3;

    /**
     * @deprecated
     * @see LogisticaItemModel::converteSituacao()
     */
    public static function converteSituacao(string $situacao): string
    {
        $situacoes = [
            'PE' => 'Pendente',
            'SE' => 'Separado',
            'CO' => 'Conferido',
            'RE' => 'Rejeitado',
            'DE' => 'Devolução',
            'DF' => 'Defeito',
            'ES' => 'Estorno',
            'Pendente' => 'PE',
            'Separado' => 'SE',
            'Conferido' => 'CO',
            'Rejeitado' => 'RE',
            'Devolução' => 'DE',
            'Defeito' => 'DF',
            'Estorno' => 'ES',
        ];

        if (array_key_exists($situacao, $situacoes)) {
            return $situacoes[$situacao];
        } else {
            throw new Exception('Situacao invalido', 1);
        }
    }

    public function extrair(): array
    {
        $objectVars = get_object_vars($this);
        $extrair = [];

        foreach ($objectVars as $objectKey => $objectVar) {
            if (in_array($objectKey, ['situacao', 'id', 'uuid_produto'])) {
                $extrair[$objectKey] = $objectVar;
            }
        }

        return $extrair;
    }
}
