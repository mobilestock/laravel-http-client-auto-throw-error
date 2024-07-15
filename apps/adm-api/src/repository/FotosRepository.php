<?php

namespace MobileStock\repository;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FotosRepository
{
    // public static function listaMelhoresFotografos()
    // {
    //     return DB::select('SELECT count(*) qtd_fotos,
    //      (SELECT usuarios.nome FROM usuarios where usuarios.id = produtos_foto.id_usuario) usuario
    //      FROM  produtos_foto
    //      WHERE produtos_foto.id_usuario IS NOT NULL
    //      GROUP BY produtos_foto.id_usuario;');
    // }

    // public static function listaLog(): array
    // {
    //     return DB::select('SELECT produtos_foto.caminho,
    //     produtos_foto.id,
    //     produtos_foto.data_hora,
    //     produtos_foto.foto_calcada,
    //     (select produtos.descricao from produtos where produtos.id = produtos_foto.id) produto,
    //     (select usuarios.nome from usuarios where usuarios.id = produtos_foto.id_usuario) usuario
    //     FROM produtos_foto ORDER BY data_hora');
    // }

    // public function darEntradaProdutoEstoque($item)
    // {
    //     return $this->voltarProdutoSeparacaoFotos($item);
    // }

    // public function adicionaFotoProduto($id_produto, $files, $descricao, $id_usuario, PDO $conn = null)
    // {
    //     ob_start();
    //     require_once __DIR__ . '/../../classes/produtos.php';
    //     require_once __DIR__ . '/../../regras/alertas.php';
    //     require_once __DIR__ .  '/../../vendor/autoload.php';
    //     require_once __DIR__ . '/../../classes/produtos.php';
    //     require_once __DIR__ . '/../../controle/produtos-insere-fotos.php';
    //     ob_clean();
    //     $_FILES = [
    //         "fotos" => [
    //             "name" => [(string) $files['fotos']['name']],
    //             "type" => [(string) $files['fotos']['type']],
    //             "tmp_name" => [(string) $files['fotos']['tmp_name']],
    //             "error" => [(int) $files['fotos']['error']],
    //             "size" => [(int) $files['fotos']['size']]
    //         ],
    //         "fotos_calcadas" => [
    //             "name" => [''],
    //             "type" => [''],
    //             "tmp_name" => [''],
    //             "error" => [4],
    //             "size" => [0]
    //         ],
    //         "foto_thumbnails" => [
    //             "name" => [''],
    //             "type" => [''],
    //             "tmp_name" => [''],
    //             "error" => [4],
    //             "size" => [0]
    //         ]
    //     ];
    //     try {
    //         $conn = is_null($conn) ? Conexao::criarConexao() : $conn;
    //         insereFotosProduto($id_produto, $_FILES, $descricao, $id_usuario, $conn);
    //     } catch (Exception $err) {

    //         return false;
    //     }
    //     return true;
    // }

    // public function alteraNumeracaoPadrao(array $linhas)
    // {
    //     $sql = '';
    //     foreach ($linhas as $key => $value) {
    //         $sql .= "UPDATE linha SET tamanho_padrao_foto = $value WHERE nome = '$key';";
    //     }

    //     Conexao::criarConexao()->exec($sql);
    //     return true;
    // }

    // public function buscaNumeracoes()
    // {
    //     return DB::select('SELECT nome, tamanho_padrao_foto FROM linha');
    // }

    // public function adicionaFotoCalcada($id_produto, $files, $descricao, $id_usuario, PDO $conn = null)
    // {
    //     ob_start();
    //     require_once __DIR__ . '/../../classes/produtos.php';
    //     require_once __DIR__ . '/../../regras/alertas.php';
    //     require_once __DIR__ .  '/../../vendor/autoload.php';
    //     require_once __DIR__ . '/../../classes/produtos.php';
    //     require_once __DIR__ . '/../../controle/produtos-insere-fotos.php';
    //     ob_clean();
    //     $_FILES = [
    //         "fotos_calcadas" => [
    //             "name" => [(string) $files['fotos']['name']],
    //             "type" => [(string) $files['fotos']['type']],
    //             "tmp_name" => [(string) $files['fotos']['tmp_name']],
    //             "error" => [(int) $files['fotos']['error']],
    //             "size" => [(int) $files['fotos']['size']]
    //         ],
    //         "fotos" => [
    //             "name" => [''],
    //             "type" => [''],
    //             "tmp_name" => [''],
    //             "error" => [4],
    //             "size" => [0]
    //         ],
    //         "foto_thumbnails" => [
    //             "name" => [''],
    //             "type" => [''],
    //             "tmp_name" => [''],
    //             "error" => [4],
    //             "size" => [0]
    //         ]
    //     ];
    //     try {
    //         $conn = is_null($conn) ? Conexao::criarConexao() : $conn;
    //         insereFotosProduto($id_produto, $_FILES, $descricao, $id_usuario, $conn);
    //     } catch (Exception $err) {

