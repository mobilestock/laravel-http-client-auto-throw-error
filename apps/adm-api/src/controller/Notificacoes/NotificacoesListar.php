<?php

use MobileStock\repository\NotificacaoRepository;

require '../../../vendor/autoload.php';

echo json_encode([
    'data' => NotificacaoRepository::buscaNaCentralNotificacoes($_POST['nome'], $_POST['data'], $_POST['tipo'], $_POST['pagina'])
]);