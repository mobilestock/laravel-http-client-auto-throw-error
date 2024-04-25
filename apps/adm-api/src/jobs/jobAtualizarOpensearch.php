<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\Log;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use MobileStock\service\ProdutoService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    public function run()
    {
        try {
            if ($_ENV['AMBIENTE'] !== 'producao') {
                return;
            }

            ConfiguracaoService::buscaTravaJobAtualizarOpensearch();

            $indexPesquisa = $_ENV['OPENSEARCH']['INDEXES']['PESQUISA'];
            $opensearchClient = new OpenSearchClient();
            $resultadoGet = $opensearchClient->get("$indexPesquisa/_search", [
                'size' => 1,
                'sort' => [['timestamp' => ['order' => 'desc']]],
            ]);
            $timestamp = $resultadoGet->body['hits']['hits'][0]['_source']['timestamp'] ?? 0;

            $limit = 100;
            $offset = 0;
            $novoTimestamp = date('c', time());
            while (!isset($produtos) || sizeof($produtos) === $limit) {
                $produtos = ProdutoService::buscaProdutosAtualizarOpensearch($timestamp, $limit, $offset);

                if (sizeof($produtos) === 0) {
                    break;
                }

                $body = '';
                foreach ($produtos as $produto) {
                    $idProduto = $produto['id_produto'];
                    $produto['timestamp'] = $novoTimestamp;
                    $body .= json_encode(['index' => ['_id' => $idProduto]]) . "\n";
                    $body .= json_encode($produto) . "\n";
                }
                $opensearchClient->post("$indexPesquisa/_bulk", $body, ['Content-Type: application/json']);

                if ($opensearchClient->codigoRetorno !== 200) {
                    Log::withContext([
                        'opensearch_response' => $opensearchClient,
                    ]);
                    throw new \Exception('Erro ao atualizar produtos no OpenSearch');
                }

                $offset += $limit;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
};
