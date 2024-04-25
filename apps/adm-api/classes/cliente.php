<?php
/*
require_once 'conexao.php';

    function insereSolicitacaoCliente($id,$regime,$cnpj,$cpf,$razao_social,$email,$telefone){
        $tipo = 'C';
        $vendedor = 0;
        $autoCadastro = 1;
        $tipo_tabela = 1;
        date_default_timezone_set('America/Sao_Paulo');
        $data = DATE('Y-m-d H:i:s');
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('INSERT INTO COLABORADORES 
        (id,
        regime,
        cnpj, 
        cpf, 
        razao_social, 
        telefone,
        email,
        tipo,
        vendedor,
        tipo_tabela,
        auto_cadastro,
        data_cadastro) 
        VALUES
        (:id,
        :regime,
        :cnpj, 
        :cpf, 
        :razao_social, 
        :telefone,
        :email, 
        :tipo, 
        :vendedor, 
        :tipo_tabela,
        :auto_cadastro,
        :data_cadastro)');
        $query->bindParam('id',$id,PDO::PARAM_INT);
        $query->bindParam('regime',$regime,PDO::PARAM_INT);
        $query->bindParam('cnpj',$cnpj,PDO::PARAM_STR,14);
        $query->bindParam('cpf',$cpf,PDO::PARAM_STR,11);
        $query->bindParam('razao_social',$razao_social,PDO::PARAM_STR,500);
        $query->bindParam('telefone',$telefone,PDO::PARAM_STR,12);
        $query->bindParam('email',$email,PDO::PARAM_STR,500);
        $query->bindParam('tipo',$tipo,PDO::PARAM_STR,1);
        $query->bindParam('vendedor',$vendedor,PDO::PARAM_INT);
        $query->bindParam('tipo_tabela',$tipo_tabela,PDO::PARAM_INT);
        $query->bindParam('auto_cadastro',$autoCadastro,PDO::PARAM_INT);
        $query->bindParam('data_cadastro',$data,PDO::PARAM_STR);
        return $query->execute() or die(print_r($query->errorInfo(),true));
    }
*/
?>