<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class EtiquetaClienteGD extends ImagemGDAbstrata
{
    private string $remetente;
    private string $nomeProduto;
    private string $tamanho;
    private string $qrCode;
    private string $destinatario;
    private string $cidade;
    private string $ponto;
    private string $entregador;
    private string $dataLimiteTrocaMobile;
    private array $blocoRemetente;
    private array $blocoEntregador;

    public function __construct(
        string $remetente,
        string $nomeProduto,
        string $tamanho,
        string $qrCode,
        string $destinatario,
        string $cidade,
        string $ponto,
        string $entregador,
        string $dataLimiteTrocaMobile,
        int $larguraDaImagem = 800,
        int $alturaDaImagem = 170
    ) {
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->blocoRemetente = ['largura' => 450, 'altura' => 50, 'cor_de_fundo' => '#000000'];
        $this->blocoEntregador = ['largura' => 200, 'altura' => 40, 'cor_de_fundo' => '#ffffff'];
        $this->remetente = $remetente;
        $this->nomeProduto = $nomeProduto;
        $this->tamanho = $tamanho;
        $this->qrCode = $qrCode;
        $this->destinatario = $destinatario;
        $this->cidade = $cidade;
        $this->ponto = $ponto;
        $this->entregador = $entregador;
        $this->dataLimiteTrocaMobile = $dataLimiteTrocaMobile;

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $nomeTamanho = strtolower(preg_replace("/[^0-9A-Za-z]/", "", $tamanho));
            $uuid = explode("w=", $qrCode)[1];
            $idProduto = trim(explode("-", $nomeProduto)[0]);
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/{$idProduto}_{$nomeTamanho}_{$uuid}.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = $this->criarImagem();
        self::adicionarRemetente($etiqueta);
        self::adicionarDestinatario($etiqueta);
        self::adicionaCidade($etiqueta);
        self::adicionarProduto($etiqueta);
        self::adicionarQrCode($etiqueta);

        return $etiqueta;
    }

    private function adicionarRemetente(Image $etiqueta): void
    {
        $tamnhoDaFonte = 22;
        $posicaoHorizontal = 2;
        $posicaoVertical = 16;
        $corDaFonte = '#ffffff';
        $insercaoHorizontal = 170;
        $insercaoVertical = 0;

        $areaRemetente = $this->criarImagem($this->blocoRemetente);
        $this->aplicarTexto(
            $areaRemetente,
            $tamnhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->remetente,
            $corDaFonte
        );

        $etiqueta->insert($areaRemetente, 'top-center', $insercaoHorizontal, $insercaoVertical);
    }

    private function adicionarDestinatario(Image $etiqueta): void
    {
        $posicaoHorizontal = 175;
        $posicaoVertical = 130;
        $tamanhoDaFonte = 35;

        if ($this->destinatario) {
            $tamanhoDaFonteRemetente = 28;
            $posicaoVerticalRemetente = 115;
            if (mb_strlen($this->destinatario) >= 26) {
                for ($i = 0; $i <= floor(mb_strlen($this->destinatario) / 20); $i++) {
                    if ($tamanhoDaFonteRemetente <= 14) {
                        $posicaoVerticalRemetente = 80;
                        continue;
                    }
                    if ($tamanhoDaFonteRemetente >= 20) {
                        $posicaoVerticalRemetente -= 13;
                        $tamanhoDaFonteRemetente -= 6;
                    }
                }
            $this->aplicarTexto(
                $etiqueta,
                $tamanhoDaFonteRemetente,
                $posicaoHorizontal,
                $posicaoVerticalRemetente,
                $this->destinatario
            );
            }
            self::adicionaEntregador($etiqueta);
        } else {
            $this->aplicarTexto(
                $etiqueta,
                $tamanhoDaFonte,
                $posicaoHorizontal,
                $posicaoVertical,
                $this->ponto);
        }
    }

    private function adicionaEntregador(Image $etiqueta): void
    {
        $tamnhoDaFonte = 26;
        $areaEntregador = $this->criarImagem($this->blocoEntregador);
        $tamanhoTexto = 15 * mb_strlen($this->entregador);
        $coordenadasDoTextoEntregador = [
            'x' => ($this->blocoEntregador['largura'] / 2) - ($tamanhoTexto / 2),
            'y' => ($this->blocoEntregador['altura'] / 4) * 1
        ];
        $insercaoHorizontal = 580;
        $insercaoVerdical = 130;

        $this->aplicarTexto(
            $areaEntregador,
            $tamnhoDaFonte,
            $coordenadasDoTextoEntregador['x'],
            $coordenadasDoTextoEntregador['y'],
            $this->entregador
        );

        $etiqueta->insert($areaEntregador, 'top-left', $insercaoHorizontal, $insercaoVerdical);
    }

    private function adicionaCidade(Image $etiqueta): void
    {
        $tamanhoDaFonte = 24;
        $posicaoHorizontal = 500;
        $posicaoVertical = 100;
        $cidadeFormatada = "";

        if ($this->cidade) {
            $limiteDeCorte = 32;
            if (mb_strlen($this->cidade) >= $limiteDeCorte) {
                for ($indice = 0; $indice <= floor(mb_strlen($this->cidade) / $limiteDeCorte); $indice++) {
                    $cidadeFormatada .= substr($this->cidade, $indice * $limiteDeCorte, $indice + $limiteDeCorte);
                    if (in_array($cidadeFormatada[mb_strlen($cidadeFormatada) - 1], [" ", PHP_EOL])) {
                        $cidadeFormatada .= PHP_EOL;
                    } else {
                        $cidadeFormatada .= "..." . PHP_EOL;
                    }
                    if ($tamanhoDaFonte === 12) {
                        $posicaoVertical = 120;
                        continue;
                    }
                    $posicaoVertical -= 12;
                    $tamanhoDaFonte -= 5;
                }
            } else {
                $cidadeFormatada = $this->cidade;
            }
            $cidadeFormatada = rtrim(trim($cidadeFormatada), ".");
            $this->aplicarTexto($etiqueta, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $cidadeFormatada);
        }
    }

    private function adicionarProduto(Image $etiqueta): void
    {
        $tamanhoDaFonteProduto = 33;
        $posicaoHorizontalProduto = 178;
        $posicaoVerticalProduto = 60;

        $tamanhoDaFonte = 36;
        $posicaoHorizontal = 650;
        $posicaoVertical = 15;

        $tamanhoDaFonteTexto = 14;
        $posicaoHorizontalTexto = 627;
        $posicaoVerticalTextoPrimeiraLinha = 60;
        $posicaoVerticalTextoSegundaLinha = 75;
        $mensagemSegundaLinha = "apenas com embalagem";

        if (mb_strlen($this->nomeProduto) >= 10) {
            for ($indice = 0; $indice <= floor(mb_strlen($this->nomeProduto) / 9); $indice++) {
                if ($tamanhoDaFonteProduto <= 12) {
                    break;
                }
                $tamanhoDaFonteProduto -= $indice * 2;
            }
        }

        $this->aplicarTexto(
            $etiqueta,
            $tamanhoDaFonteProduto,
            $posicaoHorizontalProduto,
            $posicaoVerticalProduto,
            $this->nomeProduto
        );

        $this->aplicarTexto(
            $etiqueta,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->tamanho
        );

        $this->aplicarTexto(
            $etiqueta,
            $tamanhoDaFonteTexto,
            $posicaoHorizontalTexto,
            $posicaoVerticalTextoPrimeiraLinha,
            $this->dataLimiteTrocaMobile
        );

        $this->aplicarTexto(
            $etiqueta,
            $tamanhoDaFonteTexto,
            $posicaoHorizontalTexto,
            $posicaoVerticalTextoSegundaLinha,
            $mensagemSegundaLinha
        );
    }

    private function adicionarQrCode(Image $etiqueta): void
    {
        $blocoQrCode = parent::criarQrCode($this->qrCode);
        $blocoQrCode->trim();
        $blocoQrCode->resize(165, 165);
        $etiqueta->insert($blocoQrCode, 'top-left', 4, 2);
    }
}
