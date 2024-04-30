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

    public function salvar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        $this->id = $conexao->lastInsertId();
    }

    public function editar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->update();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Nenhum dado foi alterado');
        }
    }

    public function deletar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->deleteSemGetter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
    }
}
