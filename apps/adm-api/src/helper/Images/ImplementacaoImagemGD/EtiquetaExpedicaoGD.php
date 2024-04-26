<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class EtiquetaExpedicaoGD extends ImagemGDAbstrata
{
    private int $idEntrega;
    private string $destino;
    private int $volume;
    private string $qrCode;
    private string $remetente;
    private ?string $apelidoRaio;

    public function __construct(
        int $idEntrega,
        string $destino,
        int $volume,
        string $qrCode,
        string $remetente,
        ?string $apelidoRaio,
        int $larguraDaImagem = 800,
        int $alturaDaImagem = 170
    ) {
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->idEntrega = $idEntrega;
        $this->destino = $destino;
        $this->volume = $volume;
        $this->qrCode = $qrCode;
        $this->remetente = $remetente;
        $this->apelidoRaio = $apelidoRaio;

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/etiqueta_expedicao_{$idEntrega}_{$volume}.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = parent::criarImagem();
        self::textoEntrega($etiqueta);
        if ($this->destino) self::textoCidade($etiqueta);
        self::textoVolume($etiqueta);
        self::textoRaioOuEntregador($etiqueta);
        self::adicionarQrCode($etiqueta);

        return $etiqueta;
    }

    private function textoEntrega(Image $etiqueta): void
    {
        $tamanhoDaFonte = 18;
        $posicaoHorizontal = 178;
        $posicaoVertical = 10;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            "Entrega: $this->idEntrega"
        );
    }

    private function textoCidade(Image $etiqueta): void
    {
        $tamanhoDaFonte = 18;
        $posicaoHorizontal = 500;
        $posicaoVertical = 10;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            "Cidade: $this->destino"
        );
    }

    private function textoVolume(Image $etiqueta): void
    {
        $tamanhoDaFonte = 18;
        $posicaoHorizontal = 178;
        $posicaoVertical = 140;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            "Volume: $this->volume"
        );
    }

    private function textoRaioOuEntregador(Image $etiqueta): void
    {
        $tamanhoDaFonte = 50;
        $posicaoHorizontal = 180;
        $posicaoVertical = 65;
        $remetente = $this->apelidoRaio ? $this->apelidoRaio : $this->remetente;
        $tamanhoDaFonte = parent::calculaTamanhoTexto($remetente, $tamanhoDaFonte);
        parent::aplicarTexto($etiqueta, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $remetente);
    }

    private function adicionarQrCode(Image $etiqueta): void
    {
        $posicaoHorizontal = 0;
        $posicaoVertical = 0;
        $qrCodeRenderizado = parent::criarQrCode($this->qrCode);
        $etiqueta->insert($qrCodeRenderizado, 'top-left', $posicaoHorizontal, $posicaoVertical);
    }
}
