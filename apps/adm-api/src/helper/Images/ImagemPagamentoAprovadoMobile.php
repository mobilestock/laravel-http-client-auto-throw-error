<?php

namespace MobileStock\helper\Images;

use MobileStock\helper\ConversorStrings;

class ImagemPagamentoAprovadoMobile
{
    public static function gerarImagem(array $dados, bool $miniatura = true): string
    {
        $root = __DIR__ . '/../../..';

        header('Content-Type: image/png');

        $alturaFoto = 200;
        $altura = 415;
        $qtdProdutos = count($dados);
        $altura = $alturaFoto + 100 * $qtdProdutos;

        $imagem = imagecreate(350, $altura);
        $barra = imagecreate(320, 1);

        $diretorioFonte = $root . '/fonts';
        $fontes = [
            'bold' => $diretorioFonte . '/Roboto-Bold.ttf',
            'regular' => $diretorioFonte . '/Roboto-Regular.ttf',
        ];

        switch ($dados[0]['metodo_de_envio']) {
            case 'PM':
                $responsavel = 'Entregador';
                break;
            case 'PP':
                $responsavel = 'Ponto de Retirada';
                break;
            case 'Transportadora':
                $responsavel = 'Transportadora';
                break;
        }

        imagecolorallocate($imagem, 255, 255, 255);
        imagecolorallocate($barra, 0, 0, 0);
        $textoCor = imagecolorallocate($imagem, 0, 0, 0);

        imagettftext($imagem, 15, 0, 15, 35, $textoCor, $fontes['regular'], 'Pedido ' . $dados[0]['id_transacao']);

        imagettftext($imagem, 9, 0, 25, 75, $textoCor, $fontes['bold'], 'Metodo de Envio:');
        imagettftext(
            $imagem,
            8.9,
            0,
            25,
            95,
            $textoCor,
            $fontes['regular'],
            $responsavel . '  ' . $dados[0]['telefone_entregador']
        );

        imagettftext(
            $imagem,
            9,
            0,
            25,
            115,
            $textoCor,
            $fontes['bold'],
            $dados[0]['metodo_de_envio'] === 'PP' ? 'Endereço de retirada: ' : 'Endereço de entrega: '
        );
        imagettftext(
            $imagem,
            9,
            0,
            25,
            135,
            $textoCor,
            $fontes['regular'],
            $dados[0]['endereco']['logradouro'] .
                ', ' .
                $dados[0]['endereco']['numero'] .
                PHP_EOL .
                $dados[0]['endereco']['bairro'] .
                PHP_EOL .
                $dados[0]['endereco']['cidade'] .
                ' - ' .
                $dados[0]['endereco']['uf'] .
                PHP_EOL
        );

        $alturaPrimeiraLinha = 220;
        $alturaSegundaLinha = 250;
        $alturaTerceiraLinha = 280;
        $alturaBarraDivisao = 195;
        $horizontalDados = 89;

        foreach ($dados as $produto) {
            imagecopymerge($imagem, $barra, 15, $alturaBarraDivisao, 0, 0, imagesx($barra), imagesy($barra), 80);

            if ($miniatura) {
                $fotoProduto = imagecreatefromstring(file_get_contents($produto['foto_produto']));
                imagecopyresampled(
                    $imagem,
                    $fotoProduto,
                    15,
                    $alturaFoto,
                    0,
                    0,
                    65,
                    65,
                    imagesx($fotoProduto),
                    imagesy($fotoProduto)
                );
            } else {
                $horizontalDados = 15;
            }

            imagettftext(
                $imagem,
                9,
                0,
                $horizontalDados,
                $alturaPrimeiraLinha,
                $textoCor,
                $fontes['regular'],
                mb_substr(ConversorStrings::sanitizeString($produto['nome_comercial']), 0, 30)
            );
            imagettftext(
                $imagem,
                8,
                0,
                310,
                $alturaPrimeiraLinha,
                $textoCor,
                $fontes['regular'],
                $produto['nome_tamanho']
            );
            imagettftext(
                $imagem,
                9,
                0,
                $horizontalDados,
                $alturaSegundaLinha,
                $textoCor,
                $fontes['regular'],
                'ID:' . $produto['id_produto']
            );
            if (
                !empty($produto['previsao_entrega']['media_previsao_inicial']) &&
                !empty($produto['previsao_entrega']['media_previsao_final'])
            ) {
                imagettftext(
                    $imagem,
                    8,
                    0,
                    $horizontalDados,
                    $alturaTerceiraLinha,
                    $textoCor,
                    $fontes['regular'],
                    'Previsão de entrega:  ' .
                        $produto['previsao_entrega']['media_previsao_inicial'] .
                        ' - ' .
                        $produto['previsao_entrega']['media_previsao_final']
                );
            }

            $alturaBarraDivisao += 100;
            $alturaFoto += 100;
            $alturaPrimeiraLinha += 100;
            $alturaSegundaLinha += 100;
            $alturaTerceiraLinha += 100;
        }

        ob_start();
        imagepng($imagem);
        $image_data = ob_get_clean();

        if ($_ENV['AMBIENTE'] !== 'producao') {
            imagejpeg($imagem, $root . '/downloads/fatura.jpeg');
        }

        imagedestroy($imagem);

        return base64_encode($image_data);
    }
}
