<?php

namespace MobileStock\helper;

/**
 * @issue https://github.com/mobilestock/web/issues/3076
 */
class GradeImagens
{
    private $larguraReal;
    private $alturaReal;
    private $larguraGrade;
    private $alturaGrade;
    private $imagem;

    public function __construct($larguraReal, $alturaReal, $larguraGrade, $alturaGrade)
    {
        $this->larguraReal = $larguraReal;
        $this->alturaReal = $alturaReal;
        $this->larguraGrade = $larguraGrade;
        $this->alturaGrade = $alturaGrade;

        $this->imagem = imagecreatetruecolor($larguraReal, $alturaReal);
        $fundo = imagecolorallocate($this->imagem, 255, 255, 255);
        imagefill($this->imagem, 0, 0, $fundo);
    }

    public function __destruct()
    {
        imagedestroy($this->imagem);
    }

    public function renderizar()
    {
        ob_start();
        imagejpeg($this->imagem, null, 35);
        $imagemFinal = ob_get_contents();
        ob_end_clean();
        $imagemFinal = base64_encode($imagemFinal);
        return $imagemFinal;
    }

    public function adicionarImagem($img, $tamanhoW, $tamanhoH, $posicaoX, $posicaoY)
    {
        // tamanho da grade
        $larguraGrade = $this->larguraReal / $this->larguraGrade;
        $alturaGrade = $this->alturaReal / $this->alturaGrade;

        // conversão de tamanho de grade para tamanho da imagem
        $larguraReal = ceil($larguraGrade * $tamanhoW);
        $alturaReal = ceil($alturaGrade * $tamanhoH);
        $posicaoRealX = $larguraGrade * $posicaoX;
        $posicaoRealY = $alturaGrade * $posicaoY;

        $img = $this->redimensionar($img, $larguraReal, $alturaReal);

        imagecopyresampled(
            $this->imagem,
            $img,
            $posicaoRealX,
            $posicaoRealY,
            0,
            0,
            $larguraReal,
            $alturaReal,
            imagesx($img),
            imagesy($img)
        );
    }

    public function redimensionar($img, $larguraFinal, $alturaFinal)
    {
        $larguraImg = imagesx($img);
        $alturaImg = imagesy($img);

        $proporcao = $larguraImg / $alturaImg;
        $proporcaoFinal = $larguraFinal / $alturaFinal;
        if ($larguraImg <= $larguraFinal && $alturaImg <= $alturaFinal) {
            $larguraFinalImg = $larguraImg;
            $imgalturaFinal = $alturaImg;
        } elseif ($proporcaoFinal > $proporcao) {
            $larguraFinalImg = (int) ($alturaFinal * $proporcao);
            $imgalturaFinal = $alturaFinal;
        } else {
            $larguraFinalImg = $larguraFinal;
            $imgalturaFinal = (int) ($larguraFinal / $proporcao);
        }

        $imagemDeRetorno = imagecreatetruecolor($larguraFinal, $alturaFinal);

        imagecopyresampled(
            $imagemDeRetorno,
            $img,
            ($larguraFinal - $larguraFinalImg) / 2, // centralizar
            ($alturaFinal - $imgalturaFinal) / 2, // centralizar
            0,
            0,
            $larguraFinalImg,
            $imgalturaFinal,
            $larguraImg,
            $alturaImg
        );

        return $imagemDeRetorno;
    }
}
