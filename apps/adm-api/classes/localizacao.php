<?php
    function buscaLocalizacoes(){
        $query = "SELECT * FROM localizacao_estoque WHERE local!='local';";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        return $resultado->fetchAll();
    }

    // function buscaProdutosPorLocalizacao($tipo,$local){
    //     if($tipo=="O"){
    //         $query = "SELECT * FROM produtos WHERE localizacao_online='{$local}';"; 
    //     }else if($tipo=="P"){
    //         $query = "SELECT * FROM produtos WHERE localizacao_presencial='{$local}';";
    //     }
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll();
    // }

    // function buscaGradeProdutoPorLocalizacao($id_produto,$tipo){
    //     if($tipo=="O"){
    //         $query = "SELECT tamanho, interno estoque FROM estoque_grade WHERE id_produto={$id_produto};";
    //     }else if($tipo=="P"){
    //         $query = "SELECT tamanho, presencial estoque FROM estoque_grade WHERE id_produto={$id_produto};";
    //     }
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(); 
    // }

    // function buscaTotalEstoqueProdutoPorLocalizacao($id_produto,$tipo){
    //     if($tipo=="O"){
    //         $query = "SELECT SUM(interno) estoque FROM estoque_grade WHERE id_produto={$id_produto};";
    //     }else if($tipo=="P"){
    //         $query = "SELECT SUM(presencial) estoque FROM estoque_grade WHERE id_produto={$id_produto};";
    //     }
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $linha = $resultado->fetch(); 
    //     return $linha['estoque'];
    // }

    function buscaProdutoPorLocalizacaoId($id_produto){
        $query = "SELECT * FROM ordem_localizacao WHERE id_produto={$id_produto};";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch(PDO::FETCH_ASSOC); 
        return $linha;
    }

    function buscaResultadoAnaliseEstoque(int $id_usuario){
        $query="SELECT * FROM analise_estoque_header WHERE id_usuario={$id_usuario};";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch(); 
        return $linha;
    }

    // function buscaResultadoAnaliseEstoqueItens(int $id_usuario){

    //     $query="SELECT ae.*, ae.id_produto, CONCAT(p.descricao, ' ', COALESCE(p.cores, '')) referencia, p.localizacao, p.tipo_grade,
    //     estoque_grade.nome_tamanho
    //     FROM analise_estoque ae
    //     INNER JOIN estoque_grade ON estoque_grade.id_produto= ae.id_produto AND ae.tamanho = estoque_grade.tamanho
    //     INNER JOIN produtos p ON (p.id=ae.id_produto)
    //     WHERE estoque_grade.id_responsavel = 1 AND ae.id_usuario={$id_usuario}
    //     GROUP BY p.id, ae.sequencia;";

        
    //     /*$query="SELECT ae.*, ae.id_produto, p.descricao referencia, p.localizacao FROM analise_estoque ae
    //     LEFT OUTER JOIN produtos p ON (p.id=ae.id_produto)
    //     WHERE ae.id_usuario={$id_usuario};";*/

    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $linhas = $resultado->fetchAll();
    //     return $linhas;
    // }

    // function buscaMostruariosDaLocalizacao($local){
    //     $query="SELECT descricao, mostruario FROM produtos WHERE localizacao='{$local}' AND mostruario>0;";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $linhas = $resultado->fetchAll(); 
    //     return $linhas;
    // }

// function buscaProdutosLocalizacao(string $local){
//     $query="SELECT id FROM produtos WHERE localizacao='{$local}';";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll(); 
//     return $linhas;
// }