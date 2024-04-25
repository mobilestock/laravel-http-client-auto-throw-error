<?php

use MobileStock\helper\HttpClient;
use PHPUnit\Framework\Assert;

trait HttpClientTestTrait
{

    public $httpClient;
    protected function setUp(): void
    {
        $this->httpClient = new class extends HttpClient {
            public string $logArchiveName;
            public string $responseJson;
            protected function aposRequisicao(string $response, int $statusCode, array $headers): self
            {
                $jsonResponse = json_decode($response, true);
                if(str_contains($this->url, '?')) {
                    parse_str(explode('?', $this->url)[1], $queryParams);
                    foreach ($queryParams as $key => $value) {
                        Assert::assertArrayHasKey($key, $jsonResponse['data']['query'], 'Query params were not received ' . $this->method);
                        Assert::assertEquals($value, $jsonResponse['data']['query'][$key], 'Values should be different ' . $this->method);
                    }
                }
                foreach ($jsonResponse['headers'] as $key => $value) {
                    Assert::assertArrayHasKey($key, $jsonResponse['headers'], 'Headers params were not received ' .$this->method);
                    Assert::assertEquals($value, $jsonResponse['headers'][$key], 'Values should be different ' . $this->method);
                }
                if($jsonResponse['body']) {
                    foreach ($jsonResponse['body'] as $key => $value) {
                        Assert::assertArrayHasKey($key, $jsonResponse['body'], 'Body params were not received ' . $this->method);
                        Assert::assertEquals($value, $jsonResponse['body'][$key], 'Values should be different ' . $this->method);
                    }
                }

                $this->responseJson = $response;

                $fileName = __DIR__ . 'log\\' . $this->logArchiveName;
                $fileName = str_replace('\test', '', $fileName);
                $fileName = str_replace('traits', '', $fileName);

                Assert::assertFileExists($fileName);
                $fileContent = file_get_contents($fileName);

                $pattern = '';
                $pattern .= " LOGGER DEFAULT.INFO: {$this->method} | {$this->url}";
                $pattern .= " Corpo: [" . trim(preg_replace('/\s\s+/', '     ', $this->body)) . ", ";
                $pattern .= json_encode($this->headers);
                $pattern .= "] Resposta: $statusCode | ";
                $pattern .= "[{$this->responseJson}";
                $pattern .= ", " . json_encode($headers);
                $pattern .= '] [] []';
                $pattern = preg_quote($pattern, '\\');
                $pattern = '#' . '^\[' . date('d/m/Y') . ' \b([00-9]|[01-9][00-9])\b:\b([00-9]|[01-9][00-9])\b:\b([00-9]|[01-9][00-9])\b' . ']' . $pattern . '$#';
                preg_match_all($pattern, $fileContent, $matches, PREG_SET_ORDER, 0);
                Assert::assertStringContainsString($matches[0][0] ?? null, $fileContent, 'Log not found');

                unlink($fileName);

                return parent::aposRequisicao($response, $statusCode, $headers);
            }

            protected function nomeArquivoLog(): string
            {   
                $uuid = uniqid(rand(), true);
                $formatedUuid = preg_replace('/\D/', '', $uuid);
                return $this->logArchiveName = $formatedUuid . '_http_client_tests.log';
            }
        };
    }
}