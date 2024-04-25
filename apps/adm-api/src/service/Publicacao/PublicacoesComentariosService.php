<?php /*

namespace MobileStock\service\Publicacao;

use Error;
use Exception;
use MobileStock\model\Publicacao\PublicacaoComentario;
use PDO;

class PublicacoesComentariosService extends PublicacaoComentario
{
    public function adiciona(PDO $conexao): PublicacaoComentario
    {
        $sql = '';
        $camposTabela = [];
        $dadosTabela = [];
        $dados = [];
        foreach ($this as $key => $value) {
            if (!$value || in_array($key,['id'])) continue;
            array_push($camposTabela,$key);
            array_push($dadosTabela ,":{$key}");
            $dados[$key] = $value;
        }
        $sql = "INSERT INTO publicacoes_comentarios (".implode(',',$camposTabela).") VALUES (".implode(',',$dadosTabela).")";
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);
        $this->id = $conexao->lastInsertId();
        return $this;
    }


    public function atualiza(pdo $conexao)
    {
        $dados = [];
        $sql = "UPDATE publicacoes_comentarios SET ";

        foreach ($this as $key => $valor) {
            if ((!$valor && !is_null($valor)) || in_array($key,['id', 'data_criacao', 'data_atualizacao'])) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if(is_null($valor)){
                $valor = "NULL";
            }
            array_push($dados, $key . " = " . $valor);
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= " " . implode(',', $dados) . " WHERE publicacoes_comentarios.id = '" . $this->id. "'";

        return $conexao->exec($sql);
    }


    public function busca(PDO $conexao)
    {
        if(!$this->id) throw new Exception("Erro ao buscar comentário", 1);

        $sql = "SELECT publicacoes_comentarios.id,
                    publicacoes_comentarios.id_colaborador,
                    publicacoes_comentarios.id_publicacao,
                    publicacoes_comentarios.comentario,
                    publicacoes_comentarios.data_criacao,
                    publicacoes_comentarios.data_atualizacao
                FROM publicacoes_comentarios
                INNER JOIN publicacoes ON publicacoes.id = publicacoes_comentarios.id_publicacao
                WHERE publicacoes_comentarios.id = {$this->id}";

        $dados = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);
        if($dados && count($dados) !== 0):
            $this->id = $dados['id'];
            $this->id_colaborador = $dados['id_colaborador'];
            $this->id_publicacao = $dados['id_publicacao'];
            $this->comentario = $dados['comentario'];
            $this->data_criacao = $dados['data_criacao'];
            $this->data_atualizacao = $dados['data_atualizacao'];
        endif;
    }


    public function remove(PDO $conn)
    {
        $sql = "DELETE FROM publicacoes_comentarios WHERE publicacoes_comentarios.id = {$this->id}";
        return $conn->exec($sql);
    }
} */
