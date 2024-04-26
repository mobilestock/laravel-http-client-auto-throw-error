<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class EtiquetaProdutoEstoqueGD extends ImagemGDAbstrata
{
    private int $idProduto;
    private string $nomeTamanho;
    private string $referencia;
    private string $codBarras;

    public function __construct(
        int    $idProduto,
        string $nomeTamanho,
        string $referencia,
        string $codBarras,
        int    $larguraDaImagem = 800,
        int    $alturaDaImagem = 170)
    {
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->idProduto = $idProduto;
        $this->nomeTamanho = $nomeTamanho;
        $this->referencia = $referencia;
        $this->codBarras = $codBarras;

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/etiqueta_produto_estoque_{$idProduto}_{$codBarras}.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = parent::criarImagem();
        self::adicionarIdProduto($etiqueta);
        self::adicionarReferencia($etiqueta);
        self::adicionarTamanho($etiqueta);
        self::adicionarSublinhado($etiqueta);
        self::adicionarQrCode($etiqueta);

        return $etiqueta;
    }

    private function adicionarIdProduto(Image $etiqueta): void
    {
        $tamanhoDaFonte = 100;
        $posicaoHorizontal = 175;
        $posicaoVertical = 20;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->idProduto,
            '#000000',
            $this->fontes['bold']
        );
    }

    private function adicionarReferencia(Image $etiqueta): void
    {
        $tamanhoDaFonte = parent::calculaTamanhoTexto($this->referencia, 35);
        $posicaoHorizontal = 175;
        $posicaoVertical = 130;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->referencia
        );
    }

    private function adicionarTamanho(Image $etiqueta): void
    {
        $tamanhoDaFonte = 90;
        $posicaoHorizontal = 535;
        $posicaoVertical = 20;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            "$this->nomeTamanho"
        );
    }

    private function adicionarSublinhado(Image $etiqueta): void
    {
        $tamanhoDaFonte = 50;
        $posicaoHorizontal = 500;
        $posicaoVertical = 100;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            "____________"
        );
    }

    private function adicionarQrCode(Image $etiqueta): void
    {
        $blocoQrCode = parent::criarQrCode("SKU_{$this->idProduto}_{$this->codBarras}");
        $blocoQrCode->trim();
        $blocoQrCode->resize(165, 165);
        $etiqueta->insert($blocoQrCode, 'top-left', 4, 2);
    }
}
