<?php

/*
namespace api_cliente\Models;

use PDO;

class Produto
{

    /**
     * @param string $campos_tabela
     * @param string $join_tabela
     * @param string $filtro
     * @param int $numero_de_compras
     * @param string $filtro2
     * @param string $filtro3
     * @param int|null $pagina
     * @param int $itens
     * @param \api_cliente\Controller\Catalogo $instance
     * @return array
     */
    

    
    /*
    public static function buscaProdutoPorId(\PDO $conn, int $id)
    {
        $sql = "SELECT p.id, (SELECT pf.caminho FROM produtos_foto pf WHERE p.id=pf.id AND pf.sequencia=1) foto,
        p.valor_venda_cpf, p.valor_venda_cnpj FROM produtos p WHERE p.id=:id";
        $stm = $conn->prepare($sql);
        $stm->bindValue(":id",$id,PDO::PARAM_INT);
        $stm->execute();
        $produto = $stm->fetchAll(PDO::FETCH_ASSOC);
        $produto['grade']=[
            [
                "quantidade"=>1,
                "tamanho" =>33
            ],
            [
                "quantidade"=>2,
                "tamanho" =>34
            ],
            [
                "quantidade"=>3,
                "tamanho" =>35
            ],
            [
                "quantidade"=>4,
                "tamanho" =>36
            ],
            [
                "quantidade"=>5,
                "tamanho" =>37
            ],
            [
                "quantidade"=>5,
                "tamanho" =>38
            ],
            [
                "quantidade"=>4,
                "tamanho" =>39
            ],
            [
                "quantidade"=>3,
                "tamanho" =>40
            ],
            [
                "quantidade"=>2,
                "tamanho" =>41
            ],
            [
                "quantidade"=>1,
                "tamanho" =>42
                ]
            ];
        return $produto;
    }
}
*/