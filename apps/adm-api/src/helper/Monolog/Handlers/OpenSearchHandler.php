<?php

namespace MobileStock\helper\Monolog\Handlers;

use DomainException;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class OpenSearchHandler extends AbstractProcessingHandler
{
    protected OpenSearchClient $opensearch;

    public function __construct(OpenSearchClient $client, $level = Logger::DEBUG, $bubble = true)
    {
        $this->opensearch = $client;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $title = $record['context']['title'] ?? $record['channel'];
        unset($record['formatted']['context']['title']);
        $this->opensearch->post(
            "{$_ENV['OPENSEARCH']['INDEXES']['LOGS']}/_doc",
            [
                'origem' => str_starts_with($title, 'fila') ? $title : 'adm.' . $title,
                'nivel' => mb_strtolower($record['level_name']),
                'dados' => array_merge(
                    $record['formatted']['message'] ? [
                        'message' => $record['formatted']['message'],
                    ] : [],
                    $record['formatted']['context']
                ),
                'data_criacao' => $record['datetime'],
            ]
        );

        if ($this->opensearch->codigoRetorno !== Response::HTTP_CREATED) {
            throw new DomainException('Não foi possivel inserir os logs no OpenSearch');
        }
    }
}