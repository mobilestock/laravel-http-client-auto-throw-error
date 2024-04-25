<?php /*
require_once 'conexao.php';

// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaListaVendedores($anoAtual,$mesAtual){
//    $query="SELECT u.id,u.nome,
//    (SELECT COUNT(fi.id_faturamento)pares FROM faturamento_item fi INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE MONTH(f.data_fechamento)='{$mesAtual}' AND YEAR(f.data_fechamento)='{$anoAtual}' AND
//    fi.id_vendedor=u.id AND f.situacao>=2)pares
//    FROM usuarios u WHERE u.nivel_acesso>=50 ORDER BY pares DESC,u.nome";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaListaSeparadores($anoAtual,$mesAtual){
//    $query="SELECT u.id,u.nome, (SELECT COUNT(fi.id_faturamento)pares FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE MONTH(f.data_fechamento)='{$mesAtual}' AND YEAR(f.data_fechamento)='{$anoAtual}' AND fi.id_separador=u.id AND f.situacao>=2)pares FROM usuarios u WHERE u.nivel_acesso>=51 ORDER BY pares DESC,u.nome";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaListaConferidores($anoAtual,$mesAtual){
//    $query="SELECT u.id,u.nome, (SELECT COUNT(fi.id_faturamento)pares FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE MONTH(f.data_fechamento)='{$mesAtual}' AND YEAR(f.data_fechamento)='{$anoAtual}' AND fi.id_conferidor=u.id AND f.situacao>=2)pares FROM usuarios u WHERE u.nivel_acesso>=51 ORDER BY pares DESC,u.nome";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaNomeVendedor($id){
//    $query="SELECT nome FROM usuarios WHERE id = {$id};";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['nome'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaMesComissoes($id){
//    $query="SELECT MONTH(fi.data_f)mes,YEAR(f.data_fechamento)ano FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE fi.id_vendedor={$id} AND f.situacao >= 2
//    GROUP BY YEAR(f.data_fechamento),MONTH(f.data_fechamento) ORDER BY f.data_fechamento DESC;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaComissoes($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares, c.razao_social cliente, fi.id_faturamento, f.data_fechamento FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    INNER JOIN colaboradores c ON (c.id=fi.id_cliente)
//    WHERE fi.id_vendedor={$id}  AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'
//    GROUP BY fi.id_faturamento ORDER BY f.data_fechamento;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaComissoesSeparador($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares, c.razao_social cliente, fi.id_faturamento, f.data_fechamento FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    INNER JOIN colaboradores c ON (c.id=fi.id_cliente)
//    WHERE f.id_separador={$id}  AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'
//    GROUP BY fi.id_faturamento ORDER BY f.data_fechamento;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaComissoesConferente($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares, c.razao_social cliente, fi.id_faturamento, f.data_fechamento FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    INNER JOIN colaboradores c ON (c.id=fi.id_cliente)
//    WHERE fi.id_conferidor={$id}  AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'
//    GROUP BY fi.id_faturamento ORDER BY f.data_fechamento;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaTotalComissoes($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE fi.id_vendedor={$id} AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaTotalComissoesSeparador($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE fi.id_separador={$id} AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaTotalComissoesConferente($id,$ano,$mes){
//    $query="SELECT count(fi.id_faturamento)pares FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE fi.id_conferidor={$id} AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaTotalDeFaturamentos($id,$ano,$mes){
//    $query="SELECT COUNT(DISTINCT(fi.id_faturamento)) quant FROM faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE fi.id_vendedor={$id} AND f.situacao >= 2 AND YEAR(f.data_fechamento) = '{$ano}' AND MONTH(f.data_fechamento) = '{$mes}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['quant'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaNomeMes($mes){
//    if($mes==1){
//        return 'Janeiro';
//    }else if($mes==2){
//        return 'Fevereiro';
//    }else if($mes==3){
//        return 'Março';
//    }else if($mes==4){
//        return 'Abril';
//    }else if($mes==5){
//        return 'Maio';
//    }else if($mes==6){
//        return 'Junho';
//    }else if($mes==7){
//        return 'Julho';
//    }else if($mes==8){
//        return 'Agosto';
//    }else if($mes==9){
//        return 'Setembro';
//    }else if($mes==10){
//        return 'Outubro';
//    }else if($mes==11){
//        return 'Novembro';
//    }else if($mes==12){
//        return 'Dezembro';
//    }
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function geraComissoes($mes,$ano){
//
//    $query = "DELETE FROM comissao WHERE mes={$mes} AND ano={$ano};";
//    $conexao = Conexao::criarConexao();
//    $conexao->exec($query);
//
//    $sql = "";
//
//    // $query = "SELECT COUNT(fi.id_produto)pares, fi.id_vendedor from faturamento_item fi
//    // INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    // WHERE MONTH(f.data_fechamento)={$mes}
//    // AND YEAR(f.data_fechamento)={$ano} GROUP BY fi.id_vendedor;";
//    // $resultado = $conexao->query($query);
//    // $linhas = $resultado->fetchAll();
//    // foreach ($linhas as $key => $l) {
//    //     $sql .= "INSERT INTO comissao (usuario,pares,mes,ano,tipo) VALUES ({$l['id_vendedor']},{$l['pares']},$mes,$ano,'V');";
//    // }
//
//    $query = "SELECT COUNT(fi.id_produto)pares, f.id_separador from faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE MONTH(f.data_fechamento)={$mes} AND f.id_separador>0
//    AND YEAR(f.data_fechamento)={$ano} AND fi.situacao=6 GROUP BY f.id_separador;";
//    $resultado = $conexao->query($query);
//    $linhas = $resultado->fetchAll();
//    foreach ($linhas as $key => $l) {
//        $sql .= "INSERT INTO comissao (usuario,pares,mes,ano,tipo) VALUES ({$l['id_separador']},{$l['pares']},$mes,$ano,'S');";
//    }
//
//    $query = "SELECT COUNT(fi.id_produto)pares, fi.id_conferidor from faturamento_item fi
//    INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//    WHERE MONTH(f.data_fechamento)={$mes} AND fi.id_conferidor>0
//    AND YEAR(f.data_fechamento)={$ano} GROUP BY fi.id_conferidor;";
//    $resultado = $conexao->query($query);
//    $linhas = $resultado->fetchAll();
//    foreach ($linhas as $key => $l) {
//        $sql .= "INSERT INTO comissao (usuario,pares,mes,ano,tipo) VALUES ({$l['id_conferidor']},{$l['pares']},$mes,$ano,'C');";
//    }
//    if($sql!=''){
//    $conexao->exec($sql);
//    }
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)


// --Commented out by Inspection START (12/08/2022 15:23):
//function buscaComissoesLista($mes,$ano,$tipo){
//    $query = "SELECT c.*, u.nome nome_usuario FROM comissao c
//    INNER JOIN usuarios U ON (u.id=c.usuario)
//    WHERE c.mes={$mes} AND c.ano={$ano} AND c.tipo='{$tipo}' ORDER BY c.pares DESC;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:23)
*/