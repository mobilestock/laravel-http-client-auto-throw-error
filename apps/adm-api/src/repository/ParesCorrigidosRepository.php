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


    private function clienteReservaProduto($id_produto, $id_cliente, $nome_cliente, $tamanho, $tipo_envio = false)
    {
        //   tipo_envio:"1"
        // id_produto:"4076"
        // id_cliente:"3091"
        // grade_json:"[["34","1"],["35","0"],["36","0"],["37","0"],["38","0"],["39","0"]]"
        // preco:"35.10"
        // cliente2:"lucas"
        if ($nome_cliente != '') {
            $nome_cliente = preg_replace(
                array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"),
                explode(" ", "a A e E i I o O u U n N"),
                $nome_cliente
            );
        }

        $cliente = buscaIdUsuario($id_cliente); //-> ID= 3066 (provavel id user)

        date_default_timezone_set('America/Sao_Paulo');
        $dataEmissao = date('Y-m-d H:i:s');
        $data_vencimento = buscaDataVencimentoCliente($id_cliente, $dataEmissao);
        $tipo_cobranca = buscaTipoCobranca($id_cliente);
        $id_tabela = buscaTabelaProduto($id_produto);
        //$preco = buscaPrecoTabelaProduto($id_tabela, $tipo_cobranca);
        $preco = PriceHandler::getPrecoProdutoByUserRegime( $id_produto , $id_cliente );
        $sequencia = buscaUltimaSequenciaProdutoPedidoCliente($id_cliente);
        $cod_barras = buscaCodBarras($id_produto, $tamanho);
        $sequencia++;

        $sql = "";
        $uuid = uniqid(rand(), true);
        $sql .= "INSERT INTO pedido_item (
                id_cliente,
                id_produto,
                sequencia,
                tamanho,
                id_vendedor,
                preco,
                situacao,
                data_hora,
                data_vencimento,
                cod_barras,
                tipo_cobranca,
                id_tabela,
                uuid,
                pedido_cliente,
                cliente
                ) VALUES (
                {$id_cliente}, 
                {$id_produto},
                {$sequencia},
                {$tamanho},
                {$cliente},
                '{$preco}',
                15,
                '{$dataEmissao}',
                '{$data_vencimento}',
                '{$cod_barras}',
                {$tipo_cobranca},
                {$id_tabela},
                '{$uuid}',
                1,
                '{$nome_cliente}'
                );";
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare($sql);
        $retorno = $query->execute();



        if ($retorno) {
            $ano = DATE('Y');
            $mes = DATE('m');
            $sql = "UPDATE paginas_acessadas SET adicionados = adicionados+1 WHERE ano={$ano} AND mes={$mes} AND id_produto={$id_produto}; ";
            $query = $conexao->prepare($sql);
            $query->execute();
        }
        return $retorno;
    }
}
*/