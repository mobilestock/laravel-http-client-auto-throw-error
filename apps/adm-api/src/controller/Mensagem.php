<?php 
namespace MobileStock\controller;
use PDO;

class Mensagem{
    public $mensagem;
    public $id_remetente;
    public $data;
 
    public function setMensagem($mensagem){
        $this->mensagem = $mensagem;
    }
    public function getNome(){
        return $this->mensagem;
    }
    public function setRemetente($id){
        $this->id_remetente = $id;
    }
    public function getRemetente(){
        return $this->id_remetente;
    }
    public function setData($data){
        $this->data = $data;
    }
    public function getData(){
        return $this->data;
    }
}


?>