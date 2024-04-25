<?php

namespace MobileStock\model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * https://github.com/mobilestock/web/issues/2903
 *
 * @property int $id
 * @property int $id_colaborador
 * @property int $dias_pedido_chegar
 */
class PontoColetaModel extends Model
{
    public $timestamps = false;
    protected $table = 'pontos_coleta';
    protected $fillable = ['dias_pedido_chegar'];
    public static function buscaInformacoesPontoColeta(int $idColaborador): self
    {
        $pontoColeta = self::fromQuery(
            "SELECT
                pontos_coleta.id,
                pontos_coleta.id_colaborador,
                pontos_coleta.dias_pedido_chegar
            FROM pontos_coleta
            WHERE pontos_coleta.id_colaborador = $idColaborador"
        )->first();
        if (empty($pontoColeta)) {
            throw new NotFoundHttpException('Ponto de coleta não encontrado');
        }

        return $pontoColeta;
    }
}
