<?php

namespace MobileStock\service;
use Aws\S3\S3Client;
use DomainException;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use MobileStock\helper\Globals;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FotoService
{
    private string $diretorio;

    /**
     * @param GdImage|resource $imagemOriginal
     */
    public function adicionaTagImagem($imagemOriginal, string $texto): void
    {
        $configuracoesTag = [
            'tamanho_fonte' => 20,
            'padding' => 10,
            'margin' => 10,
            'background' => [
                'tag' => ['red' => 255, 'green' => 255, 'blue' => 255],
                'font' => ['red' => 0, 'green' => 0, 'blue' => 0],
                'alpha' => 60,
            ],
            'posicao' => ['altura' => 100, 'largura' => 100],
        ];

        $larguraImagem = imagesx($imagemOriginal);
        $alturaImagem = imagesy($imagemOriginal);
        $tamanhoTexto = mb_strlen($texto) * $configuracoesTag['tamanho_fonte'] * 0.78;

        $imagemTexto = imagecreate(
            $tamanhoTexto + $configuracoesTag['padding'] * 2,
            $configuracoesTag['tamanho_fonte']
        );
        imagecolorallocate($imagemTexto, ...array_values($configuracoesTag['background']['tag']));
        $corTexto = imagecolorallocate($imagemTexto, ...array_values($configuracoesTag['background']['font']));

        imagettftext(
            $imagemTexto,
            $configuracoesTag['tamanho_fonte'],
            0,
            $configuracoesTag['padding'],
            $configuracoesTag['tamanho_fonte'],
            $corTexto,
            __DIR__ . '/../../fonts/Swis721_BT_Bold.ttf',
            $texto
        );

        $larguraTag = imagesx($imagemTexto);
        $posX = min($larguraImagem - $larguraTag, $larguraImagem * ($configuracoesTag['posicao']['largura'] / 100));
        $posX -= $configuracoesTag['margin'];

        $alturaTag = imagesy($imagemTexto);
        $posY = min($alturaImagem - $alturaTag, $alturaImagem * ($configuracoesTag['posicao']['altura'] / 100));
        $posY -= $configuracoesTag['margin'];

        imagecopymerge(
            $imagemOriginal,
            $imagemTexto,
            $posX,
            $posY,
            0,
            0,
            $larguraTag,
            $alturaTag,
            $configuracoesTag['background']['alpha']
        );

        if (!imagewebp($imagemOriginal, $this->diretorio, 90)) {
            throw new DomainException('Erro ao salvar imagem com tag');
        }
    }

    public function insereFotosProduto(UploadedFile $foto, int $idProduto, int &$sequencia, string $tipoFoto): void
    {
        $prefixo = App::isProduction() ? 'pro_' : 'dev_';
        $s3Client = new S3Client(Globals::S3_OPTIONS());
        $dataAtual = (new Carbon())->format('d_m_Y');
        $larguraMax = $alturaMax = 800;
        if ($tipoFoto === 'SM') {
            $prefixo .= 'small_';
            $larguraMax = $alturaMax = 100;
        }

        $sequencia++;
        if (!in_array($foto->extension(), ['jpg', 'jpeg'])) {
            throw new UnprocessableEntityHttpException('Sistema permite apenas imagens com extensão .jpg ou .jpeg');
        }

        $nomeImagem = "{$prefixo}{$idProduto}_{$sequencia}_{$dataAtual}.webp";
        $this->diretorio = __DIR__ . "/../../downloads/$nomeImagem";

        $imagem = imagecreatefromwebp($foto->path());
        $alturaOriginal = imagesy($imagem);
        $larguraOriginal = imagesx($imagem);

        $novaImagem = $imagem;
        if ($larguraOriginal > $larguraMax || $alturaOriginal > $alturaMax) {
            $ratio = $larguraOriginal / $alturaOriginal;
            $alturaMax = round($larguraMax * $ratio);
            $larguraMax = round($alturaMax * $ratio);

            $novaImagem = imagecreatetruecolor($larguraMax, $alturaMax);
            imagecopyresampled(
                $novaImagem,
                $imagem,
                0,
                0,
                0,
                0,
                $larguraMax,
                $alturaMax,
                $larguraOriginal,
                $alturaOriginal
            );
        }
        if (!imagewebp($novaImagem, $this->diretorio, 90)) {
            throw new DomainException('Erro ao salvar imagem');
        }

        $imagemCriada = imagecreatefromwebp($this->diretorio);
        if ($tipoFoto !== 'SM') {
            $this->adicionaTagImagem($imagemCriada, $idProduto);
            $imagemCriada = imagecreatefromwebp($this->diretorio);
        }

        $s3Client->putObject([
            'Bucket' => 'mobilestock-s3',
            'Key' => $nomeImagem,
            'SourceFile' => $this->diretorio,
        ]);

        imagedestroy($imagemCriada);
        unlink($this->diretorio);
        $caminhoImagem = 'https://cdn-s3.' . env('URL_CDN') . "/$nomeImagem";

        ProdutoService::inserirImagensProduto($idProduto, $caminhoImagem, $nomeImagem, $sequencia, $tipoFoto);
    }
}
