<?php
#'producao', 'teste', 'local', 'savio', 'roberto', 'guilherme'

#  id_produtos_frete => [82042 ~> padrão, 82044 ~> expresso, 99265 ~> volume_expresso]
$_ENV['AMBIENTE'] = 'local';

$opensearch = 'producao';
$banco_selecionado = 'banco_local';

if ($_ENV['AMBIENTE'] == 'teste') {
    $_ENV['MYSQL_HOST'] = 'mobilestock-homolog.cwlisnj4go4t.sa-east-1.rds.amazonaws.com';
    $_ENV['MYSQL_DB_NAME'] = $banco_selecionado;
    $_ENV['MYSQL_USER'] = 'admin';
    $_ENV['MYSQL_PASSOWORD'] = 'BAIxnUpdoOHahsUIlgwk';
    $_ENV['MYSQL_USER_SUPER'] = 'admin';
    $_ENV['MYSQL_PASSOWORD_SUPER'] = 'BAIxnUpdoOHahsUIlgwk';
}
if ($_ENV['AMBIENTE'] == 'local') {
    $_ENV['MYSQL_HOST'] = '192.168.0.57';
    $_ENV['MYSQL_DB_NAME'] = $banco_selecionado;
    $_ENV['MYSQL_USER'] = 'root';
    $_ENV['MYSQL_PASSOWORD'] = '';
    $_ENV['MYSQL_USER_SUPER'] = 'root';
    $_ENV['MYSQL_PASSOWORD_SUPER'] = '';
}
if ($_ENV['AMBIENTE'] == 'savio') {
    $_ENV['MYSQL_HOST'] = '192.168.0.159';
    $_ENV['MYSQL_DB_NAME'] = $banco_selecionado;
    $_ENV['MYSQL_USER'] = 'root';
    $_ENV['MYSQL_PASSOWORD'] = '';
    $_ENV['MYSQL_USER_SUPER'] = 'root';
    $_ENV['MYSQL_PASSOWORD_SUPER'] = '';
}

if ($_ENV['AMBIENTE'] == 'roberto') {
    $_ENV['MYSQL_HOST'] = '192.168.0.200';
    $_ENV['MYSQL_DB_NAME'] = $banco_selecionado;
    $_ENV['MYSQL_USER'] = 'root';
    $_ENV['MYSQL_PASSOWORD'] = '';
    $_ENV['MYSQL_USER_SUPER'] = 'root';
    $_ENV['MYSQL_PASSOWORD_SUPER'] = '';
}
if ($_ENV['AMBIENTE'] == 'guilherme') {
    $_ENV['MYSQL_HOST'] = '192.168.0.240';
    $_ENV['MYSQL_DB_NAME'] = $banco_selecionado;
    $_ENV['MYSQL_USER'] = 'root';
    $_ENV['MYSQL_PASSOWORD'] = '';
    $_ENV['MYSQL_USER_SUPER'] = 'root';
    $_ENV['MYSQL_PASSOWORD_SUPER'] = '';
}
$_ENV['TELEFONE_WHATSAPP_TESTE'] = '5537991185674';
$_ENV['SECRET_TOKEN_WHATSAPP'] = 'ZzhC0PHzrS9dcoeU3H';
$_ENV['TENSORFLOW_ENDPOINT'] = 'http://artbella.ddns.net:8282/';
$_ENV['SICOOB_SENHA_CERTIFICADO'] = 'MOBILE2020*';
$_ENV['SICOOB_CLIENT_ID'] = '66824f2a-524a-4767-af89-49ed0372bae3';
$_ENV['SICOOB_CHAVE_PIX'] = '37360647000134';
$_ENV['GOOGLE_TOKEN_PUBLICO'] = 'AIzaSyDbbBwYLokd5eF0O85LdDCaWOgzQztMPiI';

