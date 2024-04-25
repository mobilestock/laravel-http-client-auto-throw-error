<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property int $dias_pedido_chegar
 * @property bool $deve_recalcular_percentual
 * @property float $valor_custo_frete
 * @property float $porcentagem_frete
 *
 * @deprecated
 * @see Usar: MobileStock\model\PontoColetaModel
 */
class PontosColeta
{
    public string $nome_tabela = 'pontos_coleta';
    public function extrair(): array
    {
        $extraido = get_object_vars($this);

        return $extraido;
    }
}
