<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\Log;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(OpenSearchClient $opensearch): void
    {
        if ($_ENV['AMBIENTE'] !== 'producao') {
            return;
        }

        $opensearch->get("{$_ENV['OPENSEARCH']['INDEXES']['LOGS']}/_search", [
            'size' => 0,
            'aggs' => [
                'notificacoes' => [
                    'terms' => [
                        'field' => 'origem',
                    ],
                ],
            ],
            'query' => [
                'bool' => [
                    'filter' => [
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'match_phrase' => [
                                            'nivel' => 'error',
                                        ],
                                    ],
                                    [
                                        'match_phrase' => [
                                            'nivel' => 'warning',
                                        ],
                                    ],
                                    [
                                        'match_phrase' => [
                                            'nivel' => 'notice',
                                        ],
                                    ],
                                ],
                                'minimum_should_match' => 1,
                            ],
                        ],
                        [
                            'range' => [
                                'data_criacao' => [
                                    'gte' => 'now-1h',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        foreach ($opensearch->body['aggregations']['notificacoes']['buckets'] as $bucket) {
            Log::driver('telegram')->critical(
                "Ocorreram {$bucket['doc_count']} erro(s) com a origem {$bucket['key']} na ultima hora",
                [
                    'title' => preg_replace('/^adm-api\./', '', $bucket['key']),
                ]
            );
        }

        $opensearch->post("{$_ENV['OPENSEARCH']['INDEXES']['LOGS']}/_delete_by_query", [
            'query' => [
                'range' => [
                    'data_criacao' => [
                        'lte' => 'now-30d',
                    ],
                ],
            ],
        ]);

        if ($opensearch->codigoRetorno !== Response::HTTP_OK) {
            Log::withContext([
                'opensearch_http_client' => $opensearch,
            ]);
            throw new \DomainException('Não foi possível remover os logs do OpenSearch');
        }
    }
};
