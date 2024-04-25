<?php

namespace MobileStock\helper;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use MobileStock\repository\UsuariosRepository;
use PDO;

class RegrasAutenticacao
{
    private $token;
    private $authorization;
    private $medAuthorization;
    private static $estruturaJWT = [
        'id_usuario',
        'nivel_acesso',
        'permissao',
        'id_colaborador',
        'regime',
        'uf',
        'nome',
        'criado_em'
    ];

    public function validaMedAuthorization(): array
    {
        if (!$this->medAuthorization) {
            throw new \InvalidArgumentException('Não autorizado',
                401);
        }

        $jwt = str_replace('Bearer ', '', $this->medAuthorization);

        $decode = (array) JWT::decode($jwt, Globals::JWT_KEY, ['HS256']);

        if (
            !array_key_exists('id_cliente', $decode) ||
            !array_key_exists('id_consumidor_final', $decode) ||
            !array_key_exists('qtd_compras_cliente', $decode)
        ) {
            throw new \InvalidArgumentException('Não autorizado');
        }

        $decode['id_usuario'] = 0;
        $decode['id_colaborador'] = $decode['id_cliente'];
        $decode['qtd_compras'] = $decode['qtd_compras_cliente'];
        $decode['nivel_acesso'] = 0;
        $decode['regime'] = 0;
        $decode['uf'] = '';
        $decode['nome'] = '';

        return $decode;
    }

    public function validaToken()
    {
            
        if($usuario = UsuariosRepository::buscaIDColaboradorComToken($this->token)){
            if($usuario['id_usuario'] > 0){
                return $usuario;
            } 
                return false;
        } else if($usuario = UsuariosRepository::buscaIDColaboradorComTokenTemporario($this->token)) {
            if($usuario['id_usuario'] > 0){
                return $usuario;
            }
        }
        return [];          
    }

    public function validaAuthorization()
    {
        $dadosToken = get_object_vars(JWT::decode($this->authorization, Globals::JWT_KEY, ['HS256']));
        $arrayDadosToken = array_keys($dadosToken);
        
        self::validaEstruturaJwt($arrayDadosToken);
        
        return $dadosToken;
    }

    public static function geraAuthorization(
        int $idUsuario,
        int $idColaborador,
        int $nivelAcesso,
        string $permissao,
        string $nome,
        ?string $uf,
        string $regime
    ): string {
        $dados = array(
            "id_usuario" => $idUsuario,
            "id_colaborador" => $idColaborador,
            "nivel_acesso" => $nivelAcesso,
            "permissao" => $permissao,
            "nome" => $nome,
            "uf" => $uf,
            "regime" => $regime,
            "criado_em" => date('Y-m-d H:i:s'),
        );

        self::validaEstruturaJwt(array_keys($dados));

        return JWT::encode($dados, Globals::JWT_KEY,'HS256');        
    }

    public static function geraTokenPadrao(\PDO $conexao, int $idUsuario): string
    {
        $tokenUsuario =  UsuariosRepository::retornaToken($conexao,$idUsuario);
        if($tokenUsuario['token'])
            return $tokenUsuario['token'];
        $token = md5($idUsuario.uniqid());
        self::armazenaTokenUsuario($idUsuario,$token,$conexao);
        return $token;
    }

    public static function geraTokenTemporario(\PDO $conexao, int $idUsuario): string
    {
        $tokenTemporario =  UsuariosRepository::retornaTokenTemporario($conexao,$idUsuario);
        if(isset($tokenTemporario['token_temporario'])){
            return $tokenTemporario['token_temporario'];
        }
        $token_temporario = md5($idUsuario.uniqid());
        self::armazenaTokenTemporarioUsuario($idUsuario,$token_temporario,$conexao);
        return $token_temporario;
    }

    private static function validaEstruturaJwt(array $arrayDadosToken): void
    {
        foreach (self::$estruturaJWT as $itemEstrutura) {
            if(in_array($itemEstrutura, $arrayDadosToken)) continue;

            throw new InvalidArgumentException('Estrutura inválida para JWT, campo "' . $itemEstrutura . '" não existe');
        }
    }  
    
    public static function armazenaTokenUsuario(string $id, ?string $token, PDO $conexao): bool
    {
        $query = "UPDATE usuarios SET usuarios.token=:token WHERE usuarios.id=:id";
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }

    private static function armazenaTokenTemporarioUsuario(string $id, string $token, PDO $conexao): bool
    {
        $query = "UPDATE usuarios SET usuarios.token_temporario=:token_temporario, usuarios.data_token_temporario=NOW() WHERE usuarios.id=:id;";
        $stmt = $conexao->prepare($query);
        $stmt->bindParam(':token_temporario', $token, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }

    /**
     * Set the value of authorization
     *
     * @return  self
     */ 
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;

        return $this;
    }
   

    /**
     * Set the value of Token
     *
     * @return  self
     */ 
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function setMedAuthorization(string $token)
    {
        $this->medAuthorization = $token;
    }

}