<?php

namespace MobileStock\helper\Images\Etiquetas;

class ImagemEtiquetaCliente extends ImagemAbstrata
{
    public string $remetente;
    public string $produto;
    public string $tamanho;
    public string $qrcode;
    public string $destinatario;
    public string $cidade;
    public string $ponto;
    public string $entregador;
    public string $dataLimiteTrocaMobile;

    public function __construct(
        string $remetente,
        string $produto,
        string $tamanho,
        string $qrcode,
        string $destinatario,
        string $cidade,
        string $ponto,
        string $entregador,
        string $dataLimiteTrocaMobile
    ) {
        $this->remetente = $remetente;
        $this->produto = $produto;
        $this->tamanho = $tamanho;
        $this->qrcode = $qrcode;
        $this->destinatario = $destinatario;
        $this->cidade = $cidade;
        $this->ponto = $ponto;
        $this->entregador = $entregador;
        $this->dataLimiteTrocaMobile = $dataLimiteTrocaMobile;
        parent::__construct();

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $nomeTamanho = mb_strtolower(preg_replace('/[^0-9A-Za-z]/', '', $tamanho));
            $uuid = explode('w=', $qrcode)[1];
            $idProduto = trim(explode('-', $produto)[0]);
            $this->diretorioFinalDaImagem =
                $this->diretorioRaiz . "/downloads/{$idProduto}_{$nomeTamanho}_{$uuid}.jpeg";
        }
    }
    public function renderiza()
    {
        $etiqueta = $this->criaImagem();

        $dimencoesAreaRemetente = [
            'largura' => 450,
            'altura' => 40,
            'rgb' => [0, 0, 0],
        ];
        $areaRemetente = $this->criaImagem($dimencoesAreaRemetente);
        $this->texto($areaRemetente, 16, 10, 25, $this->remetente, [255, 255, 255]);

        imagecopymerge(
            $etiqueta,
            $areaRemetente,
            170,
            0,
            0,
            0,
            $dimencoesAreaRemetente['largura'],
            $dimencoesAreaRemetente['altura'],
            100
        );

        if ($this->destinatario) {
            $tamanhoDaFonteRemetente = 30;
            $alturaDoTextoRemetente = 160;
            if (mb_strlen($this->destinatario) >= 22) {
                for ($i = 0; $i <= floor(mb_strlen($this->destinatario) / 22); $i++) {
                    if ($tamanhoDaFonteRemetente <= 12) {
                        $alturaDoTextoRemetente = 100;
                        continue;
                    }
                    if ($tamanhoDaFonteRemetente >= 14) {
                        $alturaDoTextoRemetente -= 15;
                        $tamanhoDaFonteRemetente -= 6;
                    }
                }
            }
            $this->texto($etiqueta, $tamanhoDaFonteRemetente, 170, $alturaDoTextoRemetente, $this->destinatario);

            $dimencoesAreaEntregador = [
                'largura' => 200,
                'altura' => 40,
                'rgb' => [255, 255, 255],
            ];

            $areaEntregador = $this->criaImagem($dimencoesAreaEntregador);

            $tamanhoTexto = 12 * mb_strlen($this->entregador);
            $coordenadasDoTextoEntregador = [
                'x' => $dimencoesAreaEntregador['largura'] / 2 - $tamanhoTexto / 2,
                'y' => ($dimencoesAreaEntregador['altura'] / 4) * 3,
            ];

            $this->texto(
                $areaEntregador,
                18,
                $coordenadasDoTextoEntregador['x'],
                $coordenadasDoTextoEntregador['y'],
                $this->entregador
            );

            imagecopymerge(
                $etiqueta,
                $areaEntregador,
                580,
                120,
                0,
                0,
                $dimencoesAreaEntregador['largura'],
                $dimencoesAreaEntregador['altura'],
                100
            );
        } else {
            $this->texto($etiqueta, 25, 170, 150, $this->ponto);
        }

        if ($this->cidade) {
            $tamanhoDaFonteCidade = 16;
            $alturaDoTextoCidade = 110;
            $cidadeFormatada = '';
            $limiteDeCorte = 24;
            if (mb_strlen($this->cidade) >= $limiteDeCorte) {
                for ($indice = 0; $indice <= floor(mb_strlen($this->cidade) / $limiteDeCorte); $indice++) {
                    $cidadeFormatada .= mb_substr($this->cidade, $indice * $limiteDeCorte, $indice + $limiteDeCorte);
                    if (in_array($cidadeFormatada[mb_strlen($cidadeFormatada) - 1], [' ', PHP_EOL])) {
                        $cidadeFormatada .= PHP_EOL;
                    } else {
                        $cidadeFormatada .= '...' . PHP_EOL;
                    }
                    if ($tamanhoDaFonteCidade === 12) {
                        $alturaDoTextoCidade = 120;
                        continue;
                    }
                    $alturaDoTextoCidade -= 12;
                    $tamanhoDaFonteCidade -= 5;
                }
            } else {
                $cidadeFormatada = $this->cidade;
            }
            $cidadeFormatada = rtrim(trim($cidadeFormatada), '.');
            $this->texto($etiqueta, $tamanhoDaFonteCidade, 580, $alturaDoTextoCidade, $cidadeFormatada);
        }

        $tamanhoTextoProduto = 18;
        if (mb_strlen($this->produto) >= 10) {
            for ($indice = 0; $indice <= floor(mb_strlen($this->produto) / 10); $indice++) {
                if ($tamanhoTextoProduto <= 12) {
                    break;
                }
                $tamanhoTextoProduto -= $indice * 2;
            }
        }
        $this->texto($etiqueta, $tamanhoTextoProduto, 170, 65, $this->produto);

        $this->texto($etiqueta, 25, 660, 40, $this->tamanho);
        $this->texto($etiqueta, 11, 620, 60, $this->dataLimiteTrocaMobile);
        $this->texto($etiqueta, 11, 620, 75, 'apenas com embalagem');

        $imagemQrCode = $this->criarQrCode($this->qrcode);

        imagecopymerge($etiqueta, $imagemQrCode, 0, 0, 0, 0, $this->alturaDaImagem, $this->alturaDaImagem, 100);

        return $etiqueta;
    }
}
