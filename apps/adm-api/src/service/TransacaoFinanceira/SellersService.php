<?php /*

namespace MobileStock\service\TransacaoFinanceira;

use Aws\Sdk;
use Error;
use MobileStock\database\Conexao;
use PDO;
class SellersService
{
    public static function buscaSellers(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $data_de = date('Y-m-d');
        $sql = '';
        $condicao_ = '';
        if (isset($filtros['data_de'])) {
            $filtro .= " AND DATE(data_atualizacao) >= '".$filtros['data_de']."'";
        } else {
            $filtro.= " AND date(transacao_financeiras.data_atualizacao) >= '{$data_de}'";
        }
        if (isset($filtros['data_ate'])) {
            $filtro .= " AND DATE(data_atualizacao) <= '".$filtros['data_ate']."'";
        } else {
            $filtro.=" AND DATE(transacao_financeiras.data_atualizacao) <= '{$data_de}'";
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] == 1) {
            $condicao_ = " AND transacao_financeiras.data_atualizacao = (SELECT MIN(tf.data_atualizacao) 
            FROM transacao_financeiras tf 
          WHERE tf.pagador = transacao_financeiras.pagador
            AND tf.status IN ('PA','PE'))";
        }
        
        //  if ($filtros['situacao']) {
        //      $sql .= " AND transacao_financeiras.status = '".$filtros['situacao']."'";
        //  }

        switch ($filtros['ordenar']) {
            
            case 'dia':
          $sql = "SELECT 

          DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y')data_atualizacao_,
        --   DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y')data_atualizacao_,
          SUM(transacao_financeiras.valor_liquido) valor_liquido,
          SUM(transacao_financeiras.valor_credito) valor_credito,
          SUM(transacao_financeiras.valor_acrescimo) valor_acrescimo,
          SUM(transacao_financeiras.valor_comissao_fornecedor) valor_comissao_fornecedor,
          COUNT(transacao_financeiras.id) qtd
          FROM transacao_financeiras 
          WHERE transacao_financeiras.status = 'PA' 
          {$condicao_}{$filtro}
                            GROUP BY data_atualizacao_
                            order by data_atualizacao";
          
        break;

        case 'semana':
            
            $sql = "SELECT 
            CONCAT(WEEK(transacao_financeiras.data_atualizacao),' - ',DATE_FORMAT(transacao_financeiras.data_atualizacao,'%m/%Y'))data_atualizacao_,
             SUM(transacao_financeiras.valor_liquido) valor_liquido,
             SUM(transacao_financeiras.valor_credito) valor_credito,
             SUM(transacao_financeiras.valor_acrescimo) valor_acrescimo,
             SUM(transacao_financeiras.valor_comissao_fornecedor) valor_comissao_fornecedor,
             COUNT(transacao_financeiras.id) qtd
             FROM transacao_financeiras
             WHERE transacao_financeiras.status = 'PA'
            --      AND date(transacao_financeiras.data_atualizacao) >= '2021-01-01'
            --    AND date(transacao_financeiras.data_atualizacao) <= '2021-06-01' 
                --  AND transacao_financeiras.data_atualizacao = (SELECT MIN(tf.data_atualizacao) 
                --                            FROM transacao_financeiras tf
                --                          WHERE tf.pagador = transacao_financeiras.pagador
                --                            AND tf.status IN ('PA','PE'))
                {$condicao_}{$filtro}
             GROUP BY data_atualizacao_";

             break;

             case 'mes':
                $sql = "SELECT 
                DATE_FORMAT(transacao_financeiras.data_atualizacao,'%m/%Y')data_atualizacao_,
                 SUM(transacao_financeiras.valor_liquido) valor_liquido,
                 SUM(transacao_financeiras.valor_credito) valor_credito,
                 SUM(transacao_financeiras.valor_acrescimo) valor_acrescimo,
                 SUM(transacao_financeiras.valor_comissao_fornecedor) valor_comissao_fornecedor,
                 COUNT(transacao_financeiras.id) qtd
                 FROM transacao_financeiras
                 WHERE transacao_financeiras.status = 'PA'
                --      AND date(transacao_financeiras.data_atualizacao) >= '2021-01-01'
                --    AND date(transacao_financeiras.data_atualizacao) <= '2021-06-01' 
                --      AND transacao_financeiras.data_atualizacao = (SELECT MIN(tf.data_atualizacao) 
                --                                FROM transacao_financeiras tf
                --                              WHERE tf.pagador = transacao_financeiras.pagador
                --                                AND tf.status IN ('PA','PE')) 
                {$condicao_}{$filtro}
                 GROUP BY data_atualizacao_";
               
               break;
            }
    
        
        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function buscaClienteHistorico(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $data_de = date('Y-m-d');
        $sql = '';

        if (isset($filtros['data_de'])) {
            $filtro .= " AND DATE(data_cadastro) >= '".$filtros['data_de']."'";
        } else {
            $filtro.= " AND date(colaboradores.data_cadastro) >= '{$data_de}'";
        }
        if (isset($filtros['data_ate'])) {
            $filtro .= " AND DATE(data_cadastro) <= '".$filtros['data_ate']."'";
        } else {
            $filtro.=" AND DATE(colaboradores.data_cadastro) <= '{$data_de}'";
        }

        switch ($filtros['ordenar']) {
            case 'semana':
                $sql = "SELECT
                    CONCAT(WEEK(colaboradores.data_cadastro),' - ',DATE_FORMAT(colaboradores.data_cadastro,'%m/%Y'))data_c,
                    COUNT(colaboradores.id) qtd,
                    CONCAT(MIN(DAY(colaboradores.data_cadastro)),' Até ',MAX(DAY(colaboradores.data_cadastro)),' de ',YEAR(colaboradores.data_cadastro)) dias
                    FROM colaboradores
                    WHERE 1=1 {$filtro} GROUP BY data_c
                    ORDER BY data_cadastro ";
                break;
                
            case 'dia':
                $sql = "SELECT
                    DATE_FORMAT(colaboradores.data_cadastro,'%d/%m/%Y')data_c,
                    COUNT(colaboradores.id)qtd,
                    'Sem Dados Para Mostrar' dias
                    FROM colaboradores
                    WHERE 1=1 {$filtro} GROUP BY data_c
                    ORDER BY data_cadastro";
                break;
                
            case 'mes':
                $sql = "SELECT
                    DATE_FORMAT(colaboradores.data_cadastro,'%m/%Y')data_c,
                    COUNT(colaboradores.id) qtd,
                    'Sem Dados Para Mostrar' dias
                    FROM colaboradores
                    WHERE 1=1 {$filtro} GROUP BY data_c
                    ORDER BY data_cadastro";
                break;

       

            }
        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function buscaFaturamento(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $data_de = date('Y-m');
        $sql = '';
        if (isset($filtros['data_de'])) {
            $filtro .= " AND DATE(faturamento.data_emissao) >= '".$filtros['data_de']."'";
        } else {
            $filtro.= " AND date(faturamento.data_emissao) >= '{$data_de}'";
        }
        if (isset($filtros['data_ate'])) {
            $filtro .= " AND DATE(faturamento.data_emissao) <= '".$filtros['data_ate']."'";
        } else {
            $filtro.=" AND DATE(faturamento.data_emissao) <= '{$data_de}'";
        }
        $sql = "SELECT 
             CONCAT(DAY(NOW()) -1,'/',DATE_FORMAT(faturamento.data_emissao, '%m/%Y')) data_c,
             SUM(faturamento_item.preco + faturamento_item.acrescimo) + ((COALESCE((SELECT SUM(transacao_financeiras_produtos_itens.preco) 
                    FROM transacao_financeiras_produtos_itens
                   WHERE (transacao_financeiras_produtos_itens.tipo_item = 'PR' OR transacao_financeiras_produtos_itens.tipo_item = 'RF')
                     AND transacao_financeiras_produtos_itens.situacao = 'CO'
                    AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento 
                              WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao)
                    AND DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%Y-%m') = DATE_FORMAT(faturamento.data_emissao, '%Y-%m')) ,0))) Valor_total,
             COUNT(distinct faturamento.id) Pedidos,
             @qtd := CAST((COUNT(distinct faturamento_item.uuid) + (COALESCE((SELECT COUNT(DISTINCT transacao_financeiras_produtos_itens.uuid) 
                    FROM transacao_financeiras_produtos_itens
                   WHERE (transacao_financeiras_produtos_itens.tipo_item = 'PR' OR transacao_financeiras_produtos_itens.tipo_item = 'RF')
                     AND transacao_financeiras_produtos_itens.situacao = 'CO'
                    AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento 
                              WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao)
                    AND DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%Y-%m') = DATE_FORMAT(faturamento.data_emissao, '%Y-%m')) ,0))) AS INTEGER) Pares,
        
             IF((CAST((COUNT(distinct faturamento_item.uuid) + (COALESCE((SELECT COUNT(DISTINCT transacao_financeiras_produtos_itens.uuid) 
                    FROM transacao_financeiras_produtos_itens
                   WHERE (transacao_financeiras_produtos_itens.tipo_item = 'PR' OR transacao_financeiras_produtos_itens.tipo_item = 'RF')
                     AND transacao_financeiras_produtos_itens.situacao = 'CO'
                    AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento 
                              WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao)
                    AND DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%Y-%m') = DATE_FORMAT(faturamento.data_emissao, '%Y-%m')) ,0))) AS INTEGER) / @qtd) >= 1, 
                CONCAT(ROUND((CAST((COUNT(distinct faturamento_item.uuid) + (COALESCE((SELECT COUNT(DISTINCT transacao_financeiras_produtos_itens.uuid) 
                    FROM transacao_financeiras_produtos_itens
                   WHERE (transacao_financeiras_produtos_itens.tipo_item = 'PR' OR transacao_financeiras_produtos_itens.tipo_item = 'RF')
                     AND transacao_financeiras_produtos_itens.situacao = 'CO'
                    AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento 
                              WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao)
                    AND DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%Y-%m') = DATE_FORMAT(faturamento.data_emissao, '%Y-%m')) ,0))) AS INTEGER) / @qtd - 1)*100,1)),
               CONCAT('-',ROUND((1 - CAST((COUNT(distinct faturamento_item.uuid) + (COALESCE((SELECT COUNT(DISTINCT transacao_financeiras_produtos_itens.uuid) 
                    FROM transacao_financeiras_produtos_itens
                   WHERE (transacao_financeiras_produtos_itens.tipo_item = 'PR' OR transacao_financeiras_produtos_itens.tipo_item = 'RF')
                     AND transacao_financeiras_produtos_itens.situacao = 'CO'
                    AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento 
                              WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao)
                    AND DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%Y-%m') = DATE_FORMAT(faturamento.data_emissao, '%Y-%m')) ,0))) AS INTEGER) / @qtd)*100,1))) ref_mes_anterior 
        FROM faturamento
        INNER JOIN faturamento_item ON faturamento_item.id_faturamento = faturamento.id
        WHERE DATE(faturamento.data_emissao) >= '2020-01-1'
          AND day(faturamento.data_emissao) BETWEEN 1 AND DAY(NOW()) -1 
          AND faturamento.situacao IN (2,1)
          {$filtro}
        GROUP BY data_c
        order by data_emissao";

        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

        
    public static function buscaValorQuantidade(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $data_de = date('Y-m-d');
        $sql = '';
        $condicao_ = '';
        if (isset($filtros['data_de'])) {
            $filtro .= " AND DATE(data_atualizacao) >= '".$filtros['data_de']."'";
        } else {
            $filtro.= " AND date(transacao_financeiras.data_atualizacao) >= '{$data_de}'";
        }
        if (isset($filtros['data_ate'])) {
            $filtro .= " AND DATE(data_atualizacao) <= '".$filtros['data_ate']."'";
        } else {
            $filtro.=" AND DATE(transacao_financeiras.data_atualizacao) <= '{$data_de}'";
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] == 1) {
            $condicao_ = " AND transacao_financeiras.data_atualizacao = (SELECT MIN(tf.data_atualizacao) 
            FROM transacao_financeiras tf 
          WHERE tf.pagador = transacao_financeiras.pagador
            AND tf.status IN ('PA','PE')) ";
        }
        
        //  if ($filtros['situacao']) {
        //      $sql .= " AND transacao_financeiras.status = '".$filtros['situacao']."'";
        //  }

        switch ($filtros['ordenar']) {
            
        case 'cnpj':
          $sql = "SELECT 
            DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y')data_atualizacao_,
            SUM(colaboradores.cnpj) cnpj,
            SUM(transacao_financeiras.valor_liquido) valor_liquido,
            COUNT(transacao_financeiras.id) qtd
            FROM transacao_financeiras 
            INNER JOIN colaboradores ON(colaboradores.id = transacao_financeiras.id_usuario)
            WHERE transacao_financeiras.status = 'PA' 
            {$condicao_}{$filtro}
                
            GROUP BY data_atualizacao_";
          
        break;

        case 'cpf':
            $sql = "SELECT 
                DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y')data_atualizacao_,
                SUM(colaboradores.cpf) cpf,
                SUM(transacao_financeiras.valor_liquido) valor_liquido,
                COUNT(transacao_financeiras.id) qtd
                FROM transacao_financeiras 
                  INNER JOIN colaboradores ON(colaboradores.id = transacao_financeiras.id_usuario)
                WHERE transacao_financeiras.status = 'PA' 
                    {$condicao_}{$filtro}
                GROUP BY data_atualizacao_
                order by data_atualizacao";
                
               break;
            }
    
        
        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }


    public static function buscaVendaEstado(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $sql = '';
        // if (isset($filtros['data_de'])) {
        //     $filtro .= " AND DATE(transacao_financeiras.data_atualizacao) >= '".$filtros['data_de']."'";
        // } else {
        //     $filtro.= " AND date(transacao_financeiras.data_atualizacao) >= '{$data_de}'";
        // }
        if (isset($filtros['uf'])) {
            $uf = $filtros['uf'];
            $filtro .= " AND (UPPER(colaboradores.uf) LIKE UPPER('{$uf}%'))";
        }
        if (isset($filtros['cidade'])) {
            $cidade = $filtros['cidade'];
            $filtro .= " AND (UPPER(colaboradores.cidade) LIKE UPPER('{$cidade}%'))";
        }
        $sql = "SELECT colaboradores.uf identificacao, colaboradores.cidade cid,
            SUM(transacao_financeiras.valor_liquido) valor_liquido,
            COUNT(transacao_financeiras.id) qtd
            FROM colaboradores
            INNER JOIN transacao_financeiras ON(transacao_financeiras.id = colaboradores.id)
            WHERE transacao_financeiras.status = 'PA' 
            
        
            {$filtro}
            GROUP BY uf";


        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function buscaValorFrete(PDO $conexao = null, array $filtros)
    {
        $conexao = $conexao??Conexao::criarConexao();
        $filtro = '';
        $data_de = date('Y-m-d');
        $sql = '';
        if (isset($filtros['data_de'])) {
            $filtro .= " AND DATE(data_emissao) >= '".$filtros['data_de']."'";
        } else {
            $filtro.= " AND date(faturamento.data_emissao) >= '{$data_de}'";
        }
        if (isset($filtros['data_ate'])) {
            $filtro .= " AND DATE(data_emissao) <= '".$filtros['data_ate']."'";
        } else {
            $filtro.=" AND DATE(faturamento.data_emissao) <= '{$data_de}'"; 
        }

        $sql = "SELECT faturamento.valor_frete frete, 
        tipo_frete.titulo tipo_frete, 
        DATE_FORMAT(faturamento.data_emissao,'%d/%m/%Y')data_at,
        COUNT(faturamento.data_emissao) qtd,
        (faturamento.valor_frete * COUNT(faturamento.data_emissao))total_frete
      FROM tipo_frete
      INNER JOIN faturamento ON (faturamento.tipo_frete = tipo_frete.id)
      WHERE valor_frete > 0 {$filtro}
      group by tipo_frete.titulo, data_at";

        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }
}
    ?>*/