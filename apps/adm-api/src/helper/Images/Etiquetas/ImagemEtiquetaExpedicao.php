<?php

namespace MobileStock\helper\Images\Etiquetas;

class ImagemEtiquetaExpedicao extends ImagemAbstrata
{
    public string $id_entrega;
    public string $destino;
    public string $volume;
    public string $qrcode;
    public string $remetente;
    public ?string $apelidoRaio;

    public function __construct(
        string $id_entrega,
        string $destino,
        string $volume,
        string $qrcode,
        string $remetente,
        ?string $apelidoRaio = null
    ) {
        $this->id_entrega = $id_entrega;
        $this->destino = $destino;
        $this->volume = $volume;
        $this->qrcode = $qrcode;
        $this->remetente = $remetente;
        $this->apelidoRaio = $apelidoRaio;
        parent::__construct();
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem =
                $this->diretorioRaiz . "/downloads/etiqueta_expedicao_{$id_entrega}_{$volume}.jpeg";
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();

        $espacamentoQrcode = $this->alturaDaImagem + 10;

        $this->texto($etiqueta, 20, $espacamentoQrcode, 24, "Entrega: $this->id_entrega");
        $this->texto($etiqueta, 20, $espacamentoQrcode + 320, 24, "Volume: $this->volume");

        if (!empty($this->apelidoRaio)) {
            $fonteApelido = $this->calculaTamanhoFonte($this->apelidoRaio, 36);
            $this->texto($etiqueta, $fonteApelido, $espacamentoQrcode, 90, "Raio: $this->apelidoRaio");
        } else {
            $fonteRemetente = $this->calculaTamanhoFonte($this->remetente);
            $this->texto($etiqueta, $fonteRemetente, $espacamentoQrcode, 90, $this->remetente);
        }
        if ($this->destino) {
            $this->texto($etiqueta, 20, $espacamentoQrcode, 150, "Cidade: $this->destino");
        }

        $imagemQrCode = $this->criarQrCode($this->qrcode);
        imagecopymerge($etiqueta, $imagemQrCode, 0, 0, -1, 0, $this->alturaDaImagem, $this->alturaDaImagem, 100);

        return $etiqueta;
    }
}
