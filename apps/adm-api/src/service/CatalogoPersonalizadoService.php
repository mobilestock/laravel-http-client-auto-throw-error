<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado;
use MobileStock\model\Origem;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @deprecated
 * @see Usar: MobileStock\model\CatalogoPersonalizadoModel
 */
class CatalogoPersonalizadoService extends CatalogoPersonalizado
{

    public function deletar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->deleteSemGetter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
    }
}
