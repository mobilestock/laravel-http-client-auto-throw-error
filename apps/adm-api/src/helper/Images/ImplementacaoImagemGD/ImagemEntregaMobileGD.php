<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class ImagemEntregaMobileGD extends ImagemGDAbstrata
{
    public int $idEntrega;
    public bool $miniatura;
    public array $produtos;
    public string $dataAtualizacao;
    public string $razaoSocial;
    public string $endereco;
    public string $numero;
    public string $bairro;
    public string $cidade;
    public string $uf;

    public function __construct(
        array $dadosParaImagem,
        bool $miniatura = true,
        int $larguraDaImagem = 400,
        int $alturaDaImagem = 215
    ) {
        parent::__construct($larguraDaImagem, $alturaDaImagem);
        $this->miniatura = $miniatura;

        $this->idEntrega = $dadosParaImagem['id'];
        $this->produtos = $dadosParaImagem['produtos'];
        $this->dataAtualizacao = $dadosParaImagem['data_atualizacao'];
        $this->razaoSocial = $dadosParaImagem['razao_social'];
        $this->endereco = $dadosParaImagem['endereco'];
        $this->numero = $dadosParaImagem['numero'];
        $this->bairro = $dadosParaImagem['bairro'];
        $this->cidade = $dadosParaImagem['cidade'];
        $this->uf = $dadosParaImagem['uf'];
    }

    public function renderizar(): Image
    {
        $qtdProdutos = count($this->produtos);
        $espacamentoEntreProdutos = 120;
        $this->alturaDaImagem += ($espacamentoEntreProdutos* $qtdProdutos);

        $imagem = $this->criarImagem();

        self::adicionaNumeroDaEntrega($imagem);
        self::adicionaDataDaEntrega($imagem);
        self::adicionaNomeDoCliente($imagem);
        self::adicionaEnderecoDoCliente($imagem);
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

    private function adicionaNumeroDaEntrega(Image $imagem): void
    {
        $tamanhoDaFonte = 20;
        $posicaoHorizontal = 15;
        $posicaoVertical = 20;
        $texto = 'ENTREGA ' . $this->idEntrega;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaDataDaEntrega(Image $imagem): void
    {
        $tamanhoDaFonte = 20;
        $posicaoHorizontal = 15;
        $posicaoVertical = 40;
        $texto = $this->dataAtualizacao;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaNomeDoCliente(Image $imagem): void
    {
        $tamanhoDaFonte = 20;
        $posicaoHorizontal = 15;
        $posicaoVertical = 100;
        $texto = $this->razaoSocial;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVertical, $texto);
    }

    private function adicionaEnderecoDoCliente(Image $imagem): void
    {
        $tamanhoDaFonte = 14;
        $posicaoHorizontal = 15;
        $posicaoVerticalRuaENumero = 130;
        $posicaoVerticalBairro = 147;
        $posicaoVerticalCidade = 165;
        $textoRuaENumero = $this->endereco . ' ' . $this->numero;
        $textoBairro = $this->bairro;
        $textoCidadeComUf = $this->cidade . ' ' . $this->uf;
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVerticalRuaENumero, $textoRuaENumero);
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVerticalBairro, $textoBairro);
        parent::aplicarTexto($imagem, $tamanhoDaFonte, $posicaoHorizontal, $posicaoVerticalCidade, $textoCidadeComUf);
    }

    private function adicionaProdutos(Image $imagem): void
    {
        $alturaFoto = 210;
        $alturaPrimeiraLinha = 215;
        $alturaSegundaLinha = 245;
        $alturaTerceiraLinha = 275;
        $alturaBarraDivisao = 200;
        $posicaoHorizontalDados = 100;
        $posicaoHorizontalNomeTamanho = 355;
        $tamanhoDaFonte = 16;

        foreach ($this->produtos as $produto) {
            $barra = $this->gerenciadorDeImagem->canvas(355, 1, '#ababab');

            $textoNomeProduto = substr($produto['nome_produto'], 0, 25);
            $textoIdProduto = 'ID: ' . $produto['id_produto'];
            $textoPreco = 'R$ ' . number_format($produto['preco'], 2, ',', '.');
            $textoNomeTamanho = $produto['nome_tamanho'];

            if ($this->miniatura) {
                $imagem->insert($barra, 'top-left', 15, $alturaBarraDivisao);
                $fotoProduto = parent::criarImagem(null, $produto['foto']);
                $fotoProduto->resize(90, 90);
                $imagem->insert($fotoProduto, 'top-left', 10, $alturaFoto);
            } else {
                $posicaoHorizontalDados = 15;
            }

            parent::aplicarTexto(
                $imagem,
                $tamanhoDaFonte,
                $posicaoHorizontalDados,
                $alturaPrimeiraLinha,
                $textoNomeProduto
            );
            parent::aplicarTexto(
                $imagem,
                $tamanhoDaFonte,
                $posicaoHorizontalDados,
                $alturaSegundaLinha,
                $textoIdProduto
            );
            parent::aplicarTexto(
                $imagem,
                $tamanhoDaFonte,
                $posicaoHorizontalDados,
                $alturaTerceiraLinha,
                $textoPreco
            );
            parent::aplicarTexto(
                $imagem,
                $tamanhoDaFonte,
                $posicaoHorizontalNomeTamanho,
                $alturaPrimeiraLinha,
                $textoNomeTamanho
            );

            $alturaBarraDivisao += 115;
            $alturaFoto += 115;
            $alturaPrimeiraLinha += 115;
            $alturaSegundaLinha += 115;
            $alturaTerceiraLinha += 115;
        }
    }
}
