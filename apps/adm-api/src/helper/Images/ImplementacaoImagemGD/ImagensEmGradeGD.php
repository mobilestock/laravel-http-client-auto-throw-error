<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class ImagensEmGradeGD extends ImagemGDAbstrata
{
    public array $imagens;

    public function __construct(array $imagens, int $larguraDaImagem = 800, int $alturaDaImagem = 800)
    {
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->imagens = $imagens;
    }

    public function renderizar(): Image
    {
        $fundo = parent::criarImagem();

        if (sizeof($this->imagens) > 1 && sizeof($this->imagens) <= 3) {
            $posicaoHorizontal = 0;
            $posicaoVertical = 0;
            foreach ($this->imagens as $index => $imagem) {
                if ($index === 1) {
                    $posicaoVertical = 400;
                }
                if ($index === 2) {
                    $posicaoHorizontal = 400;
                }
                $img = $this->gerenciadorDeImagem->make($imagem);
                $img->resize(400, 400);
                $fundo->insert($img, 'top-left', $posicaoHorizontal, $posicaoVertical);
                $img->destroy();
            }
        }
        $posicaoHorizontal = 0;
        $posicaoVertical = 0;
        foreach ($this->imagens as $index => $imagem) {
            if ($index === 1) {
                $posicaoVertical = 400;
            }
            if ($index === 2) {
                $posicaoHorizontal = 400;
            }
            if ($index === 3) {
                $posicaoVertical = 0;
            }
            $img = $this->gerenciadorDeImagem->make($imagem);
            $img->resize(400, 400);
            $fundo->insert($img, 'top-left', $posicaoHorizontal, $posicaoVertical);
            $img->destroy();
        }

        return $fundo;
    }

    public function gerarGradeDeImagensEmBase64(): ?string
    {
        $imagem = $this->renderizar();
        $imagemComQualidadeReduzida = $imagem->encode('jpg', 35);
        return base64_encode($imagemComQualidadeReduzida);
    }
}
