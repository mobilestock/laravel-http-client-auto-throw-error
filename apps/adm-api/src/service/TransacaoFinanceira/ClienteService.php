<?php

namespace MobileStock\service\TransacaoFinanceira;

use Error;
use MobileStock\database\Conexao;
use PDO;


 class ClienteService
 {
//      public static function buscaClienteHistorico(PDO $conexao = null, array $filtros)
//      {
//          $conexao = $conexao??Conexao::criarConexao();
//          $filtro = '';
//          $data_de = date('Y-m-d');

//          if (isset($filtros['data_de'])) {
//              $filtro .= " AND DATE(data_cadastro) >= '".$filtros['data_de']."'";
//          } else {
//              $filtro.= " AND date(colaboradores.data_cadastro) >= '{$data_de}'";
//          }
//          if (isset($filtros['data_ate'])) {
//              $filtro .= " AND DATE(data_cadastro) <= '".$filtros['data_ate']."'";
//          } else {
//              $filtro.=" AND DATE(colaboradores.data_cadastro) <= '{$data_de}'";
//          }
//          $order = '';
//          $order .= " ORDER BY  data_c DESC LIMIT 500";
//          $sql = "SELECT
//                 DATE_FORMAT(colaboradores.data_cadastro,'%d/%m/%Y')data_c,
//                 COUNT(colaboradores.id) qtd,
//              '' dias
//             FROM colaboradores
//             WHERE date(colaboradores.data_cadastro) 
//             GROUP BY data_c;{$filtro}";
                    
            
    
//          $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
//          return $retorno;
//      }
  }

    ?>