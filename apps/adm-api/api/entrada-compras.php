<?php
require_once '../classes/conexao.php';
require_once '../classes/lancamento.php';
require_once '../classes/movimentacao.php';
header('Content-Type: application/json');

$uri = $_GET['url'];

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    if($uri!=""){
        $query = "SELECT entrada_compra_temp FROM configuracoes;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch();
        if($linha['entrada_compra_temp']==0){
            $sql = "SELECT cic.id_compra compra,
            cic.id_sequencia sequencia,
            cic.volume volume,
            colaboradores.id id_fornecedor,
            colaboradores.razao_social fornecedor,
            produtos.id id_produto,
            produtos.descricao produto,
            cic.situacao id_situacao,
            cic.codigo_barras codigo_barras,
            compras_itens.preco_unit,
            CONCAT(c.id,' - ',cic.id_sequencia,' - ',cic.volume) cod_compra,
            cic.quantidade pares,
            compras_itens.valor_total
            FROM compras_itens_caixas cic
            INNER JOIN compras c ON (c.id=cic.id_compra)
            INNER JOIN colaboradores ON (colaboradores.id = cic.id_fornecedor)
            INNER JOIN produtos ON (produtos.id = cic.id_produto)
            INNER JOIN compras_itens ON (compras_itens.id_compra = cic.id_compra
            AND compras_itens.sequencia = cic.id_sequencia)
            WHERE cic.codigo_barras = '{$uri}';";
            $conexao = Conexao::criarConexao();
            $resultado = $conexao->query($sql);
            if($linha =  $resultado->fetch()){
                echo json_encode($linha);
                http_response_code(200);
            }else{
                echo 'Código inválido';
                http_response_code(405);
            }
        }else{
            echo 'Existem compras na fila de espera de entrada.';
            http_response_code(405);
        }   
    }
}else if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($uri=='entrada'){
        //recebe as caixas baixadas
        $json = file_get_contents('php://input');
        $volumes = json_decode($json,true);

        $conexao = Conexao::criarConexao();
        $query = "UPDATE configuracoes SET entrada_compra_temp=1 WHERE entrada_compra_temp=0;";
        $conexao->exec($query);

        $compraTemp = 0;
        $usuario = 0;
        $valorTotal = 0;
        $fornecedor = 0;
        $pares = 0;
        $id_lancamento = buscaUltimoLancamento();
        $id_lancamento++;
        $idMov = buscaUltimaMovimentacao();
        $idMov++;
        $inseriuMov = true;
        //atualiza pares reservados ou da entrada no estoque
        $seq=0;
        $sql = "";
        foreach ($volumes as $key => $volume) {
                $sql .= "INSERT INTO compras_entrada_temp
                (codigo_barras,
                id_fornecedor,
                id_produto,
                id_situacao,
                pares,
                preco_unit,
                compra,
                sequencia,
                volume,
                usuario,
                valor_total)
                VALUES
                (
                '{$volume['codigo_barras']}',
                {$volume['id_fornecedor']},
                {$volume['id_produto']},
                {$volume['id_situacao']},
                {$volume['pares']},
                {$volume['preco_unit']},
                {$volume['compra']},
                {$volume['sequencia']},
                {$volume['volume']},
                {$volume['usuario']},
                {$volume['pares']}*{$volume['preco_unit']}
                );";
                if($key%29==0){
                    $conexao->exec($sql);
                    $sql = "";
                }
        }
        if($sql!=""){
            $conexao->exec($sql);
        }
        http_response_code(200);
    }else{
        http_response_code(405);
    }
}