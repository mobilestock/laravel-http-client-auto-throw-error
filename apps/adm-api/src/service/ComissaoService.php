<?php
namespace MobileStock\service;

use PDO;

require_once __DIR__."/../../vendor/autoload.php";

class ComissaoService
{
    public function buscaComissoes($conexao)
    {
        $query = "SELECT * FROM lancamentos_financeiros_recebiveis WHERE id_recebedor=1 GROUP BY ";
        $stm = $conexao->prepare($query);
        $stm->execute();
    }

    // public function buscaComissoesFornecedor(PDO $conexao, int $mes, int $ano)
    // {
    //     $query = "SELECT f.valor_produtos, DATE_FORMAT(f.data_fechamento,'%d/%m/%Y')data_fechamento, (SELECT c.razao_social FROM colaboradores c WHERE c.id=lf.id_colaborador) seller,
    //     (SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.id_colaborador=lf.id_colaborador AND lf1.origem='SP') valor_split
    //     -- (SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.origem='SP') valor_total_split,
    //     -- (f.valor_produtos - (SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.origem='SP')) comissao_mobile,
    //     -- CONCAT((CASE WHEN f.tabela_preco=1 THEN 'Cartao' WHEN f.tabela_preco=2 THEN 'Deposito' WHEN f.tabela_preco=3 THEN 'Boleto' END)
    //     -- ,' ',
    //     -- (SELECT (CASE WHEN COUNT(lfr.id)=0 THEN 1 ELSE COUNT(lfr.id) END) FROM lancamentos_financeiros_recebiveis lfr WHERE lfr.id_lancamento=lf.id))pagamento,
    //     -- ((SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.id_colaborador=lf.id_colaborador AND lf1.origem='SP')/(SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.origem='SP')*(f.valor_produtos-(SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 WHERE lf1.pedido_origem=f.id AND lf1.origem='SP')))comissao
    //     FROM lancamento_financeiro lf 
    //     inner join faturamento f ON f.id = lf.pedido_origem
    //     WHERE (lf.origem = 'SP' OR lf.origem = 'SC') AND 
    //     MONTH(f.data_fechamento)={$mes} AND 
    //     YEAR(f.data_fechamento)={$ano} 
    //     group by lf.id_colaborador, f.id
    //     order by f.data_fechamento, lf.id_colaborador;";
    //     $resultado = $conexao->query($query);  
    //     return $resultado->fetchAll();
    // }
    
    // public function buscaComissaoFornecedorSplit(PDO $conexao, int $mes, int $ano)
    // {
    //     $query = "SELECT SUM(lf1.valor)split, lf1.id_colaborador, lf1.pedido_origem FROM lancamento_financeiro lf1
    //     INNER JOIN faturamento f WHERE (lf1.origem = 'SP' OR lf1.origem = 'SC') AND MONTH(f.data_emissao)={$mes} AND 
    //     YEAR(f.data_emissao)={$ano} ;";
    //     $resultado = $conexao->query($query);  
    //     return $resultado->fetchAll();
    // }

    // public function buscaComissaoFornecedorSplitTotal(PDO $conexao, int $mes, int $ano)
    // {
    //     $query = "SELECT SUM(lf1.valor)split_total, lf1.pedido_origem FROM lancamento_financeiro lf1 
    //     INNER JOIN faturamento f WHERE (lf1.origem = 'SP' OR lf1.origem = 'SC') AND MONTH(f.data_emissao)={$mes} AND 
    //     YEAR(f.data_emissao)={$ano} ;";
    //     $resultado = $conexao->query($query);  
    //     return $resultado->fetchAll();
    // }

    // public function buscaComissaoFornecedorComissaoMobile(PDO $conexao, int $mes, int $ano)
    // {
    //     $query = "SELECT (f.valor_produtos - (SELECT SUM(lf1.valor) FROM lancamento_financeiro lf1 
    //     WHERE lf1.pedido_origem=f.id AND (lf1.origem = 'SP' OR lf1.origem = 'SC')))comissao, f.id 
    //     FROM faturamento f WHERE  MONTH(f.data_emissao)={$mes} AND YEAR(f.data_emissao)={$ano} ;";
    //     $resultado = $conexao->query($query);  
    //     return $resultado->fetchAll();
    // }

    // public function buscaValorTotalProdutosPorFaturamento(PDO $conexao, int $dia, int $mes, int $ano)
    // {
    //     $query = "SELECT DATE_FORMAT(f.data_fechamento, '%d/%m/%Y') data_fechamento, f.id, f.valor_total total,
    //     c.razao_social AS seller, lf.id_colaborador, lf.parcelamento
    //     FROM lancamento_financeiro lf 
    //     INNER JOIN faturamento f ON lf.pedido_origem=f.id
    //     INNER JOIN colaboradores c ON c.id=lf.id_colaborador
    //     WHERE MONTH(f.data_fechamento)={$mes} 
    //     AND YEAR(f.data_fechamento)={$ano}
    //     AND DAY(f.data_fechamento)={$dia}
    //     AND f.tabela_preco<>2
    //     AND (lf.origem='SP')
    //     GROUP BY f.id, lf.id_colaborador, lf.parcelamento
    //     ORDER BY f.data_fechamento, lf.parcelamento, c.razao_social;";
    //     $resultado = $conexao->query($query);  
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }

    public function buscarCustoFornecedor(PDO $conexao, int $id, int $idColaborador)
    {
        $query = "SELECT SUM(lf.valor)valor FROM lancamento_financeiro lf 
        WHERE lf.pedido_origem={$id} AND lf.id_colaborador={$idColaborador}
        AND (lf.origem='SP' OR lf.origem='SC');";
        $resultado = $conexao->query($query);  
        $retorno = $resultado->fetch(PDO::FETCH_ASSOC);
        return $retorno['valor'];
    }

    public function buscarCustoFaturamento(PDO $conexao, int $id)
    {
        $query ="SELECT SUM(lf.valor)valor FROM lancamento_financeiro lf 
        WHERE lf.pedido_origem={$id}
        AND (lf.origem='SP' OR lf.origem='SC');";
        $resultado = $conexao->query($query);  
        $retorno = $resultado->fetch(PDO::FETCH_ASSOC);
        return $retorno['valor'];
    }
}