# Define se usar opensearch da produção ou local
if ($opensearch == 'producao') {
    $_ENV['OPENSEARCH'] = [
        'AUTH' => 'Basic bWV1bG9vazpHTXlkR1gyTVBpd20kXl4zJTdoNVZNWloheVBzZ05zd00hcg==',
        'ENDPOINT' => 'https://search-meulook-search-ds7kzc5pchwexjmhayvas6lfpy.sa-east-1.es.amazonaws.com/',
        'INDEXES' => [
            'PESQUISA' => 'meulook_produtos',
            'AUTOCOMPLETE' => 'meulook_autocomplete',
            'LOGS' => 'logs',
        ],
    ];
} else {
    $_ENV['OPENSEARCH'] = [
        'AUTH' => '',
        'ENDPOINT' => 'http://192.168.0.57:9200/',
        'INDEXES' => [
            'PESQUISA' => 'meulook_produtos',
            'AUTOCOMPLETE' => 'meulook_autocomplete',
        ],
    ];
}

$_ENV['EMAIL_PAGAMENTO_TESTE'] = 'teste@teste.com';
$_ENV['OPTIONS'] = [
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_STATEMENT_CLASS => [MobileStock\database\PDOStatement::class],
];

$_ENV['URL_MOBILE'] = 'http://192.168.0.57:8008/';
$_ENV['URL_AREA_CLIENTE'] = 'http://192.168.0.57:5000/';
$_ENV['URL_LOOKPAY'] = 'http://192.168.0.57:8808';
$_ENV['URL_MED_API'] = 'http://192.168.0.57:8080/';
$_ENV['URL_MEULOOK'] = 'http://192.168.0.57:5003/';
$_ENV['URL_MOBILE_ENTREGAS'] = 'http://192.168.0.57:7005/';
$_ENV['URL_LOOKPAY'] = 'http://192.168.0.57/lookpay/pay/public_html/';
$_ENV['URL_GERADOR_QRCODE'] = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=';

$_ENV['MED_AUTH_TOKEN'] = 'tokenmed'; # tokens de autenticação não devem ficar expostos no Front-End por questões de segurança
$_ENV['PHPUNIT_PHPSESSID'] = 'l722usnb8m7ijdorq3quddr22a';

$_ENV['URL_CDN'] = 'lookpay.com.br';

$_ENV['GOOGLE_TOKEN_GEOLOCALIZACAO'] = 'AIzaSyB2_Lp9A_TY7Hxx1OpMi_Vn5YhZC8YU1N0';

$_ENV['PHPUNIT_AUTH'] =
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZF91c3VhcmlvIjoiNzkyMSIsIm5pdmVsX2FjZXNzbyI6IjU3IiwicGVybWlzc2FvIjoiMTAsNTciLCJpZF9jb2xhYm9yYWRvciI6Ijc5NTgiLCJyZWdpbWUiOiIyIiwidWYiOiJNRyIsIm5vbWUiOiJDYXJsb3MiLCJjcmlhZG9fZW0iOiIyMDIyLTAzLTAyIiwicXRkX2NvbXByYXMiOjB9.HUyq3VYISt2NsOFoZb2-DPgFuWYDkA3zfQDpOKhUTvk';

$_ENV['MAIL_USER'] = 'meulook@mobilestock.com.br';
$_ENV['MAIL_PASSWORD'] = '#TNyZJw0*G6tK2M&M!$9TvS$sfW0f4';

#CHAVES DE TESTE
$_ENV['DADOS_PAGAMENTO_IUGUAPITOKEN'] = '975c4ef1c8054f6ed8f06e44cad4636c';
$_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'] = 'A0240A3F1EBC4D849C6CD94F9F79CBC9';

$_ENV['DADOS_PAGAMENTO_MERCHANTID'] = 'e0078a38-67b5-4779-8018-5e6648301a2b';
$_ENV['DADOS_PAGAMENTO_MERCHANTKEY'] = 'SGTACJXCISVAMNZTLMDQWXCCJEJWMVZHFGNGXFMV';
$_ENV['DADOS_PAGAMENTO_ZOOP_API_TOKEN'] =
    'enBrX3Rlc3RfNU1oRGo1UFY3RGxNY281VHh3S29XbjAxOnpwa190ZXN0XzVNaERqNVBWN0RsTWNvNVR4d0tvV24wMQ==';
$_ENV['DADOS_PAGAMENTO_ZOOP_ID_MARKETPLACE'] = '5bfcfc997171472eaa3e65719ffd0a42';
$_ENV['DADOS_PAGAMENTO_ZOOP_CONTA_MOBILE'] = '232e53629a984ad1bb7d6bc84e3ce4d8';

