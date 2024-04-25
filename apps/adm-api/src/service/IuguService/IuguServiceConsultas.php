<?php

namespace MobileStock\service\IuguService;

use PDO;

class IuguServiceConsultas
{
    public static function dadosColaboradoresIugu(PDO $conexao, $idPagador)
    {
        $stmt = $conexao->prepare("SELECT conta_bancaria_colaboradores.id_iugu,
                                                conta_bancaria_colaboradores.iugu_token_teste,
                                                conta_bancaria_colaboradores.iugu_token_live,
                                                conta_bancaria_colaboradores.conta_iugu_verificada
                                            FROM conta_bancaria_colaboradores
                                            WHERE conta_bancaria_colaboradores.id = :idColaborador");
        $stmt->execute(['idColaborador' => $idPagador]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    public static function idColaboradoresComCodIugu(PDO $conexao, $idIugu)
    {
        $stmt = $conexao->prepare("SELECT api_colaboradores.id_colaborador
                                                    FROM api_colaboradores
                                                    WHERE api_colaboradores.id_iugu = :idIugu");
        $stmt->execute(['idIugu' => $idIugu]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
