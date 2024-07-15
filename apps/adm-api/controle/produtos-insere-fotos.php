<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../regras/alertas.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use MobileStock\helper\Globals;

// Função para ajustar tamanho de imagem
function upload($tmp, $arquivo, $max_x, $max_y)
{
    $img = imagecreatefromjpeg($tmp);
    $original_x = imagesx($img);
    $original_y = imagesy($img);
    $diretorio = __DIR__ . '/../downloads/' . $arquivo;
    if ($original_x > $max_x || $original_y > $max_y) {
        if ($original_x > $original_y) {
            $max_y = ($max_x * $original_y) / $original_x;
        } else {
            $max_x = ($max_y * $original_x) / $original_y;
        }
        $nova = imagecreatetruecolor($max_x, $max_y);
        imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
        imagewebp($nova, $diretorio, 100);
        imagedestroy($nova);
        imagedestroy($img);
    } else {
        imagewebp($img, $diretorio);
        imagedestroy($img);
    }
}

function uploadPNG($tmp, $arquivo, $max_x, $max_y)
{
    $img = imagecreatefrompng($tmp);
    $original_x = imagesx($img);
    $original_y = imagesy($img);
    $diretorio = '../downloads/' . $arquivo;
    if ($original_x > $max_x || $original_y > $max_y) {
        if ($original_x > $original_y) {
            $max_y = ($max_x * $original_y) / $original_x;
        } else {
            $max_x = ($max_y * $original_x) / $original_y;
        }
        $nova = imagecreatetruecolor($max_x, $max_y);
        imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
        imagepng($nova, $diretorio);
        imagedestroy($nova);
        imagedestroy($img);
    } else {
        imagepng($img, $diretorio);
        imagedestroy($img);
    }
}

