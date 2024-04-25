<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class ImagensEmGradeGD extends ImagemGDAbstrata
{
    public array $imagens;

    public function __construct(array $imagens)
    {
        $larguraDaImagem = 800;
        $alturaDaImagem = 800;
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->imagens = $imagens;
    }

    public function renderizar(): Image
    {
        $fundo = parent::criarImagem();
        $posicaoVertical = 0;
        $posicaoHorizontal = 0;
        foreach ($this->imagens as $index => $imagem) {
            switch ($index) {
                case 1:
                    $posicaoVertical = 0;
                    $posicaoHorizontal = 400;
                    break;
                case 2:
                    $posicaoVertical = 400;
                    $posicaoHorizontal = 0;
                    break;
                case 3:
                    $posicaoVertical = 400;
                    $posicaoHorizontal = 400;
                    break;
            }
            $novaImagem = $this->gerenciadorDeImagem->make($imagem);
            $novaImagem->resize(400, 400);
            $fundo->insert($novaImagem, 'top-left', $posicaoHorizontal, $posicaoVertical);
            $novaImagem->destroy();
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
