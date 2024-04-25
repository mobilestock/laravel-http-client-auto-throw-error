<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use MobileStock\helper\Globals;
//'.jpg', '.JPG', '.jpeg', '.JPEG'
function upload($tmp, $arquivo, $max_x, $max_y, $extensao)
{
        if ($extensao == '.png' || $extensao == '.PNG' ) {
                $img = imagecreatefrompng($tmp); // abre a imagem salva na pasta upload
        } else {
                $img     = imagecreatefromjpeg($tmp);
        }

        $original_x = imagesx($img);
        $original_y = imagesy($img);
        $diretorio  = '../../downloads/' . $arquivo;
        if (($original_x > $max_x) || ($original_y > $max_y)) {
                if ($original_x > $original_y) {
                        $max_y = ($max_x * $original_y) / $original_x;
                } else {
                        $max_x = ($max_y * $original_x) / $original_y;
                }
                $nova = imagecreatetruecolor($max_x, $max_y);
                imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
                imagejpeg($nova, $diretorio);
                imagedestroy($nova);
                imagedestroy($img);
        } else {
                imagejpeg($img, $diretorio);
                imagedestroy($img);
        }
        sleep(2);
}

function redimensionaFoto($files, $id_cliente)
{
        $caminhoImagens = NULL;
        try {
                $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
        } catch (Exception $e) {
                die("Error " . $e->getMessage());
        }
        if (isset($files['fotos'])) {
                $imagens = $files['fotos'];
                for ($i = 0; $i < count($imagens['name']); $i++) {
                        if ($imagens['name'][$i] != "") {
                                $extensao   = substr($imagens['name'][$i], strripos($imagens['name'][$i], '.'));
                                $img_extensao = array('.jpg', '.JPG', '.jpeg', '.JPEG', '.png', '.PNG');
                                if (!in_array($extensao, $img_extensao)) { // valida extensão da imagem.
                                        $_SESSION["danger"] = "Sistema permite apenas imagens com extensão '.jpg/png'";
                                        break;
                                }
                                $nomeimagem = $id_cliente . "_" . date('d-m-Y_H-i-s') . strtolower($extensao);
                                // tamanho máximo da imagem 
                                $largura_max        = 600;
                                $altura_max        = 600;
                                $temp_file         = $imagens['tmp_name'][$i];
                                upload($temp_file, $nomeimagem, $largura_max, $altura_max, $extensao); // ajusta o tamanho da imagem e salva na pasta upload
                                if ($extensao == 'PNG' || $extensao == 'png') {
                                        $imagem = imagecreatefrompng("../../downloads/" . $nomeimagem); // abre a imagem salva na pasta upload
                                } else {
                                        $imagem = imagecreatefromjpeg("../../downloads/" . $nomeimagem);
                                }
                                try {
                                        $result = $s3->putObject([
                                                'Bucket' => 'mobilestock-fotos',
                                                'Key' => $nomeimagem,
                                                'SourceFile' => "../../downloads/" . $nomeimagem,
                                        ]);
                                } catch (S3Exception $e) {
                                        echo $e->getMessage();
                                }
                                imagedestroy($imagem);
                                unlink("../../downloads/" . $nomeimagem); //remove a imagem da pasta temporaria
                                $nomeFoto = $nomeimagem;

                                if (count($imagens['name']) > 1 && $i > 0 && $i < 3) {
                                        $caminhoImagens = $caminhoImagens . ',' . 'https://cdn-fotos.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                                } else {
                                        $caminhoImagens = 'https://cdn-fotos.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
                                }
                                //$atendimento->InsereMensagemClienteAtendimento($id_cliente, $tipo_atendimento, json_encode($mensagem), $caminhoImagens, $id_faturameto, $id_produto, $id_colaborador, $situacao, $data_final);

                        }
                }
        }

        return $caminhoImagens;
}
