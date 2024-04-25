<?php
require_once '../regras/alertas.php';
require_once '../classes/usuarios.php';

if(isset($_POST['token']) && $_POST['token']!=''){
    if(isset($_POST['senha']) && $_POST['senha']!=''){
       if($usuario = existeToken($_POST['token'])){
         if(alteraSenhaUsuario($usuario['id'],$_POST['senha'])){
           $_SESSION['success'] = 'Senha atualizada com sucesso';
         }else{
          $_SESSION['danger'] = 'Não foi possível atualizar a senha';
         }
       }else{
        $_SESSION['danger'] = 'Não foi possível encontrar o usuário para atualizar a senha';
       }
    }else{
      $_SESSION['danger'] = 'Informe uma senha válida';
    }
}else{
  $_SESSION['danger'] = 'A atualização da senha expirou';
}

header('Location:../login.php');
die();