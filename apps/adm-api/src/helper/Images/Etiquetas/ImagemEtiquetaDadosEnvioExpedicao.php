<?php

namespace MobileStock\helper\Images\Etiquetas;

class ImagemEtiquetaDadosEnvioExpedicao extends ImagemAbstrata
{
    public string $idEntrega;
    public string $nomeCliente;
    public string $endereco;
    public string $numero;
    public string $bairro;
    public string $cidade;
    public string $estado;
    public string $telefone;
    public string $volume;
    public string $volumeTotal;

    public function __construct(
        string $idEntrega,
        string $nomeCliente,
        string $endereco,
        string $numero,
        string $bairro,
        string $cidade,
        string $estado,
        string $telefone,
        string $volume,
        string $volumeTotal
    ) {
        $this->idEntrega = $idEntrega;
        $this->nomeCliente = $nomeCliente;
        $this->endereco = $endereco;
        $this->numero = $numero;
        $this->bairro = $bairro;
        $this->cidade = $cidade;
        $this->estado = $estado;
        $this->telefone = $telefone;
        $this->volume = $volume;
        $this->volumeTotal = $volumeTotal;
        parent::__construct();
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem =
                $this->diretorioRaiz . "/downloads/etiqueta_dados_envio_expedicao_{$idEntrega}.jpeg";
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();

        $destinatario = [
            'largura' => 800,
            'altura' => 70,
            'rgb' => [0, 0, 0],
        ];

        $tamanho_nome = 40;
        if (mb_strlen($this->nomeCliente) >= 20 && mb_strlen($this->nomeCliente) <= 30) {
            for ($i = 0; $i <= mb_strlen($this->nomeCliente); $i++) {
                $tamanho_nome = $tamanho_nome - 0.3;
            }
        } elseif (mb_strlen($this->nomeCliente) > 30) {
            $tamanho_nome = 30;
        }

        $areaDestinatario = $this->criaImagem($destinatario);
        $this->texto($areaDestinatario, $tamanho_nome, 10, 50, $this->nomeCliente, [255, 255, 255]);

        imagecopymerge(
            $etiqueta,
            $areaDestinatario,
            0,
            0,
            0,
            0,
            $destinatario['largura'],
            $destinatario['altura'],
            100
        );

        $this->texto($etiqueta, 20, 10, 100, $this->endereco . ', ' . $this->numero);
        $this->texto($etiqueta, 20, 10, 130, $this->bairro . ',' . $this->cidade . ' - ' . $this->estado);
        $this->texto($etiqueta, 20, 10, 160, $this->telefone);
        $this->texto($etiqueta, 40, 680, 155, $this->volume . '/' . $this->volumeTotal);

        return $etiqueta;
    }
}
