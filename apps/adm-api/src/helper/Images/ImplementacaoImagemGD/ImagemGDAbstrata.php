<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\PngResult;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Zebra\Zpl\GdDecoder;
use Zebra\Zpl\Image as ZplImage;
use Zebra\Zpl\Builder as ZplBuilder;

abstract class ImagemGDAbstrata implements ImagemInterface
{
    protected ImageManager $gerenciadorDeImagem;
    protected int $larguraDaImagem;
    protected int $alturaDaImagem;
    protected string $diretorioRaiz;
    protected array $imagens;
    protected string $diretorioFinalDaImagem = '';
    protected string $corDeFundo;
    protected array $fontes;

    public function __construct(int $larguraDaImagem, int $alturaDaImagem, string $corDeFundo = '#ffffff')
    {
        $this->gerenciadorDeImagem = new ImageManager(['driver' => 'gd']);
        $this->diretorioRaiz = __DIR__ . '/../../../..';
        $this->fontes = [
            'bold' => $this->diretorioRaiz . '/fonts/Roboto-Bold.ttf',
            'light' => $this->diretorioRaiz . '/fonts/Roboto-Light.ttf',
            'regular' => $this->diretorioRaiz . '/fonts/Roboto-Regular.ttf',
        ];

        $this->larguraDaImagem = $larguraDaImagem;
        $this->alturaDaImagem = $alturaDaImagem;
        $this->corDeFundo = $corDeFundo;

        header('Content-Type: image/png');
    }

    public function criarImagem(array $dimensoes = null, string $imagemQueVemDoHeader = null): Image
    {
        if ($imagemQueVemDoHeader) {
            $dadosDaImagem = file_get_contents($imagemQueVemDoHeader);
            return $this->gerenciadorDeImagem->make($dadosDaImagem);
        }

        if (empty($dimensoes)) {
            $dimensoes = [
                'largura' => $this->larguraDaImagem,
                'altura' => $this->alturaDaImagem,
                'cor_de_fundo' => $this->corDeFundo,
            ];
        }

        $imagem = $this->gerenciadorDeImagem->canvas(
            $dimensoes['largura'],
            $dimensoes['altura'],
            $dimensoes['cor_de_fundo']
        );
        return $imagem;
    }

    abstract public function renderizar(): Image;

    public function criarZpl(): string
    {
        $imagemGDRenderizada = $this->renderizar();
        $recursoDaImagem = $imagemGDRenderizada->getCore();
        if ($_ENV['AMBIENTE'] !== 'producao' && $this->diretorioFinalDaImagem) {
            $qualidadeDaImagem = 100;
            $imagemGDRenderizada->save($this->diretorioFinalDaImagem, $qualidadeDaImagem);
        }

        $decoder = GdDecoder::fromResource($recursoDaImagem);
        $imagem = new ZplImage($decoder);

        $zpl = new ZplBuilder();
        $posicaoX = 20;
        $posicaoY = 20;
        $zpl->command('fo', $posicaoX, $posicaoY)->gf($imagem);

        self::limparImagens();

        return $zpl->toZpl();
    }

    public function calculaTamanhoTexto(string $texto, int $fonte): int
    {
        if (mb_strlen($texto) >= 10) {
            for ($indice = 0; $indice <= floor(mb_strlen($texto) / 10); $indice++) {
                $fonte -= $indice * 2;
            }
        }
        return $fonte;
    }

    public function aplicarTexto(
        Image $imagem,
        int $tamanhoFonte,
        int $posicaoHorizontal,
        int $posicaoVertical,
        string $texto,
        string $corDaFonte = '#000000',
        string $fonte = null
    ): void {
        if ($fonte === null) {
            $fonte = $this->fontes['regular'];
        }

        $imagem->text($texto, $posicaoHorizontal, $posicaoVertical, function ($font) use (
            $tamanhoFonte,
            $corDaFonte,
            $fonte
        ) {
            $font->file($fonte);
            $font->size($tamanhoFonte);
            $font->color($corDaFonte);
            $font->align('left');
            $font->valign('top');
        });
    }

    public function criarQrCode(string $dado): Image
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

        $gdImage = $resultado->getImage();
        ob_start();
        imagepng($gdImage);
        $data = ob_get_clean();
        $this->imagens[$indice] = $this->gerenciadorDeImagem->make($data);

        return $this->imagens[$indice];
    }

    private function limparImagens()
    {
        $this->larguraDaImagem = 0;
        $this->alturaDaImagem = 0;
        $this->imagens = [];
        $this->diretorioFinalDaImagem = '';
    }
}
