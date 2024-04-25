<?php

namespace MobileStock\repository;

use PDO;

class ZoopSellerRepository
{
    public static function atualizaSellerDadosIugo(int $idColaborador, object $dadosIugu, PDO $conn)
    {
        $query = "UPDATE api_colaboradores
                                SET api_colaboradores.id_iugu = '{$dadosIugu->account_id}',
                                    api_colaboradores.iugu_token_user = '{$dadosIugu->user_token}',
                                    api_colaboradores.iugu_token_teste = '{$dadosIugu->test_api_token}',
                                    api_colaboradores.iugu_token_live = '{$dadosIugu->live_api_token}'
                                WHERE api_colaboradores.id_colaborador = '{$idColaborador}'";
        return $conn->query($query)->rowCount();
    }
}
