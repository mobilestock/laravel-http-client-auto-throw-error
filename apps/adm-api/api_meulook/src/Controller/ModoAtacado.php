<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ModoAtacadoService;
use Symfony\Component\HttpFoundation\Response;

class ModoAtacado extends Request_m
{
    public function __construct()
    {
        parent::__construct();
        $this->conexao = app(\PDO::class);
    }

    public function gerenciaModoAtacado()
    {
        try {
            Validador::validar(['json' => $this->json], [
                'json' => [Validador::JSON]
            ]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'ativar' => [Validador::BOOLEANO]
            ]);
            ModoAtacadoService::gerenciaModoAtacado($this->conexao, $this->idUsuario, $dadosJson['ativar']);
            $this->resposta = [];
        } catch (\Exception $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_BAD_REQUEST;
        }
    }

    public function verificaModoAtacadoAtivado()
    {
        try {
            $permissoes = ColaboradoresRepository::buscaPermissaoUsuario($this->conexao, $this->idCliente);
            $this->resposta = ['ativado' => in_array('ATACADISTA', $permissoes)];
        } catch (\Exception $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_BAD_REQUEST;
        } finally {
            $this->respostaJson->setData($this->resposta)->setStatusCode($this->codigoRetorno)->send();
        }
    }
}