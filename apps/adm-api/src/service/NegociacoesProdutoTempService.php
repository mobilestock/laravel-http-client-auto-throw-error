<?php

namespace MobileStock\service;

use DomainException;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\NegociacoesProdutoTemp;
use PDO;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NegociacoesProdutoTempService extends NegociacoesProdutoTemp
{
    const SITUACAO_CRIADA = 'CRIADA';
    const SITUACAO_ACEITA = 'ACEITA';
    const SITUACAO_CANCELADA = 'CANCELADA';
    private PDO $conexao;
    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }
    public function salva(): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insertSemFilter();
        $sql = $this->conexao->prepare($sql);
        $sql->execute($geradorSql->bind);
    }
    public function remove(): void
    {
        if (empty($this->uuid_produto)) {
            throw new NotFoundHttpException('Produto não informado');
        }

        $sql = $this->conexao->prepare(
            'DELETE FROM negociacoes_produto_temp WHERE negociacoes_produto_temp.uuid_produto = :uuid_produto;'
        );
        $sql->bindValue(':uuid_produto', $this->uuid_produto, PDO::PARAM_STR);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new DomainException("Erro ao remover negociação {$this->uuid_produto}");
        }
    }
    public function buscaNegociacaoAbertaPorProduto(): ?NegociacoesProdutoTemp
    {
        if (empty($this->uuid_produto)) {
            throw new NotFoundHttpException('Produto não informado');
        }

        $lockId = __METHOD__ . $this->uuid_produto;
        $sql = $this->conexao->prepare(
            "SELECT GET_LOCK(:lock_id, 99999) AS `lock`;

            SELECT
                negociacoes_produto_temp.uuid_produto,
                negociacoes_produto_temp.itens_oferecidos,
                negociacoes_produto_temp.data_criacao
            FROM negociacoes_produto_temp
            WHERE negociacoes_produto_temp.uuid_produto = :uuid_produto;"
        );
        $sql->bindValue(':lock_id', $lockId, PDO::PARAM_STR);
        $sql->bindValue(':uuid_produto', $this->uuid_produto, PDO::PARAM_STR);
        $sql->execute();
        $sql->nextRowset();
        $negociacao = $sql->fetchObject(NegociacoesProdutoTemp::class);
        if (empty($negociacao)) {
            return null;
        }

        return $negociacao;
    }
    public function buscaInformacoesProdutosOferecidos(): array
    {
        $negociacao = $this->buscaNegociacaoAbertaPorProduto();
        if (empty($negociacao)) {
            throw new NotFoundHttpException('Negociação não existe desse produto');
        }

        [$bind, $valores] = ConversorArray::criaBindValues($negociacao->itens_oferecidos, 'id_produto');
        $sql = $this->conexao->prepare(
            "SELECT
                estoque_grade.id_produto,
                GROUP_CONCAT(estoque_grade.nome_tamanho ORDER BY estoque_grade.sequencia ASC) AS `grades`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`,
                produtos.nome_comercial,
                produtos.valor_venda_ml AS `preco`,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id = estoque_grade.id_produto
            WHERE estoque_grade.estoque > 0
                AND estoque_grade.id_responsavel > 1
                AND estoque_grade.id_produto IN ($bind)
            GROUP BY estoque_grade.id_produto;"
        );
        foreach ($valores as $key => $valor) {
            $sql->bindValue($key, $valor, PDO::PARAM_INT);
        }
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        if (empty($produtos)) {
            // https://github.com/mobilestock/backend/issues/134
            $this->remove();
            throw new NotFoundHttpException('Não existem produtos disponíveis para negociação.');
        }

        $produtos = array_map(function (array $produto): array {
            $produto['grades'] = explode(',', $produto['grades']);
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['preco'] = (float) $produto['preco'];

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public function buscaNegociacoesAbertasPorCliente(int $idCliente): array
    {
        $sql = $this->conexao->prepare(
            "SELECT
                logistica_item.id_transacao,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.preco,
                colaboradores.razao_social AS `nome_fornecedor`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`,
                produtos.nome_comercial,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`,
                negociacoes_produto_temp.uuid_produto,
                DATE_FORMAT(negociacoes_produto_temp.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`
            FROM logistica_item
            INNER JOIN negociacoes_produto_temp ON negociacoes_produto_temp.uuid_produto = logistica_item.uuid_produto
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE logistica_item.id_cliente = :id_cliente;"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $negociacoes = $sql->fetchAll(PDO::FETCH_ASSOC);
        $negociacoes = array_map(function (array $produtoNegociado): array {
            $produtoNegociado['id_produto'] = (int) $produtoNegociado['id_produto'];
            $produtoNegociado['preco'] = (float) $produtoNegociado['preco'];

            return $produtoNegociado;
        }, $negociacoes);

        return $negociacoes;
    }
    /**
     * @param string $situacao [ 'CRIADA', 'ACEITA', 'RECUSADA', 'CANCELADA' ]
     * @param array $produtoNegociado
     * @param array<int> $itensOferecidos
     * @param int $idUsuario
     * @param array|null $produtoEscolhido
     * @throws DomainException
     * @return void
     */
    public function criarLogNegociacao(
        string $situacao,
        array $produtoNegociado,
        array $itensOferecidos,
        int $idUsuario,
        ?array $produtoEscolhido = null
    ): void {
        $situacoes = (new ReflectionClass(self::class))->getConstants();

        Validador::validar(
            [
                'situacao' => $situacao,
                'itens_oferecidos' => $itensOferecidos,
                'uuid_produto' => $this->uuid_produto,
            ],
            [
                'situacao' => [Validador::ENUM(...array_values($situacoes))],
                'itens_oferecidos' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'uuid_produto' => [Validador::OBRIGATORIO],
            ]
        );
        Validador::validar($produtoNegociado, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome_tamanho' => [Validador::OBRIGATORIO],
        ]);
        if (!empty($produtoEscolhido)) {
            Validador::validar($produtoEscolhido, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);
        }

        $mensagemLog = [
            'produto_negociado' => $produtoNegociado,
            'produtos_oferecidos' => json_encode($itensOferecidos),
            'produto_substituto' => $produtoEscolhido,
        ];
        $sql = $this->conexao->prepare(
            "INSERT INTO negociacoes_produto_log (
                negociacoes_produto_log.uuid_produto,
                negociacoes_produto_log.mensagem,
                negociacoes_produto_log.situacao,
                negociacoes_produto_log.id_usuario
            ) VALUES (
                :uuid_produto,
                :mensagem,
                :situacao,
                :id_usuario
            );"
        );
        $sql->bindValue(':uuid_produto', $this->uuid_produto, PDO::PARAM_STR);
        $sql->bindValue(':mensagem', json_encode($mensagemLog), PDO::PARAM_STR);
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_STR);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new DomainException('Erro ao criar log de negociação');
        }
    }
    /**
     * @see https://github.com/mobilestock/backend/issues/136
     */
    public function atualizaInformacoesProduto(string $uuidProduto, int $idNovoProduto, string $novoNomeTamanho): void
    {
        $sql = $this->conexao->prepare(
            "UPDATE logistica_item SET
                logistica_item.id_produto = :id_produto,
                logistica_item.nome_tamanho = :nome_tamanho
            WHERE logistica_item.uuid_produto = :uuid_produto;

            UPDATE transacao_financeiras_produtos_itens SET
                transacao_financeiras_produtos_itens.id_produto = :id_produto,
                transacao_financeiras_produtos_itens.nome_tamanho = :nome_tamanho
            WHERE transacao_financeiras_produtos_itens.uuid_produto = :uuid_produto
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.id_produto IS NOT NULL
                AND transacao_financeiras_produtos_itens.nome_tamanho IS NOT NULL;

            UPDATE pedido_item_meu_look SET
                pedido_item_meu_look.id_produto = :id_produto,
                pedido_item_meu_look.nome_tamanho = :nome_tamanho
            WHERE pedido_item_meu_look.uuid = :uuid_produto;"
        );
        $sql->bindValue(':id_produto', $idNovoProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $novoNomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar as informações do produto (1)');
        }
        $sql->nextRowset();
        if ($sql->rowCount() < 1) {
            throw new RuntimeException('Não foi possível atualizar as informações do produto (2)');
        }
        $sql->nextRowset();
        if ($sql->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar as informações do produto (3)');
        }
    }
}
