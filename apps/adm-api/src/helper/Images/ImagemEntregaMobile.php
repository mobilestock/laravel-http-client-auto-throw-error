<?php

namespace MobileStock\helper\Images;

use MobileStock\helper\ConversorStrings;

class ImagemEntregaMobile
{
    /**
     * @param string[] $dados
     * @return string
     * @issue https://github.com/mobilestock/web/issues/3076
     */
    public static function gerarImagem(array $dados, bool $miniatura = true): string
    {
        header('Content-Type: image/png');

        $altura = 215;
        $qtdProdutos = count($dados['produtos']);
        $altura = $altura + 120 * $qtdProdutos;

        $imagem = imagecreate(395, $altura);
        $barra = imagecreate(355, 1);

        $diretorioFonte = __DIR__ . '/../../../fonts';
        $fontes = [
            'bold' => $diretorioFonte . '/Roboto-Bold.ttf',
            'light' => $diretorioFonte . '/Roboto-Light.ttf',
            'regular' => $diretorioFonte . '/Roboto-Regular.ttf',
        ];

        imagecolorallocate($imagem, 255, 255, 255);
        imagecolorallocate($barra, 171, 171, 171);
        $textoCor = imagecolorallocate($imagem, 0, 0, 0);

        //Número da Entrega
        imagettftext($imagem, 15, 0, 15, 35, $textoCor, $fontes['regular'], 'ENTREGA ' . $dados['id']);

        //Data da Entrega
        imagettftext($imagem, 15, 0, 15, 60, $textoCor, $fontes['regular'], $dados['data_atualizacao']);

        //Nome do Cliente
        imagettftext($imagem, 15, 0, 15, 120, $textoCor, $fontes['regular'], $dados['razao_social']);

        //Endereço do Cliente
        imagettftext(
            $imagem,
            12,
            0,
            15,
            145,
            $textoCor,
            $fontes['regular'],
            $dados['logradouro'] . ' ' . $dados['numero']
        );
        imagettftext($imagem, 12, 0, 15, 163, $textoCor, $fontes['regular'], $dados['bairro']);
        imagettftext($imagem, 12, 0, 15, 181, $textoCor, $fontes['regular'], $dados['cidade'] . ' - ' . $dados['uf']);

        $alturaFoto = 215;
        $alturaPrimeiraLinha = 230;
        $alturaSegundaLinha = 260;
        $alturaTerceiraLinha = 290;
        $alturaBarraDivisao = 200;
        $horizontalDados = 100;

        foreach ($dados['produtos'] as $produto) {
            if ($miniatura) {
                imagecopymerge($imagem, $barra, 15, $alturaBarraDivisao, 0, 0, imagesx($barra), imagesy($barra), 100);
                $fotoProduto = imagecreatefromstring(file_get_contents($produto['foto']));
                imagecopyresampled(
                    $imagem,
                    $fotoProduto,
                    15,
                    $alturaFoto,
                    0,
                    0,
                    80,
                    80,
                    imagesx($fotoProduto),
                    imagesy($fotoProduto)
                );
            } else {
                $horizontalDados = 15;
            }

            imagettftext(
                $imagem,
                12,
                0,
                $horizontalDados,
                $alturaPrimeiraLinha,
                $textoCor,
                $fontes['regular'],
                mb_substr(ConversorStrings::sanitizeString($produto['nome_produto']), 0, 25)
            );
            imagettftext(
                $imagem,
                12,
                0,
                $horizontalDados,
                $alturaSegundaLinha,
                $textoCor,
                $fontes['regular'],
                'ID: ' . $produto['id_produto']
            );
            imagettftext(
                $imagem,
                12,
                0,
                $horizontalDados,
                $alturaTerceiraLinha,
                $textoCor,
                $fontes['regular'],
                'R$ ' . number_format($produto['preco'], 2, ',', '.')
            );
            imagettftext(
                $imagem,
                12,
                0,
                335,
                $alturaPrimeiraLinha,
                $textoCor,
                $fontes['regular'],
                $produto['nome_tamanho']
            );
            $alturaBarraDivisao += 115;
            $alturaFoto += 115;
            $alturaPrimeiraLinha += 115;
            $alturaSegundaLinha += 115;
            $alturaTerceiraLinha += 115;
        }

        ob_start();
        imagepng($imagem);
        $image_data = ob_get_contents();
        ob_end_clean();

        if ($_ENV['AMBIENTE'] !== 'producao') {
            imagejpeg($imagem, __DIR__ . '/../../../downloads/fatura.jpeg');
        }

        imagedestroy($imagem);

        return base64_encode($image_data);
    }
}