function insereFotosProduto($id_produto, $files, $descricao, $id_usuario, PDO $conn = null)
{
    $conn = is_null($conn) ? Conexao::criarConexao() : $conn;
    if (is_null($id_usuario)) {
        throw new InvalidArgumentException('Usuário inválido');
    }

    $descricao = mb_substr($descricao, 0, 27);
    try {
        $s3 = new S3Client(Globals::S3_OPTIONS());
    } catch (Exception $e) {
        die('Error ' . $e->getMessage());
    }

    $sequencia = buscaSequenciaFotoProduto($conn, $id_produto);
    $sequencia++;

    //upload das imagens ******* 20/02/2019 ********
    if (isset($files['fotos'])) {
        $imagens = $files['fotos'];
        for ($i = 0; $i < count($imagens['name']); $i++) {
            if ($imagens['name'][$i] != '') {
                $extensao = mb_substr($imagens['name'][$i], mb_strripos($imagens['name'][$i], '.'));
                $img_extensao = ['.jpg', '.JPG', '.jpeg', '.JPEG'];
                if (!in_array($extensao, $img_extensao)) {
                    // valida extensão da imagem.
                    $_SESSION['danger'] = "Sistema permite apenas imagens com extensão '.jpg'";
                    break;
                }
                $nomeimagem = PREFIXO_LOCAL . $id_produto . '_' . $sequencia . '_' . date('d_m_Y') . $extensao;
                // tamanho máximo da imagem
                $largura_max = 800;
                $altura_max = 800;
                $temp_file = $imagens['tmp_name'][$i];
                upload($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
                $imagem = imagecreatefromjpeg(__DIR__ . '/../downloads/' . $nomeimagem); // abre a imagem salva na pasta upload

                $text_font_size = 20;
                $padding = 10;
                $margin = 10;
                $background = [
                    'tag' => [
                        'red' => 255,
                        'green' => 255,
                        'blue' => 255,
                    ],
                    'font' => [
                        'red' => 0,
                        'blue' => 0,
                        'green' => 0,
                    ],
                    'alpha' => 60,
                ];
                $tag_position = [
                    'width' => 100,
                    'height' => 100,
                ];

                $image_width = imagesx($imagem);
                $image_height = imagesy($imagem);
                $text_length = mb_strlen($id_produto) * ($text_font_size * 0.78);
                $img_texto = imagecreate($text_length + $padding * 2, $text_font_size); //cria uma imagem
                imagecolorallocate(
                    $img_texto,
                    $background['tag']['red'],
                    $background['tag']['green'],
                    $background['tag']['blue']
                ); // cor de fundo
                $textcolor = imagecolorallocate(
                    $img_texto,
                    $background['font']['red'],
                    $background['font']['green'],
                    $background['font']['blue']
                ); //cor da font
                imagettftext(
                    $img_texto,
                    $text_font_size,
                    0,
                    $padding,
                    $text_font_size,
                    $textcolor,
                    __DIR__ . '/../fonts/Swis721_BT_Bold.ttf',
                    $id_produto
                ); //cria a etiqueta com a descrição

                $width_card = imagesx($img_texto);
                $largura_disponivel = $image_width - $width_card;
                $largura_calculada = $image_width * ($tag_position['width'] / 100) + $margin;
                if ($largura_disponivel <= $largura_calculada) {
                    $largura_calculada = $largura_disponivel - $margin;
                }

                $height_card = imagesy($img_texto);
                $altura_disponivel = $image_height - $height_card - $padding;
                $altura_calculada = $image_height * ($tag_position['height'] / 100) + $margin;
                if ($altura_disponivel <= $altura_calculada) {
                    $altura_calculada = $altura_disponivel - $margin;
                }

                imagecopymerge(
                    $imagem,
                    $img_texto,
                    $largura_calculada,
                    $altura_calculada,
                    0,
                    $text_font_size * 0.78 - $text_font_size,
                    $width_card,
                    $text_font_size + $padding,
                    $background['alpha']
                ); //coloca a etiqueta sobre a imagem do produto
                imagejpeg($imagem, __DIR__ . '/../downloads/' . $nomeimagem, 100); // salva a imagem com 100% da resolução
                try {
                    $result = $s3->putObject([
                        'Bucket' => 'mobilestock-s3',
                        'Key' => $nomeimagem,
                        'SourceFile' => __DIR__ . '/../downloads/' . $nomeimagem,
                    ]);
                } catch (S3Exception $e) {
                    $exception = $e->getMessage();

                    echo $exception;
                }
                imagedestroy($imagem);
                unlink(__DIR__ . '/../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
                $nomeFoto = $nomeimagem;
                $caminhoImagens = 'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                inserirImagensProduto($id_produto, $caminhoImagens, $nomeFoto, $sequencia, $id_usuario, $conn);
                $sequencia++;

                /// salva Imagem com tamanho reduzido
                $nomeimagem =
                    PREFIXO_LOCAL . 'small_' . $id_produto . '_' . $sequencia . '_' . date('d_m_Y') . $extensao;
                $largura_max = 100;
                $altura_max = 100;
                $temp_file = $imagens['tmp_name'][$i];
                upload($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
                $imagem = imagecreatefromjpeg(__DIR__ . '/../downloads/' . $nomeimagem); // abre a imagem salva na pasta upload
                imagejpeg($imagem, __DIR__ . '/../downloads/' . $nomeimagem, 100); // salva a imagem com 100% da resolução
                try {
                    $result = $s3->putObject([
                        'Bucket' => 'mobilestock-s3',
                        'Key' => $nomeimagem,
                        'SourceFile' => __DIR__ . '/../downloads/' . $nomeimagem,
                    ]);
                } catch (S3Exception $e) {
                    $exception = $e->getMessage();

                    echo $exception;
                }
                imagedestroy($imagem);
                unlink(__DIR__ . '/../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
                $nomeFoto = $nomeimagem;
                $caminhoImagens = 'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                inserirImagensProduto($id_produto, $caminhoImagens, $nomeFoto, $sequencia, $id_usuario, $conn, 3, 'SM');
                $sequencia++;
            }
        }
    }

    //upload das imagens ******* 20/02/2019 ********
    if (isset($files['fotos_calcadas'])) {
        $imagens = $files['fotos_calcadas'];
        for ($i = 0; $i < count($imagens['name']); $i++) {
            if ($imagens['name'][$i] != '') {
                $extensao = mb_substr($imagens['name'][$i], mb_strripos($imagens['name'][$i], '.'));
                $img_extensao = ['.jpg', '.JPG', '.jpeg', '.JPEG'];
                if (!in_array($extensao, $img_extensao)) {
                    // valida extensão da imagem.
                    $_SESSION['danger'] = "Sistema permite apenas imagens com extensão '.jpg'";
                    break;
                }
                $nomeimagem = PREFIXO_LOCAL . $id_produto . '_' . $sequencia . '_' . date('d_m_Y') . $extensao;
                // tamanho máximo da imagem
                $largura_max = 800;
                $altura_max = 800;
                $temp_file = $imagens['tmp_name'][$i];
                upload($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
                $imagem = imagecreatefromjpeg(__DIR__ . '/../downloads/' . $nomeimagem); // abre a imagem salva na pasta upload

                $text_font_size = 20;
                $padding = 10;
                $margin = 10;
                $background = [
                    'tag' => [
                        'red' => 255,
                        'green' => 255,
                        'blue' => 255,
                    ],
                    'font' => [
                        'red' => 0,
                        'blue' => 0,
                        'green' => 0,
                    ],
                    'alpha' => 60,
                ];
                $tag_position = [
                    'width' => 100,
                    'height' => 100,
                ];
                $image_width = imagesx($imagem);
                $image_height = imagesy($imagem);
                $text_length = mb_strlen($id_produto) * ($text_font_size * 0.78);
                $img_texto = imagecreate($text_length + $padding * 2, $text_font_size); //cria uma imagem
                imagecolorallocate(
                    $img_texto,
                    $background['tag']['red'],
                    $background['tag']['green'],
                    $background['tag']['blue']
                ); // cor de fundo
                $textcolor = imagecolorallocate(
                    $img_texto,
                    $background['font']['red'],
                    $background['font']['green'],
                    $background['font']['blue']
                ); //cor da font
                imagettftext(
                    $img_texto,
                    $text_font_size,
                    0,
                    $padding,
                    $text_font_size,
                    $textcolor,
                    __DIR__ . '/../fonts/Swis721_BT_Bold.ttf',
                    $id_produto
                ); //cria a etiqueta com a descrição

                $width_card = imagesx($img_texto);
                $largura_disponivel = $image_width - $width_card;
                $largura_calculada = $image_width * ($tag_position['width'] / 100) + $margin;
                if ($largura_disponivel <= $largura_calculada) {
                    $largura_calculada = $largura_disponivel - $margin;
                }

                $height_card = imagesy($img_texto);
                $altura_disponivel = $image_height - $height_card - $padding;
                $altura_calculada = $image_height * ($tag_position['height'] / 100) + $margin;
                if ($altura_disponivel <= $altura_calculada) {
                    $altura_calculada = $altura_disponivel - $margin;
                }

                imagecopymerge(
                    $imagem,
                    $img_texto,
                    $largura_calculada,
                    $altura_calculada,
                    0,
                    $text_font_size * 0.78 - $text_font_size,
                    $width_card,
                    $text_font_size + $padding,
                    $background['alpha']
                ); //coloca a etiqueta sobre a imagem do produto

                //$img_texto = imagecreate(imagesx($imagem), 50); //cria uma imagem
                //imagecolorallocate($img_texto, 0, 0, 0); // cor de fundo
                //$textcolor = imagecolorallocate($img_texto, 255, 255, 255); //cor da font
                //$borda = (imagesx($imagem) / 2) - (strlen($descricao) * 10);
                //imagettftext($img_texto, 30, 0, 1 + $borda, 40, $textcolor, __DIR__ . '/../fonts/Swis721_BT_Bold.ttf', $descricao); //cria a etiqueta com a descrição
                //$nova = imagecreatetruecolor(imagesx($imagem), (imagesy($imagem) + imagesy($img_texto))); //cria uma imagem em branco que comporte a foto + tarja
                //imagesettile($nova, $imagem); //cola a foto na imagem em branco
                //imagefilledrectangle($nova, 0, 0, imagesx($imagem), imagesy($imagem), IMG_COLOR_TILED); //comando necessario para exibir a foto

                //// imagecopyresampled($nova, $imagem, 0, 0, 0, 0, imagesx($imagem), (imagesy($imagem) + imagesy($img_texto)), imagesx($imagem), imagesy($imagem));
                //imagecopymerge($nova, $img_texto, 0, (imagesy($imagem)), 0, 0, imagesx($imagem), 50, 100); //coloca a etiqueta sobre a imagem do produto
                unlink(__DIR__ . '/../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
                imagejpeg($imagem, __DIR__ . '/../downloads/' . $nomeimagem, 100); // salva a imagem com 100% da resolução
                try {
                    $result = $s3->putObject([
                        'Bucket' => 'mobilestock-s3',
                        'Key' => $nomeimagem,
                        'SourceFile' => __DIR__ . '/../downloads/' . $nomeimagem,
                    ]);
                } catch (S3Exception $e) {
                    echo $e->getMessage();
                }
                imagedestroy($imagem);
                unlink(__DIR__ . '/../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
                $nomeFoto = $nomeimagem;
                $caminhoImagens = 'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                inserirImagensProduto($id_produto, $caminhoImagens, $nomeFoto, $sequencia, $id_usuario, $conn, 1, 'LG');
                $sequencia++;
            }
        }
    }

    if (isset($files['foto_thumbnails'])) {
        $imagens = $files['foto_thumbnails'];
        for ($i = 0; $i < count($imagens['name']); $i++) {
            if ($imagens['name'][$i] != '') {
                $extensao = mb_substr($imagens['name'][$i], mb_strripos($imagens['name'][$i], '.'));
                $img_extensao = ['.jpg', '.JPG', '.jpeg', '.JPEG', '.png', '.PNG'];
                if (in_array($extensao, $img_extensao)) {
                    // valida extensão da imagem.
                    $key = array_search($extensao, $img_extensao);
                    $nomeimagem = PREFIXO_LOCAL . $id_produto . '_' . $sequencia . '_' . date('d_m_Y') . $extensao;
                    // tamanho máximo da imagem
                    $largura_max = 200;
                    $altura_max = 200;
                    $temp_file = $imagens['tmp_name'][$i];
                    if ($key <= 3) {
                        upload($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
                        $imagem = imagecreatefromjpeg('../downloads/' . $nomeimagem); // abre a imagem salva na pasta upload
                        imagejpeg($imagem, '../downloads/' . $nomeimagem, 100); // salva a imagem com 100% da resolução
                    } else {
                        uploadPNG($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
                        $imagem = imagecreatefrompng('../downloads/' . $nomeimagem); // abre a imagem salva na pasta upload
                        // imagepng($imagem, "../downloads/" . $nomeimagem, 100); // salva a imagem com 100% da resolução
                    }

                    try {
                        $result = $s3->putObject([
                            'Bucket' => 'mobilestock-s3',
                            'Key' => $nomeimagem,
                            'SourceFile' => '../downloads/' . $nomeimagem,
                        ]);
                    } catch (S3Exception $e) {
                        echo $e->getMessage();
                    }
                    imagedestroy($imagem);
                    unlink('../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
                    $nomeFoto = $nomeimagem;
                    $caminhoImagens = 'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                    inserirImagensProduto($id_produto, $caminhoImagens, $nomeFoto, $sequencia, $id_usuario, $conn, 2);
                    $sequencia++;
                } else {
                    $_SESSION['danger'] = "Sistema permite apenas imagens com extensão '.jpg', '.jpeg' ou '.png'";
                    break;
                }
            }
        }
    }
}
