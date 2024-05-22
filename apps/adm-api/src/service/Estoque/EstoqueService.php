<?php
namespace MobileStock\service\Estoque;

use DomainException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use PDO;

class EstoqueService
{
    // public static function ConsultaEstoqueFornecedor(PDO $conexao, int $idFornecedor, string $produto)
    // {
    //     $query = "SELECT p.id, p.descricao, DATE_FORMAT(p.data_entrada,'%d/%m/%Y') data_entrada, p.valor_custo_produto custo,
    //     (SELECT SUM(eg.estoque) FROM estoque_grade eg where eg.id_produto=p.id) estoque,
    //     (SELECT pf.caminho foto FROM produtos_foto pf WHERE pf.id=p.id and pf.sequencia=1)foto from produtos p
    //     WHERE p.id_fornecedor=:idFornecedor AND p.bloqueado=0
    //     AND lower(p.descricao) like lower('%{$produto}%')
    //     ORDER BY p.data_entrada desc;";
    //     $consulta = $conexao->prepare($query);
    //     $consulta->bindParam(':idFornecedor', $idFornecedor, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);

    //     foreach ($resposta as $key => $r) {
    //         $query = "SELECT eg.tamanho, eg.nome_tamanho, (SUM(eg.estoque))estoque FROM estoque_grade eg
    //         WHERE eg.id_produto = {$r['id']} GROUP BY eg.tamanho ORDER BY eg.tamanho;";
    //         $consulta = $conexao->prepare($query);
    //         $consulta->execute();
    //         $grade = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //         $resposta[$key]['grade'] = $grade;
    //     }
    //     return $resposta;
    // }

    // public static function ConsultaEstoqueFornecedorFoto(PDO $conexao, int $idFornecedor, string $produto)
    // {
    //     $query = "SELECT produtos_separacao_fotos.id_produto, produtos.descricao,
    //     DATE_FORMAT(produtos_separacao_fotos.data_separado,'%d/%m/%Y')datas,
    //                         produtos.descricao,produtos_separacao_fotos.tamanho,
    //                             (SELECT produtos_foto.caminho
    //                                     FROM produtos_foto
    //                                         WHERE produtos_foto.id=produtos.id
    //                                             AND produtos_foto.sequencia=1
    //                             )foto  FROM produtos
    //                         INNER JOIN produtos_separacao_fotos
    //                             ON(produtos_separacao_fotos.id_produto = produtos.id)
    //                                 WHERE produtos.id_fornecedor = {$idFornecedor}
    //                                   AND produtos_separacao_fotos.id_produto IN (SELECT p.id FROM produtos p WHERE lower(p.descricao) like lower('%{$produto}%'))";
    //     $consulta = $conexao->prepare($query);
    //     $consulta->bindParam(':idFornecedor', $idFornecedor, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     return $resposta;
    // }

    public static function ConsultaEstoqueProduto(PDO $conexao, $idProduto)
    {
        $stmt = $conexao->prepare(
            "SELECT count(*) estoque
            FROM produtos_aguarda_entrada_estoque
            WHERE
                id_produto = :idProduto AND
                em_estoque = 'F'"
        );
        $stmt->bindValue(':idProduto', $idProduto);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            return 0;
        }

