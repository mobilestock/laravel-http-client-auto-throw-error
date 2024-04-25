<?php

namespace MobileStock\service\Fila;

use Aws\Sqs\SqsClient;

/**
 * @deprecated
 * https://laravel.com/docs/8.x/queues
 */
class FilaService
{
    protected SqsClient $sqsClient;
    public string $id;
    public array $conteudoArray;
    public string $url_fila;

    public function __construct(SqsClient $sqsClient)
    {
        $this->sqsClient = $sqsClient;
    }
    //    /**
    //     * @deprecated
    //     * @see \MobileStock\service\Pagamento\Filas\FilaPagamentosService::envia
    //     */
    //    public function salva(PDO $conexao): void
    //    {
    //        $gerador = new GeradorSql($this);
    //        $id = $this->id ?? 0;
    //        $sql = $id ? $gerador->update() : $gerador->insert();
    //
    //        $stmt = $conexao->prepare($sql);
    //        $stmt->execute($gerador->bind);
    //        if ($stmt->rowCount() !== 1) {
    //            throw new \DomainException('Não foi possivel atualizar o item da fila de pagamento.');
    //        }
    //
    //        if (!$id) {
    //            $this->id = $conexao->lastInsertId();
    //
    //            $conteudo = $this->conteudoArray;
    //            $conteudo['id'] = $this->id;
    //            $conteudo['QueueUrl'] = $this->url_fila;
    //
    //
    //            if (in_array($_ENV['AMBIENTE'], ['producao', 'homologado'])) {
    //                $jsonEncode = json_encode($conteudo);
    //                $hash = hash('sha256', $jsonEncode);
    //                $sqs = SqsClientFactory::default();
    //                $sqs->sendMessage([
    //                    'MessageBody' => $jsonEncode,
    //                    'QueueUrl' => $this->url_fila,
    //                    'MessageGroupId' => $hash,
    //                    'MessageDeduplicationId' => $hash,
    //                ]);
    //            } else {
    //                $filas = Filas::lista();
    //                $array = array_filter($filas, fn(array $fila) => $fila['QueueUrl'] === $this->url_fila);
    //                $itemFila = end($array);
    //                if (empty($itemFila)) {
    //                    throw new \DomainException("Não foi possivel processar o item na fila.");
    //                }
    //                if($conexao->inTransaction()) {
    //                    $conexao->commit();
    //                    $conexao->beginTransaction();
    //                }
    //                $process = Filas::executaProcesso($itemFila, $conteudo);
    //                $process->wait();
    //            }
    //        }
    //    }

    public function envia()
    {
        $this->conteudoArray['QueueUrl'] = $this->url_fila;
        $jsonEncode = json_encode($this->conteudoArray);

        $this->id = $this->sqsClient
            ->sendMessage([
                'MessageBody' => $jsonEncode,
                'QueueUrl' => $this->url_fila,
                'MessageGroupId' => (new \DateTimeImmutable())->format('dmyhm'),
                'MessageDeduplicationId' => uniqid(rand(), true),
            ])
            ->get('MessageId');

        $this->conteudoArray = [];
    }

    //    /**
    //     * @deprecated
    //     * @see \MobileStock\service\Pagamento\Filas\FilaPagamentosService::buscaResposta
    //     */
    //    public static function buscaItemFila(PDO $conexao, int $id): array
    //    {
    //        $stmt = $conexao->prepare(
    //            "SELECT
    //                fila_pagamentos.situacao,
    //                fila_pagamentos.resposta
    //            FROM fila_pagamentos WHERE fila_pagamentos.id = :id"
    //        );
    //        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    //        $stmt->execute();
    //        $itemFila = $stmt->fetch(\PDO::FETCH_ASSOC);
    //
    //        $itemFila['resposta'] = json_decode($itemFila['resposta'], true);
    //        return $itemFila;
    //    }

    //    public static function buscaResposta(string $idFila): ?array
    //    {
    //        $openSearchClient = new OpenSearchClient();
    //        $openSearchClient->get(
    //            "{$_ENV['OPENSEARCH']['INDEXES']['LOGS']}/_search",
    //            [
    //                'query' => [
    //                    'bool' => [
    //                        'must' => [
    //                            [
    //                                'match' => [
    //                                    'origem' => 'fila.respondida'
    //                                ]
    //                            ],
    //                            [
    //                                'match' => [
    //                                    'dados.id_fila' => $idFila
    //                                ]
    //                            ]
    //                        ]
    //                    ]
    //                ],
    //            ]);
    //
    //        $hits = $openSearchClient->body['hits']['hits'];
    //        if (!array_key_exists(0, $hits)) {
    //            return null;
    //        }
    //
    //        $retorno = $hits[0]['_source'];
    //        $retorno['dados'] = Arr::except($retorno['dados'], ['id_fila', 'url_fila']);
    //
    //        if (isset($retorno['dados']['exception'])) {
    //            unset($retorno['dados']['exception']);
    //        }
    //
    //        if (isset($retorno['dados']['error'])) {
    //            throw new HttpException($retorno['dados']['error']['code'], $retorno['dados']['message']);
    //        }
    //
    //        return $retorno['dados'];
    //    }
}
