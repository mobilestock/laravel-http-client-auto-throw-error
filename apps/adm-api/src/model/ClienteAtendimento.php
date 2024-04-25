<?php
/*
namespace MobileStock\model;

use MobileStock\database\Conexao;
use PDO;

class ClienteAtendimento
{

  private $id_cliente;
  private $id_atendente;
  private $id_tipo_atendimento;
  private $mensagem;
  private $anexo;
  private $id_faturameto;
  private $id_produto;
  private $situacao;
  private $numero_par;


  function InsereMensagemClienteAtendimento(
    $id_cliente,
    $id_tipo_atendimento,
    $mensagem,
    $anexo,
    $id_faturameto,
    $id_produto,
    $id_colaborador,
    $situacao,
    string $numero_par
  ) {
    $query = "INSERT INTO atendimento_cliente(id_cliente, id_tipo_atendimento, mensagem, anexo, id_faturamento,id_produto,id_colaborador,
                situacao,data_final, numero_par)
                    VALUES({$id_cliente},{$id_tipo_atendimento},'{$mensagem}','{$anexo}','{$id_faturameto}'
                    ,'{$id_produto}','{$id_colaborador}',{$situacao}, 
                        NOW(),'{$numero_par}');";

    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
  }


   * Adiciona o valor solicitado de credito ou reembolso dentro das informações do atendimento


  public function AtualizaPrecoCreditoReembolso(string $id_atendimento, string $valor): bool
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare('UPDATE atendimento_cliente SET atendimento_cliente.valor =:valor WHERE atendimento_cliente.id=:idAtendimento;');
    $query->bindValue(':idAtendimento', $id_atendimento, PDO::PARAM_INT);
    $query->bindValue(':valor', $valor, PDO::PARAM_STR);
    if ($query->execute()) {
      return true;
    } else {
      return false;
    }
  }

  // public function buscaFaturamentoItem($id_faturamento, $id_produto, $tamanho)
  // {
  //   $conexao = Conexao::criarConexao();
  //   //$query = $conexao->prepare('SELECT uuid, cod_barras, data_hora FROM faturamento_item WHERE faturamento_item.id_faturamento=:idFaturamento AND faturamento_item.id_produto = :idProduto AND faturamento_item.tamanho = :tamanho AND NOT EXISTS(SELECT 1 FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = faturamento_item.uuid ) LIMIT 1;');
  //   $query = $conexao->prepare('SELECT uuid, cod_barras, data_hora FROM faturamento_item WHERE faturamento_item.id_faturamento=:idFaturamento AND faturamento_item.id_produto = :idProduto AND faturamento_item.tamanho = :tamanho AND faturamento_item.situacao = 6 AND NOT EXISTS(SELECT 1 FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = faturamento_item.uuid )  LIMIT 1;');
  //   $query->bindValue(':idFaturamento', $id_faturamento, PDO::PARAM_INT);
  //   $query->bindValue(':idProduto', floatval($id_produto), PDO::PARAM_INT);
  //   $query->bindValue(':tamanho', floatval($tamanho), PDO::PARAM_INT);
  //   $query->execute();
  //   $faturamento_item = $query->fetch(PDO::FETCH_ASSOC);
  //   return $faturamento_item;
  // }
  public function buscaFaturamento(int $id_faturamento)
  {
    $conexao = Conexao::criarConexao();
    //$query = $conexao->prepare('SELECT uuid, cod_barras, data_hora FROM faturamento_item WHERE faturamento_item.id_faturamento=:idFaturamento AND faturamento_item.id_produto = :idProduto AND faturamento_item.tamanho = :tamanho AND NOT EXISTS(SELECT 1 FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = faturamento_item.uuid ) LIMIT 1;');
    $query = $conexao->prepare('SELECT faturamento.data_emissao FROM faturamento WHERE faturamento.id=:idFaturamento  LIMIT 1;');
    $query->bindValue(':idFaturamento', $id_faturamento, PDO::PARAM_INT);
    $query->execute();
    $faturamento = $query->fetch(PDO::FETCH_ASSOC);
    return $faturamento['data_emissao'];
  }


   * Verifica se existe respostas em aberto

  public function ExisteRespostaClientePendente(string $id_cliente): string
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare('SELECT COUNT(*) AS respostas FROM atendimento_cliente WHERE id_cliente=:idCliente AND situacao=2');
    $query->bindValue(':idCliente', $id_cliente, PDO::PARAM_INT);
    $result = $query->execute();
    if ($result['respostas'] > 0) {
      return $result['respostas'];
    } else {
      return 0;
    }
  }


   * Função Lista todos os créditos e reembolsos do mês atual



  public function ListaAtendimentosCreditoReembolso($data): array
  {
    if ($data == '') {
      $mes =  date('m');
      $ano =  date('Y');
      $conexao = Conexao::criarConexao();
      $query = $conexao->prepare('SELECT lancamento_financeiro.status_estorno, lancamento_financeiro.atendimento,atendimento_cliente.id,atendimento_cliente.id_faturamento,
            (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id=atendimento_cliente.id_cliente
            ) AS cliente, 
            (
              SELECT usuarios.nome
                FROM usuarios
                  WHERE usuarios.id = lancamento_financeiro.id_usuario_edicao
            ) AS usuarios, lancamento_financeiro.data_emissao, lancamento_financeiro.valor, atendimento_cliente.numero_par, 
            (
              SELECT produtos.descricao FROM produtos WHERE produtos.id=atendimento_cliente.id_produto
            ) AS produto, 
            (
              SELECT tipo_atendimento.nome FROM tipo_atendimento WHERE tipo_atendimento.id = atendimento_cliente.id_tipo_atendimento
            ) AS problema
                FROM lancamento_financeiro 
                  JOIN atendimento_cliente
                    WHERE atendimento_cliente.id_faturamento = lancamento_financeiro.pedido_origem 
                      AND lancamento_financeiro.origem = "AT"
                      AND MONTH(lancamento_financeiro.data_emissao) = :mes
                      AND YEAR(lancamento_financeiro.data_emissao) = :ano GROUP BY atendimento_cliente.id ;');
      $query->bindValue(':mes', $mes, PDO::PARAM_STR);
      $query->bindValue(':ano', $ano, PDO::PARAM_STR);
      $query->execute();
    } else {
      $data = explode("-", $data);
      $conexao = Conexao::criarConexao();
      $query = "SELECT lancamento_financeiro.status_estorno, lancamento_financeiro.atendimento,atendimento_cliente.id,atendimento_cliente.id_faturamento,
            (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id=atendimento_cliente.id_cliente
            ) AS cliente, 
            (
              SELECT usuarios.nome
                FROM usuarios
                  WHERE usuarios.id = lancamento_financeiro.id_usuario_edicao
            ) AS usuarios, lancamento_financeiro.data_emissao, lancamento_financeiro.valor, atendimento_cliente.numero_par, 
            (
              SELECT produtos.descricao FROM produtos WHERE produtos.id=atendimento_cliente.id_produto
            ) AS produto, 
            (
              SELECT tipo_atendimento.nome FROM tipo_atendimento WHERE tipo_atendimento.id = atendimento_cliente.id_tipo_atendimento
            ) AS problema
                FROM lancamento_financeiro 
                  JOIN atendimento_cliente
                    WHERE atendimento_cliente.id_faturamento = lancamento_financeiro.pedido_origem 
                      AND lancamento_financeiro.atendimento='S' 
                      AND MONTH(lancamento_financeiro.data_emissao) = '{$data[1]}'
                      AND YEAR(lancamento_financeiro.data_emissao) ='{$data[0]}' GROUP BY atendimento_cliente.id ;";
      $stmt = $conexao->prepare($query);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $result;
    }

    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function setControle($status, $id): void
  {

    $query = "UPDATE atendimento_cliente SET atendimento_cliente.controle = '{$status}' WHERE atendimento_cliente.id= '{$id}';";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
  }


public function ListaAtendimentosCreditoReembolso2():array{
  $mes=  date('m') ;
  $ano=  date('Y');
  $query = "SELECT lancamento_financeiro.status_estorno, lancamento_financeiro.atendimento,atendimento_cliente.id,atendimento_cliente.id_faturamento,
            (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id=atendimento_cliente.id_cliente
            ) AS cliente, 
            (
              SELECT usuarios.nome
                FROM usuarios
                  WHERE usuarios.id = lancamento_financeiro.id_usuario_edicao
            ) AS usuarios, lancamento_financeiro.data_emissao, atendimento_cliente.valor, atendimento_cliente.numero_par, 
            (
              SELECT produtos.descricao FROM produtos WHERE produtos.id=atendimento_cliente.id_produto
            ) AS produto, 
            (
              SELECT tipo_atendimento.nome FROM tipo_atendimento WHERE tipo_atendimento.id = atendimento_cliente.id_tipo_atendimento
            ) AS problema
                FROM lancamento_financeiro 
                  JOIN atendimento_cliente
                    WHERE atendimento_cliente.id_faturamento = lancamento_financeiro.pedido_origem 
                      AND lancamento_financeiro.atendimento='S' 
                      AND MONTH(lancamento_financeiro.data_emissao) ='{$mes}'
                      AND YEAR(lancamento_financeiro.data_emissao) = '{$ano}';";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
  return $lista;
}



  public static function listaClienteAtendimento(PDO $conexao, int $idCliente)
  {
    $query = $conexao->prepare("SELECT
                                  atendimento_cliente.id,
                                  IF(atendimento_cliente.situacao = 0, 'Processando', 'Respondido') AS `situacao`,
                                  tipo_atendimento.nome AS `tipo`,
                                  DATE_FORMAT(atendimento_cliente.data_inicio, '%d/%m/%Y %H:%i') AS `data_inicio`
                                FROM atendimento_cliente 
                                JOIN tipo_atendimento ON tipo_atendimento.id = atendimento_cliente.id_tipo_atendimento
                                WHERE atendimento_cliente.id_cliente = :idCliente
                                ORDER BY situacao ASC, id DESC");
    $query->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public static function listaPedidosParaAtendimento(PDO $conexao, int $idCliente) {
    $query = $conexao->prepare("SELECT
                                  transacao_financeiras.id,
                                  DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y') AS `data`
                                FROM transacao_financeiras
                                WHERE transacao_financeiras.pagador = :idCliente
                                  AND transacao_financeiras.`status` = 'PA'
                                ORDER BY transacao_financeiras.id DESC
                                LIMIT 200");
    $query->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }


   * Problemas e Informações 
   * Auxiliares sobre o atendimento são setados. Recebemos um inteiro referente a informação.

  public function setControleProblema($id_atendimento, $problema)
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare('UPDATE atendimento_cliente SET atendimento_cliente.controle = idProblema WHERE atendimento_cliente.ido = idAtendimento');
    $query->bindValue(':idAtendimento', $id_atendimento, PDO::PARAM_INT);
    $query->bindValue(':idProblema', floatval($problema), PDO::PARAM_INT);
    if ($query->execute()) {
      return true;
    } else {
      return false;
    }
  }

   * Insere dentro do banco de dados as informações do PAC Reverso dentro da tabela

  public function InserePacReverso($id_cliente, $id_atendimento, $numeroColeta, $data, $idObj, $statusObj)
  {
    $prazo = implode('-', array_reverse(explode('/', $data)));
    $conexao = Conexao::criarConexao();
    $query = "INSERT INTO correios_atendimento(id_cliente,id_atendimento,numeroColeta,prazo,idObjeto,statusObjeto)
              VALUES({$id_cliente}, {$id_atendimento},'{$numeroColeta}', '{$prazo}', '{$idObj}', '{$statusObj}');";
    $stmt = $conexao->prepare($query);
    if ($stmt->execute()) {
      return true;
    } else {
      return false;
    }
  }

   * Verifica qual é PAC que possui prazo máximo menor 

  public function verificaAtualizacaoPAC()
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare("SELECT *, CAST(MIN(correios_atendimento.prazo) AS DATE) AS ultima_data FROM correios_atendimento LIMIT 1;");
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }


   * Retorna todos os PACs em Aberto

  public function buscaPACsEmAberto()
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare("SELECT * FROM correios_atendimento WHERE correios_atendimento.status='A';");
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

   * Retorna todos os PACs Postados

  public function buscaPACsPostados()
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare("SELECT * FROM correios_atendimento WHERE correios_atendimento.status='P';");
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

   * Retorna todos os PAC Gerados

  public function buscaCorreiosAtendimentos()
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare("SELECT * FROM correios_atendimento;");
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

   * Atualiza no banco status PAC 

  public function atualizaStatusPAC($numeroColeta, $status)
  {

    $conexao = Conexao::criarConexao();
    $query = "UPDATE correios_atendimento SET correios_atendimento.status='{$status}' WHERE correios_atendimento.numeroColeta={$numeroColeta}";
    $stmt = $conexao->prepare($query);
    if ($stmt->execute()) {
      return true;
    } else {
      return false;
    }
  }

   * Atualiza no banco status do Objeto do PAC Correios

  public function atualizaStatusObjetoPAC($numeroColeta, $status)
  {

    $conexao = Conexao::criarConexao();
    $query = "UPDATE correios_atendimento SET correios_atendimento.statusObjeto={$status} WHERE correios_atendimento.numeroColeta={$numeroColeta}";
    $stmt = $conexao->prepare($query);
    if ($stmt->execute()) {
      return true;
    } else {
      return false;
    }
  }


   *  Atualiza data verificação do PAC


  public function atualizaDataVerificacaoPAC($numeroColeta, $data)
  {
    if ($data != '') {
      $data = date('Y-m-d', strtotime($data));
      $query = "UPDATE correios_atendimento SET correios_atendimento.data_verificacao = {$data} WHERE correios_atendimento.numeroColeta={$numeroColeta}";
    } else {
      $query = "UPDATE correios_atendimento SET correios_atendimento.data_verificacao = NOW() WHERE correios_atendimento.numeroColeta={$numeroColeta}";
    }
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    if ($stmt->execute()) {
      return true;
    } else {
      return false;
    }
  }

  public function deletaCorreioAtendimento($id)
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare('DELETE FROM correios_atendimento WHERE id=:id');
    $query->bindValue(':id', $id, PDO::PARAM_INT);
    if ($query->execute()) {
      return true;
    } else {
      return false;
    }
  }
  public function atualizaCorreioAtendimento()
  {
    $conexao = Conexao::criarConexao();
    $query = $conexao->prepare('UPDATE correios_atendimento SET correios_atendimento.statusObjeto=0 WHERE correios_atendimento.id = (SELECT correios_atendimento.id FROM correios_atendimento order by correios_atendimento.id desc limit 1)');
    if ($query->execute()) {
      return true;
    } else {
      return false;
    }
  }

  public function consultaTotalAtendimentosFinalizadosMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND MONTH(atendimento_cliente.data_final)='{$data[1]}' and YEAR(atendimento_cliente.data_final)='{$data[0]}' AND atendimento_cliente.situacao=0;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND MONTH(atendimento_cliente.data_final)=MONTH(NOW()) and YEAR(atendimento_cliente.data_final)=YEAR(NOW()) AND atendimento_cliente.situacao=0;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }

  public function consultaTotalDiferenteMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento =8;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 8;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }

  public function consultaTotalAtendimentosMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}';";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW());";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }
  public function consultaTotalDefeitosMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 1;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 1;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }

  public function consultaTotalFaltandoMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 2;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 2 ";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }

  public function consultaTotalErradoMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 3;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 3;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }

  public function existePACAberto(int $id_cliente)
  {
    $sql = "SELECT correios_atendimento.numeroColeta FROM correios_atendimento WHERE correios_atendimento.id_cliente ={$id_cliente} AND correios_atendimento.status='A' LIMIT 1";
    $conexao = Conexao::criarConexao();
    $stm = $conexao->prepare($sql);
    $stm->execute();
    $result = $stm->fetch(PDO::FETCH_ASSOC);
    if ($result['numeroColeta']) {
      return $result['numeroColeta'];
    } else {
      return 0;
    }
  }

  public function setUuidProduto(string $uuid, int $id_atendimento)
  {
    $sql = "UPDATE atendimento_cliente SET uuid = '{$uuid}' WHERE id = {$id_atendimento}";
    $conexao = Conexao::criarConexao();
    $stm = $conexao->prepare($sql);
    $retorno = $stm->execute();
    return $retorno;
  }
  public function consultaTotalSugestaoMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 5;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 5;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }
  public function consultaTotalReclamacaoMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 6;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento =6;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }
  public function consultaTotalDuvidaMes($data)
  {
    $conexao = Conexao::criarConexao();
    if ($data != "") {
      $data = explode("-", $data);
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' and YEAR(atendimento_cliente.data_inicio)='{$data[0]}' AND atendimento_cliente.id_tipo_atendimento = 7;";
    } else {
      $query = "SELECT COUNT(*) AS total FROM atendimento_cliente WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) and YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) AND atendimento_cliente.id_tipo_atendimento = 7;";
    }
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
  }
  // public function ListaAtendimentoRelatorio($data, $tipo)
  // {
  //   $conexao = Conexao::criarConexao();
  //   if ($data != "" && $tipo == "") {
  //     $data = explode("-", $data);
  //     $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //               (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}'
  //                             AND YEAR(atendimento_cliente.data_inicio)='{$data[0]}'
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //   } else if ($data == "" && $tipo == "") {
  //     $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) 
  //                             AND YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) 
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //   } else if ($data != "" && $tipo != "") {
  //     $data = explode("-", $data);
  //     if ($tipo != "" && $tipo > 9) {
  //       $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //               (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' 
  //                             AND YEAR(atendimento_cliente.data_inicio)='{$data[0]}'
  //                             AND atendimento_cliente.id_tipo_atendimento<>1
  //                             AND atendimento_cliente.id_tipo_atendimento<>2
  //                             AND atendimento_cliente.id_tipo_atendimento<>8
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //     } else {
  //       $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //               (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)='{$data[1]}' 
  //                             AND YEAR(atendimento_cliente.data_inicio)='{$data[0]}'
  //                             AND atendimento_cliente.id_tipo_atendimento='{$tipo}'
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //     }
  //   } else if ($data == "" && $tipo != "") {
  //     if ($tipo != "" && $tipo > 9) {
  //       $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) 
  //                             AND YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) 
  //                             AND atendimento_cliente.id_tipo_atendimento<>1
  //                             AND atendimento_cliente.id_tipo_atendimento<>2
  //                             AND atendimento_cliente.id_tipo_atendimento<>8
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //     } else {
  //       $query = "SELECT atendimento_cliente.id, atendimento_cliente.id_faturamento,atendimento_cliente.id_tipo_atendimento, 
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id=atendimento_cliente.id_colaborador) AS atendente,
  //                 atendimento_cliente.situacao, tipo_atendimento.nome,colaboradores.razao_social,faturamento_item.id_separador,
  //                 (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador AND faturamento_item.id_faturamento=atendimento_cliente.id_faturamento LIMIT 1) as usuario 
  //                 FROM atendimento_cliente 
  //                   JOIN tipo_atendimento 
  //                     JOIN colaboradores 
  //                       JOIN faturamento_item 
  //                         JOIN usuarios 
  //                           WHERE MONTH(atendimento_cliente.data_inicio)=MONTH(NOW()) 
  //                             AND YEAR(atendimento_cliente.data_inicio)=YEAR(NOW()) 
  //                             AND atendimento_cliente.id_tipo_atendimento='{$tipo}'
  //                             AND usuarios.id=faturamento_item.id_separador 
  //                             AND atendimento_cliente.id_tipo_atendimento= tipo_atendimento.id 
  //                             AND atendimento_cliente.id_cliente = colaboradores.id 
  //                             AND faturamento_item.id_faturamento = atendimento_cliente.id_faturamento GROUP BY atendimento_cliente.id;";
  //     }
  //   }
  //   $stmt = $conexao->prepare($query);
  //   $stmt->execute();
  //   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   return $result;
  // }

function ListaClienteAtendimento2($id_cliente){
  $query = "SELECT atendimento_cliente.id,atendimento_cliente.situacao,(
              SELECT tipo_atendimento.nome 
                FROM tipo_atendimento 
                  WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
              )AS tipo, atendimento_cliente.data_inicio
                FROM atendimento_cliente 
                  WHERE atendimento_cliente.id_cliente={$id_cliente} 
                    AND atendimento_cliente.situacao!=1 
                    AND atendimento_cliente.situacao != 0 ORDER BY situacao ASC, id DESC ;";
 
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

  function ListaMensagensCliente($id_atendimento)
  {
    $query = "SELECT atendimento_cliente.*,(
      SELECT colaboradores.razao_social 
        FROM colaboradores 
          WHERE colaboradores.id = atendimento_cliente.id_cliente
      )AS cliente,(
      SELECT tipo_atendimento.nome 
        FROM tipo_atendimento 
          WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
      )AS tipo, (
  SELECT colaboradores.razao_social
    FROM colaboradores 
      INNER JOIN produtos ON colaboradores.id = produtos.id_fornecedor
      WHERE colaboradores.id = produtos.id_fornecedor AND produtos.id = atendimento_cliente.id_produto
      GROUP BY colaboradores.id
  )AS Seller
        FROM atendimento_cliente
          WHERE atendimento_cliente.id = {$id_atendimento};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetch(PDO::FETCH_ASSOC);
    return $lista;
  }

  function ListaAtendimentos(array $param)
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento)AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final,
             DATE_FORMAT(atendimento_cliente.data_inicio, '%d/%m/%Y %H:%i:%s') AS inicio,
             DATE_FORMAT(atendimento_cliente.data_final, '%d/%m/%Y %H:%i:%s') AS final 
              FROM atendimento_cliente WHERE atendimento_cliente.id_cliente <> 0
              AND atendimento_cliente.data_inicio BETWEEN DATE_ADD(NOW(), INTERVAL -60 DAY) AND NOW()";

    extract($param);

    if ($id_colaborador) {
      $query .= " AND atendimento_cliente.id_cliente={$id_colaborador}";
    }
    if ($id_produto) {
      $query .= " AND atendimento_cliente.id_produto={$id_produto}";
    }
    if ($situacao) {
      $query .= " AND atendimento_cliente.situacao={$situacao}";
    }
    if ($tipo) {
      $query .= " AND atendimento_cliente.id_tipo_atendimento={$tipo}";
    }
    if ($dia) {
      $query .= " AND (DATE(atendimento_cliente.data_inicio) ='{$dia}' OR DATE(atendimento_cliente.data_final) ='{$dia}')";
    }
    if ($mes) {
      $query .= " AND atendimento_cliente.data_inicio LIKE '{$mes}%'";
    }
    if ($ordenar) {
      switch ($ordenar) {
        case '1':
          $query .= " ORDER BY atendimento_cliente.data_inicio ASC";
          break;
        case '2':
          $query .= " ORDER BY atendimento_cliente.data_final ASC";
          break;
        case '3':
          $query .= " ORDER BY atendimento_cliente.data_inicio DESC";
          break;
        case '4':
          $query .= " ORDER BY atendimento_cliente.data_final DESC";
          break;
        default:
          $query .= " ORDER BY situacao ASC;";
      }
    }
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }

  function ListaOutrosAtendimentos()
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
            SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
            )AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final,
             DATE_FORMAT(atendimento_cliente.data_inicio, '%d/%m/%Y %H:%i:%s') AS inicio,
             DATE_FORMAT(atendimento_cliente.data_final, '%d/%m/%Y %H:%i:%s') AS final 
              FROM atendimento_cliente WHERE id_tipo_atendimento IN (4,5,6,7) AND situacao IN (1,4,2)
              AND atendimento_cliente.data_inicio BETWEEN DATE_ADD(NOW(), INTERVAL -60 DAY) AND NOW() ORDER BY situacao ASC;";

    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
  }

  function ListaTodosAtendimentosClientes()
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
            SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
            )AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final,
             DATE_FORMAT(atendimento_cliente.data_inicio, '%d/%m/%Y %H:%i:%s') AS inicio,
             DATE_FORMAT(atendimento_cliente.data_final, '%d/%m/%Y %H:%i:%s') AS final 
              FROM atendimento_cliente WHERE atendimento_cliente.data_inicio BETWEEN DATE_ADD(NOW(), INTERVAL -60 DAY) AND NOW()
              ORDER BY situacao DESC;";

    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }

  function ListaAtendimentosPendentesClientes($id_atendente)
  {

    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
          SELECT tipo_atendimento.nome 
            FROM tipo_atendimento 
              WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
          )AS tipo, (
            SELECT colaboradores.razao_social 
              FROM colaboradores 
                WHERE colaboradores.id = atendimento_cliente.id_cliente
          )AS cliente, (
            SELECT usuarios.nome 
              FROM usuarios 
                WHERE usuarios.id = atendimento_cliente.id_colaborador
          )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final 
            FROM atendimento_cliente 
              WHERE atendimento_cliente.id_colaborador = {$id_atendente}
                AND atendimento_cliente.situacao = 4 or atendimento_cliente.situacao=3; ";

    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }

  function ListaAtendimentosNaoFinalizado($id_atendente)
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
            SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
            )AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final 
              FROM atendimento_cliente WHERE atendimento_cliente.situacao != 0  AND atendimento_cliente.id_colaborador = {$id_atendente}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }

  function ListaAtendimentosAtendente($id_atendente)
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
            SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
            )AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final 
              FROM atendimento_cliente WHERE atendimento_cliente.id_colaborador = {$id_atendente} ORDER BY situacao DESC;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
    $lista=$resultado->fetchAll(PDO::FETCH_ASSOC);
  $novoArray = [];
  $novoArray[4] = [];
  $novoArray[3] = [];
  $novoArray[1] = [];
  $novoArray[2] = [];
  $novoArray[5] = [];
  foreach ($lista as $key => $value) {
    $novoArray[$value['situacao']][] = $value;
  }
  return $novoArray;
  }
  function ListaSolicitacaoAtendimento()
  {
    $query = "SELECT atendimento_cliente.id, atendimento_cliente.situacao,(
            SELECT tipo_atendimento.nome 
              FROM tipo_atendimento 
                WHERE tipo_atendimento.id=atendimento_cliente.id_tipo_atendimento
            )AS tipo, (
              SELECT colaboradores.razao_social 
                FROM colaboradores 
                  WHERE colaboradores.id = atendimento_cliente.id_cliente
            )AS cliente, (
              SELECT usuarios.nome 
                FROM usuarios 
                  WHERE usuarios.id = atendimento_cliente.id_colaborador
            )AS atendente, atendimento_cliente.data_inicio, atendimento_cliente.data_final 
              FROM atendimento_cliente WHERE atendimento_cliente.situacao = 3 ";

    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
  }
  function existeAtendente($id_atendimento)
  {
    $query = "SELECT atendimento_cliente.id_colaborador as id FROM atendimento_cliente WHERE atendimento_cliente.id={$id_atendimento}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $existe = $resultado->fetch();
    return $existe;
  }

  function AtendenteIngressaAtendimento($id_atendimento, $id_atendente)
  {
    $query = "UPDATE atendimento_cliente SET atendimento_cliente.id_colaborador={$id_atendente} WHERE atendimento_cliente.id={$id_atendimento}";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
  }
  function AtendenteRespondeCliente($id_atendimento, $mensagem)
  {
    $query = "UPDATE atendimento_cliente SET atendimento_cliente.mensagem = '{$mensagem}',atendimento_cliente.situacao=2 WHERE atendimento_cliente.id={$id_atendimento}";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
  }
  function ClienteRespondeAtendente($situacao, $id_atendimento, $mensagem, $fotos)
  {
    $query = "UPDATE atendimento_cliente SET atendimento_cliente.mensagem = '{$mensagem}',atendimento_cliente.situacao={$situacao}, atendimento_cliente.anexo='{$fotos}' WHERE atendimento_cliente.id={$id_atendimento}";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
  }
  function setSituacao($situacao, $id_atendimento)
  {
    if ($situacao == 0) {
      $query = "UPDATE atendimento_cliente SET situacao ={$situacao}, score = 0 WHERE id = {$id_atendimento}";
      $conexao = Conexao::criarConexao();
      return $conexao->exec($query);
    } else {

      $query = "UPDATE atendimento_cliente SET situacao ={$situacao} WHERE id = {$id_atendimento}";
      $conexao = Conexao::criarConexao();
      return $conexao->exec($query);
    }
  }

  function anexoCliente($id_atendimento)
  {
    $query = "SELECT atendimento_cliente.anexo FROM atendimento_cliente WHERE atendimento_cliente.id={$id_atendimento};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $existe = $resultado->fetch(PDO::FETCH_ASSOC);
    return $existe['anexo'];
  }
  function QuantidadeAtendimentoCliente($id_cliente)
  {
    $query = "SELECT COUNT(atendimento_cliente.id) as total FROM atendimento_cliente WHERE atendimento_cliente.id_cliente={$id_cliente} AND atendimento_cliente.situacao != 1 AND atendimento_cliente.situacao != 0;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $qtd = $resultado->fetch();
    return $qtd;
  }

  function atendimentoAvaliacao($id_cliente)
  {
    $query = "SELECT atendimento_cliente.id FROM atendimento_cliente WHERE atendimento_cliente.score = 0 AND atendimento_cliente.id_cliente = {$id_cliente};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $avaliacao = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $avaliacao;
  }
  function atendimentoAtualizaAvaliacao($score, $id, $sugestao = 0)
  {
    if ($sugestao != '0') {
      $query = "UPDATE atendimento_cliente SET atendimento_cliente.score = {$score}, atendimento_cliente.sugestao='{$sugestao}' WHERE atendimento_cliente.id={$id}";
    } else {
      $query = "UPDATE atendimento_cliente SET atendimento_cliente.score = {$score} WHERE atendimento_cliente.id={$id}";
    }
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
  }
  // function FiltrosClientePainelAtendimento(array $filtro, $id_cliente)
  // {
  //   $conexao = Conexao::criarConexao();
  //   if (isset($filtro['Ref']) && $filtro['Ref'] != '') {

  //     if (isset($filtro['Data']) && $filtro['Data'] != '') {
  //       $filtro['Data'] = date('Y-m-d', strtotime($filtro['Data']));
  //       if (isset($filtro['NPedido']) && $filtro['NPedido'] != '') {
  //         $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.id_faturamento={$filtro['NPedido']} AND faturamento_item.id_produto = (SELECT produtos.id FROM produtos WHERE produtos.descricao ='{$filtro['Ref']}') AND faturamento_item.data_hora={$filtro['Data']} AND faturamento_item.id_cliente={$id_cliente};";
  //       } else {

  //         $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.id_produto = (SELECT produtos.id FROM produtos WHERE produtos.descricao ='{$filtro['Ref']}') AND faturamento_item.data_hora={$filtro['Data']} AND faturamento_item.id_cliente={$id_cliente};";
  //       }
  //     } else {

  //       $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.id_produto =  (SELECT produtos.id FROM produtos WHERE produtos.descricao ='{$filtro['Ref']}') AND faturamento_item.id_cliente={$id_cliente};";
  //     }
  //   } else if (isset($filtro['Data']) && $filtro['Data'] != '') {
  //     $filtro['Data'] = date('Y-m-d', strtotime($filtro['Data']));

  //     if (isset($filtro['NPedido']) && $filtro['NPedido'] != '') {

  //       $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.data_hora={$filtro['Data']} AND faturamento_item.id_faturamento={$filtro['NPedido']} AND faturamento_item.id_cliente={$id_cliente};";
  //     } else {

  //       $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.data_hora={$filtro['Data']} AND faturamento_item.id_cliente={$id_cliente};";
  //     }
  //   } else if (isset($filtro['NPedido']) && $filtro['NPedido'] != '') {

  //     if (isset($filtro['Ref']) && $filtro['Ref'] != '') {

  //       $query = "SELECT *,faturamento_item.id_faturamento AS id, faturamento_item.data_hora as data_emissao FROM faturamento_item WHERE faturamento_item.id_faturamento={$filtro['NPedido']} AND faturamento_item.id_produto = (SELECT produtos.id FROM produtos WHERE produtos.descricao ='{$filtro['Ref']}') AND faturamento_item.id_cliente={$id_cliente}";
  //     } else {

  //       $query = "SELECT * FROM faturamento WHERE faturamento.id={$filtro['NPedido']} AND faturamento.id_cliente={$id_cliente};";
  //     }
  //   }
  //   $stmt =  $conexao->prepare($query);
  //   $stmt->execute();
  //   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //   return $result;
  // }
  public function AvaliacaobyMes($data)
  {
    if ($data != "") {
      $data = explode("-", $data);
      $sql = "SELECT id, (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = atendimento_cliente.id_cliente) as nome_cliente, atendimento_cliente.sugestao, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = atendimento_cliente.id_colaborador) as atendente,score,data_inicio,data_final FROM atendimento_cliente WHERE MONTH(data_inicio)={$data[1]} AND YEAR(data_inicio) = {$data[0]} AND atendimento_cliente.score > 0 AND atendimento_cliente.score < 4  ORDER BY atendimento_cliente.score ASC;";
    } else {
      $sql = "SELECT id, (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = atendimento_cliente.id_cliente) as nome_cliente, atendimento_cliente.sugestao, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = atendimento_cliente.id_colaborador) as atendente,score,data_inicio,data_final FROM atendimento_cliente WHERE MONTH(data_inicio)=MONTH(NOW()) AND YEAR(data_inicio) = YEAR(NOW()) AND atendimento_cliente.score > 0 AND atendimento_cliente.score < 4  ORDER BY atendimento_cliente.score ASC;";
    }
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($sql);
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }
  public function AvaliacaoTotalSomabyMes($data)
  {
    if ($data != "") {
      $data = explode("-", $data);
      $sql = "SELECT SUM(atendimento_cliente.score) as soma,COUNT(atendimento_cliente.score) as qtd FROM atendimento_cliente WHERE MONTH(data_inicio)={$data[1]} AND YEAR(data_inicio) = {$data[0]} AND atendimento_cliente.score > 0 ;";
    } else {

      $sql = "SELECT SUM(atendimento_cliente.score) as soma,COUNT(atendimento_cliente.score) as qtd FROM atendimento_cliente WHERE MONTH(data_inicio)=MONTH(NOW()) AND YEAR(data_inicio) = YEAR(NOW()) AND atendimento_cliente.score > 0;";
    }
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($sql);
    $lista = $resultado->fetch(PDO::FETCH_ASSOC);
    return $lista;
  }
  public static function buscaTiposAtendimento()
  {
    $sql = "SELECT * FROM tipo_atendimento WHERE id IN (1,2,3,8)";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->prepare($sql);
    $resultado->execute();
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }

  static function buscaTiposAtendimentoTelaUnica() {
    $sql = "SELECT * FROM tipo_atendimento WHERE id IN (1,2,3,5,6,7,8)";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->prepare($sql);
    $resultado->execute();
    $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    return $lista;
  }
}
*/