<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class ImagemPainelEstoqueGD extends ImagemGDAbstrata
{
    private int $idLocalizacao;

    public function __construct(int $idLocalizacao)
    {
        $larguraDaImagem = 800;
        $alturaDaImagem = 170;
        $this->idLocalizacao = $idLocalizacao;
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/etiqueta_painel_{$idLocalizacao}.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = parent::criarImagem();

        $blocoQrCode = parent::criarQrCode('P' . $this->idLocalizacao);

        $blocoQrCode->trim();
        $blocoQrCode->resize(165, 165);
        $etiqueta->insert($blocoQrCode, 'top-left', 4, 2);
        parent::aplicarTexto($etiqueta, 150, 180, 30, $this->idLocalizacao);

        return $etiqueta;
    }
}
