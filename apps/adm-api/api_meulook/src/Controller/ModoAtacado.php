<?php

namespace api_meulook\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\repository\ColaboradoresRepository;
use Illuminate\Support\Facades\Auth;

class ModoAtacado
{
    public function alternaModoAtacado()
    {
        DB::beginTransaction();
        $ativar = Request::boolean('ativar');
        $idUsuario = Auth::user()->id;
        $ativar
            ? ColaboradoresRepository::adicionaPermissaoUsuario(DB::getPdo(), $idUsuario, [13])
            : ColaboradoresRepository::removePermissaoUsuario($idUsuario, [13]);
        DB::commit();
    }

    public function estaAtivo()
    {
        $permissoes = ColaboradoresRepository::buscaPermissaoUsuario(DB::getPdo(), Auth::user()->id_colaborador);
        $resposta = in_array('ATACADISTA', $permissoes);
        return new JsonResponse($resposta);
    }
}
