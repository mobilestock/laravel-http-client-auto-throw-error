<?php 

namespace MobileStock\service;

use Exception;
use MobileStock\service\RodonavesHttpClient;
use MobileStock\service\ConfiguracaoService;

class RodonavesService
{

    public function realizaAutenticacao(\PDO $conexao)
    {
        $rodonaves = new RodonavesHttpClient();
        $rodonaves->listaCodigosPermitidos = [200];
        $username = $_ENV['USUARIO_RODONAVES'];
        $password = $_ENV['SENHA_RODONAVES'];
        $resultado = $rodonaves->post('token', 
        [
                'auth_type' => 'DEV',
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password
        ],
        [
            'Content-Type: application/x-www-form-urlencoded',
        ]
        );

        $resposta = $resultado->body;
        $token = $resposta['access_token'];
        ConfiguracaoService::salvaTokenRodonaves($conexao, $token);
    }

    public static function insereNotaFiscal(\PDO $conexao,int $notaFiscal,string $cnpj,int $idEntrega,int $idTransportadora): void
    {
        $sql = $conexao->prepare(
            "INSERT INTO entregas_transportadoras
            (
                entregas_transportadoras.id_entrega,
                entregas_transportadoras.id_transportadora,
                entregas_transportadoras.cnpj,
                entregas_transportadoras.nota_fiscal
            ) 
            VALUES 
            (
                :id_entrega,
                :id_transportadora,
                :cnpj,
                :notaFiscal
            )   
        ");
        $sql->bindValue(':notaFiscal',$notaFiscal);
        $sql->bindValue(':cnpj', $cnpj);
        $sql->bindValue(':id_entrega', $idEntrega);
        $sql->bindValue(':id_transportadora', $idTransportadora);
        $sql->execute();
    } 
    public static function atualizaDadosRastreio(\PDO $conexao, int $notaFiscal,string $cnpj,int $idEntrega,int $idTransportadora): void 
    {
        $sql = $conexao->prepare(
        "UPDATE 
            entregas_transportadoras 
        SET entregas_transportadoras.id_transportadora = :id_transportadora,
            entregas_transportadoras.cnpj = :cnpj,
            entregas_transportadoras.nota_fiscal = :notaFiscal
        WHERE 
            entregas_transportadoras.id_entrega = :id_entrega");
        $sql->bindValue(':notaFiscal',$notaFiscal);
        $sql->bindValue(':cnpj', $cnpj);
        $sql->bindValue(':id_entrega', $idEntrega);
        $sql->bindValue(':id_transportadora', $idTransportadora);
        $sql->execute();
        if ($sql->rowCount() === 0) throw new Exception('Falha ao atualizar!');
    }
}