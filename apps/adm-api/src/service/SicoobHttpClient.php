<?php

namespace MobileStock\service;

use MobileStock\helper\HttpClient;
use MobileStock\service\Cache\CacheManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

class SicoobHttpClient extends HttpClient
{
    private AbstractAdapter $cache;
    private array $headersBkp;

    public function __construct(AbstractAdapter $cache)
    {
        $this->cache = $cache;
        $this->headersBkp = [];
    }

    public function nomeArquivoLog(): string
    {
        return 'logs_requisicoes_sicoob.log';
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function antesRequisicao(): HttpClient
    {
        $this->headersBkp = $this->headers;
        $this->certificado['caminho'] = __DIR__ . '/../../certificados/MS.pfx';
        $this->certificado['senha'] = $_ENV['SICOOB_SENHA_CERTIFICADO'];

        /** @var CacheItem $sicoobToken */
        $sicoobToken = $this->cache->getItem('sicoob_token');
        if (!str_contains($this->url, 'protocol/openid-connect/token') && $sicoobToken->isHit()) {
            $this->headers[] = 'Authorization: Bearer ' . $sicoobToken->get();
        }
        $this->headers[] = 'client_id: ' . $_ENV['SICOOB_CLIENT_ID'];

        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): HttpClient
    {
        if ($statusCode === 401) {
            $http = new self($this->cache);
            $http->post(
                'https://auth.sicoob.com.br/auth/realms/cooperado/protocol/openid-connect/token',
                [
                    'client_id' => $_ENV['SICOOB_CLIENT_ID'],
                    'grant_type' => 'client_credentials',
                    'scope' => implode(' ', [
                        'cob.write',
                        'cob.read',
                        'pix.write',
                        'pix.read',
                        'cobv.write',
                        'cobv.read',
                        'payloadlocation.write',
                        'payloadlocation.read',
                        'webhook.read',
                        'webhook.write',
                    ]),
                ],
                ['Content-Type: application/x-www-form-urlencoded']
            );

            $this->cache->delete('sicoob_token');
            $this->cache->get('sicoob_token', function (ItemInterface $itemCache) use ($http) {
                CacheManager::sobrescreveMergeByLifetime($this->cache);
                $itemCache->expiresAfter(60 * 60 * 24);

                return $http->body['access_token'];
            });

            $this->headers = $this->headersBkp;

            return $this->antesRequisicao()->envia();
        }

        return parent::aposRequisicao($response, $statusCode, $headers);
    }
}
