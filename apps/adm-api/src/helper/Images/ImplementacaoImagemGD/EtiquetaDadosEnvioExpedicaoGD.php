<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class EtiquetaDadosEnvioExpedicaoGD extends ImagemGDAbstrata
{
    private string $nomeCliente;
    private string $endereco;
    private string $numero;
    private string $bairro;
    private string $cidade;
    private string $estado;
    private string $telefone;
    private int $volume;
    private int $volumeTotal;

    public function __construct(
        int $idEntrega,
        string $nomeCliente,
        string $endereco,
        string $numero,
        string $bairro,
        string $cidade,
        string $estado,
        string $telefone,
        int $volume,
        int $volumeTotal
    ) {
        $larguraDaImagem = 800;
        $alturaDaImagem = 170;
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->nomeCliente = $nomeCliente;
        $this->endereco = $endereco;
        $this->numero = $numero;
        $this->bairro = $bairro;
        $this->cidade = $cidade;
        $this->estado = $estado;
        $this->telefone = $telefone;
        $this->volume = $volume;
        $this->volumeTotal = $volumeTotal;
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem =
                $this->diretorioRaiz . "/downloads/etiqueta_dados_envio_expedicao_{$idEntrega}.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = parent::criarImagem();
        self::adicionarCliente($etiqueta);
        self::adicionarEndereco($etiqueta);
        self::adicionaVolume($etiqueta);
        return $etiqueta;
    }

    private function adicionarCliente(Image $etiqueta): void
    {
        $posicaoHorizontal = 5;
        $posicaoVertical = 25;
        $corTexto = '#FFFFFF';

        $destinatario = [
            'largura' => 800,
            'altura' => 70,
            'cor_de_fundo' => '#000000',
        ];

        $fonteNome = 60;
        if (mb_strlen($this->nomeCliente) >= 20 && mb_strlen($this->nomeCliente) <= 30) {
            $diminuirFonteEm = mb_strlen($this->nomeCliente) * 0.5;
            $fonteNome -= $diminuirFonteEm;
        } elseif (mb_strlen($this->nomeCliente) > 30) {
            $fonteNome = 40;
        }

        $areaDestinatario = parent::criarImagem($destinatario);
        parent::aplicarTexto(
            $areaDestinatario,
            $fonteNome,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->nomeCliente,
            $corTexto,
            $this->fontes['bold']
        );

        $etiqueta->insert($areaDestinatario, 'top-left', 0, 0);
    }

    private function adicionarEndereco(Image $etiqueta): void
    {
        $tamanhofonte = 25;
        $cor = '#000000';
        $estiloFonte = $this->fontes['bold'];
        $posicaoHorizontal = 10;
        $primeiraLinha = 80;
        $segundaLinha = 110;
        $terceiraLinha = 140;
        $enderecoFormatado = $this->endereco . ', ' . $this->numero;
        $estadoFormatado = $this->bairro . ', ' . $this->cidade . ' - ' . $this->estado;
        parent::aplicarTexto(
            $etiqueta,
            $tamanhofonte,
            $posicaoHorizontal,
            $primeiraLinha,
            $enderecoFormatado,
            $cor,
            $estiloFonte
        );
        parent::aplicarTexto(
            $etiqueta,
            $tamanhofonte,
            $posicaoHorizontal,
            $segundaLinha,
            $estadoFormatado,
            $cor,
            $estiloFonte
        );
        parent::aplicarTexto(
            $etiqueta,
            $tamanhofonte,
            $posicaoHorizontal,
            $terceiraLinha,
            $this->telefone,
            $cor,
            $estiloFonte
        );
    }

    private function adicionaVolume(Image $etiqueta): void
    {
        $fonte = 60;
        $posicaoHorizontal = 675;
        $posicaoVertical = 100;
        $texto = $this->volume . '/' . $this->volumeTotal;
        parent::aplicarTexto(
            $etiqueta,
            $fonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $texto,
            '#000000',
            $this->fontes['bold']
        );
    }
}
