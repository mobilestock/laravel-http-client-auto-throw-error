<?php

namespace MobileStock\helper;

use MobileStock\repository\TrocaPendenteRepository;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe para gerenciar requisições em uma api
 */
class Acesso
{
    public $repository;

    public function __construct(string $repository = TrocaPendenteRepository::class)
    {
        $repository = new $repository();
        $this->repository = $repository;
    }

    /**
     * Executa uma função quando a requisição for GET
     *
     * @param [type] $callback
     * @param array $params
     * @return void
     */
    public function get($callback, array $params = [])
    {

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->handle($callback, $params);
        }
    }

    public function post($callback, array $params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle($callback, $params);
        }
    }

    public function put($callback, array $params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT' || (isset($_GET['_method']) && $_GET['_method'] === 'PUT')) {
            unset($_GET['_method'], $_REQUEST['_method']);
            $this->handle($callback, $params);
        }
    }

    public function delete($callback, array $params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $this->handle($callback, $params);
        }
    }

    public function patch($callback, array $params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $this->handle($callback, $params);
        }
    }

    private function handle($callback, array $params = [])
    {

        $request = Request::createFromGlobals();
        if (is_callable($callback)) {
            /** @var Response $response */
            $response = $callback($request);
            $response->send();
            exit();
        }

        if (empty($params)) {

            $response = $this->repository->$callback($request);
            $response->send();
            exit();
        }
        $params = json_encode($params);
        $params = json_decode($params);
        $params->request = $request;

        /**
         * @var Response
         */
        $response = $this->repository->$callback($params);

        $response->send();
        exit();
    }
}