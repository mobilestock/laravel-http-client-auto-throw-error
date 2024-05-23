<?php
$_ENV['MYSQL_HOST'] = '';
$_ENV['MYSQL_DB_NAME'] = '';
$_ENV['MYSQL_USER'] = '';
$_ENV['MYSQL_PASSOWORD'] = '';
$_ENV['MYSQL_USER_SUPER'] = '';
$_ENV['MYSQL_PASSOWORD_SUPER'] = '';
$_ENV['OPTIONS'] = [
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_STATEMENT_CLASS => [MobileStock\database\PDOStatement::class],
];

$_ENV['MYSQL_USER_READ_ONLY'] = '';
$_ENV['MYSQL_PASSWORD_READ_ONLY'] = '';

$_ENV['URL_MOBILE'] = '';

$_ENV['URL_MED_API'] = '';

$_ENV['URL_AREA_CLIENTE'] = '';

$_ENV['URL_LOOKPAY'] = '';

$_ENV['PHPUNIT_PHPSESSID'] = '';

$_ENV['URL_MEULOOK'] = '';

$_ENV['URL_GERADOR_QRCODE'] = "{$_ENV['URL_MEULOOK']}api/qrcode.png?texto=";

$_ENV['URL_MOBILE_ENTREGAS'] = '';

$_ENV['URL_CDN'] = '';

$_ENV['MED_AUTH_TOKEN'] = ''; # tokens de autenticação não devem ficar expostos no Front-End por questões de segurança
$_ENV['GOOGLE_TOKEN_GEOLOCALIZACAO'] = '';

$_ENV['SECRET_TOKEN_WHATSAPP'] = '';

$_ENV['MAIL_USER'] = '';
$_ENV['MAIL_PASSWORD'] = '';

$_ENV['AMBIENTE'] = '';

$_ENV['DADOS_PAGAMENTO_IUGUAPITOKEN'] = '';
$_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'] = '';
$_ENV['DADOS_PAGAMENTO_MERCHANTID'] = '';
$_ENV['DADOS_PAGAMENTO_MERCHANTKEY'] = '';
$_ENV['DADOS_PAGAMENTO_PAGARMEAPITOKEN'] = '';
$_ENV['DADOS_PAGAMENTO_ZOOP_CONTA_MOBILE'] = '';
$_ENV['DADOS_PAGAMENTO_ZOOP_API_TOKEN'] = '';
$_ENV['DADOS_PAGAMENTO_ZOOP_ID_MARKETPLACE'] = '';

$_ENV['S3_OPTIONS_ARRAY'] = [];

$_ENV['SQS_ENDPOINTS'] = [
    'ATUALIZAR_PAGAMENTO_WEBHOOK' => '',
    'GERAR_PAGAMENTO' => '',
    'MS_PAGAMENTO_RAPIDO' => '',
    'MS_FECHAR_PEDIDO' => '',
    'APPENTREGAS_GERAR_PAGAMENTO_PIX' => '',
    'MENSAGERIA' => '',
    'PROCESSO_CRIAR_TRANSACAO_CREDITO' => '',
];

$_ENV['OPENSEARCH'] = [
    'AUTH' => '',
    'ENDPOINT' => '',
    'INDEXES' => [
        'PESQUISA' => '',
        'AUTOCOMPLETE' => '',
        'LOGS' => '',
    ],
];

$_ENV['TELEFONE_WHATSAPP_TESTE'] = '5537123456789';
$_ENV['EMAIL_PAGAMENTO_TESTE'] = 'testemeulook@gmail.com';

$_ENV['USUARIO_RODONAVES'] = '';
$_ENV['SENHA_RODONAVES'] = '';

$_ENV['ZEO_API_TOKEN'] = '';

$_ENV['CACHE'] = [
    'REDIS' => [
        'HOST' => '',
        'PORT' => '',
        'PASSWORD' => '',
    ],
];

$_ENV['SICOOB_SENHA_CERTIFICADO'] = '';
$_ENV['SICOOB_CLIENT_ID'] = '';
$_ENV['SICOOB_CHAVE_PIX'] = '';

$_ENV['TELEGRAM_LOG_TOKEN'] = '';
$_ENV['TELEGRAM_LOG_CHAT_ID'] = '';

$_ENV['AWS_PREFIX'] = '';
$_ENV['AWS_DEFAULT_REGION'] = '';
$_ENV['AWS_ACCESS_KEY_ID'] = '';
$_ENV['AWS_SECRET_ACCESS_KEY'] = '';

$_ENV['SECRET_MOBILE_STOCK_API_TOKEN'] = '';
