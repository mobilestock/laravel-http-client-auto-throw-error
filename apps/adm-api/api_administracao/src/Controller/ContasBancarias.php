<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\repository\ContaBancariaRepository;

class ContasBancarias
{
    public function dadosContasBancarias()
    {
        $dados = ContaBancariaRepository::dadosContasBancarias();
        return $dados;
    }

    public function alterandoDadosBancarios(int $idConta)
    {
        DB::beginTransaction();

        $dados = Request::all();

        ContaBancariaRepository::alteraDadosBancarios(
            $idConta,
            $dados['cod_alterado'],
            $dados['agencia_alterada'],
            $dados['conta_alterada'],
            $dados['nome_alterado']
        );

        DB::commit();
    }
}
