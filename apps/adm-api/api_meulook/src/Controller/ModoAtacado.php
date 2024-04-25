<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use MobileStock\repository\ColaboradoresRepository;
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
        $idUsuario = Auth::user()->id;
        $ehModoAtacado = Gate::allows('MODO_ATACADO');
        if ($ehModoAtacado) {
            ColaboradoresRepository::removePermissaoUsuario($idUsuario, [13]);
        } else {
            ColaboradoresRepository::adicionaPermissaoUsuario(DB::getPdo(), $idUsuario, [13]);
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
