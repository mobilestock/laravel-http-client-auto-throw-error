<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class CriarProxyCustomizado extends Command
{
    protected $signature = 'make:proxy {url} {metodo=Get}';
    protected $description = 'Cria um proxy customizado para tratar chamadas ao ADM-API';

    public function handle(): void
    {
        $caminhoPastaProxy = 'Http/Middleware/Proxy';
        $caminhoEstruturaPastas = $this->argument('url');
        $nomeArquivo = ucfirst(mb_strtolower($this->argument('metodo')));

        $segmentos = explode('/', $caminhoEstruturaPastas);

        foreach ($segmentos as &$segmento) {
            if (preg_match('/\{(.+?)\}/', $segmento)) {
                $segmento = 'parametro_url';
            }
        }

        $caminhoEstruturaPastasModificado = implode('/', $segmentos);

        $caminhoCompleto = app_path("$caminhoPastaProxy/{$caminhoEstruturaPastasModificado}");
        $caminhoArquivo = "{$caminhoCompleto}/{$nomeArquivo}.php";

        $namespace = str_replace('/', '\\',  "App\Http\Middleware\Proxy\\{$caminhoEstruturaPastasModificado}");

        if (File::exists($caminhoArquivo)) {
            $this->error("O arquivo {$nomeArquivo}.php já existe em {$caminhoArquivo}");
            return;
        }

        if (!File::exists($caminhoCompleto)) {
            File::makeDirectory($caminhoCompleto, recursive: true);
        }

        $conteudo = <<<EOT
        <?php

        namespace {$namespace};

        use App\Http\Middleware\Proxy\ProxyAbstract;
        use Symfony\Component\HttpFoundation\Response;

        /**
         * {$nomeArquivo}: {$caminhoEstruturaPastas}
         */
        class {$nomeArquivo} extends ProxyAbstract
        {
            /**
             * Se esse método não for usado, favor remover o metodo e o comentário
             * Se o metodo for utilizado, favor remover este comentário
             */
            public function preRequisicao(): void
            {
            }

            /**
             * Se esse método não for usado, favor remover o metodo e o comentário
             * Se o metodo for utilizado, favor remover este comentário
             */
            public function posRequisicao(Response \$response): void
            {
            }
        }
        EOT;

        File::put($caminhoArquivo, $conteudo);
        exec("cd " . app_path("/$caminhoPastaProxy") . " && chmod -R 777 {$segmentos[0]}");
        $this->info("O arquivo {$nomeArquivo}.php foi criado em {$caminhoArquivo}");
    }
}
