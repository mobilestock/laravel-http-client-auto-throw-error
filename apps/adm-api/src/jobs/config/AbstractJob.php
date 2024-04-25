<?php

namespace MobileStock\jobs\config;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\Logger;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use MobileStock\helper\Globals;
use PDO;
use Throwable;

abstract class AbstractJob
{
    protected ?PDO $conexao;
    protected array $middlewares = [];

    public function __destruct()
    {
        if (PHP_SAPI !== 'cli') {
            die('Não pode acessar');
        }

        date_default_timezone_set('America/Sao_Paulo');

        try {
            app()->bootstrapWith(Globals::BOOTSTRAPPERS);

            Auth::setUser(
                new GenericUser([
                    'id' => 2,
                    'nome' => 'mobile',
                    'id_colaborador' => 12,
                    'permissao' => '30,12,50',
                ])
            );

            app(Pipeline::class)
                ->through([...$this->middlewares, fn($dados) => app()->call([$this, 'run'], ['dados' => $dados])])
                ->thenReturn();
        } catch (Throwable $throwable) {
            $classeFilho = get_called_class();
            $substr = mb_substr($classeFilho, mb_stripos($classeFilho, 'jobs') + 5);
            $classeFilho = mb_substr($substr, 0, mb_stripos($substr, '.php'));

            app(Logger::class)->withContext([
                'title' => $classeFilho,
            ]);
            app(ExceptionHandler::class)->report($throwable);
            throw $throwable;
        }
    }
}
