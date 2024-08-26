<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Midia
{
    public function baixaMidia()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'fonte_midia' => [Validador::OBRIGATORIO],
            'tipo' => [Validador::OBRIGATORIO, Validador::ENUM('FOTO', 'VIDEO')],
        ]);

        if ($dadosJson['tipo'] === 'FOTO') {
            $resposta = Http::get($dadosJson['fonte_midia'])->throw();

            $arquivo = $resposta->getBody();

            return new Response($arquivo, Response::HTTP_OK, [
                'Content-Type' => $resposta->header('Content-Type'),
            ]);
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $dadosJson['fonte_midia'])) {
            throw new BadRequestHttpException('Id de vídeo inválido');
        }

        $url = "https://www.youtube.com/watch?v={$dadosJson['fonte_midia']}";

        $comando = [
            __DIR__ . '/../../../yt-dlp/yt-dlp',
            '--embed-metadata',
            '-f',
            "bv*[vcodec!~='vp0?9'][height<=1080]+ba/bv*[height<=1080]+ba/b",
            '-S',
            'vcodec:h264,res,acodec:aac',
            '--downloader-args',
            '-f mp4 -movflags +frag_keyframe+empty_moov -strict -2',
            '--cookies',
            __DIR__ . '/../../../yt-dlp/cookies.txt',
            '-o',
            '-',
            $url,
        ];

        $process = new Process($comando);

        $process->setTimeout(60 * 30); // 30 minutos

        if (!App::isProduction()) {
            /**
             * @issue https://github.com/mobilestock/backend/issues/492
             */
            $envTemporario = $_ENV;
            $_ENV = [];
        }

        $resposta = new StreamedResponse(
            function () use ($process, $envTemporario) {
                $process->start();
                $_ENV = $envTemporario;
                if (
                    0 !==
                    $process->wait(function (string $type, string $buffer) {
                        if (Process::ERR === $type) {
                            return;
                        }
                        echo $buffer;
                        flush();
                    })
                ) {
                    throw new ProcessFailedException($process);
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'attachment; filename="video.mp4"',
            ]
        );

        return $resposta;
    }
}
