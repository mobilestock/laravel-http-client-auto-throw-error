<?php

use MobileStock\repository\ProdutosRepository;

require_once 'cabecalho.php';
require_once 'classes/produtos.php';
require_once 'classes/cadastros.php';
require_once 'classes/colaboradores.php';
require_once 'classes/estoque.php';

acessoUsuarioVendedor();

$categorias = listaTabela("categorias");
$fornecedores = listaFornecedores();
$conexao = Conexao::criarConexao();

$filtro = "";

$produtos = null;

//filtra codigo
if(isset($_POST['id']) && $_POST['id']!=""){
  $idProduto = (int) $_POST['id'];
  $filtro .= " AND produtos.id REGEXP $idProduto";
}

//filtra descrição
if(isset($_POST['descricao']) && $_POST['descricao']!=""){
  $descricao = (string) $_POST['descricao'];
  $filtro .= " AND produtos.descricao REGEXP '$descricao'";
}

//filtra descrição
if(isset($_POST['cod_barras']) && $_POST['cod_barras']!=""){
  $codigoBarras = (string) $_POST['cod_barras'];
  $filtro .= " AND EXISTS(
    SELECT 1
    FROM produtos_grade
    WHERE produtos_grade.id_produto = produtos.id
      AND produtos_grade.cod_barras = $codigoBarras
  )";
}

//filtra categoria
if(isset($_POST['id_categoria']) && $_POST['id_categoria']!=0){
  $idCategoria = (string) $_POST['id_categoria'];
  $filtro .= " AND EXISTS(
    SELECT 1
    FROM produtos_categorias
    WHERE produtos_categorias.id_produto = produtos.id
      AND produtos_categorias.id_categoria = $idCategoria
  )";
}

//filtra fornecedor
if(isset($_POST['id_fornecedor']) && $_POST['id_fornecedor']!=0){
  $idFornecedor = (int) $_POST['id_fornecedor'];
  $filtro .= " AND produtos.id_fornecedor = $idFornecedor";
}

//filtra tabela
if(isset($_POST['id_tabela']) && $_POST['id_tabela']!=0){
  $idTabela = (int) $_POST['id_tabela'];
  $filtro .= " AND produtos.id_tabela = $idTabela";
}

//filtra grade
if(isset($_POST['id_grade']) && $_POST['id_grade']!=0){
  $grade = (string) $_POST['id_grade'];
  $filtro .= " AND produtos.grade = $grade";
}

if($filtro==""){
  $produtos = buscaProdutosComCorrecaoDeEstoque();
}else{
  $produtos = ProdutosRepository::filtraProdutosEstoque($conexao, $filtro);
}
?>
<div class="container-fluid">
<h2><b>Correção de estoque manual</b></h2>
  <form method="post">
  <div class="pesquisa"><h4>Buscar:</h4>
    <div class="row">
      <div class="col-sm-1"><label>Código:</label><input class="form-control" type="text" name="id"/></div>
      <div class="col-sm-3"><label>Descrição:</label><input class="form-control" type="text" name="descricao"/></div>
      <div class="col-sm-2"><label>Categoria:</label>
        <select name="id_categoria"
          class="form-control">
                  <option value="0">-- Categoria</option>
              <?php foreach ($categorias as $categoria):?>
              <option value="<?=$categoria['id']?>">
                <?= $categoria['nome']?></option>
              <?php endforeach;?>
            </select>
      </div>
      <div class="col-sm-2"><label>Fornecedor:</label><select name="id_fornecedor"
          class="form-control">
                  <option value="0">-- Fornecedor</option>
              <?php foreach ($fornecedores as $fornecedor):?>
              <option value="<?=$fornecedor['id']?>"><?= $fornecedor['razao_social']?></option>
              <?php endforeach;?>
            </select>
      </div>
    </div><br/>
    <div class="row">
      <div class="col-sm-2">
        <a href="movimentacao-estoque-lista.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a>
      </div>
      <div class="col-sm-8"></div>
      <div class="col-sm-2"><button class="btn btn-success btn-block" type="submit"><b>FILTRAR</b></button></div>
    </div>
  </div>
</form>
<br/>
<div class="row cabecalho">
        <div class="col-sm-1">Código</div>
        <div class="col-sm-3">Descrição</div>
        <div class="col-sm-3">Localização</div>
        <div class="col-sm-2">Usuário</div>
        <div class="col-sm-2">Data</div>
        <div class="col-sm-1">Corrigir</div>
    </div>
      <?php
      if($produtos!=null){
      foreach ($produtos as $indice=>$produto):
        if($indice%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
        ?>
        <div class="row corpo <?=$estilo;?>">
          <div class="col-sm-1"><?=$produto['id'];?></div>
          <div class="col-sm-3"><?=$produto['descricao'];?></div>
          <div class="col-sm-3"><?=$produto['localizacao'];?></div>
          <div class="col-sm-2"><?php if(isset($produto['usuario'])){ echo $produto['usuario'];};?></div>
          <div class="col-sm-2"><?php if(isset($produto['data_emissao'])){ echo $produto['data_emissao'];};?></div>
          <div class="col-sm-1">
            <a href="produto-corrigir-estoque-detalhes.php?id=<?=$produto['id'];?>" class="btn btn-primary"><i class="fa fa-pen"></i></a>
          </div>
        </div>
      <?php endforeach; }?>
      <br>
      <div class="row">
        <div class="col-sm-2">
          <a href="movimentacao-estoque-lista.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a>
        </div>
      </div>
      </div>
<?php require_once 'rodape.php'; ?>