<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

interface ImagemInterface
{
    public function renderizar(): Image;

    public function criarZpl(): string;

    public function criarImagem(): Image;

    public function aplicarTexto(
        Image $imagem,
        int $tamanhoFonte,
        int $posicaoX,
        int $posicaoY,
        string $texto,
        string $corDaFonte,
        string $fonte
    ): void;

    public function criarQrCode(string $dado): Image;
}
