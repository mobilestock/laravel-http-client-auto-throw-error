<?php

namespace MobileStock\helper;
/**
 * @deprecated
 * @issue https://github.com/mobilestock/backend/issues/100
 */
class GeradorSql
{
    private $model;
    public $bind;

    /**
     * @param $model \MobileStock\model\ModelInterface
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->dados = $this->model->extrair();
        unset($this->dados['nome_tabela']);
    }

    public function insert(): string
    {
        $dados = array_filter($this->dados);
        $sql = 'INSERT INTO ' . $this->model->nome_tabela . ' (' . implode(',', array_keys($dados)) . ') VALUES (';

        foreach ($dados as $key => $value) {
            if ($value) {
                $sql .= " :{$key},";
            }
        }
        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1) . ')';
        $this->bind = $dados;
        return $sql;
    }

    public function insertSemFilter(): string
    {
        $dados = $this->dados;
        unset($dados['id']);
        $sql = 'INSERT INTO ' . $this->model->nome_tabela . ' (' . implode(',', array_keys($dados)) . ') VALUES (';

        foreach ($dados as $key => $value) {
            if ($key != 'id') {
                $sql .= " :{$key},";
            }
        }
        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1) . ')';
        $this->bind = $dados;
        return $sql;
    }

    public function insertFromDual(array $camposUnicos)
    {
        $dados = $this->dados;
        $sql = 'INSERT INTO ' . $this->model->nome_tabela . ' (' . implode(',', array_keys($dados)) . ') SELECT';

        foreach ($dados as $key => $value) {
            $sql .= " '$value',";
        }

        $fromDual = '';
        foreach ($camposUnicos as $campoUnico) {
            $metodoCampoUnico = 'get';
            $array = explode('_', $campoUnico);
            for ($i = 0; $i < sizeof($array); $i++) {
                $metodoCampoUnico .= ucfirst($array[$i]);
            }
            $fromDual .= " AND $campoUnico = '{$this->model->$metodoCampoUnico()}'";
        }
        $sql =
            mb_substr($sql, 0, mb_strlen($sql) - 1) .
            ' FROM DUAL WHERE NOT EXISTS
                         (SELECT 1 FROM ' .
            $this->model->nome_tabela .
            " WHERE 1 = 1 $fromDual);";
        $this->bind = $dados;
        return $sql;
    }

    public function update(): string
    {
        $dados = $this->dados;
        $sql = 'UPDATE ' . $this->model->nome_tabela . ' SET id = id';

        foreach ($dados as $key => $value) {
            $sql .= ", $key = :$key";
        }

        $sql .= ' WHERE id = :id;';
        $this->bind = $dados;
        return $sql;
    }

    // public function updateDinamico(string $nomeCampoBase): string
    // {
    // 	$dados = array_filter($this->dados);
    // 	$sql = 'UPDATE ' . $this->model->nome_tabela . ' SET '. $nomeCampoBase . ' = ' . $nomeCampoBase;

    // 	foreach ($dados as $key => $value) {
    // 		if ($value) $sql .= ", $key = :$key";
    // 	}

    // 	$sql .= ' WHERE ' . $nomeCampoBase . ' = :'. $nomeCampoBase . ';';
    // 	$this->bind = $dados;
    // 	return $sql;
    // }

    public function updateSomenteDadosPreenchidos()
    {
        $dados = array_filter($this->dados);
        $sql = 'UPDATE ' . $this->model->nome_tabela . ' SET id = id';

        foreach ($dados as $key => $value) {
            $sql .= ", $key = :$key";
        }

        $sql .= ' WHERE id = :id;';
        $this->bind = $dados;
        return $sql;
    }

    /**
     * @deprecated
     * Utilizar o deleteSemGetter()
     */
    public function delete(): string
    {
        $nomeTabela = $this->model->nome_tabela;
        $sql = 'DELETE FROM ' . $nomeTabela . ' WHERE id = :id';

        $this->bind = ['id' => $this->model->getId()];

        return $sql;
    }

    public function deleteSemGetter(): string
    {
        $nomeTabela = $this->model->nome_tabela;
        $sql = 'DELETE FROM ' . $nomeTabela . ' WHERE id = :id';
        $this->bind = ['id' => $this->model->id];
        return $sql;
    }
    public function updatePorCampo(array $listaCampos)
    {
        $dados = $this->dados;
        $sql = 'UPDATE ' . $this->model->nome_tabela . ' SET';

        foreach ($dados as $key => $value) {
            $virgula = $key === array_key_first($dados) ? '' : ',';
            $sql .= "$virgula $key = :$key";
        }

        $sql .=
            ' WHERE ' .
            implode(
                ' AND ',
                array_map(function (string $nomeCampo) {
                    return "$nomeCampo = :$nomeCampo";
                }, $listaCampos)
            );
        $this->bind = $dados;
        return $sql;
    }
}
