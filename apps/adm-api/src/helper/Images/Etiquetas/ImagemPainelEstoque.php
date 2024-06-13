<?php

namespace MobileStock\helper\Images\Etiquetas;

/**
 * @issue https://github.com/mobilestock/backend/issues/126
 */
class ImagemPainelEstoque extends ImagemAbstrata
{
    public string $idLocalizacao;

    public function __construct(string $idLocalizacao)
    {
        $this->idLocalizacao = $idLocalizacao;
        parent::__construct();
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = $this->diretorioRaiz . "/downloads/etiqueta_painel_{$idLocalizacao}.jpeg";
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();

        $imagemQrCode = $this->criarQrCode('P' . $this->idLocalizacao);

        imagecopymerge($etiqueta, $imagemQrCode, 0, 0, 0, 0, $this->alturaDaImagem, $this->alturaDaImagem, 100);
        $this->texto($etiqueta, 100, 180, 130, $this->idLocalizacao);

        return $etiqueta;
    }
}
