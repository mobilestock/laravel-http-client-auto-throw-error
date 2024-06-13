<?php

namespace MobileStock\helper\Images\Etiquetas;

class ImagemEtiquetaProdutoEstoque extends ImagemAbstrata
{
    private int $idProduto;
    private string $nomeTamanho;
    private string $referencia;
    private string $codBarras;

    public function __construct(int $idProduto, string $nomeTamanho, string $referencia, string $codBarras)
    {
        $this->idProduto = $idProduto;
        $this->nomeTamanho = $nomeTamanho;
        $this->referencia = $referencia;
        $this->codBarras = $codBarras;
        parent::__construct();

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/etiqueta_produto_estoque_{$idProduto}_{$codBarras}.jpeg";
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();
        $this->texto($etiqueta, 65, 170, 70, $this->idProduto);
        $this->texto($etiqueta, $this->calculaTamanhoFonte($this->referencia, 35), 175, 160, $this->referencia);
        $this->texto($etiqueta, 80, 500, 100, "$this->nomeTamanho");
        $this->texto($etiqueta, 50, 500, 100, '_________');
        $blocoQrCode = $this->criarQrCode("SKU_{$this->idProduto}_{$this->codBarras}");
        imagecopymerge($etiqueta, $blocoQrCode, 0, 0, 0, 0, $this->alturaDaImagem, $this->alturaDaImagem, 100);
        return $etiqueta;
    }
}
