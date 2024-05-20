<?php

namespace MobileStock\service\Estoque;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\model\EstoqueGrade;
use MobileStock\model\Origem;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EstoqueGradeService extends EstoqueGrade
{
    /**
     * um int         ex: -1, 5, 10
     * uma string SQL ex: - estoque_grade.estoque
     * @var int|string
     */
    public $alteracao_estoque = 0;
    public int $pares = 0;
    public string $tamanho_foto = '';

    public function movimentaEstoque(PDO $conexao, int $idUsuario): void
    {
        if ($this->alteracao_estoque === 0) {
            throw new \InvalidArgumentException('Não movimentou estoque');
        }

        $dadosValidar = [
            'tipo_movimentacao' => $this->tipo_movimentacao,
            'descricao' => $this->descricao,
            'id_produto' => $this->id_produto,
            'nome_tamanho' => $this->nome_tamanho,
            'id_responsavel' => $this->id_responsavel,
        ];

        Validador::validar($dadosValidar, [
            'tipo_movimentacao' => [Validador::OBRIGATORIO, Validador::STRING],
            'descricao' => [Validador::OBRIGATORIO],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'id_responsavel' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        if (is_numeric($this->alteracao_estoque)) {
            $movimentacaoSql = (string) ($this->alteracao_estoque > 0 ? '+' : '-') . abs($this->alteracao_estoque);
        } else {
            $movimentacaoSql = $this->alteracao_estoque;
        }

        $update = "UPDATE estoque_grade
                    SET estoque_grade.estoque = estoque_grade.estoque $movimentacaoSql,
                        estoque_grade.tipo_movimentacao = '$this->tipo_movimentacao',
                        estoque_grade.descricao = '$this->descricao'
                WHERE estoque_grade.id_produto = $this->id_produto
                    AND estoque_grade.nome_tamanho = '$this->nome_tamanho'
                    AND estoque_grade.id_responsavel = $this->id_responsavel";

        $sql = $conexao->prepare(
            'CALL insere_grade_responsavel(:id_produto, :nome_tamanho, :id_responsavel, :id_usuario, :update)'
        );
        $sql->bindValue(':id_produto', $this->id_produto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $this->nome_tamanho, PDO::PARAM_STR);
        $sql->bindValue(':id_responsavel', $this->id_responsavel, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':update', $update, PDO::PARAM_STR);
        $sql->execute();
    }
    public function buscaEstoqueEspecifico(PDO $conexao): int
    {
        if (!isset($this->id_produto, $this->nome_tamanho, $this->id_responsavel)) {
            throw new NotFoundHttpException('Não foi informado id_produto, nome_tamanho ou id_responsavel');
        }

        $sql = $conexao->prepare(
            "SELECT estoque_grade.estoque
            FROM estoque_grade
            WHERE estoque_grade.id_produto = :id_produto
                AND estoque_grade.id_responsavel = :id_responsavel_estoque
                AND estoque_grade.nome_tamanho = :nome_tamanho;"
        );
        $sql->bindValue(':id_produto', $this->id_produto, PDO::PARAM_INT);
        $sql->bindValue(':id_responsavel_estoque', $this->id_responsavel, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $this->nome_tamanho, PDO::PARAM_STR);
        $sql->execute();
        $estoque = $sql->fetchColumn();
        if ($estoque === false) {
            throw new NotFoundHttpException('Estoque não encontrado');
        }

        return $estoque;
    }
    public function zerarEstoqueDisponivelDoResponsavelSemVerificacao(int $idResponsavelEstoque, int $idUsuario): void
    {
        $descricao = "Usuário $idUsuario zerou o estoque do responsável $idResponsavelEstoque";
        DB::update(
            "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = :descricao
            WHERE estoque_grade.estoque > 0
                AND estoque_grade.id_responsavel = :id_responsavel;",
            [
                'id_responsavel' => $idResponsavelEstoque,
                'descricao' => $descricao,
            ]
        );
    }
    public function retornaSqlAdicionarAguardEstoque(
        int $idUsuario,
        int $idProduto,
        string $nomeTamanho,
        int $idCompra
    ): string {
        if ($this->pares > 0) {
            $sql = '';
            for ($i = 0; $i < $this->pares; $i++) {
                $sql .= "INSERT INTO produtos_aguarda_entrada_estoque(
                    produtos_aguarda_entrada_estoque.id_produto,
                    produtos_aguarda_entrada_estoque.nome_tamanho,
                    produtos_aguarda_entrada_estoque.qtd,
                    produtos_aguarda_entrada_estoque.tipo_entrada,
                    produtos_aguarda_entrada_estoque.usuario,
                    produtos_aguarda_entrada_estoque.identificao
                ) VALUES (
                    $idProduto,
                    '$nomeTamanho',
                    1,
                    'CO',
                    $idUsuario,
                    $idCompra
                );";
            }

            return $sql;
        } else {
            throw new Exception('Erro para atualizar produtos_aguardando_estoque');
        }
    }

    public static function retornarItensComEstoque(array $idsProdutos, string $origem): array
    {
        if (empty($idsProdutos)) {
            return $idsProdutos;
        }
        $where = $origem === Origem::MS ? 'AND estoque_grade.id_responsavel = 1' : '';
        [$itens, $bind] = ConversorArray::criaBindValues($idsProdutos);
        $idsProdutosComEstoque = DB::selectColumns(
            "SELECT estoque_grade.id_produto
            FROM estoque_grade
            WHERE estoque_grade.id_produto IN ($itens)
                AND estoque_grade.estoque > 0 $where
            GROUP BY estoque_grade.id_produto",
            $bind
        );
        return $idsProdutosComEstoque;
    }
}
