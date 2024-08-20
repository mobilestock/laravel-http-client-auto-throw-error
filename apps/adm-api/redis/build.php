<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!($argv[0] ?? '')) {
    http_response_code(404);
    exit();
}

$redisAuth = env('REDIS_PASSWORD');
$arquivo = 'Dockerfile';
$conteudo = "FROM redis
RUN mkdir -p /usr/local/etc/redis
COPY redis.conf /usr/local/etc/redis/redis.conf
RUN echo \"\\nrequirepass {$redisAuth}\" >> /usr/local/etc/redis/redis.conf
CMD [ \"redis-server\", \"/usr/local/etc/redis/redis.conf\" ]
EXPOSE 6379";

file_put_contents($arquivo, $conteudo);
echo '[+] Pronto, Dockerfile gerado em: ' . getcwd() . DIRECTORY_SEPARATOR . $arquivo . "\n";