    //         return false;
    //     }
    //     return true;
    // }

    // public static function buscaQtdFotosPublicadas(string $agrupar = 'month', string $inicio = '2019-01-01', string $final = 'CURRENT_DATE'): array
    // {
    //     if (!$inicio) {
    //         $inicio = '2019-01-01';
    //     }
    //     if (!$final) {
    //         $final = 'CURRENT_DATE';
    //     }
    //     return DB::select('SELECT count(*) qtd_fotos,
    //         ' . $agrupar . '(produtos_foto.data_hora) ' . $agrupar . ',
    //         produtos_foto.data_hora,
    //         (SELECT usuarios.nome FROM usuarios where usuarios.id = produtos_foto.id_usuario) usuario
    //      FROM produtos_foto
    //      WHERE produtos_foto.id_usuario IS NOT NULL AND date(produtos_foto.data_hora) BETWEEN ' . $inicio . ' AND ' . (function () use ($final) {
    //         return $final === 'CURRENT_DATE' ? "$final" : "'$final'";
    //     })() . '
    //      GROUP BY ' . $agrupar . '(produtos_foto.data_hora),
    //      produtos_foto.id_usuario;
    //      ');
    // }

    public static function salvarFotoAwsS3(array $file, string $title, string $bucket, bool $privado = false): string
    {
        $optionsBucket = Globals::S3_OPTIONS($bucket);
        $caminhoImagem = '';
        $nomeBucket = $privado ? 'mobilestock-signed-urls' : 'mobilestock-s3';

        try {
            $s3 = new S3Client($optionsBucket);
        } catch (Exception $e) {
            throw new Exception('Erro ao conectar com o S3: ' . $e->getMessage());
        }

        if (isset($file['name'])) {
            if ($file['name'][0] != '') {
                $extensao = mb_substr($file['type'], mb_strripos($file['type'], '/') + 1);
                $img_extensao = ['jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG'];
                if (!in_array($extensao, $img_extensao)) {
                    // valida extensão da imagem.
                    throw new BadRequestHttpException('O formato da imagem deve jpg, jpeg ou png');
                }
                $nomeimagem = $title . '.webp';
                self::upload($file['tmp_name'], $nomeimagem, 1000, 1000);
                // $caminhoImagem = 'https://s3-sa-east-1.amazonaws.com/' . $nomeBucket . '/' . $nomeimagem;

                try {
                    $uploadS3 = $s3->putObject([
                        'Bucket' => $nomeBucket,
                        'Key' => $nomeimagem,
                        'SourceFile' => '../downloads/' . $nomeimagem,
                    ]);
                    $caminhoImagem = $uploadS3['ObjectURL'];
                } catch (S3Exception $e) {
                    throw new Exception('Erro ao fazer upload para o S3: ' . $e->getMessage());
                }
            }
        }

        if ($privado == false) {
            $caminhoImagem =
                'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . preg_replace('/.*amazonaws\.com\//', '', $caminhoImagem);
        }
        return $caminhoImagem;
    }

    public static function apagarFotoAwsS3(string $key, string $bucketGlobals, string $nomeBucket)
    {
        $ObjectKey = preg_replace('/(.*br\/)/i', '', $key);
        $s3 = new S3Client(Globals::S3_OPTIONS($bucketGlobals));
        $s3->deleteObject([
            'Bucket' => $nomeBucket,
            'Key' => $ObjectKey,
        ]);
    }

