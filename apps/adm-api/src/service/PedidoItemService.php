<?php
namespace MobileStock\service;

use MobileStock\model\Taxas;
use PDO;

class PedidoItemService
{
    private $taxa_boleto;
    private $taxa_juros;
    private $valor_credito;
    private $juros_fornecedor;
    // public function buscaProdutoPedidoUuid(PDO $conexao, string $uuid)
    // {
    //     $query = " SELECT
    //             pedido_item.id_cliente,
    //             pedido_item.id_produto,
    //             pedido_item.tamanho,
    //             pedido_item.sequencia,
    //             pedido_item.tipo_cobranca,
    //             pedido_item.premio,
    //             pedido_item.id_tabela,
    //             pedido_item.id_vendedor,
    //             pedido_item.preco,
    //             pedido_item.situacao,
    //             pedido_item.data_hora,
    //             pedido_item.uuid,
    //             pedido_item.cliente,
    //             pedido_item.pedido_cliente,
    //             case (SELECT colaboradores.regime FROM colaboradores WHERE colaboradores.id = pedido_item.id_cliente)
    //                 when 1 then produtos.porcentagem_comissao_cnpj
    //                 ELSE produtos.porcentagem_comissao
    //             END porcentagem_comissao,
    //             produtos.id_fornecedor,
    //             produtos.consignado,
    //             COALESCE((SELECT id_zoop FROM api_colaboradores WHERE id_colaborador = produtos.id_fornecedor),null)id_zoop,
    //             (SELECT pf.caminho FROM produtos_foto pf WHERE pf.id=pedido_item.id_produto AND pf.sequencia=1)foto,
    //             produtos.valor_custo_produto
    //         FROM pedido_item
    //             INNER JOIN produtos ON produtos.id = pedido_item.id_produto
    //         WHERE
    //         pedido_item.uuid='{$uuid}';";
    //     $stm = $conexao->prepare($query);
    //     if ($stm->execute()) {
    //         return $stm->fetch(PDO::FETCH_ASSOC);
    //     }
    //     throw new Excecao("Aconteceu um erro ao buscar os produtos do pedido.", 1);
    // }

    // public function buscaProdutosPedido(PDO $conexao, array $post)
    // {

    //     $uuids = json_decode($post['produtos'], true);
    //     $produtos = [];

    //     $taxas = new Taxas();
    //     $taxas->SetJuros($conexao,$post['parcelas']);
    //     $valor_credito = $post['valor_credito'];
    //     foreach ($uuids as $key => $u) {
    //         $produto = $this->buscaProdutoPedidoUuid($conexao, $u['uuid']);
    //         if($valor_credito >= 0 ){
    //             $produto['comissao_fornecedor'] = $produto['valor_custo_produto'];
    //             $produto['acrescimo'] = 0;
    //             $valor_credito = $valor_credito - $produto['preco'];
    //         } else {
    //             $produto['comissao_fornecedor'] = $produto['valor_custo_produto'];
    //             $produto['acrescimo'] = 0;
    //              $comissoes = $taxas->calculaCustoFornecedor($produto['valor_custo_produto'],$post['tipoPagamento'],$produto['preco']);
    //             // $produto['comissao_fornecedor'] = $comissoes['comissao_fornecedor'];
    //             // $produto['acrescimo'] = $comissoes['acrescimo'];
    //         }
    //         array_push($produtos, $produto);
    //     }

    //     $this->valor_credito = $post['valor_credito'] - floatval(($valor_credito > 0)?$valor_credito:0);
    //     $this->taxa_boleto = $post['tipoPagamento']==3? $taxas->getBoleto():0;
    //     $this->taxa_juros = $taxas->getJuros();
    //     $this->juros_fornecedor = $taxas->getJurosFornecedor();
    //     return $produtos;
    // }

    public function buscaValorDosProdutos(array $produtos)
    {
        $valor = 0;
        foreach ($produtos as $key => $p) {
            $valor += $p['preco'];
        }
        return $valor;
    }

    public function rateiaTaxaProdutos(array $produtos, float $valorProdutos, float $valorComTaxa)
    {
        foreach ($produtos as $key => $p) {
            $produtos[$key]['preco'] =
                $p['premio'] == 0 ? number_format((float) ($valorComTaxa * ($p['preco'] / $valorProdutos)), 2) : 0;
        }

        $soma = 0;
        foreach ($produtos as $key => $p) {
            $soma += $p['preco'];
        }
        $resto = number_format($valorComTaxa - $soma, 2);

        if ($resto != 0) {
            $repeticoesResto = $this->verificaRepeticoesParaResto($resto, sizeof($produtos));
            $rateio = $resto / $repeticoesResto;
            for ($i = 0; $i < $repeticoesResto; $i++) {
                $produtos[$i]['preco'] += $rateio;
            }
        }
        return $produtos;
    }

    public function verificaRepeticoesParaResto($resto, $pares)
    {
        $repeticoes = $resto < 0 ? $resto * -1 * 100 : $resto * 100;
        return $repeticoes > $pares ? $pares : $repeticoes;
    }

    public function calculaTaxasProdutos(PDO $conexao, array $produtos, array $post)
    {
        /*switch ($post['tipoPagamento']) {
            case '1': //cartao
                $taxas = Taxas::__aPrazo($post['cardNumber'], $post['parcelas']);
                break;
            case '2': //a vista
                $taxas = Taxas::__aVista(sizeof($produtos));
                break;
            case '3': //boleto
                $taxas = Taxas::__aVistaBoleto(sizeof($produtos));
                break;
            default:
                $taxas = Taxas::__aVista(sizeof($produtos));
                break;
        }*/
        $taxas = new Taxas();
        $taxas->SetJuros($conexao, $post['parcelas']);
        foreach ($produtos as $key => $produto) {
            $comissoes = $taxas->calculaCustoFornecedor(
                $produto['valor_custo_produto'],
                $post['tipoPagamento'],
                $produto['preco']
            );
            $produtos[$key]['comissao_fornecedor'] = $comissoes['comissao_fornecedor'];
            $produtos[$key]['acrescimo'] = $comissoes['acrescimo'];
        }
        return $produtos;
    }

    public function getTaxa_boleto()
    {
        return $this->taxa_boleto;
    }
    public function getTaxa_juros()
    {
        return $this->taxa_juros;
    }
    public function getvalor_credito()
    {
        return $this->valor_credito;
    }
    public function getjuros_fornecedor()
    {
        return $this->juros_fornecedor;
    }
}
