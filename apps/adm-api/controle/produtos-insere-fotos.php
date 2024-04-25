<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../regras/alertas.php";

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use MobileStock\helper\Globals;

// Função para ajustar tamanho de imagem
function upload($tmp, $arquivo, $max_x, $max_y)
{
        $img     = imagecreatefromjpeg($tmp);
        $original_x = imagesx($img);
        $original_y = imagesy($img);
        $diretorio  =  __DIR__ . '/../downloads/' . $arquivo;
        if (($original_x > $max_x) || ($original_y > $max_y)) {
                if ($original_x > $original_y) {
                        $max_y   = ($max_x * $original_y) / $original_x;
                } else {
                        $max_x   = ($max_y * $original_x) / $original_y;
                }
                $nova = imagecreatetruecolor($max_x, $max_y);
                imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
                imagejpeg($nova, $diretorio, 100);
                imagedestroy($nova);
                imagedestroy($img);
        } else {
                imagejpeg($img, $diretorio);
                imagedestroy($img);
        }
}

function uploadPNG($tmp, $arquivo, $max_x, $max_y)
{
        $img     = imagecreatefrompng($tmp);
        $original_x = imagesx($img);
        $original_y = imagesy($img);
        $diretorio  = '../downloads/' . $arquivo;
        if (($original_x > $max_x) || ($original_y > $max_y)) {
                if ($original_x > $original_y) {
                        $max_y   = ($max_x * $original_y) / $original_x;
                } else {
                        $max_x   = ($max_y * $original_x) / $original_y;
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
