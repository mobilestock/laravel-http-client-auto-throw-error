<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class ImagemPagamentoAprovadoMobileGD extends ImagemGDAbstrata
{
    private bool $ehMiniatura;
    private array $produtos;
    private int $qtdProdutos;
    private int $idTransacao;
    private int $posicaoVerticalDaFoto;
    private string $responsavel;
    private string $telefoneEntregador;
    private string $metodoDeEnvio;
    private string $endereco;
    private string $bairro;
    private string $cidade;
    private string $uf;
    private string $numero;

    public function __construct(array $dados, bool $ehMiniatura)
    {
        $larguraDaImagem = 350;
        $alturaDaImagem = 415;
        parent::__construct($larguraDaImagem, $alturaDaImagem);

        $template = $dados[0];

        $this->produtos = $dados;
        $this->ehMiniatura = $ehMiniatura;
        $this->posicaoVerticalDaFoto = 200;
        $this->qtdProdutos = count($dados);
        $this->idTransacao = $template['id_transacao'];
        $this->telefoneEntregador = $template['telefone_entregador'];
        $this->metodoDeEnvio = $template['metodo_de_envio'] === 'PP' ? 'Endereço de retirada' : 'Endereço de entrega';
        $this->endereco = $template['endereco']['endereco'];
        $this->bairro = $template['endereco']['bairro'];
        $this->cidade = $template['endereco']['cidade'];
        $this->uf = $template['endereco']['uf'];
        $this->numero = $template['endereco']['numero'];

        switch ($dados[0]['metodo_de_envio']) {
            case 'PM':
                $this->responsavel = 'Entregador';
                break;
            case 'PP':
                $this->responsavel = 'Ponto de Retirada';
                break;
            case 'Transportadora':
                $this->responsavel = 'Transportadora';
                break;
        }
    }

    public function renderizar(): Image
    {
        $espacamentoEntreProdutos = 100;
        $this->alturaDaImagem = $this->posicaoVerticalDaFoto + $espacamentoEntreProdutos * $this->qtdProdutos;

        $imagem = parent::criarImagem();

        self::adicionaPedido($imagem);
        self::adicionaSubtitulo($imagem);
        self::adicionaResponsavel($imagem);
        self::adicionaMetodoDeEnvio($imagem);
        self::adicionaEndereco($imagem);
        self::adicionaProdutos($imagem);

        return $imagem;
    }

    public function gerarImagemBase64(): string
    {
        $imagem = self::renderizar();
        $DadosDaImagem = $imagem->encode('png');

        $qualidadeDaImagem = 100;
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $imagem->save(__DIR__ . '/../../../../downloads/fatura.jpeg', $qualidadeDaImagem, 'jpeg');
        }

        $imagem->destroy();

        return base64_encode($DadosDaImagem);
    }

    private function adicionaPedido(Image $imagem): void
    {
        $tamanhoDaFonte = 20;
        $posicaoHorizontal = 10;
        $posicaoVertical = 10;
        $texto = 'Pedido ' . $this->idTransacao;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaSubtitulo(Image $imagem): void
    {
        $tamanhoDaFonte = 15;
        $posicaoHorizontal = 25;
        $posicaoVertical = 50;
        $texto = 'Metodo de Envio:';
        $corDaFonte = '#000000';
        parent::aplicarTexto(
            $imagem,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $texto,
            $corDaFonte,
            $this->fontes['bold']
        );
    }

    private function adicionaResponsavel(Image $imagem): void
    {
        $tamanhoDaFonte = 13;
        $posicaoHorizontal = 25;
        $posicaoVertical = 70;
        $texto = $this->responsavel . '  ' . $this->telefoneEntregador;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaMetodoDeEnvio(Image $imagem): void
    {
        $tamanhoDaFonte = 15;
        $posicaoHorizontal = 25;
        $posicaoVertical = 100;
        $corDaFonte = '#000000';
        parent::aplicarTexto(
            $imagem,
            $tamanhoDaFonte,
            $posicaoHorizontal,
            $posicaoVertical,
            $this->metodoDeEnvio,
            $corDaFonte,
            $this->fontes['bold']
        );
    }

    private function adicionaEndereco(Image $imagem): void
    {
        $tamanhoDaFonte = 13;
        $posicaoHorizontal = 25;
        $posicaoVertical = 120;
        $texto =
            $this->endereco .
            ', ' .
            $this->numero .
            PHP_EOL .
            $this->bairro .
            PHP_EOL .
            $this->cidade .
            ' - ' .
            $this->uf;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaProdutos(Image $imagem): void
    {
        $alturaPrimeiraLinha = 210;
        $alturaSegundaLinha = 240;
        $alturaTerceiraLinha = 265;
        $alturaBarraDivisao = 195;
        $horizontalDados = 89;
        $cor = '#000000';
        $barra = $this->gerenciadorDeImagem->canvas(320, 1, $cor);

        $nomeProdutoTamanhoDaFonte = 14;
        $tamanhoDoProdutoFonte = 13;
        $posicaoHorizontalTamanhoDoProduto = 305;
        $tamanhoDaFonteIdProduto = 16;
        $tamanhoDaFontePrevisaoEntrega = 10;

        foreach ($this->produtos as $produto) {
            $imagem->insert($barra, 'top-left', 15, $alturaBarraDivisao);

            $textoNomeDoProduto = substr($produto['nome_comercial'], 0, 28);
            $textoIdProduto = 'ID: ' . $produto['id_produto'];
            $textoPrevisaoEntrega =
                'Previsão de entrega: ' .
                $produto['previsao_entrega']['media_previsao_inicial'] .
                ' a ' .
                $produto['previsao_entrega']['media_previsao_final'];

            if ($this->ehMiniatura) {
                $fotoDoProduto = parent::criarImagem(null, $produto['foto_produto']);
                $fotoDoProduto->resize(70, 70);
                $imagem->insert($fotoDoProduto, 'top-left', 5, $this->posicaoVerticalDaFoto);
            } else {
                $horizontalDados = 15;
            }

            parent::aplicarTexto(
                $imagem,
                $nomeProdutoTamanhoDaFonte,
                $horizontalDados,
                $alturaPrimeiraLinha,
                $textoNomeDoProduto
            );
            parent::aplicarTexto(
                $imagem,
                $tamanhoDoProdutoFonte,
                $posicaoHorizontalTamanhoDoProduto,
                $alturaPrimeiraLinha,
                $produto['nome_tamanho']
            );
            parent::aplicarTexto(
                $imagem,
                $tamanhoDaFonteIdProduto,
                $horizontalDados,
                $alturaSegundaLinha,
                $textoIdProduto
            );

            if (
                !empty($produto['previsao_entrega']['media_previsao_inicial']) &&
                !empty($produto['previsao_entrega']['media_previsao_final'])
            ) {
                parent::aplicarTexto(
                    $imagem,
                    $tamanhoDaFontePrevisaoEntrega,
                    $horizontalDados,
                    $alturaTerceiraLinha,
                    $textoPrevisaoEntrega
                );
            }

            $alturaBarraDivisao += 100;
            $this->posicaoVerticalDaFoto += 100;
            $alturaPrimeiraLinha += 100;
            $alturaSegundaLinha += 100;
            $alturaTerceiraLinha += 100;
        }
    }
}