        return $resultado['estoque'];
    }

    public static function ConsultaProdutosPorTamanho(PDO $conexao, int $idProduto, ?string $numeracoes): array
    {
        $select = ', 0 qtd';
        $where = '';
        $bind = [];

        if (isset($numeracoes)) {
            $numeracoesArray = [];
            foreach (explode(',', $numeracoes) as $index => $numero) {
                $key = ":n_$index";
                $bind[$key] = $numero;
                $numeracoesArray[] = $key;
            }
            $select =
                ", COALESCE((
                SELECT COUNT(paee.id)
                FROM produtos_aguarda_entrada_estoque paee
                WHERE
                    paee.id IN (" .
                implode(',', $numeracoesArray) .
                ") AND
                    paee.nome_tamanho = produtos_aguarda_entrada_estoque.nome_tamanho
                GROUP BY paee.nome_tamanho
            ), 0) qtd";
            $where .= ' AND produtos_aguarda_entrada_estoque.id IN (' . implode(',', $numeracoesArray) . ')';
        }

        $stmt = $conexao->prepare(
            "SELECT
                produtos_aguarda_entrada_estoque.nome_tamanho
                $select
            FROM produtos_aguarda_entrada_estoque
            WHERE
                produtos_aguarda_entrada_estoque.id_produto = :idProduto
                $where
            GROUP BY produtos_aguarda_entrada_estoque.nome_tamanho"
        );
        $stmt->execute(array_merge([':idProduto' => $idProduto], $bind));
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            return [];
        }

        return $resultado;
    }

    public static function DeletaTabelaProdutoTemporaria(PDO $conexao)
    {
        return $conexao->exec('DROP TABLE IF EXISTS temp_produtos_a_inserir_estoque');
    }

    public static function CriaTabelaTemporaria(PDO $conexao, $numeracoes)
    {
        return $conexao->exec(
            "CREATE temporary TABLE IF NOT EXISTS temp_produtos_a_inserir_estoque
            SELECT produtos_aguarda_entrada_estoque.id
            FROM produtos_aguarda_entrada_estoque
            WHERE id IN ({$numeracoes})"
        );
    }

    public static function defineLocalizacaoProduto(
        PDO $conexao,
        int $idProduto,
        int $localizacao,
        int $idUsuario,
        string $numeracoes
    ): array {
        $numeracoes = (array) explode(',', $numeracoes);

        [$idAguardandoEntrada, $bindIdAguardandoEntrada] = ConversorArray::criaBindValues($numeracoes);

        $stmt = $conexao->prepare(
            "SELECT
                produtos_aguarda_entrada_estoque.id_produto,
                produtos_aguarda_entrada_estoque.nome_tamanho,
                produtos_aguarda_entrada_estoque.tipo_entrada,
                produtos_aguarda_entrada_estoque.qtd,
                produtos_aguarda_entrada_estoque.identificao,
                produtos.valor_venda_ms,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) as `foto`
            FROM produtos_aguarda_entrada_estoque
            JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
            WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto
                AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.id IN ($idAguardandoEntrada)
                GROUP BY produtos_aguarda_entrada_estoque.id;"
        );

        foreach ($bindIdAguardandoEntrada as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->execute();
        $informacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($informacoes)) {
            throw new Exception("O produto $idProduto já foi inserido no estoque");
        }

        $query = 'SELECT 1 from localizacao_estoque WHERE localizacao_estoque.local = :localizacao';
        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':localizacao', $localizacao);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            throw new Exception("Localização $localizacao não encontrada");
        }

        $query = 'SELECT produtos.localizacao FROM produtos WHERE produtos.id = :id_produto';
        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->execute();
        $antigaLocalizacao = (int) $stmt->fetch(PDO::FETCH_ASSOC)['localizacao'];

        if ($antigaLocalizacao !== 0 && $antigaLocalizacao !== $localizacao) {
            throw new Exception(
                "Localização incorreta. O produto $idProduto precisa ser colocado no painel $antigaLocalizacao"
            );
        }

        $query = "UPDATE produtos_aguarda_entrada_estoque
                    SET produtos_aguarda_entrada_estoque.em_estoque = 'T',
                        produtos_aguarda_entrada_estoque.localizacao = :localizacao,
                        produtos_aguarda_entrada_estoque.usuario = :usuario
                    WHERE produtos_aguarda_entrada_estoque.id IN ($idAguardandoEntrada)
                    AND produtos_aguarda_entrada_estoque.em_estoque = 'F'";

        $stmt = $conexao->prepare($query);

        foreach ($bindIdAguardandoEntrada as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':localizacao', $localizacao, PDO::PARAM_INT);
        $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== count($bindIdAguardandoEntrada)) {
            throw new Exception('A quantidade de produtos inseridos no estoque não confere');
        }

        if ($antigaLocalizacao !== $localizacao) {
            $query = "UPDATE produtos
                    SET produtos.localizacao = :localizacao
                    WHERE produtos.id = :id_produto";
            $stmt = $conexao->prepare($query);
            $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
            $stmt->bindValue(':localizacao', $localizacao, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() !== 1) {
                throw new Exception("Erro ao atualizar localização do produto $idProduto");
            }

            $query = "INSERT INTO log_produtos_localizacao
                        (id_produto, old_localizacao, new_localizacao, usuario)
                    VALUE
                        (:id_produto, :antiga_localizacao, :nova_localizacao, :usuario)";
            $stmt = $conexao->prepare($query);
            $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
            $stmt->bindValue(':antiga_localizacao', $antigaLocalizacao, PDO::PARAM_INT);
            $stmt->bindValue(':nova_localizacao', $localizacao, PDO::PARAM_INT);
            $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
        }

        foreach ($informacoes as $informacao) {
            Validador::validar($informacao, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            switch ($informacao['tipo_entrada']) {
                case 'FT':
                    $descricao = (string) 'Retorno de produto da sessão de fotos';
                    break;
                case 'CO':
                    $descricao = (string) 'Entrada de produtos de compra';
                    break;
                case 'PC':
                    $descricao = (string) 'Retorno de produto de Pedido Cancelado';
                    break;
                case 'TR':
                    $descricao = (string) 'Entrada de produto de troca';
                    break;
                default:
                    $descricao = (string) 'Não foi identificado o tipo de entrada';
                    break;
            }
            $identificacao = (string) $informacao['identificao'];
            $descricao .= (string) " identificação: $identificacao usuário $idUsuario";
            $quantidade = (int) $informacao['qtd'];
            $nomeTamanho = (string) $informacao['nome_tamanho'];
            $update = (string) "UPDATE estoque_grade SET
                    estoque_grade.estoque = estoque_grade.estoque + $quantidade,
                    estoque_grade.tipo_movimentacao = 'E',
                    estoque_grade.descricao = '$descricao'
                WHERE estoque_grade.id_produto = $idProduto
                    AND estoque_grade.id_responsavel = 1
                    AND estoque_grade.nome_tamanho = '$nomeTamanho'";

            $sql = $conexao->prepare(
                'CALL insere_grade_responsavel(:id_produto, :nome_tamanho, 1, :id_usuario, :update)'
            );
            $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
            $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
            $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $sql->bindValue(':update', $update, PDO::PARAM_STR);
            $sql->execute();
        }

        return $informacoes;
    }

    public static function limpaLocalizacaoProdutosAguardaEntrada(int $idProduto): void
    {
        $rowCount = DB::update("
                UPDATE produtos_aguarda_entrada_estoque
                SET produtos_aguarda_entrada_estoque.localizacao = NULL,
                    produtos_aguarda_entrada_estoque.usuario = :id_usuario
                WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.id_produto = :id_produto;
        ", ['id_produto' => $idProduto, 'id_usuario' => Auth::user()->id]);

        if (!$rowCount) {
            Log::withContext(['id_produto' => $idProduto]);
            throw new DomainException('Não foi possível zerar a localização do produto aguardando entrada estoque');
        }
    }

    public static function NotificaClientesReestoque(PDO $conexao, $idProduto, $tamanho)
    {
        $stmt = $conexao->prepare('CALL notifica_clientes_produto_chegou(:idProduto, :tamanho)');
        $stmt->bindValue(':idProduto', $idProduto);
        $stmt->bindValue(':tamanho', $tamanho);
        $stmt->execute();
    }

    public static function BuscaClientesComProdutosNaFilaDeEspera(PDO $conexao, $produtos = []): array
    {
        $bind = [];
        $where = ' AND (FALSE';
        foreach ($produtos as $index => $produto) {
            $keyId = ":id_{$index}";
            $keyTamanho = ":tamanho_{$index}";
            $keyQtd = ":qtd_{$index}";
            $where .= " OR (
                pedido_item.id_produto = $keyId AND
                pedido_item.nome_tamanho = $keyTamanho AND
                EXISTS(
                    SELECT 1
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = pedido_item.id_produto
                        AND estoque_grade.id_responsavel = pedido_item.id_responsavel_estoque
                        AND estoque_grade.nome_tamanho = pedido_item.nome_tamanho
                        AND estoque_grade.estoque - $keyQtd <= 0
                )
            )";
            $bind[$keyId] = $produto['id_produto'];
            $bind[$keyTamanho] = $produto['tamanho'];
            $bind[$keyQtd] = $produto['qtd_movimentado'];
        }
        $where .= ')';

        $stmt = $conexao->prepare(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                pedido_item.id_produto,
                pedido_item.nome_tamanho tamanho,
                colaboradores.telefone,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = pedido_item.id_produto
                    ORDER BY produtos_foto.sequencia = 'MD' DESC
                    LIMIT 1
                ) foto,
                IF(EXISTS (
                    SELECT 1
                    FROM pedido_item_meu_look
                    WHERE pedido_item_meu_look.uuid = pedido_item.uuid
                ), 'meulook', 'mobile') as `origem`
            FROM colaboradores
            INNER JOIN pedido_item ON
                pedido_item.id_cliente = colaboradores.id AND
                pedido_item.tipo_adicao = 'FL' AND
                pedido_item.situacao = 1
            WHERE
                1=1
                $where
            GROUP BY
                colaboradores.id,
                pedido_item.id_produto,
                pedido_item.nome_tamanho,
                origem"
        );
        $stmt->execute($bind);
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $consulta = array_map(function (array $item): array {
            $item['mensagem'] = self::MensagemNotificacaoReposicaoFila($item['origem']);
            return $item;
        }, $consulta);
        return $consulta;
    }
    public static function consultaLocalizacoesEstoque(PDO $conexao)
    {
        $sql = $conexao->prepare(
            "SELECT localizacao_estoque.local
            FROM localizacao_estoque"
        );
        $sql->execute();
        $localizacoes = $sql->fetchAll(PDO::FETCH_ASSOC);

        $localizacoes = array_map(function ($local) {
            return (int) $local['local'];
        }, $localizacoes);

        return $localizacoes;
    }
    public static function limpaAnaliseEstoque(PDO $conexao, int $idUsuario): void
    {
        $sql = $conexao->prepare(
            "DELETE FROM analise_estoque
            WHERE analise_estoque.id_usuario = :id_usuario;
            DELETE FROM analise_estoque_header
            WHERE analise_estoque_header.id_usuario = :id_usuario;"
        );
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
    }
    public static function preencheAnaliseEstoque(
        PDO $conexao,
        array $produtos,
        int $local,
        int $pares,
        int $idUsuario
    ): void {
        $sql = '';
        $sequencia = 0;
        foreach ($produtos as $produto) {
            Validador::validar(
                $produto,
                $produto['situacao'] !== 'CN'
                    ? [
                        'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                        'nome_tamanho' => [Validador::OBRIGATORIO, Validador::NAO_NULO],
                        'situacao' => [Validador::OBRIGATORIO, Validador::STRING],
                    ]
                    : [
                        'codigo_barras' => [Validador::OBRIGATORIO],
                    ]
            );
            $idProduto = (int) $produto['id_produto'];
            $nomeTamanho = (string) $produto['nome_tamanho'];
            $codigoBarras = (string) $produto['codigo_barras'];
            $tipo = (string) $produto['situacao'];
            $sequencia++;

            $sql .= "INSERT INTO analise_estoque (
                analise_estoque.id_produto,
                analise_estoque.nome_tamanho,
                analise_estoque.codigo,
                analise_estoque.tipo,
                analise_estoque.id_usuario,
                analise_estoque.cod_barras,
                analise_estoque.sequencia
            ) VALUES (
                $idProduto,
                '$nomeTamanho',
                '$codigoBarras',
                '$tipo',
                $idUsuario,
                '$codigoBarras',
                $sequencia
            );";
        }
        $sql .= "INSERT INTO analise_estoque_header (
            analise_estoque_header.localizacao,
            analise_estoque_header.pares,
            analise_estoque_header.id_usuario
        ) VALUES(
            :localizacao,
            :pares,
            :id_usuario
        );";
        $sql = $conexao->prepare($sql);
        $sql->bindValue(':localizacao', $local, PDO::PARAM_INT);
        $sql->bindValue(':pares', $pares, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
    }
    public static function buscaEstoqueGradeProduto(PDO $conexao, int $idProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.id,
                estoque_grade.id_produto,
                estoque_grade.nome_tamanho,
                (estoque_grade.estoque + estoque_grade.vendido) estoque,
                (
                    SELECT produtos.localizacao
                    FROM produtos
                    WHERE produtos.id = estoque_grade.id_produto
                ) localizacao
            FROM estoque_grade
            WHERE estoque_grade.id_produto = :id_produto
            AND estoque_grade.id_responsavel = 1;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $grades ?? [];
    }
    public static function resultadoAnaliseEstoque(PDO $conexao, int $idUsuario): array
    {
        $sql = $conexao->prepare(
            "SELECT
                analise_estoque_header.localizacao,
                analise_estoque_header.pares
            FROM analise_estoque_header
            WHERE analise_estoque_header.id_usuario = :id_usuario"
        );
        $sql->bindValue(':id_usuario', $idUsuario, PDO::FETCH_ASSOC);
        $sql->execute();
        $resultado = $sql->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }
    public static function resultadoItensAnaliseEstoque(PDO $conexao, int $idUsuario): array
    {
        $sql = $conexao->prepare(
            "SELECT
                analise_estoque.id_produto,
                analise_estoque.nome_tamanho,
                IF (LENGTH(COALESCE(analise_estoque.cod_barras, '')) > 0, analise_estoque.cod_barras, (
                    SELECT COALESCE(produtos_grade.cod_barras)
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = analise_estoque.id_produto
                        AND produtos_grade.nome_tamanho = analise_estoque.nome_tamanho
                ))cod_barras,
                analise_estoque.tipo,
                analise_estoque.sequencia,
                CASE
                    WHEN analise_estoque.tipo = 'CN' THEN 'Código não cadastrado'
                    WHEN analise_estoque.tipo = 'LE' THEN CONCAT('Produto com localização incorreta - Local: ', produtos.localizacao)
                    WHEN analise_estoque.tipo = 'MS' THEN 'Mostruário'
                    WHEN analise_estoque.tipo = 'PF' THEN 'Produto faltando'
                    WHEN analise_estoque.tipo = 'PS' THEN 'Produto sobrando'
                    ELSE 'Sem informação'
                END AS descricao,
                produtos.localizacao,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) referencia,
                (
                    SELECT estoque_grade.estoque
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = analise_estoque.id_produto
                        AND estoque_grade.nome_tamanho = analise_estoque.nome_tamanho
                        AND estoque_grade.id_responsavel = 1
                )estoque
            FROM analise_estoque
            INNER JOIN produtos ON produtos.id = analise_estoque.id_produto
            WHERE analise_estoque.id_usuario = :id_usuario;"
        );
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $produtos ?: [];
    }
    public static function removeParAnalise(
        PDO $conexao,
        int $idProduto,
        string $nomeTamanho,
        int $sequencia,
        int $idUsuario
    ): void {
        $sql = $conexao->prepare(
            "DELETE FROM analise_estoque
            WHERE analise_estoque.id_produto = :id_produto
                AND analise_estoque.id_usuario = :id_usuario
                AND analise_estoque.nome_tamanho = :nome_tamanho
                AND analise_estoque.sequencia = :sequencia;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':sequencia', $sequencia, PDO::PARAM_INT);
        $sql->execute();
    }
    public static function buscaProdutosAguardandoEntrada(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos_aguarda_entrada_estoque.id_produto,
                produtos_aguarda_entrada_estoque.localizacao,
                GROUP_CONCAT(DISTINCT produtos_aguarda_entrada_estoque.identificao)identificao,
                MAX(produtos_aguarda_entrada_estoque.data_hora)data_hora,
                COALESCE(SUM(produtos_aguarda_entrada_estoque.qtd), 0)qtd,
                SUM(IF(produtos_aguarda_entrada_estoque.tipo_entrada = 'TR', produtos_aguarda_entrada_estoque.qtd, 0))qtd_troca,
                SUM(IF(produtos_aguarda_entrada_estoque.tipo_entrada = 'CO', produtos_aguarda_entrada_estoque.qtd, 0))qtd_compra,
                GROUP_CONCAT(DISTINCT produtos_aguarda_entrada_estoque.usuario)usuario,
                GROUP_CONCAT(DISTINCT produtos_aguarda_entrada_estoque.nome_tamanho)tamanho,
                (
                    SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id = produtos_aguarda_entrada_estoque.usuario_resp
                )usuario_resp,
                GROUP_CONCAT(DISTINCT(
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = produtos_aguarda_entrada_estoque.id_produto
                        AND produtos_grade.nome_tamanho = produtos_aguarda_entrada_estoque.nome_tamanho
                ))cod_barras,
                (
                    SELECT CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, ''))
                    FROM produtos
                    WHERE produtos.id = produtos_aguarda_entrada_estoque.id_produto
                )produto,
                GROUP_CONCAT(DISTINCT CASE
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'CO' THEN 'Compra'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'FT' THEN 'Foto'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'TR' THEN 'Troca'
                    WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'PT' THEN 'Pedido Cancelado'
                    ELSE 'Não Identificado'
                END)tipo_entrada
            FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.tipo_entrada <> 'SP'
            GROUP BY produtos_aguarda_entrada_estoque.id_produto

            UNION ALL

            SELECT
                produtos_aguarda_entrada_estoque.id_produto,
                produtos_aguarda_entrada_estoque.localizacao,
                GROUP_CONCAT(DISTINCT produtos_aguarda_entrada_estoque.identificao)identificao,
                produtos_aguarda_entrada_estoque.data_hora,
                COALESCE(produtos_aguarda_entrada_estoque.qtd, 0),
                0 qtd_troca,
                0 qtd_compra,
                produtos_aguarda_entrada_estoque.usuario,
                produtos_aguarda_entrada_estoque.nome_tamanho AS tamanho,
                (
                    SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id = produtos_aguarda_entrada_estoque.usuario_resp
                )usuario_resp,
                GROUP_CONCAT(DISTINCT(
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = produtos_aguarda_entrada_estoque.id_produto
                        AND produtos_grade.nome_tamanho = produtos_aguarda_entrada_estoque.nome_tamanho
                ))cod_barras,
                (
                    SELECT CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, ''))
                    FROM produtos
                    WHERE produtos.id = produtos_aguarda_entrada_estoque.id_produto
                )produto,
                'Separar para foto' tipo_entrada
            FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.tipo_entrada = 'SP';"
        );
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $produtos;
    }
    public static function consultaEstoqueGradeProduto(PDO $conexao, int $idProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.nome_tamanho,
                estoque_grade.estoque,
                estoque_grade.vendido,
                (estoque_grade.estoque + estoque_grade.vendido) total
            FROM estoque_grade
            WHERE estoque_grade.id_produto = :id_produto
                AND estoque_grade.id_responsavel = 1
            ORDER BY estoque_grade.sequencia ASC;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $grades ?: [];
    }

    public static function buscaProdutosNaLocalizacao(PDO $conexao, int $local): array
    {
        $query = 'SELECT produtos.id FROM produtos WHERE produtos.localizacao = :local';

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':local', $local, PDO::PARAM_INT);
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $produtos ?: [];
    }

    public static function buscaEstoqueGrade(PDO $conexao, int $id_produto): array
    {
        $query = "SELECT
                estoque_grade.id,
                estoque_grade.id_produto,
                estoque_grade.nome_tamanho,
                (estoque_grade.estoque + estoque_grade.vendido) estoque,
                (
                    SELECT produtos.localizacao
                    FROM produtos
                    WHERE produtos.id = estoque_grade.id_produto
                ) localizacao,
                produtos_grade.cod_barras
            FROM estoque_grade
                INNER JOIN
                    produtos_grade
                ON
                    produtos_grade.id_produto = estoque_grade.id_produto
                AND
                    produtos_grade.nome_tamanho = estoque_grade.nome_tamanho
            WHERE estoque_grade.id_produto = :id_produto
            AND estoque_grade.id_responsavel = 1";

        $stmt = $conexao->prepare($query);

        $stmt->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);

        $resultados = $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultados ?? [];
    }

    public static function buscaProdutosPorLocalizacaoEmAnalise(PDO $conexao, int $local, int $id_usuario): array
    {
        $query = "SELECT produtos.id,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) descricao,
                estoque_grade.nome_tamanho,
                (estoque_grade.estoque + estoque_grade.vendido) estoque,
                (
                    SELECT produtos.localizacao
                    FROM produtos
                    WHERE produtos.id = estoque_grade.id_produto
                ) localizacao,
                (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id LIMIT 1) produto_foto,
                produtos_grade.cod_barras
        FROM produtos
        INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
        INNER JOIN analise_estoque ON analise_estoque.id_produto= produtos.id AND analise_estoque.nome_tamanho = estoque_grade.nome_tamanho
        INNER JOIN produtos_grade ON produtos_grade.id_produto = produtos.id AND produtos_grade.nome_tamanho = estoque_grade.nome_tamanho
            WHERE
                produtos.localizacao = :localizacao
            AND
                analise_estoque.id_usuario = :id_usuario
            AND estoque_grade.id_responsavel = 1";

        $stmt = $conexao->prepare($query);

        $stmt->bindValue(':localizacao', $local, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_map(function ($item) {
            $produtos['uuid'] = (int) rand();
            $produtos['id'] = (int) $item['id'];
            $produtos['estoque'] = (int) $item['estoque'];
            $produtos['localizacao'] = (int) $item['localizacao'];
            $produtos['cod_barras'] = (int) $item['cod_barras'];
            $produtos['descricao'] = $item['descricao'];
            $produtos['nome_tamanho'] = $item['nome_tamanho'];
            $produtos['produto_foto'] = $item['produto_foto'];

            return $produtos;
        }, $produtos);

        return $resultado ?: [];
    }

    public static function buscarLocalizacaoDoProduto(PDO $conexao, int $cod_barras): array
    {
        $query = "SELECT
                    produtos_grade.cod_barras,
                    produtos_grade.id_produto,
                    (
                        SELECT
                            localizacao
                        FROM
                            produtos
                        WHERE
                            produtos.id = produtos_grade.id_produto) localizacao,
                    (
                        SELECT
                            CONCAT(
                                produtos.descricao, ' ', COALESCE(produtos.cores, '')
                            ) descricao
                        FROM
                            produtos
                        WHERE
                            produtos.id = produtos_grade.id_produto) descricao,
                    (
                        SELECT
                            nome_tamanho
                        FROM
                            estoque_grade
                        WHERE
                            estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                        AND
                            estoque_grade.id_produto =  produtos_grade.id_produto
                        AND
                            estoque_grade.id_responsavel = 1
                        ) tamanho,
                    (
                        SELECT
                            (estoque_grade.estoque + estoque_grade.vendido) estoque
                        FROM
                            estoque_grade
                        WHERE
                            estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                        AND
                            estoque_grade.id_produto =  produtos_grade.id_produto
                        AND
                            estoque_grade.id_responsavel = 1
                        ) estoque
                FROM produtos_grade
                WHERE
                    produtos_grade.cod_barras = :cod_barras";

        $stmt = $conexao->prepare($query);

        $stmt->bindValue(':cod_barras', $cod_barras, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public static function limparAnalise(PDO $conexao, int $id_usuario): void
    {
        $query = "DELETE FROM analise_estoque
        WHERE analise_estoque.id_usuario = :id_usuario;
        DELETE FROM analise_estoque_header
        WHERE analise_estoque_header.id_usuario = :id_usuario;";

        $stmt = $conexao->prepare($query);

        $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function adicionaProdutosNaAnalise(PDO $conexao, array $produtos, int $id_usuario)
    {
        $sequencial = 0;
        $values = '';
        $bindIn = [];

        foreach ($produtos as $key => $value) {
            $sequencial++;

            if (
                !empty($produtos[$key]['id_produto']) &&
                !empty($produtos[$key]['nome_tamanho']) &&
                !empty($produtos[$key]['codigo_barras'])
            ) {
                $bindIn[":id_produto$sequencial"] = $produtos[$key]['id_produto'];
                $bindIn[":nome_tamanho$sequencial"] = $produtos[$key]['nome_tamanho'];
                $bindIn[":cod_barras$sequencial"] = $produtos[$key]['codigo_barras'];
                $bindIn[":id_usuario$sequencial"] = $id_usuario;
                $bindIn[":sequencia$sequencial"] = $sequencial;
                $values .= "(:id_produto$sequencial, :sequencia$sequencial, :nome_tamanho$sequencial, :id_usuario$sequencial, :cod_barras$sequencial),";
            }
        }

        $values = mb_substr($values, 0, -1);

        $query = "INSERT INTO
                    analise_estoque
                        (
                            analise_estoque.id_produto,
                            analise_estoque.sequencia,
                            analise_estoque.nome_tamanho,
                            analise_estoque.id_usuario,
                            analise_estoque.cod_barras
                        )
                VALUES
                    $values";

        $stmt = $conexao->prepare($query);

        $stmt->execute($bindIn);
    }

    public static function buscaCodigoDeBarras(PDO $conexao, int $id_produto, string $nome_tamanho): int
    {
        $query = "SELECT
                    cod_barras
                FROM
                    produtos_grade
                WHERE
                    produtos_grade.id_produto = :id_produto
                AND
                    produtos_grade.nome_tamanho = :nome_tamanho";

        $stmt = $conexao->prepare($query);

        $stmt->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
        $stmt->bindValue(':nome_tamanho', $nome_tamanho, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado[0]['cod_barras'];
    }

    public static function MensagemNotificacaoReposicaoFila(string $origem): string
    {
        $url = "{$_ENV['URL_MEULOOK']}carrinho";
        if ($origem === 'mobile') {
            $url = "{$_ENV['URL_AREA_CLIENTE']}pedido";
        }
        $mensagem = "Acabou de chegar reposição do produto que você estava aguardando. Acesse {$url} e garanta o seu antes que acabe novamente!";
        return $mensagem;
    }

    public static function verificaRemoveLocalizacao(PDO $conexao, int $idProduto): void
    {
        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.localizacao = NULL
            WHERE produtos.id = :id_produto
                AND produtos.localizacao IS NOT NULL
                AND NOT EXISTS(
                    SELECT 1
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = produtos.id
                    AND estoque_grade.id_responsavel = 1
                    AND (estoque_grade.estoque > 0 OR estoque_grade.vendido > 0)
                );"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
    }
    public static function estoqueDetalhadoPorFornecedor(int $idFornecedor, int $pagina, string $estoque): array
    {
        $qtdItens = 50;
        $offset = $qtdItens * ($pagina - 1);
        $selectFoto = "
            (
                SELECT produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id = produtos.id
                ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                LIMIT 1
            ) produto_foto";

        switch ($estoque) {
            case 'FULFILLMENT':
                $sqlProdutosGrades = "SELECT
                        produtos_grade.id_produto,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'nome_tamanho', produtos_grade.nome_tamanho,
                                'quantidade', COALESCE(
                                    (
                                        SELECT SUM(estoque_grade.estoque)
                                        FROM estoque_grade
                                        WHERE estoque_grade.id_produto = produtos_grade.id_produto
                                            AND estoque_grade.id_responsavel = 1
                                            AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                                    ), 0
                                )
                            ) ORDER BY produtos_grade.sequencia ASC),
                            ']'
                        ) json_grades,
                        produtos.valor_custo_produto,
                        COALESCE(produtos.nome_comercial, produtos.descricao, '') descricao,
                        $selectFoto
                    FROM produtos_grade
                    INNER JOIN produtos ON produtos.id = produtos_grade.id_produto
                    INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.id_responsavel = 1
                        AND estoque_grade.estoque > 0
                    WHERE produtos.id_fornecedor = :id_fornecedor
                    GROUP BY produtos_grade.id_produto
                    ORDER BY produtos.id DESC
                    LIMIT :qtd_itens OFFSET :offset;";

                $sqlProdutosQuantidade = "SELECT
                        CEIL(COUNT(produtos.id)/:qtd_itens) total_pags,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT(
                                'valor_custo_produto', produtos.valor_custo_produto,
                                'produto_estoque_total', COALESCE(
                                    (
                                        SELECT SUM(estoque_grade.estoque)
                                        FROM estoque_grade
                                        WHERE estoque_grade.id_produto = produtos.id
                                            AND estoque_grade.id_responsavel = 1
                                            AND estoque_grade.estoque > 0
                                    ), 0
                                )
                            )),
                            ']'
                        ) json_produtos
                    FROM produtos
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND EXISTS(
                            SELECT 1
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = produtos.id
                                AND estoque_grade.id_responsavel = 1
                                AND estoque_grade.estoque > 0
                        );";
                break;
            case 'EXTERNO':
                $sqlProdutosGrades = "SELECT
                        produtos_grade.id_produto,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'nome_tamanho', produtos_grade.nome_tamanho,
                                'quantidade', COALESCE(
                                    (
                                        SELECT SUM(estoque_grade.estoque)
                                        FROM estoque_grade
                                        WHERE estoque_grade.id_produto = produtos_grade.id_produto
                                            AND estoque_grade.id_responsavel = :id_fornecedor
                                            AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                                    ), 0
                                )
                            ) ORDER BY produtos_grade.sequencia ASC),
                            ']'
                        ) json_grades,
                        produtos.valor_custo_produto,
                        COALESCE(produtos.nome_comercial, produtos.descricao, '') descricao,
                        $selectFoto
                    FROM produtos_grade
                    INNER JOIN produtos ON produtos.id = produtos_grade.id_produto
                    INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.id_responsavel = :id_fornecedor
                        AND estoque_grade.estoque > 0
                    WHERE produtos.id_fornecedor = :id_fornecedor
                    GROUP BY produtos_grade.id_produto
                    ORDER BY produtos.id DESC
                    LIMIT :qtd_itens OFFSET :offset;";

                $sqlProdutosQuantidade = "SELECT
                        CEIL(COUNT(produtos.id)/:qtd_itens) total_pags,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT(
                                'valor_custo_produto', produtos.valor_custo_produto,
                                'produto_estoque_total', COALESCE(
                                    (
                                        SELECT SUM(estoque_grade.estoque)
                                        FROM estoque_grade
                                        WHERE estoque_grade.id_produto = produtos.id
                                            AND estoque_grade.id_responsavel = :id_fornecedor
                                            AND estoque_grade.estoque > 0
                                    ), 0
                                )
                            )),
                            ']'
                        ) json_produtos
                    FROM produtos
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND EXISTS(
                            SELECT 1
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = produtos.id
                                AND estoque_grade.id_responsavel = :id_fornecedor
                                AND estoque_grade.estoque > 0
                        );";
                break;
            case 'AGUARD_ENTRADA':
                $sqlProdutosGrades = "SELECT
                        produtos_aguarda_entrada_estoque.id_produto,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'nome_tamanho', produtos_aguarda_entrada_estoque.nome_tamanho,
                                'quantidade', produtos_aguarda_entrada_estoque.qtd,
                                'defeito', FALSE,
                                'descricao_defeito', '',
                                'data_hora', produtos_aguarda_entrada_estoque.data_hora
                            )),
                            ']'
                        ) json_grades,
                        produtos.valor_custo_produto,
                        COALESCE(produtos.nome_comercial, produtos.descricao, '') descricao,
                        $selectFoto
                    FROM produtos_aguarda_entrada_estoque
                    INNER JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
                    GROUP BY produtos_aguarda_entrada_estoque.id
                    ORDER BY produtos_aguarda_entrada_estoque.data_hora ASC
                    LIMIT :qtd_itens OFFSET :offset;";

                $sqlProdutosQuantidade = "SELECT
                        CEIL(COUNT(produtos.id)/:qtd_itens) total_pags,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT(
                                'valor_custo_produto', produtos.valor_custo_produto,
                                'produto_estoque_total', COALESCE(
                                    (
                                        SELECT SUM(produtos_aguarda_entrada_estoque.qtd)
                                        FROM produtos_aguarda_entrada_estoque
                                        WHERE produtos_aguarda_entrada_estoque.id_produto = produtos.id
                                            AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
                                    ), 0
                                )
                            )),
                            ']'
                        ) json_produtos
                    FROM produtos
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND EXISTS(
                            SELECT 1
                            FROM produtos_aguarda_entrada_estoque
                            WHERE produtos_aguarda_entrada_estoque.id_produto = produtos.id
                                AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
                        );";
                break;
            case 'PONTO_RETIRADA':
                $sqlProdutosGrades = "SELECT
                        entregas_devolucoes_item.id_produto,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'nome_tamanho', entregas_devolucoes_item.nome_tamanho,
                                'quantidade', COALESCE(
                                    (
                                        SELECT COUNT(entregas_devolucoes_item_qtd.uuid_produto)
                                        FROM entregas_devolucoes_item AS entregas_devolucoes_item_qtd
                                        WHERE entregas_devolucoes_item_qtd.id_produto = entregas_devolucoes_item.id_produto
                                            AND entregas_devolucoes_item_qtd.nome_tamanho = entregas_devolucoes_item.nome_tamanho
                                            AND entregas_devolucoes_item_qtd.situacao NOT IN ('CO', 'RE')
                                    ), 0
                                ),
                                'defeito', entregas_devolucoes_item.tipo = 'DE',
                                'descricao_defeito', IF(entregas_devolucoes_item.tipo = 'DE', COALESCE(
                                    (
                                        SELECT troca_fila_solicitacoes.descricao_defeito
                                        FROM troca_fila_solicitacoes
                                        WHERE troca_fila_solicitacoes.uuid_produto = entregas_devolucoes_item.uuid_produto
                                    ), 'Defeito sem descrição'
                                ), (
                                    SELECT
                                        lancamento_financeiro.observacao
                                    FROM lancamento_financeiro
                                    WHERE
                                        lancamento_financeiro.transacao_origem = entregas_devolucoes_item.id_transacao
                                    AND lancamento_financeiro.origem = 'TR'
                                    AND lancamento_financeiro.numero_documento = entregas_devolucoes_item.uuid_produto
                                )),
                                'data_hora', entregas_devolucoes_item.data_atualizacao
                            )),
                            ']'
                        ) json_grades,
                        produtos.valor_custo_produto,
                        COALESCE(produtos.nome_comercial, produtos.descricao, '') descricao,
                        $selectFoto
                    FROM entregas_devolucoes_item
                    INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND entregas_devolucoes_item.situacao = 'PE'
                    GROUP BY entregas_devolucoes_item.id
                    ORDER BY entregas_devolucoes_item.data_atualizacao ASC
                    LIMIT :qtd_itens OFFSET :offset;";

                $sqlProdutosQuantidade = "SELECT
                        CEIL(COUNT(produtos.id)/:qtd_itens) total_pags,
                        CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT(
                                'valor_custo_produto', produtos.valor_custo_produto,
                                'produto_estoque_total', COALESCE(
                                    (
                                        SELECT COUNT(entregas_devolucoes_item.id)
                                        FROM entregas_devolucoes_item
                                        WHERE entregas_devolucoes_item.id_produto = produtos.id
                                            AND entregas_devolucoes_item.situacao = 'PE'
                                    ), 0
                                )
                            )),
                            ']'
                        ) json_produtos
                    FROM produtos
                    WHERE produtos.id_fornecedor = :id_fornecedor
                        AND EXISTS(
                            SELECT 1
                            FROM entregas_devolucoes_item
                            WHERE entregas_devolucoes_item.id_produto = produtos.id
                                AND entregas_devolucoes_item.situacao = 'PE'
                        );";
                break;
            default:
                throw new InvalidArgumentException('Estoque não informado');
        }
        $produtos = DB::select($sqlProdutosGrades, [
            'id_fornecedor' => $idFornecedor,
            'qtd_itens' => $qtdItens,
            'offset' => $offset,
        ]);

        $resultado['produtos'] = array_map(function (array $produto) use ($estoque): array {
            if (in_array($estoque, ['FULFILLMENT', 'EXTERNO'])) {
                $produto['descricao'] = "{$produto['id_produto']} - {$produto['descricao']}";
                unset($produto['id_produto']);
            } else {
                $produto['id_produto'] = (int) $produto['id_produto'];
                $produto['grades'] = array_map(function (array $grade): array {
                    $grade['descricao_defeito'] = preg_replace('/_/', ' ', $grade['descricao_defeito']);
                    $grade['defeito'] = (bool) $grade['defeito'];
                    $grade['data_hora'] = date_format(date_create($grade['data_hora']), 'd/m/Y H:i');

                    return $grade;
                }, $produto['grades']);
            }
            $produto['total_produto'] = array_sum(array_column($produto['grades'], 'quantidade'));
            $produto['valor_total_produto'] = round($produto['valor_custo_produto'] * $produto['total_produto'], 2);

            return $produto;
        }, $produtos);

        $todosProdutos = DB::selectOne($sqlProdutosQuantidade, [
            'id_fornecedor' => $idFornecedor,
            'qtd_itens' => $qtdItens,
        ]);
        $todosProdutos['produtos'] = array_map(
            function (array $produto): array {
                $produto['valor_total'] = round($produto['valor_custo_produto'] * $produto['produto_estoque_total'], 2);

                return $produto;
            },
            $todosProdutos['produtos'] ?: []
        );
        $resultado['quantidade_total'] = array_sum(array_column($todosProdutos['produtos'], 'produto_estoque_total'));
        $resultado['valor_total'] = round(array_sum(array_column($todosProdutos['produtos'], 'valor_total')), 2);
        $resultado['mais_pags'] = $todosProdutos['total_pags'] - $pagina > 0;

        return $resultado;
    }

    public static function buscaProdutoPorCodBarras(PDO $conexao, int $codBarras): array
    {
        $query = "SELECT
                    produtos_grade.id_produto,
                    CONCAT(
                        produtos.descricao, ' ', COALESCE(produtos.cores, '')
                    ) descricao,
                    (
						SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
						WHERE produtos_foto.id = produtos_grade.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                        LIMIT 1
                    ) foto_produto
                FROM
                    produtos_grade
                INNER JOIN
                    produtos ON produtos.id = produtos_grade.id_produto
                WHERE
                    produtos_grade.cod_barras = :cod_barras";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public static function buscarPainelLocalizacao(PDO $conexao, int $idPainel): bool
    {
        $query = "SELECT
                    localizacao_estoque.local
                FROM
                    localizacao_estoque
                WHERE
                    localizacao_estoque.local = :id_painel";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_painel', $idPainel, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = (bool) $stmt->fetchColumn();

        return $resultado;
    }

    public static function atualizaLocalizacaoProduto(
        PDO $conexao,
        int $idProduto,
        int $antigaLocalizacao,
        ?int $novaLocalizacao,
        int $idUsuario,
        int $quantidade
    ): void {
        $query = "UPDATE
                    produtos
                SET
                    produtos.localizacao = :id_localizacao
                WHERE produtos.id = :id_produto";

        $stmt = $conexao->prepare($query);
        $tipo = is_null($novaLocalizacao) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $stmt->bindValue(':id_localizacao', $novaLocalizacao, $tipo);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->execute();

        $query = "INSERT INTO log_produtos_localizacao
                    (id_produto, old_localizacao, new_localizacao, qtd_entrada, usuario)
                VALUES
                (
                    :id_produto,
                    :old_localizacao,
                    :new_localizacao,
                    :qtd_entrada,
                    :usuario
                )";

        $novaLocalizacao ??= 0;
        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':old_localizacao', $antigaLocalizacao, PDO::PARAM_INT);
        $stmt->bindValue(':new_localizacao', $novaLocalizacao, PDO::PARAM_INT);
        $stmt->bindValue(':qtd_entrada', $quantidade, PDO::PARAM_INT);
        $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    //     public static function buscalistaLocalizacaoLogs(PDO $conexao, string $pesquisa): array
    // {
    //         $query = "SELECT
    //                     log_produtos_localizacao.id_produto,
    //                     CONCAT
    //                         (
    //                             log_produtos_localizacao.id_produto,' - ', produtos.descricao,' ', produtos.cores
    //                         ) descricao,
    //                     log_produtos_localizacao.old_localizacao,
    //                     log_produtos_localizacao.new_localizacao,
    //                     log_produtos_localizacao.qtd_entrada,
    //                     (
    //                         SELECT
    //                             usuarios.nome
    //                             FROM usuarios
    //                             WHERE usuarios.id = log_produtos_localizacao.usuario
    //                     ) usuario,
    //                     DATE_FORMAT(log_produtos_localizacao.data_hora,'%d/%m/%Y %H:%i:%s') data_alteracao
    //                     FROM log_produtos_localizacao
    //                     INNER JOIN produtos ON produtos.id = log_produtos_localizacao.id_produto
    //                     WHERE produtos.descricao LIKE '%:pesquisa%' OR produtos.id = :pesquisa ORDER BY log_produtos_localizacao.data_hora DESC";

    //         $stmt = $conexao->prepare($query);
    //         $stmt->bindValue(":pesquisa", $pesquisa, PDO::PARAM_STR);
    //         $stmt->execute();

    //         $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         return $resultado ?: [];
    //     }

    public static function buscaDevolucoesAguardandoEntrada(PDO $conexao, int $codBarras): array
    {
        $query = "SELECT
                produtos_aguarda_entrada_estoque.id id_devolucao,
                produtos_aguarda_entrada_estoque.id_produto,
                COALESCE(produtos.localizacao, '1') localizacao,
                produtos_aguarda_entrada_estoque.identificao AS uuid_produto,
                produtos_aguarda_entrada_estoque.data_hora,
                produtos_aguarda_entrada_estoque.nome_tamanho,
                produtos_grade.cod_barras,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) `nome_produto`,
                (
					SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_aguarda_entrada_estoque.id_produto
						AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                ) `foto_produto`,
                (
					SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_aguarda_entrada_estoque.id_produto
						AND produtos_foto.tipo_foto = 'SM'
                        LIMIT 1
                ) `foto_produto_sm`,
                (
					SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id = produtos_aguarda_entrada_estoque.usuario
                ) `usuario`
            FROM produtos_aguarda_entrada_estoque
            INNER JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
            INNER JOIN produtos_grade
                ON produtos_grade.id_produto = produtos_aguarda_entrada_estoque.id_produto
                AND produtos_grade.nome_tamanho = produtos_aguarda_entrada_estoque.nome_tamanho
            WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.tipo_entrada = 'TR'";

        if ($codBarras !== 0) {
            $query .= ' AND produtos_grade.cod_barras = :cod_barras ';
        }

        $stmt = $conexao->prepare($query);

        if ($codBarras !== 0) {
            $stmt->bindValue(':cod_barras', $codBarras, PDO::PARAM_STR);
        }

        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }
}
