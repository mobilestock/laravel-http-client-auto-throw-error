<?php

namespace MobileStock\jobs;

use Exception;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\ProdutosPontosService;
use PDO;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    protected array $middlewares = [
        SetLogLevel::class . ':' . LogLevel::CRITICAL,
    ];

    public function run(PDO $conexao)
    {
        try {
            $conexao->beginTransaction();
            $produtosPontosService = new ProdutosPontosService();
            $produtosPontosService->removeItensInvalidos($conexao);
            $produtosPontosService->geraNovosProdutos($conexao);
            $conexao->commit();
        } catch (Exception $exception) {
            $conexao->rollback();
            throw $exception;
        }

        $idsProdutosAtualizados = $produtosPontosService->atualizaDadosProdutos($conexao);
        if (!empty($idsProdutosAtualizados)) {
            $produtosPontosService->calcularTotalNormalizado($conexao);
            ProdutosRepository::atualizaDataQualquerAlteracao($conexao, $idsProdutosAtualizados);
        }
    }
};