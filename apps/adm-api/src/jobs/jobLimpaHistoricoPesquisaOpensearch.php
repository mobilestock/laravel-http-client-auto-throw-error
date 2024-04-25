<?php

namespace MobileStock\jobs;

use Exception;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\OpenSearchService\OpenSearchClient;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        if ($_ENV['AMBIENTE'] !== 'producao') {
            return;
        }
        $opensearchClient = new OpenSearchClient();
        $resultados = $opensearchClient->limparHistoricoPesquisaAutocomplete();
        if ($resultados->codigoRetorno !== 200) {
            throw new Exception('Erro ao limpar histórico de pesquisa autocomplete: ' . json_encode($resultados->body));
        }
    }
};