$_ENV['SICOOB_SENHA_CERTIFICADO'] = 'MOBILE2020*';
$_ENV['SICOOB_CLIENT_ID'] = '66824f2a-524a-4767-af89-49ed0372bae3';
$_ENV['SICOOB_CHAVE_PIX'] = '37360647000134';

$_ENV['S3_OPTIONS_ARRAY'] = [
    'PADRAO' => [
        'credentials' => [
            'key' => 'AKIASHWRDYSYKB6BMG76',
            'secret' => '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',
        ],
        'version' => 'latest',
        'region' => 'sa-east-1',
    ],
    'AVALIACAO_DE_PRODUTOS' => [
        'credentials' => [
            'key' => 'AKIASHWRDYSYHBULBA73',
            'secret' => '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',
        ],
        'version' => 'latest',
        'region' => 'sa-east-1',
    ],
    'FISCAL_PDF' => [
        'credentials' => [
            'key' => 'AKIASHWRDYSYJX5C7Z7A',
            'secret' => '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',
        ],
        'version' => 'latest',
        'region' => 'sa-east-1',
    ],
    'BACKUP_AUTOMATICO' => [
        'credentials' => [
            'key' => 'AKIASHWRDYSYAL6BKF7N',
            'secret' => '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',
        ],
        'version' => 'latest',
        'region' => 'sa-east-1',
    ],
    'ARQUIVOS_PRIVADOS' => [
        'credentials' => [
            'key' => 'AKIASHWRDYSYDEFHXVGR',
            'secret' => '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',
        ],
        'version' => 'latest',
        'region' => 'us-east-1',
    ],
];

$_ENV['USUARIO_RODONAVES'] = 'MSTECE';
$_ENV['SENHA_RODONAVES'] = '9CK8V43F';

$_ENV['SQS_ENDPOINTS'] = [
    'ATUALIZAR_PAGAMENTO_WEBHOOK' => 'http://192.168.0.57:9324/000000000000/atualizar-pagamento-webhook.fifo',
    'GERAR_PAGAMENTO' => 'http://192.168.0.57:9324/000000000000/gerar-pagamento.fifo',
    'MS_PAGAMENTO_RAPIDO' => 'http://192.168.0.57:9324/000000000000/ms-pagamento-rapido.fifo',
    'APPENTREGAS_GERAR_PAGAMENTO_PIX' => 'http://192.168.0.57:9324/000000000000/appentregas-gerar-pagamento-pix.fifo',
    'PROCESSAMENTO_ASSINCRONO_CANCELAMENTO' =>
        'http://192.168.0.57:9324/000000000000/processamento-assincrono-cancelamento.fifo',
    'MS_FECHAR_PEDIDO' => 'http://192.168.0.57:9324/000000000000/ms-fechar-pedido.fifo',
    'PROCESSO_CRIAR_TRANSACAO' => 'http://192.168.0.57:9324/processo-criar-transacao.fifo',
    'PROCESSAMENTO_ASSINCRONO_PAGAMENTO' =>
        'http://192.168.0.57:9324/000000000000/processamento-assincrono-pagamento.fifo',
    'PROCESSO_CRIAR_TRANSACAO_CREDITO' =>
        'http://192.168.0.57:9324/000000000000/processamento-criar-transacao-credito.fifo',
    'GERENCIAR_ACOMPANHAMENTO' => 'http://192.168.0.57:9324/000000000000/gerenciar-acompanhamento.fifo',
    'MENSAGERIA' => 'https://sqs.sa-east-1.amazonaws.com/153983632560/whatsapp.fifo',
    # 'MENSAGERIA' => 'http://192.168.0.57:9324/000000000000/whatsapp.fifo',
];

$_ENV['CACHE'] = [
    'REDIS' => [
        'HOST' => 'redis',
        'PORT' => '6379',
        'PASSWORD' => '',
    ],
];

$_ENV['TELEGRAM_LOG_TOKEN'] = '6870515493:AAF3fLmE4qbOMWQTcsb5O_WUYg4qlqC0rUM';
$_ENV['TELEGRAM_LOG_CHAT_ID'] = '-1002085946467';

$_ENV['AWS_PREFIX'] = 'http://192.168.0.57:9324/000000000000'; #Teste
$_ENV['SECRET_MOBILE_STOCK_API_TOKEN'] = 'dummy';

$_ENV['MED_APP_AUTH_TOKEN'] = 'tokenmed';
