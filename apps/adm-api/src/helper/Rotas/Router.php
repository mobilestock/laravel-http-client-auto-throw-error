<?php

namespace MobileStock\helper\Rotas;

use CoffeeCode\Router\Dispatch;

/**
 * @deprecated
 * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
 */
class Router extends \CoffeeCode\Router\Router
{
    public function __construct()
    {
        parent::__construct('', ':');
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function post(string $route, $handler, string $name = null): void
    {
        parent::post($route,$handler,$name);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function get(string $route, $handler, string $name = null): void
    {
        parent::get($route,$handler,$name);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function delete(string $route, $handler, string $name = null): void
    {
        parent::delete($route,$handler,$name);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function put(string $route, $handler, string $name = null): void
    {
        parent::put($route,$handler,$name);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function patch(string $route, $handler, string $name = null): void
    {
        parent::patch($route,$handler,$name);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function group(?string $group): Dispatch
    {
        return parent::group($group);
    }

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public function namespace(?string $namespace): Dispatch
    {
        return parent::namespace($namespace);
    }

//    public array $configIndexes = [
//        'attempts',
//        'notify_on_error'
//    ];
//
//    public function dispatch(): bool
//    {
//        if (empty($this->routes) || empty($this->routes[$this->httpMethod])) {
//            $this->error = self::NOT_IMPLEMENTED;
//            return false;
//        }
//
//        $this->route = null;
//        $routesMatche = [];
//        foreach ($this->routes[$this->httpMethod] as $key => $route) {
//            if (preg_match("~^" . $key . "$~", $this->patch, $found)) {
//                $routesMatche[$key] = $route;
//            }
//        }
//
//        if (empty($routesMatche)) {
//            $this->error = self::NOT_FOUND;
//            return false;
//        }
//
//        if (count($routesMatche) > 1) {
//            $this->route = array_reduce(
//                $routesMatche,
//                function (array $total, array $route) {
//                    if (count($this->onlyParams($route['data'])) < count($this->onlyParams($total['data'] ?? range(0, 100)))) {
//                        return $route;
//                    }
//
//                    return $total;
//                },
//                []
//            );
//        } else {
//            $this->route = last($routesMatche);
//        }
//
//        if (!$this->route) {
//            $this->error = self::NOT_FOUND;
//            return false;
//        }
//
//        if (is_callable($this->route['handler'])) {
//            call_user_func($this->route['handler'], ($this->route['data'] ?? []));
//            return true;
//        }
//
//        $controller = $this->route['handler'];
//        $method = $this->route['action'];
//        $attempts = $this->route['data']['attempts'] ?? 1;
//
//        if ($attempts > 1) {
//            $maxTime = 200 * $attempts;
//            \set_time_limit($maxTime);
//        }
//
//        /** @var Request_m $newController */
//        $newController = new $controller($this);
//        try {
//            Retentador::retentar($attempts, function () use ($method, $newController) {
//                $newController->$method(($this->route['data'] ?? []));
//
//                $newController->respostaJson
//                    ->setData(empty($newController->resposta) ? $newController->retorno : $newController->resposta)
//                    ->setStatusCode($newController->codigoRetorno)
//                    ->send();
//            });
//        } catch (\Throwable $exception) {
//            if ($this->route['data']['notify_on_error'] ?? false) {
//                Notificacoes::criaNotificacoes(
//                    Conexao::criarConexao(),
//                    "Não foi possivel atender a rota: $this->patch. Tentamos $attempts vez(es) e aconteceu a exceção $exception"
//                );
//            }
//
//            if ($newController->codigoRetorno === 200) {
//                $newController->resposta['message'] = $exception->getMessage();
//                $newController->codigoRetorno = 400;
//            }
//
//            $newController->respostaJson
//                ->setData(empty($newController->resposta) ? $newController->retorno : $newController->resposta)
//                ->setStatusCode($newController->codigoRetorno)
//                ->send();
//        }
//
//        return false;
//    }
//
//    protected function formSpoofing(): void
//    {
//        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
//
//        if (!empty($post['_method']) && in_array($post['_method'], ["PUT", "PATCH", "DELETE"])) {
//            $this->httpMethod = $post['_method'];
//            $this->setData($post);
//
//            unset($this->data["_method"]);
//            return;
//        }
//
//        if ($this->httpMethod == "POST") {
//            $this->setData(filter_input_array(INPUT_POST, FILTER_DEFAULT));
//
//            unset($this->data["_method"]);
//            return;
//        }
//
//        if (in_array($this->httpMethod, ["PUT", "PATCH", "DELETE"]) && !empty($_SERVER['CONTENT_LENGTH'])) {
//            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
//            $this->setData($putPatch);
//
//            unset($this->data["_method"]);
//            return;
//        }
//
//        $this->setData([]);
//        return;
//    }
//
//    public function setData($newData)
//    {
//        $itensToAdd = [];
//
//        foreach ($this->configIndexes as $config) {
//            if ($configValue = $this->data[$config] ?? '') {
//                $itensToAdd[$config] = $configValue;
//            }
//        }
//
//        $this->data = \array_merge($newData ?? [], $itensToAdd);
//    }
//
//    protected function onlyParams(array $data)
//    {
//        foreach ($this->configIndexes as $configIndex) {
//            unset($data[$configIndex]);
//        }
//
//        return $data;
//    }
//
//    public function get(string $route, $handler, string $name = null, int $attempts = 1, ?bool $notifyOnError = null): void
//    {
//        $this->config($attempts, $notifyOnError);
//        parent::get($route, $handler, $name);
//    }
//
//    public function post(string $route, $handler, string $name = null, int $attempts = 1, ?bool $notifyOnError = null): void
//    {
//        $this->config($attempts, $notifyOnError);
//        parent::post($route, $handler, $name);
//    }
//
//    public function put(string $route, $handler, string $name = null, int $attempts = 1, ?bool $notifyOnError = null): void
//    {
//        $this->config($attempts, $notifyOnError);
//        parent::put($route, $handler, $name);
//    }
//
//    public function delete(string $route, $handler, string $name = null, int $attempts = 1, ?bool $notifyOnError = null): void
//    {
//        $this->config($attempts, $notifyOnError);
//        parent::delete($route, $handler, $name);
//    }
//
//    public function patch(string $route, $handler, string $name = null, int $attempts = 1, ?bool $notifyOnError = null): void
//    {
//        $this->config($attempts, $notifyOnError);
//        parent::patch($route, $handler, $name);
//    }
//
//    protected function config(int $attempts, ?bool $notifyOnError): void
//    {
//        if ($attempts > 1 && $notifyOnError === null) {
//            $notifyOnError = true;
//        }
//
//        $this->data = ['attempts' => $attempts, 'notify_on_error' => $notifyOnError];
//    }

}