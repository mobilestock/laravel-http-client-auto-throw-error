<?php
/*
namespace MobileStock\repository;

use MobileStock\database\Conexao;
use MobileStock\helper\PriceHandler;
use MobileStock\repository\RepositoryInterface;
use Mpdf\Utils\Arrays;

use MobileStock\model\ParesCorrigidos;
use PDO;

require_once __DIR__ . '/../../classes/usuarios.php';
class ParesCorrigidosRepository
{

    public function listar(array $params): array
    {
        $teste = [];
        return $teste;
    }

    public function criar(array $params)
    {
    }

    public function atualizar()
    {
    }

    public function apagar(): bool
    {
        return false;
    }
    public function buscaParesCorrigidosFaturamentoID(int $id_faturamento): array
    {
        $conexao = Conexao::criarConexao();
        $query = "SELECT * FROM pares_corrigidos WHERE pares_corrigidos.id_faturamento='{$id_faturamento}';";
        $query = $conexao->prepare($query);
        $query->execute();
        $r = $query->fetchAll(PDO::FETCH_ASSOC);
        if (isset($r)) {
            $corrigidos = [];
            foreach ($r as $resultado) {

                $par_corrigido = new ParesCorrigidos(
                    intVal($resultado['id']),
                    intVal($resultado['id_faturamento']),
                    intVal($resultado['id_produto']),
                    intVal($resultado['tamanho']),
                    intVal($resultado['id_separador']),
                    intVal($resultado['data_separacao']),
                    intVal($resultado['uuid'])
                );
                $par_corrigido->setLocalizado($resultado['localizado']);
                $par_corrigido->setConferido($resultado['conferido']);
                $par_corrigido->setId_conferidor($resultado['id_conferidor']);
                $par_corrigido->setData_conferencia($resultado['data_conferencia']);
                $par_corrigido->setPreco($resultado['preco']);
                array_push($corrigidos, $par_corrigido);
            }
        }
        return $corrigidos;
    }

    public function adicionaParCorrigidoReservaCliente(int $id_faturamento, string $uuid)
    {
        $conexao = Conexao::criarConexao();
        $sql = "SELECT * from faturamento_item where id_faturamento = '{$id_faturamento}' and uuid = '{$uuid}'";
        $query = $conexao->prepare($sql);
        $query->execute();
        if ($result = $query->fetchAll(PDO::FETCH_ASSOC)) {
            $item = $result[0];
            return $this->clienteReservaProduto($item['id_produto'], $item['id_cliente'], $item['cliente'], $item['tamanho']);
        }

        return false;
    }
}
*/
