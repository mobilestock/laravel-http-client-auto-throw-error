<?php

namespace MobileStock\helper\Images\Etiquetas;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ImagemEtiquetaSku extends ImagemAbstrata
{
    private int $idProduto;
    private string $nomeTamanho;
    private string $referencia;
    private string $sku;

    public function __construct(int $idProduto, string $nomeTamanho, string $referencia, string $sku)
    {
        $this->idProduto = $idProduto;
        $this->nomeTamanho = $nomeTamanho;
        $this->referencia = $referencia;
        $this->sku = $sku;
        parent::__construct();

        if (!App::isProduction()) {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/sku_{$sku}.jpeg";
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();
        $this->texto($etiqueta, 70, 220, 70, $this->idProduto);
        $this->texto($etiqueta, $this->calculaTamanhoFonte($this->referencia, 30), 200, 140, $this->referencia);
        $this->texto($etiqueta, 20, 200, 168, Str::formatarSku($this->sku));

        $length = mb_strlen($this->nomeTamanho);
        $posicaoX = 680 - ($length - 2) * 40;
        $underscore = str_repeat('_', $length + 1);
        $fonteTamanho = 55;
        $fonteUnderline = 45;

        if (
            !preg_match('/\d/', $this->nomeTamanho) &&
            mb_strtoupper($this->nomeTamanho) === $this->nomeTamanho &&
            $length > 1
        ) {
            $fonteTamanho = 45;
        }

        if ($posicaoX < 580) {
            $posicaoX = 580;
            $fonteUnderline = 50;
        }

        $this->texto($etiqueta, $fonteTamanho, $posicaoX, 80, $this->nomeTamanho);
        $this->texto($etiqueta, $fonteUnderline, $posicaoX, 80, $underscore);

        $blocoQrCode = $this->criarQrCode("SKU{$this->sku}");
        imagecopymerge($etiqueta, $blocoQrCode, 10, 0, 0, 0, $this->alturaDaImagem, $this->alturaDaImagem, 100);
        return $etiqueta;
    }
}
