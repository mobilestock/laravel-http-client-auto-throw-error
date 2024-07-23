<?php

namespace MobileStock\service\EntregaService;

use Error;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\Entrega\EntregasDevolucoesItem;
use MobileStock\model\TipoFrete;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\MessageService;
use MobileStock\service\Monitoramento\MonitoramentoService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntregasDevolucoesItemServices extends EntregasDevolucoesItem
{
    public function adiciona(PDO $conexao): int
    {
        $sql = '';
        $camposTabela = [];
        $dadosTabela = [];
        $dados = [];
        unset($this->id);
        foreach ($this as $key => $value) {
            if (!$value || in_array($key, ['id', 'data_criacao'])) {
                continue;
            }
            $camposTabela[] = $key;
            $dadosTabela[] = ":{$key}";
            $dados[$key] = $value;
        }
        $sql =
            'INSERT INTO entregas_devolucoes_item (' .
            implode(',', $camposTabela) .
            ') VALUES (' .
            implode(',', $dadosTabela) .
            ');';
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);
        $this->id = $conexao->lastInsertId();
        return $this->id;
    }

    public function atualiza(PDO $conexao): bool
    {
        if ($this->id === null) {
            throw new Exception('Não é possivel atualizar sem o id', 400);
        }
        $dados = [];
        $sql = 'UPDATE entregas_devolucoes_item SET ';

        foreach ($this as $key => $valor) {
            if ((!$valor && !is_null($valor)) || in_array($key, ['id', 'data_criacao'])) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if (is_null($valor)) {
                $valor = 'NULL';
            }
            if ($key === 'data_atualizacao') {
                $valor = 'NOW()';
            }
            $dados[] = $key . ' = ' . $valor;
        }

        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= ' ' . implode(',', $dados) . " WHERE entregas_devolucoes_item.id = '" . $this->id . "'";

        return !!$conexao->exec($sql);
    }

    public function busca(PDO $conexao): EntregasDevolucoesItem
    {
        if (!$this->id) {
            throw new Exception('Não existe id de usuario na entrega para realizar a consulta', 1);
        }

        $sql =
            'SELECT entregas_devolucoes_item.* FROM entregas_devolucoes_item WHERE entregas_devolucoes_item.id = :id;';
        $prepare = $conexao->prepare($sql);
        $prepare->bindValue(':id', $this->id, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetch(PDO::FETCH_ASSOC);
        foreach ($dados as $campo => $valor) {
            $this->$campo = $valor;
        }

        return $this;
    }

    public static function buscaTrocasPendentes(): array
    {
        $query = "SELECT
            entregas_devolucoes_item.id,
            entregas_devolucoes_item.id_produto,
            colaboradores.razao_social,
            JSON_VALUE(transacao_financeiras_metadados.valor, '$.nome_destinatario') nome_destinatario,
            produtos.nome_comercial nome_produto,
            entregas_devolucoes_item.nome_tamanho,
            (
                SELECT
                    produtos_foto.caminho
                FROM produtos_foto
                    WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                LIMIT 1
            ) foto,
            entregas_devolucoes_item.data_criacao,
            (DATEDIFF(NOW(), entregas_devolucoes_item.data_criacao) >= (SELECT configuracoes.dias_atraso_para_trocas_ponto
                                                                        FROM configuracoes
                                                                        LIMIT 1)) esta_em_atraso
            FROM entregas_devolucoes_item
                INNER JOIN colaboradores ON colaboradores.id = entregas_devolucoes_item.id_cliente
                INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                INNER JOIN tipo_frete ON entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = entregas_devolucoes_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            WHERE tipo_frete.id_colaborador = :id_colaborador
                AND entregas_devolucoes_item.situacao = 'PE'
                AND entregas_devolucoes_item.id_usuario = :id_usuario
                ORDER BY entregas_devolucoes_item.data_criacao ASC";

        $resultado = DB::select($query, ['id_colaborador' => Auth::user()->id_colaborador, 'id_usuario' => Auth::id()]);
        $resultado = MonitoramentoService::trataRetornoDeBusca($resultado);

        return $resultado;
    }

    public static function buscaDetalhesTrocas(string $uuidProduto): array
    {
        $resultado = DB::selectOne(
            "SELECT
                entregas_devolucoes_item.id AS `id_devolucao`,
                entregas_devolucoes_item.id_produto,
                entregas_devolucoes_item.id_entrega,
                DATE_FORMAT(entregas_devolucoes_item.data_atualizacao, '%d/%m/%Y %H:%i')data,
                entregas_devolucoes_item.tipo,
                entregas_devolucoes_item.origem,
                (
                    SELECT tipo_frete.nome
                    FROM tipo_frete
                    WHERE tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                ) ponto,
                (
                    SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id = entregas_devolucoes_item.id_usuario
                ) nome_usuario,
                entregas_devolucoes_item.nome_tamanho,
                (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                )caminho_foto,
                (
                    SELECT produtos.id_fornecedor
                    FROM produtos
                    WHERE produtos.id = entregas_devolucoes_item.id_produto
                ) AS `id_fornecedor`,
                entregas_devolucoes_item.id_transacao,
                entregas_devolucoes_item.situacao
                FROM entregas_devolucoes_item
                WHERE entregas_devolucoes_item.uuid_produto = :uuid_produto",
            ['uuid_produto' => $uuidProduto]
        );

        if (empty($resultado)) {
            return [];
        }
        $conversao = EntregasDevolucoesItem::ConversorSiglasEntregasDevolucoesItens(
            $resultado['situacao'],
            $resultado['origem']
        );
        $resultado['situacao'] = $conversao['situacao'];
        $resultado['origem'] = $conversao['origem'];
        return $resultado;
    }

    public static function buscarTrocasPendentesAtrasadas(PDO $conexao): array
    {
        $query = "SELECT
                        entregas_devolucoes_item.uuid_produto,
                        DATE_FORMAT(entregas_devolucoes_item.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                        DATE_FORMAT(DATE_ADD(entregas_devolucoes_item.data_criacao, INTERVAL 50 DAY), '%d/%m/%Y') AS `data_limite`,
                        (
                            SELECT
                                JSON_OBJECT(
                                    'nome', CONCAT('(', entregas_devolucoes_item.id_produto, ') ', produtos.nome_comercial),
                                    'tamanho', entregas_devolucoes_item.nome_tamanho,
                                    'foto', (
                                                SELECT produtos_foto.caminho
                                                FROM produtos_foto
                                                WHERE
                                                    produtos_foto.id = produtos.id
                                                    AND produtos_foto.tipo_foto <> 'SM'
                                                ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                                LIMIT 1
                                            )
                                )
                            FROM produtos
                            WHERE produtos.id = entregas_devolucoes_item.id_produto
                        ) AS `produto`,
                        (
                            SELECT
                                JSON_OBJECT(
                                    'id_colaborador', colaboradores.id,
                                    'nome', colaboradores.razao_social
                                )
                            FROM colaboradores
                            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                            WHERE usuarios.id = entregas_devolucoes_item.id_usuario
                            LIMIT 1
                        ) AS `usuario`,
                        (
                            SELECT
                                JSON_OBJECT(
                                    'id_colaborador', tipo_frete.id_colaborador,
                                    'id_colaborador_ponto_coleta', tipo_frete.id_colaborador_ponto_coleta
                                    )
                            FROM tipo_frete
                            WHERE tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                        ) AS `ponto`
                    FROM entregas_devolucoes_item
                    WHERE
                        entregas_devolucoes_item.situacao = 'PE'
                        AND DATEDIFF(NOW(),entregas_devolucoes_item.data_criacao) = 30
                        ORDER BY entregas_devolucoes_item.id ASC";

        $stmt = $conexao->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            return [];
        }

        $resultado = array_map(function ($item) {
            $item['ponto'] = json_decode($item['ponto'], true);
            $item['usuario'] = json_decode($item['usuario'], true);
            $item['produto'] = json_decode($item['produto'], true);
            $dados = [];

            $colaborador = ColaboradoresService::buscaCadastroColaborador(
                $item['ponto']['id_colaborador_ponto_coleta'] === TipoFrete::ID_COLABORADOR_CENTRAL
                    ? $item['ponto']['id_colaborador']
                    : $item['ponto']['id_colaborador_ponto_coleta']
            );

            $dados['usuario_bip_nome'] = $item['usuario']['nome'];
            $dados['ponto_coleta_nome'] = $colaborador['razao_social'];
            $dados['ponto_coleta_telefone'] = preg_replace('/[^0-9]/', '', $colaborador['telefone']);
            $dados['data_bipagem_troca'] = $item['data_criacao'];
            $dados['data_limite'] = $item['data_limite'];
            $dados['uuid_produto'] = $item['uuid_produto'];
            $dados['produto_nome'] = $item['produto']['nome'];
            $dados['produto_tamanho'] = $item['produto']['tamanho'];
            $dados['produto_foto'] = $item['produto']['foto'];

            return $dados;
        }, $resultado);

        return $resultado;
    }

    public static function buscarTrocasPendentesAtrasadasParaDescontar(PDO $conexao, string $uuidProduto = ''): array
    {
        $where = '';

        if ($uuidProduto) {
            $where = ' AND entregas_devolucoes_item.uuid_produto = :uuid_produto';
        } else {
            $where = ' AND DATEDIFF(NOW(),entregas_devolucoes_item.data_criacao) >= 60';
        }

        $query = "SELECT
                        entregas_devolucoes_item.id,
                        entregas_devolucoes_item.situacao,
                        entregas_devolucoes_item.id_transacao,
                        entregas_devolucoes_item.uuid_produto,
                        entregas_devolucoes_item.id_ponto_responsavel
                    FROM entregas_devolucoes_item
                    WHERE
                        entregas_devolucoes_item.situacao IN ('PE','VE')
                        $where
                        ORDER BY entregas_devolucoes_item.id ASC";

        $stmt = $conexao->prepare($query);
        if ($uuidProduto) {
            $stmt->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        }
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public static function buscarProdutoSemAgendamento(string $uuidProduto): array
    {
        $query = "SELECT
                    entregas_faturamento_item.id_transacao,
                    entregas_faturamento_item.id_produto,
                    entregas_faturamento_item.nome_tamanho,
                    entregas_faturamento_item.origem,
                    entregas_faturamento_item.situacao,
                    entregas_faturamento_item.id_cliente,
                    entregas_faturamento_item.data_base_troca,
                    entregas_faturamento_item.uuid_produto,
                    DATEDIFF(NOW(), entregas_faturamento_item.data_base_troca) AS `dias_apos_entrega`,
                    (
                        SELECT
                            GROUP_CONCAT(
                                JSON_OBJECT(
                                    'nome_comercial', CONCAT(produtos.nome_comercial,' ', produtos.cores),
                                    'descricao', produtos.descricao,
                                    'produto_foto', (
                                                        SELECT produtos_foto.caminho
                                                        FROM produtos_foto
                                                        WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                                                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                                                        LIMIT 1
                                                    ),
                                    'fornecedor', (
                                        SELECT colaboradores.razao_social
                                        FROM colaboradores
                                        WHERE colaboradores.id = produtos.id_fornecedor
                                    ),
                                    'localizacao', produtos.localizacao
                                )
                            )
                        FROM produtos
                        WHERE produtos.id = entregas_faturamento_item.id_produto
                    ) AS `json_dados_produto`,
                    (
                        SELECT produtos_grade.cod_barras
                        FROM produtos_grade
                        WHERE produtos_grade.id_produto = entregas_faturamento_item.id_produto
                            AND produtos_grade.nome_tamanho = entregas_faturamento_item.nome_tamanho
                    ) AS `cod_barras`,
                    (
                        SELECT logistica_item.preco
                        FROM logistica_item
                        WHERE logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
                    ) AS `preco`
                FROM
                    entregas_faturamento_item
                WHERE
                    entregas_faturamento_item.uuid_produto = :uuid_produto AND
                    NOT EXISTS(
                                SELECT 1
                                FROM entregas_devolucoes_item
                                WHERE
                                    entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto AND
                                    entregas_devolucoes_item.situacao <> 'PE'
                                )";

        $resultado = DB::selectOne($query, $binds);

        if (empty($resultado)) {
            throw new NotFoundHttpException('Este produto não pode ser encontrado. Entre em contato com a T.I.');
        }

        return $resultado;
    }

    /**
     * Envia notificação para o cliente informando que o produto não pode ser devolvido e retorna a mensagem para ser exibido no aplicativo interno
     * @param string $tipoTroca DEFEITO | NORMAL
     * @param array $dados ['nome_produto', 'nome_tamanho', 'dias_troca', 'foto_produto', 'uuid_produto']
     */
    public static function notificarAtrasoDevolucaoMS(int $idCliente, string $tipoTroca, array $dados): string
    {
        $msgService = new MessageService();

        $cliente = ColaboradoresRepository::buscaColaboradorPorID($idCliente);

        $mensagemCliente = '';
        $mensagemInterno = '';

        $mensagemCliente .= "Olá {$cliente['nome']}. " . PHP_EOL . PHP_EOL;
        $mensagemCliente .= "O produto *{$dados['nome_produto']} [{$dados['nome_tamanho']}]*";

        switch ($tipoTroca) {
            case 'NORMAL':
                $mensagemCliente .= " não pode ser devolvido pois já passou do prazo de {$dados['dias_troca']} dias.";
                $mensagemInterno = "Esse produto não pode ser devolvido pois já passou do prazo de {$dados['dias_troca']} dias.";
                break;
            case 'DEFEITO':
                $mensagemCliente .= " não pode ser devolvido pois já passou do prazo de garantia para defeitos de {$dados['dias_troca']} dias.";
                $mensagemInterno = "Esse produto não pode ser devolvido pois já passou do prazo para defeito de {$dados['dias_troca']} dias.";
                break;
        }

        $mensagemCliente .= ' Por isso, iremos reenviá-lo para você em uma próxima compra.' . PHP_EOL . PHP_EOL;
        $mensagemCliente .= "_Código interno do produto:_ {$dados['uuid_produto']}";

        $mensagemInterno .= PHP_EOL . PHP_EOL;
        $mensagemInterno .= 'Deixe o produto separado para que seja reenviado ao cliente na próxima entrega.';
        $mensagemInterno .= PHP_EOL . PHP_EOL;
        $mensagemInterno .= 'O cliente foi notificado através do WhatsApp sobre o produto.';

        $msgService->sendImageWhatsApp($cliente['telefone'], $dados['foto_produto'], $mensagemCliente);

        return $mensagemInterno;
    }

    public static function buscarDadosEtiquetaDevolucao(PDO $conexao, string $uuidProduto): array
    {
        $query = "SELECT
                    DATE_FORMAT(entregas_faturamento_item.data_entrega, '%d/%m/%Y') AS `data_entrega`,
                    DATE_FORMAT(entregas_devolucoes_item.data_criacao, '%d/%m/%Y') AS `data_devolucao`
                FROM entregas_faturamento_item
                LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto
                WHERE entregas_faturamento_item.uuid_produto = :uuid_produto";

        $sql = $conexao->prepare($query);
        $sql->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $sql->execute();

        $resultado = $sql->fetch(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            throw new NotFoundHttpException('Este produto não pode ser encontrado. Entre em contato com a T.I.');
        }

        return $resultado;
    }

    public static function buscarProdutosBipadosPontoPorCliente(
        int $idCliente,
        int $idProduto,
        string $uuidProduto
    ): array {
        $query = "SELECT uuid
                    FROM (
                        SELECT
                            entregas_devolucoes_item.uuid_produto AS `uuid`
                        FROM entregas_devolucoes_item
                        WHERE
                            entregas_devolucoes_item.id_cliente = :id_cliente
                            AND entregas_devolucoes_item.id_produto = :id_produto
                            AND entregas_devolucoes_item.origem = 'ML'
                            AND entregas_devolucoes_item.situacao = 'PE'

                        UNION ALL

                        SELECT
                            troca_pendente_agendamento.uuid
                        FROM troca_pendente_agendamento
                        WHERE
                            troca_pendente_agendamento.id_cliente = :id_cliente
                            AND troca_pendente_agendamento.id_produto = :id_produto
                    ) AS `produtos_troca`
                    WHERE `produtos_troca`.uuid <> :uuid_produto;";

        $produtosPendentes = DB::selectColumns($query, [
            'id_cliente' => $idCliente,
            'id_produto' => $idProduto,
            'uuid_produto' => $uuidProduto,
        ]);

        if (empty($produtosPendentes)) {
            return [];
        }

        [$bind, $valores] = ConversorArray::criaBindValues($produtosPendentes, 'uuid_produto');

        $query = "SELECT
                    cliente_colaboradores.razao_social AS `nome_cliente`,
                    (
                        SELECT CONCAT('(', entregas_faturamento_item.id_produto, ') ', produtos.nome_comercial, ' - ', produtos.cores)
                        FROM produtos
                        WHERE produtos.id = entregas_faturamento_item.id_produto
                        LIMIT 1
                    ) AS `nome_produto`,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                        LIMIT 1
                    ) AS `foto_produto`,
                    CONCAT(
                        '[',
                            GROUP_CONCAT(
                                JSON_OBJECT(
                                    'nome_tamanho', entregas_faturamento_item.nome_tamanho,
                                    'nome_tipo_frete', COALESCE(tipo_frete.nome,'Ainda não entregue a um ponto'),
                                    'telefone_tipo_frete', tipo_frete_colaboradores.telefone,
                                    'data_criacao', COALESCE(
                                        DATE_FORMAT(entregas_devolucoes_item.data_criacao, '%d/%m/%Y %H:%i'), '-'
                                    ),
                                    'uuid_produto', entregas_faturamento_item.uuid_produto
                                )
                            )
                        ,']'
                    ) AS `json_pontos`
                FROM entregas_faturamento_item
                LEFT JOIN entregas_devolucoes_item ON
                    entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto
                    AND entregas_devolucoes_item.situacao = 'PE'
                LEFT JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                LEFT JOIN colaboradores AS `tipo_frete_colaboradores` ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
                INNER JOIN colaboradores AS `cliente_colaboradores` ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
                WHERE entregas_faturamento_item.uuid_produto IN ($bind);";

        $resultado = DB::selectOne($query, $valores);

        return $resultado;
    }
}
