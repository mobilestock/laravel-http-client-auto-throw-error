<?php
require_once __DIR__ . '/cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-br" style="height: auto;">

<head>
    <meta charset="utf-8">
</head>

<body>
    <div class="text">Você pode retirar seus produtos do Mobile Stock quando quiser, é só seguir os passos do vídeo abaixo:</div>
    <br />

    <div class="box-video">
        <iframe src="https://www.youtube.com/embed/ot5MAbury4E?start=1" allow="encrypted-media; gyroscope;" allowfullscreen loading="lazy"></iframe>
    </div>
    <br />

    <div class="text">Para fazer a retirada temos um custo de R$1,00 para custear a mão de obra de alocar os produtos, aramazena-los em segurança e depois fazer a retirada</div>
</body>

<style>
    .box-video {
        text-align: center;
    }

    /* Tela fina: */
    @media only screen and (max-width: 414px) {
        .box-video>iframe {
            width: 100%;
            height: auto;
        }

        .text {
            font-size: large;
            text-align: center;
        }
    }

    /* Tela larga: */
    @media only screen and (min-width: 415px) {
        .box-video>iframe {
            width: 100%;
            height: 360px;
            max-width: 720px;
        }

        .text {
            font-size: larger;
            text-align: center;
        }
    }
</style>