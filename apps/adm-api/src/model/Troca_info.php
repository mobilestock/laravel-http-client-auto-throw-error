<?php
/*
namespace MobileStock\model;

use MobileStock\database\Conexao;
use PDO;

class Troca_info
{
  private $mes;
  private $ano;

  public function __construct($data)  {
    $data = explode("-", $data);
    $this->mes = $data[1];
    $this->ano = $data[0];
  }  

  public function ListaTrocaMes()
  {
    $conexao = Conexao::criarConexao();      

        $query = "SELECT 
                    @DATA := DATE_FORMAT(faturamento_item.data_hora, '%m-%Y') mes,  
                    (SELECT FORMAT(SUM(faturamento_item.preco),2,'de_DE') FROM faturamento_item  WHERE DATE_FORMAT(faturamento_item.data_hora, '%m-%Y') = @DATA AND faturamento_item.situacao !=6) valor_total,
                    (SELECT FORMAT(COUNT(faturamento_item.uuid),0,'de_DE') FROM faturamento_item  WHERE DATE_FORMAT(faturamento_item.data_hora, '%m-%Y') = @DATA AND faturamento_item.situacao != 6 ) num_devolucao,
                    (SELECT FORMAT(COUNT(distinct faturamento_item.id_cliente),0,'de_DE') FROM faturamento_item  WHERE DATE_FORMAT(faturamento_item.data_hora, '%m-%Y') = @DATA AND faturamento_item.situacao !=6) num_clientes,
                    SUM(CASE WHEN faturamento_item.situacao = 8 THEN 1 ELSE 0 END) Devolucao,                   
                    SUM(CASE WHEN faturamento_item.situacao = 9 THEN 1 ELSE 0 END) Troca_Defeito,
                    SUM(CASE WHEN faturamento_item.situacao = 10 THEN 1 ELSE 0 END) Troca_Numeracao,
                    SUM(CASE WHEN faturamento_item.situacao = 11 THEN 1 ELSE 0 END) Troca_Normal,
                    SUM(CASE WHEN faturamento_item.situacao = 12 THEN 1 ELSE 0 END) Troca,
                    SUM(CASE WHEN faturamento_item.situacao = 16 THEN 1 ELSE 0 END) Troca_Autorizada,
                    SUM(CASE WHEN faturamento_item.situacao = 17 THEN 1 ELSE 0 END) Troca_Especial,
                    SUM(CASE WHEN faturamento_item.situacao = 18 THEN 1 ELSE 0 END) Troca_60_dias,
                    SUM(CASE WHEN faturamento_item.situacao = 19 THEN 1 ELSE 0 END) Correcao,
                    FORMAT(SUM(CASE WHEN faturamento_item.situacao IN (8,9,10,11,12,16,17,18,19) THEN 1 ELSE 0 END),0,'de_DE') total_faturado                 
                  FROM faturamento_item  
                  WHERE MONTH(faturamento_item.data_hora) = ".$this->mes."
                    AND YEAR(faturamento_item.data_hora) = ".$this->ano."
                  GROUP BY mes
                  ORDER BY faturamento_item.data_hora;";
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);


    //show testes
    echo "-------->"; echo $result['mes']; 
    echo "</br>";
    echo "-------->"; echo $result['valor_total']; 
    echo "-------->"; echo $result['num_devolucao']; 
    echo "-------->"; echo $result['num_clientes'];  
    echo "</br>";
    echo "-------->"; echo $result['Devolucao']; 
    echo "-------->"; echo $result['Troca_Defeito'];
    echo "-------->"; echo $result['Troca_Numeracao'];
    echo "-------->"; echo $result['Troca_Normal'];
    echo "-------->"; echo $result['Troca']; 
    echo "-------->"; echo $result['Troca_Autorizada']; 
    echo "-------->"; echo $result['Troca_Especial'];
    echo "-------->"; echo $result['Troca_60_dias'];
    echo "-------->"; echo $result['Correcao']; 

    
    // echo "----->"; echo $result['total_faturado'];

    return $result;
  } 
  
  public function ListaTrocaMesDetalhado(){
    $conexao = Conexao::criarConexao();  
    $query = "SELECT 
              colaboradores.razao_social nome,
              produtos.descricao produto,	
              faturamento_item.tamanho,
              FORMAT(faturamento_item.preco,2,'de_DE') valor_total,
              DATE_FORMAT(faturamento_item.data_hora, '%d/%m/%Y') data_hora,
              faturamento_item.id_faturamento
            FROM faturamento_item
              INNER JOIN colaboradores ON colaboradores.id = faturamento_item.id_cliente
              INNER JOIN produtos ON produtos.id = faturamento_item.id_produto
            WHERE MONTH(faturamento_item.data_hora) = ".$this->mes."
              AND YEAR(faturamento_item.data_hora) = ".$this->ano."              
              AND faturamento_item.situacao!=6;";
            
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


     show testes
     echo "-------->"; echo $result['mes']; 
     echo "</br>";
     echo "-------->"; echo $result['valor_total']; 
     echo "-------->"; echo $result['num_devolucao']; 
     echo "-------->"; echo $result['num_clientes'];  
     echo "</br>";
     echo "-------->"; echo $result['Devolucao']; 
     echo "-------->"; echo $result['Troca_Defeito'];
     echo "-------->"; echo $result['Troca_Numeracao'];
     echo "-------->"; echo $result['Troca_Normal'];
     echo "-------->"; echo $result['Troca']; 
     echo "-------->"; echo $result['Troca_Autorizada']; 
     echo "-------->"; echo $result['Troca_Especial'];
     echo "-------->"; echo $result['Troca_60_dias'];
     echo "-------->"; echo $result['Correcao'];


    return $result;

  }
}
*/