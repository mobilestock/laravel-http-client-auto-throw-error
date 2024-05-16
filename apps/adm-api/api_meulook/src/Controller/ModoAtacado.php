<?php

namespace api_meulook\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use MobileStock\repository\ColaboradoresRepository;

class ModoAtacado
{
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

    public function estaAtivo()
    {
        $permissoes = ColaboradoresRepository::buscaPermissaoUsuario(DB::getPdo(), Auth::user()->id);
        $resposta = in_array('ATACADISTA', $permissoes);
        return $resposta;
    }
}