    public static function salvarFotoAwsS3Tratada(array $file, string $title, string $bucket)
    {
        try {
            $s3 = new S3Client(Globals::S3_OPTIONS($bucket));
        } catch (Exception $e) {
            die('Error ' . $e->getMessage());
        }

        $extensao = mb_substr($file['name'], mb_strripos($file['name'], '.'));

        $img_extensao = ['.jpg', '.JPG', '.jpeg', '.JPEG', '.png', '.PNG'];
        if (!in_array($extensao, $img_extensao)) {
            // valida extensão da imagem.
            throw new Exception('Erro ao inserir imagem', 418);
        }
        $nomeimagem = date('d_m_Y') . "_{$title}" . $extensao;
        // tamanho máximo da imagem
        $largura_max = 600;
        $altura_max = 600;
        $temp_file = $file['tmp_name'];

        if (in_array($extensao, ['.jpg', '.JPG', '.jpeg', '.JPEG'])) {
            self::upload($temp_file, $nomeimagem, $largura_max, $altura_max, $extensao); // ajusta o tamanho da imagem e salva na pasta upload
        } elseif (in_array($extensao, ['.png', '.PNG'])) {
            self::uploadPNG($temp_file, $nomeimagem, $largura_max, $altura_max); // ajusta o tamanho da imagem e salva na pasta upload
        }

        try {
            $s3->putObject([
                'Bucket' => 'mobilestock-s3',
                'Key' => $nomeimagem,
                'SourceFile' => '../downloads/' . $nomeimagem,
            ]);
        } catch (S3Exception $e) {
            $exception = $e->getMessage();
            echo $exception;
        }
        unlink('../downloads/' . $nomeimagem); //remove a imagem da pasta temporaria
        $caminhoImagens = 'https://cdn-s3.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;
        return $caminhoImagens;
    }

    public static function gerarUrlAssinadaAwsS3(string $key, string $bucket)
    {
        $ObjectKey = preg_replace('/(.*)amazonaws.com\//i', '', $key);

        try {
            $s3 = new S3Client(Globals::S3_OPTIONS($bucket));
        } catch (Exception $e) {
            throw new Exception('Erro ao gerar URL assinada: ' . $e->getMessage());
        }

        try {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => 'mobilestock-signed-urls',
                'Key' => $ObjectKey,
            ]);
            $request = $s3->createPresignedRequest($cmd, '+20 minutes');
            $presignedUrl = (string) $request->getUri();
        } catch (S3Exception $e) {
            $exception = $e->getMessage();
            echo $exception;
        }
        return $presignedUrl;
    }

    public static function uploadPNG($tmp, $arquivo, $max_x, $max_y)
    {
        $img = imagecreatefrompng($tmp);
        $original_x = imagesx($img);
        $original_y = imagesy($img);
        $diretorio = '../downloads/' . $arquivo;
        if ($original_x > $max_x || $original_y > $max_y) {
            if ($original_x > $original_y) {
                $max_y = ($max_x * $original_y) / $original_x;
            } else {
                $max_x = ($max_y * $original_x) / $original_y;
            }
            $nova = imagecreatetruecolor($max_x, $max_y);
            imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
            imagepng($nova, $diretorio);
            imagedestroy($nova);
            imagedestroy($img);
        } else {
            imagepng($img, $diretorio);
            imagedestroy($img);
        }
    }

    public static function upload($tmp, $arquivo, $max_x, $max_y)
    {
        $img = imagecreatefromjpeg($tmp);
        $original_x = imagesx($img);
        $original_y = imagesy($img);
        $diretorio = '../downloads/' . $arquivo;
        if ($original_x > $max_x || $original_y > $max_y) {
            if ($original_x > $original_y) {
                $max_y = ($max_x * $original_y) / $original_x;
            } else {
                $max_x = ($max_y * $original_x) / $original_y;
            }
            $nova = imagecreatetruecolor($max_x, $max_y);
            imagecopyresampled($nova, $img, 0, 0, 0, 0, $max_x, $max_y, $original_x, $original_y);
            imagejpeg($nova, $diretorio);
            imagedestroy($nova);
            imagedestroy($img);
        } else {
            imagejpeg($img, $diretorio);
            imagedestroy($img);
        }
    }

    public static function buscaFotosDeSugestoes()
    {
        $conexao = Conexao::criarConexao();
        $sql = 'SELECT foto_produto FROM produtos_sugestao ORDER BY id DESC LIMIT 100';
        $stm = $conexao->prepare($sql);
        $stm->execute();
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
