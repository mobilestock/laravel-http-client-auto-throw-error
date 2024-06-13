<?php

namespace MobileStock\helper\Images\Etiquetas;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\PngResult;
use GdImage;
use Zebra\Zpl\GdDecoder;
use Zebra\Zpl\Builder as ZplBuilder;
use Zebra\Zpl\Image;

abstract class ImagemAbstrata
{
    protected int $larguraDaImagem;
    protected int $alturaDaImagem;
    protected string $diretorioRaiz;
    protected array $imagens;
    protected string $diretorioFinalDaImagem = '';

    public function __construct()
    {
        $this->larguraDaImagem = 800;
        $this->alturaDaImagem = 170;
        $this->diretorioRaiz = __DIR__ . '/../../../..';

        header('Content-Type: image/png');
    }

    /**
     * @return GdImage|resource
     */
    abstract public function renderiza();

    public function criarZpl(): string
    {
        $etiqueta = $this->renderiza();
        if ($_ENV['AMBIENTE'] !== 'producao' && $this->diretorioFinalDaImagem) {
            imagejpeg($etiqueta, $this->diretorioFinalDaImagem);
        }

        $decoder = GdDecoder::fromResource($etiqueta);
        $image = new Image($decoder);

        $zpl = new ZplBuilder();
        $zpl->command('fo', 20, 20)->gf($image);

        array_map(
            fn($imagem) => imagedestroy($imagem),
            array_filter($this->imagens, fn($imagem) => $etiqueta !== $imagem)
        );
        $this->larguraDaImagem = 0;
        $this->alturaDaImagem = 0;
        $this->imagens = [];
        $this->diretorioFinalDaImagem = '';

        return $zpl->toZpl();
    }
    /**
     * @return GdImage|resource
     */
    protected function criaImagem(
        array $parametros = [
            'largura' => 0,
            'altura' => 0,
            'rgb' => [255, 255, 255],
        ]
    ) {
        $indice = rand(0, 1000000);
        $this->imagens[$indice] = imagecreate(
            $parametros['largura'] > 0 ? $parametros['largura'] : $this->larguraDaImagem,
            $parametros['altura'] > 0 ? $parametros['altura'] : $this->alturaDaImagem
        );
        [$vermelho, $verde, $azul] = $parametros['rgb'];
        imagecolorallocate($this->imagens[$indice], $vermelho, $verde, $azul);

        return $this->imagens[$indice];
    }
    protected function texto(
        $imagem,
        int $tamanhoFonte,
        int $posicaoX,
        int $posicaoY,
        string $texto,
        array $rgb = [0, 0, 0]
    ): void {
        [$vermelho, $verde, $azul] = $rgb;
        $corDoTexto = imagecolorallocate($imagem, $vermelho, $verde, $azul);
        imagettftext(
            $imagem,
            $tamanhoFonte,
            0,
            $posicaoX,
            $posicaoY,
            $corDoTexto,
            $this->diretorioRaiz . '/fonts/Swis721_BT_Bold.ttf',
            $texto
        );
    }
    /**
     * @return GdImage|resource
     */
    protected function criarQrCode(string $dado)
    {
        $indice = 'qrcode_' . rand(0, 1000000);

        /** @var PngResult $resultado */
        $resultado = Builder::create()
            ->writer(new PngWriter())
            ->data($dado)
            ->encoding(new Encoding('UTF-8'))
            ->size($this->alturaDaImagem)
            ->margin(0)
            ->build();

        $this->imagens[$indice] = $resultado->getImage();

        return $this->imagens[$indice];
    }
    protected function calculaTamanhoFonte(string $texto, int $fonteReferencia = 40): int
    {
        if (mb_strlen($texto) >= 10) {
            for ($indice = 0; $indice <= floor(mb_strlen($texto) / 10); $indice++) {
                $fonteReferencia -= $indice * 2;
            }
        }

        return $fonteReferencia;
    }
}
