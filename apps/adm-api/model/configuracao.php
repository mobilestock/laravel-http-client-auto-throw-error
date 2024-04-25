<?php

class Configuracao
{
    public function listaCategorias()
    {
        $query = "SELECT * FROM categorias ORDER BY id;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        return $resultado->fetchAll();
    }

    public function cadastrarCategoria($nomeCategoria, $alturaSalto)
    {
        $conexao = Conexao::criarConexao();
        $query = $conexao->prepare('INSERT INTO categorias (nome,mostrar_altura_salto) VALUES (:nome,:altura)');
        $query->bindParam('nome', $nomeCategoria, PDO::PARAM_STR);
        $query->bindParam('altura', $alturaSalto, PDO::PARAM_INT);
        return $query->execute() or die(print_r($query->errorInfo(), true));
    }

    public function buscaConfiguracoes(){
        $conexao = Conexao::criarConexao();
        $query = "SELECT * FROM configuracoes;";
        return $conexao->query($query)->fetch(PDO::FETCH_ASSOC);
    }
}
