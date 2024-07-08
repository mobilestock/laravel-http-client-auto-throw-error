<?php

namespace MobileStock\repository;

use Aws\S3\S3Client;
use Error;
use Exception;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Gate as FacadesGate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Globals;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\Origem;
use MobileStock\model\PedidoItem;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoModel;
use MobileStock\service\ReposicoesService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use MobileStock\service\ReputacaoFornecedoresService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProdutosRepository
{
    public static function salvaProduto(PDO $conexao, Produto $produtoCadastrar)
    {
        $gerador = new GeradorSql($produtoCadastrar);

        if ($produtoCadastrar->getId() > 0) {
            $sql = $gerador->update();
        } else {
            $sql = $gerador->insert();
        }

        $conexao->prepare($sql)->execute($gerador->bind);

        if (!$produtoCadastrar->getId()) {
            $produtoCadastrar->setId($conexao->lastInsertId());
        }
    }

    public static function buscaLinhas(PDO $conexao): array
    {
        return DB::select('SELECT * FROM linha');
    }

    public static function tirarDeLinha(PDO $conexao, int $idProduto)
    {
        // Caso queira verificar o estado atual...
        // $estadoProduto = DB::select('SELECT fora_de_linha FROM produtos WHERE id = $idProduto');
        // if(!isset($estadoProduto)):
        //     throw new Error("Ocorreu um erro ao verificar o produto com id: ".$idProduto, 500);
        // endif;

        $updateString = "UPDATE produtos SET
                    produtos.fora_de_linha = '1'
                    WHERE produtos.id = $idProduto";
        $param = $conexao->prepare($updateString);
        if (!$param->execute()) {
            throw new Error('Erro ao tirar o produto de linha!', 500);
        }
    }

    public static function buscaProdutosFornecedor(
        PDO $conexao,
        int $idFornecedor,
        int $pagina,
        string $pesquisa,
        bool $ehPesquisaLiteral,
        int $porPagina,
        bool $foraDeLinha
    ): array {
        $where = '';
        $gate = app(Gate::class);
        $offset = ($pagina - 1) * $porPagina;
        if ($offset > PHP_INT_MAX) {
            $offset = 0;
        }
        if (!$ehPesquisaLiteral || empty($pesquisa) || !$gate->allows('ADMIN')) {
            $where .= ' AND produtos.id_fornecedor = :id_fornecedor ';
            $where .= ' AND produtos.fora_de_linha = :fora_de_linha ';
        }
        if (!empty($pesquisa)) {
            if ($ehPesquisaLiteral) {
                $where .= ' AND produtos.id = :pesquisa ';
            } else {
                $where .= " AND LOWER(CONCAT_WS(
                    ' - ',
                    produtos.id,
                    produtos.descricao,
                    produtos.nome_comercial
                )) REGEXP LOWER(:pesquisa) ";
            }
        }

        $sql = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.descricao,
                produtos.id_fornecedor,
                produtos.bloqueado,
                produtos.id_linha,
                produtos.grade,
                produtos.tipo_grade,
                produtos.destaque,
                produtos.outras_informacoes,
                produtos.grade_min,
                produtos.grade_max,
                produtos.sexo,
                produtos.nome_comercial,
                produtos.especial,
                produtos.fora_de_linha,
                produtos.permitido_reposicao,
                produtos.embalagem,
                produtos.forma,
                produtos.valor_venda_ms,
                produtos.valor_venda_ml,
                COALESCE(produtos.cores, '')cores,
                produtos.valor_custo_produto,
                COALESCE((
                    SELECT GROUP_CONCAT(produtos_categorias.id_categoria)
                    FROM produtos_categorias
                    WHERE produtos_categorias.id_produto = produtos.id
                ), '')array_id_categoria,
                EXISTS(
                    SELECT 1
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = produtos.id
                    AND estoque_grade.id_responsavel = 1
                )consignado,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'nome_tamanho', produtos_grade.nome_tamanho,
                        'sequencia', produtos_grade.sequencia,
                        'desabilitado', 1
                    ) ORDER BY produtos_grade.sequencia ASC)
                    ,']'
                )grades,
                (
                    SELECT COALESCE(ROUND(SUM(avaliacao_produtos.qualidade) / COUNT(avaliacao_produtos.id_produto)), 0)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = produtos.id
                        AND avaliacao_produtos.data_avaliacao IS NOT NULL
                        AND avaliacao_produtos.origem = 'MS'
                )rating,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT
                                    IF((COALESCE(produtos_foto.caminho, '') <> ''), JSON_OBJECT(
                                        'caminho', produtos_foto.caminho,
                                        'foto_preview', produtos_foto.caminho,
                                        'foto_calcada', produtos_foto.foto_calcada,
                          	            'foto_salva', TRUE,
                                        'tipo_foto', produtos_foto.tipo_foto,
                                        'id_usuario', produtos_foto.id_usuario,
                                        'sequencia', produtos_foto.sequencia
                                    ), NULL)
                                )
                    ,']'
                )fotos
            FROM produtos
            LEFT OUTER JOIN produtos_grade ON produtos_grade.id_produto = produtos.id
            LEFT OUTER JOIN produtos_foto ON NOT produtos_foto.tipo_foto = 'SM'
                AND produtos_foto.id = produtos.id
            WHERE TRUE $where
            GROUP BY produtos.id
            ORDER BY produtos.id DESC
            LIMIT :itens_por_pag OFFSET :offset;

            SELECT CEIL(COUNT(produtos.id)/:itens_por_pag) AS `qtd_paginas`
            FROM produtos
            WHERE TRUE $where;"
        );
        if (!$ehPesquisaLiteral || empty($pesquisa) || !$gate->allows('ADMIN')) {
            $sql->bindValue(':fora_de_linha', (int) $foraDeLinha, PDO::PARAM_INT);
            $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        }
        if (!empty($pesquisa)) {
            $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $sql->bindValue(':itens_por_pag', $porPagina, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        $sql->execute();
        $consulta = $sql->fetchAll(PDO::FETCH_ASSOC);

        $sql->nextRowset();
        $qtdPaginas = (int) $sql->fetchColumn();

        $consulta = array_map(function ($item) {
            $item['id'] = (int) $item['id'];
            $item['id_fornecedor'] = (int) $item['id_fornecedor'];
            $item['bloqueado'] = (bool) $item['bloqueado'];
            $item['destaque'] = (bool) $item['destaque'];
            $item['especial'] = (bool) $item['especial'];
            $item['fora_de_linha'] = (bool) $item['fora_de_linha'];
            $item['permitido_repor'] = (bool) $item['permitido_reposicao'];
            $item['valor_venda_ms'] = (float) $item['valor_venda_ms'];
            $item['valor_venda_ml'] = (float) $item['valor_venda_ml'];
            $item['valor_custo_produto'] = (float) $item['valor_custo_produto'];
            $item['array_id_categoria'] = explode(',', $item['array_id_categoria']);
            $item['cores'] = explode(' ', $item['cores']);
            $item['cores'] = preg_replace('/_/', ' ', $item['cores']);
            $item['listaFotosRemover'] = [];
            $item['listaFotosCalcadasAdd'] = [];
            $item['listaFotosCatalogoAdd'] = [];
            $item['listaFotosPendentes'] = [];
            $item['fotos'] = !$item['fotos'] ? [] : json_decode($item['fotos'], true);
            $item['fotos'] = array_values(
                array_map(function (array $foto) {
                    $foto['foto_calcada'] = (bool) $foto['foto_calcada'];
                    $foto['foto_salva'] = (bool) $foto['foto_salva'];
                    return $foto;
                }, $item['fotos'])
            );
            usort($item['fotos'], fn(array $a, array $b): int => $a['sequencia'] - $b['sequencia']);

            $item['grades'] = json_decode($item['grades'], true);
            $item['grades'] = array_values(
                array_map(function ($grade) {
                    $grade = (array) $grade;
                    $grade['desabilitado'] = (bool) $grade['desabilitado'];
                    return $grade;
                }, $item['grades'])
            );
            unset($item['permitido_reposicao']);
            $item['incompleto'] =
                empty($item['descricao']) ||
                empty($item['nome_comercial']) ||
                empty($item['cores']) ||
                !in_array($item['id_linha'], [0, 1, 2]) ||
                sizeof($item['array_id_categoria']) !== 2 ||
                !in_array($item['tipo_grade'], [1, 2, 3, 4]) ||
                (in_array($item['tipo_grade'], [1, 3]) && empty($item['forma']));

            return $item;
        }, $consulta);
        return ['items' => $consulta, 'qtd_paginas' => $qtdPaginas];
    }

    public static function produtoExisteRegistroNoSistema(string $id): bool
    {
        $result = FacadesDB::selectOne(
            "SELECT (EXISTS(SELECT 1 FROM reposicoes_grades WHERE reposicoes_grades.id_produto = :idProduto)
                    OR EXISTS(SELECT
                                   1
                                FROM transacao_financeiras_produtos_itens
                                WHERE transacao_financeiras_produtos_itens.id_produto = :idProduto)
                    OR EXISTS(SELECT
                                  1
                                FROM logistica_item
                                WHERE logistica_item.id_produto = :idProduto
                        )
                    OR EXISTS(SELECT
                                  1
                                FROM entregas_faturamento_item
                                WHERE entregas_faturamento_item.id_produto = :idProduto
                        )
                    ) as exists",
            ['idProduto' => $id]
        );

        return $result->exists;
    }

    public static function insereFotos(
        PDO $conexao,
        array $listaFotosAdd,
        int $idProduto,
        string $descricao,
        int $idUsuario
    ): void {
        ob_start();
        require_once __DIR__ . '/../../classes/produtos.php';
        require_once __DIR__ . '/../../regras/alertas.php';
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../classes/produtos.php';
        require_once __DIR__ . '/../../controle/produtos-insere-fotos.php';
        ob_clean();
        $_FILES = $listaFotosAdd;

        // Colocado pois a função está dando alguns warnings e essa vai ser a solucao paleativa
        @insereFotosProduto($idProduto, $_FILES, $descricao, $idUsuario, $conexao);
    }

    public static function removeFotos(PDO $conexao, array $listaFotosRemover, int $idProduto, int $idUsuario): void
    {
        $s3 = new S3Client(Globals::S3_OPTIONS());

        $listaFotosRemover = array_map(function ($foto) {
            return (int) $foto;
        }, $listaFotosRemover);

        $listaFotosRemover = implode(',', $listaFotosRemover);

        $query = $conexao->prepare("SELECT
                                        produtos_foto.id_usuario,
                                        produtos_foto.nome_foto,
                                        produtos_foto.caminho
                                    FROM produtos_foto
                                    WHERE produtos_foto.id = :idProduto
                                    AND produtos_foto.sequencia IN ($listaFotosRemover)");
        $query->execute([':idProduto' => $idProduto]);
        $caminhosProdutos = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($caminhosProdutos as $key => $linha) {
            $fotosComMesmoCaminho = ProdutosRepository::verificaSeExisteFotoComCaminhoIgual(
                $conexao,
                $linha['caminho']
            );
            if (count($fotosComMesmoCaminho) > 1) {
                throw new InvalidArgumentException('Essa foto está ligada a outro produto, você não pode apagá-la');
            }

            $gate = app(Gate::class);
            if (!$gate->allows('ADMIN') && $idUsuario !== (int) $linha['id_usuario']) {
                throw new InvalidArgumentException('Não é possivel remover uma foto de outra pessoa');
            }
            if ($_ENV['AMBIENTE'] === 'producao') {
                try {
                    $s3->deleteObject([
                        'Bucket' => 'mobilestock-s3',
                        'Key' => $linha['nome_foto'],
                    ]);
                } catch (Exception $ignorado) {
                }
            }
        }
        $conexao
            ->prepare(
                "DELETE FROM produtos_foto WHERE produtos_foto.id = ? AND produtos_foto.sequencia IN ($listaFotosRemover)"
            )
            ->execute([$idProduto]);
    }

    public static function insereRegistroAcessoProduto(PDO $conexao, int $id, string $origem, int $idColaborador)
    {
        $stmt = $conexao->prepare(
            "INSERT INTO produtos_acessos (
                produtos_acessos.id_produto,
                produtos_acessos.origem,
                produtos_acessos.id_colaborador
            ) VALUES (
                :id,
                :origem,
                :id_colaborador
            )"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function removeProduto(PDO $conexao, int $idProduto): void
    {
        $sql = $conexao->prepare(
            "DELETE FROM estoque_grade
            WHERE estoque_grade.id_produto = :id_produto;

            DELETE FROM produtos_grade
            WHERE produtos_grade.id_produto = :id_produto;

            UPDATE produtos_foto
            SET produtos_foto.id = 0
            WHERE produtos_foto.id = :id_produto;

            DELETE FROM produtos_foto
            WHERE produtos_foto.id = 0;

            DELETE FROM produtos
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();

        $linhasAfetadas = 0;

        do {
            $linhasAfetadas += $sql->rowCount();
        } while ($sql->nextRowset());

        if ($linhasAfetadas < 1) {
            throw new Exception('Não foi possível deletar o produto corretamente, consultar equipe de T.I.');
        }
    }
    public static function buscaProdutosPromocao(): array
    {
        $produtos = FacadesDB::select(
            "SELECT
                produtos.id,
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                        LIMIT 1
                    ),
                    '{$_ENV['URL_MOBILE']}images/img-placeholder.png'
                ) fotoUrl,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) json_grade
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.estoque > 0
            INNER JOIN logistica_item ON logistica_item.id_produto = produtos.id
            WHERE produtos.bloqueado = 0
                AND produtos.promocao = 1
                AND produtos.id_fornecedor = :id_fornecedor
            GROUP BY produtos.id
            ORDER BY produtos.id DESC",
            [
                'id_fornecedor' => FacadesGate::allows('ADMIN') ? 12 : Auth::user()->id_colaborador,
            ]
        );

        $produtos = array_map(function (array $produto): array {
            $produto['gradetotal'] = 0;
            foreach ($produto['grade'] as $grade) {
                $produto['gradetotal'] += $grade['estoque'];
            }
            $produto['promocao'] = true;
            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function buscaProdutosPromocaoDisponiveis(): array
    {
        $produtos = FacadesDB::select(
            "SELECT
                produtos.id,
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                        LIMIT 1
                    ),
                    '{$_ENV['URL_MOBILE']}images/img-placeholder.png'
                ) fotoUrl,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) json_grade
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.estoque > 0
            INNER JOIN logistica_item ON logistica_item.id_produto = produtos.id
            WHERE produtos.bloqueado = 0
                AND produtos.promocao = 0
                AND produtos.id_fornecedor = :id_fornecedor
                AND produtos.data_ultima_entrega IS NOT NULL
            GROUP BY produtos.id
            ORDER BY produtos.data_atualizou_valor_custo,
                produtos.id DESC",
            [
                'id_fornecedor' => FacadesGate::allows('ADMIN') ? 12 : Auth::user()->id_colaborador,
            ]
        );

        $produtos = array_map(function (array $produto): array {
            $produto['gradetotal'] = 0;
            foreach ($produto['grade'] as $grade) {
                $produto['gradetotal'] += $grade['estoque'];
            }
            $produto['promocao'] = false;
            return $produto;
        }, $produtos);

        return $produtos;
    }

    public static function consultaProdutosFornecedorPerfil(string $usuarioMeuLook, int $pagina, string $filtro): array
    {
        $porPagina = 100;
        $offset = ($pagina - 1) * $porPagina;

        if ($filtro === 'MAIS_VENDIDOS') {
            $order = 'ORDER BY produtos.quantidade_vendida DESC';
        } else {
            $order = 'ORDER BY produtos.id DESC';
        }

        $consulta = FacadesDB::select(
            "SELECT
                produtos.id,
                produtos.quantidade_vendida,
                produtos.nome_comercial,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) `foto`,
                IF (
                    produtos.preco_promocao > 0,
                    produtos.valor_venda_ml_historico,
                    0
                ) `valor_venda_ml_historico`,
                produtos.valor_venda_ml,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) grades_json,
                reputacao_fornecedores.reputacao,
                produtos.preco_promocao `desconto`
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
            WHERE colaboradores.usuario_meulook = ?
              AND estoque_grade.estoque > 0
              AND produtos.bloqueado = 0
            GROUP BY produtos.id
            $order
            LIMIT $porPagina OFFSET $offset",
            [$usuarioMeuLook]
        );

        # @issue: https://github.com/mobilestock/backend/issues/397
        $consulta = array_map(function (array $item): array {
            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grades']);
            $categoria = (object) [];
            if ($item['desconto'] > 0) {
                $categoria->tipo = 'PROMOCAO';
                $categoria->valor = "{$item['desconto']}%";
            }
            if ($item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE) {
                $categoria->tipo = $item['reputacao'];
            }

            $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda_ml']);

            return [
                'id_produto' => $item['id'],
                'nome' => $item['nome_comercial'],
                'preco' => $item['valor_venda_ml'],
                'preco_original' => $item['valor_venda_ml_historico'],
                'valor_parcela' => $valorParcela,
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                'quantidade_vendida' => $item['quantidade_vendida'],
                'foto' => $item['foto'],
                'grades' => $grades,
                'categoria' => $categoria,
            ];
        }, $consulta);

        return $consulta;
    }

    public function buscaAvaliacaoProduto(PDO $conexao, int $id): array
    {
        $horasEspera = ConfiguracaoService::produtosPromocoes($conexao)['HORAS_ESPERA_REATIVAR_PROMOCAO'];

        $stmt = $conexao->prepare(
            "SELECT produtos.valor_custo_produto,
                produtos.valor_venda_ml,
                produtos.valor_venda_ms,
                produtos.preco_promocao,
                produtos.porcentagem_comissao_ml,
                produtos.porcentagem_comissao_ms,
                produtos.data_ultima_entrega,
                produtos.data_atualizou_valor_custo
            FROM produtos
            WHERE produtos.id = :idProduto"
        );
        $stmt->bindValue(':idProduto', $id, PDO::PARAM_INT);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new NotFoundHttpException('Produto não encontrado');
        }
        // https://github.com/mobilestock/backend/issues/153
        date_default_timezone_set('America/Sao_Paulo');

        $dataAgora = new Carbon();

        $precoPromocao = (int) $produto['preco_promocao'];
        $tempoRestanteAtivarPromocao = '';
        $faltaUmaEntregaParaAtivarPromocao = false;

        if ($precoPromocao === 0) {
            $faltaUmaEntregaParaAtivarPromocao = empty($produto['data_ultima_entrega']);

            if (!empty($produto['data_atualizou_valor_custo'])) {
                $dataAtualizouPromocao = new Carbon($produto['data_atualizou_valor_custo']);
                $dataUltimaEntrega = new Carbon($produto['data_ultima_entrega']);
                $dataPodeAtivarPromocao = $dataAtualizouPromocao->addHour($horasEspera);

                if ($dataAgora < $dataPodeAtivarPromocao) {
                    $tempoRestanteAtivarPromocao = $dataAgora->diffForHumans($dataPodeAtivarPromocao, true, false, 2);
                }

                $faltaUmaEntregaParaAtivarPromocao =
                    $dataUltimaEntrega < new Carbon($produto['data_atualizou_valor_custo']);
            }
        }

        return [
            'porcentagemComissaoML' => (float) $produto['porcentagem_comissao_ml'],
            'porcentagemComissaoMS' => (float) $produto['porcentagem_comissao_ms'],
            'porcentagemPromocao' => $precoPromocao,
            'valorVendaML' => (float) $produto['valor_venda_ml'],
            'valorVendaMS' => (float) $produto['valor_venda_ms'],
            'valorBase' => (float) $produto['valor_custo_produto'],
            'tempoRestanteAtivarPromocao' => $tempoRestanteAtivarPromocao,
            'faltaUmaEntregaParaAtivarPromocao' => $faltaUmaEntregaParaAtivarPromocao,
        ];
    }

    //    public static function calculaValorFinal(float $valorBase, int $porcentagemPromocao, float $porcentagemComissao): string
    //    {
    //        $total = (($valorBase - ($valorBase * ($porcentagemPromocao / 100))) * (1 + ($porcentagemComissao / (100 - $porcentagemComissao))));;
    //        return $total;
    //    }

    public static function retornaValorProduto(PDO $conexao, int $idProduto): array
    {
        $sql = "SELECT
                produtos.valor_venda_ms valor,
                produtos.valor_custo_produto,
                produtos.id_fornecedor,
                (
                    SELECT estoque_grade.id_responsavel
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = produtos.id
                    ORDER BY estoque_grade.id_responsavel ASC
                    LIMIT 1
                ) id_responsavel_estoque
                FROM produtos
                WHERE produtos.id = :idProduto;";
        $result = $conexao->prepare($sql);
        $result->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
        $result->execute();
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    // public static function buscaEstoqueProdutoTamanho(PDO $conexao, int $produto, int $tamanho)
    // {
    //     $query = "SELECT estoque FROM estoque_grade
    //         WHERE id_produto={$produto} AND tamanho = {$tamanho} AND id_responsavel = 1;";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetch(PDO::FETCH_ASSOC);
    // }

    // public static function buscaEstoqueProduto(PDO $conexao, int $produto)
    // {
    //     $query = "SELECT tamanho, estoque FROM estoque_grade
    //         WHERE id_produto={$produto} AND id_responsavel = 1;";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public function buscaProdutoEspecifico(PDO $conexao, int $id): array
    // {

    //     $resposta = $conexao
    //         ->query("SELECT
    //                 produtos.id,
    //                 produtos.descricao,
    //                 produtos.valor_venda_cpf cpf,
    //                 produtos.valor_venda_cnpj cnpj,
    //                 (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.sequencia = 1 AND produtos_foto.id = produtos.id)  foto,
    //                 CONCAT(
    //                 '[',
    //                 GROUP_CONCAT(
    //                     JSON_OBJECT(
    //                     'tamanho', estoque_grade.nome_tamanho,
    //                     'quantidade', estoque_grade.estoque
    //                     )
    //                 ),
    //                 ']' ) grade
    //             FROM
    //                 produtos
    //                 INNER JOIN estoque_grade ON (estoque_grade.id_produto = produtos.id)
    //             WHERE
    //                 produtos.id = $id
    //                 AND estoque_grade.id_responsavel = 1")
    //         ->fetch(PDO::FETCH_ASSOC);

    //     $grade = json_decode($resposta['grade']);

    //     $resposta['grade'] = $grade;

    //     return $resposta;
    // }

    // public function buscaProdutosCatalogo(\PDO $conn, string $filtro, int $pagina, int $itens)
    // {
    //     return $conn->query("SELECT p.id,
    //     p.descricao,
    //     p.valor_venda_cpf cpf,
    //     p.valor_venda_cnpj cnpj,
    //     (SELECT pf.caminho FROM   produtos_foto pf WHERE  pf.id = p.id LIMIT  1) foto,
    //     p.preco_promocao promocao,
    //     (SELECT SUM(eg.estoque) FROM estoque_grade eg WHERE eg.id_produto=p.id AND eg.id_responsavel = 1)estoque
    //     FROM produtos p
    //     WHERE
    //     p.bloqueado=0 AND p.preco_promocao <> 100 AND p.especial = 0 {$filtro} HAVING estoque > 0
    //     LIMIT {$pagina},{$itens};")->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public static function buscaEstoque(\PDO $conexao, int $idProduto): array
    // {
    //     $sql = $conexao->prepare(
    //         "SELECT
    //             estoque_grade.estoque,
    //             estoque_grade.nome_tamanho,
    //             (
    //                 SELECT COALESCE(DATE_FORMAT(compras.data_previsao,'%d-%m-%Y'),'SEM PREVISAO')
    //                 FROM compras_itens
    //                 INNER JOIN compras ON compras.id = compras_itens.id_compra
    //                 INNER JOIN compras_itens_grade ON compras_itens_grade.id_produto = compras_itens.id_produto
    //                     AND compras_itens_grade.id_compra = compras_itens.id_compra
    //                     AND compras_itens_grade.id_sequencia = compras_itens.sequencia
    //                     AND compras_itens.id_situacao = 1
    //                 WHERE compras_itens.id_produto = estoque_grade.id_produto
    //                 ORDER BY compras.data_previsao DESC LIMIT 1
    //             ) previsao
    //         FROM estoque_grade
    //         WHERE estoque_grade.id_produto = :id_produto
    //             AND estoque_grade.id_responsavel = 1
    //         ORDER BY estoque_grade.sequencia ASC;"
    //     );
    //     $sql->bindValue("id_produto", $idProduto, PDO::PARAM_INT);
    //     $sql->execute();
    //     $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

    //     return $grades;
    // }

    public function buscaProdutosPorNomeDescricaoID(string $busca = '')
    {
        $conexao = Conexao::criarConexao();
        $sql = $conexao->prepare(
            "SELECT
                produtos.descricao nome,
                produtos.id,
                produtos.nome_comercial
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            INNER JOIN produtos_foto ON produtos_foto.id = produtos.id AND produtos_foto.tipo_foto = 'MD'
            WHERE estoque_grade.id_responsavel = 1 AND
                produtos.bloqueado = 0 AND
                estoque_grade.estoque > 0 AND
                produtos.preco_promocao <> 100 AND
                UPPER(produtos.descricao) REGEXP UPPER(:busca)
                OR produtos.id REGEXP :busca
                OR UPPER(produtos.nome_comercial) REGEXP UPPER(:busca)
            GROUP BY produtos.id
            LIMIT 5;"
        );
        $sql->bindValue(':busca', $busca, PDO::PARAM_STR);
        $sql->execute();
        $retorno = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $retorno;
    }

    // public static function buscaProduto(PDO $conexao, int $idProduto)
    // {
    //     $sql = "SELECT id_fornecedor FROM produtos WHERE id = {$idProduto};";
    //     $resultado = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }

    public static function pesquisaProdutos(
        string $pesquisa,
        string $ordenar,
        array $linhas,
        array $sexos,
        array $tamanho,
        array $cores,
        array $categorias,
        array $reputacoes,
        array $fornecedores,
        string $estoque,
        string $tipo,
        int $pagina,
        string $origem
    ): array {
        $where = 'TRUE';
        $order = ['TRUE'];
        $limit = 1;
        $offset = ($pagina - 1) * $limit;
        $binds = [];

        $resultados = [
            'parametros' => [
                'linhas' => [],
                'sexos' => [],
                'numeros' => [],
                'cores' => [],
                'categorias' => [],
                'reputacoes' => [],
                'fornecedores' => [],
                'estoque' => [],
                'pagina' => $pagina,
            ],
            'produtos' => [],
        ];

        if (is_numeric($pesquisa)) {
            $where = "produtos.id = $pesquisa";
        } else {
            $tipoCliente = 'CLIENTE_NOVO';
            if (Auth::check()) {
                if (FacadesGate::allows('FORNECEDOR')) {
                    $fornecedores[] = Auth::user()->id_colaborador;
                    $tipoCliente = 'SELLER';
                } elseif (EntregasFaturamentoItem::clientePossuiCompraEntregue()) {
                    $tipoCliente = 'CLIENTE_COMUM';
                }
            }

            $opensearch = new OpenSearchClient();
            $resultadosOpensearch = $opensearch->pesquisa(
                $pesquisa,
                $ordenar,
                $linhas,
                $sexos,
                $tamanho,
                $cores,
                $categorias,
                $reputacoes,
                $fornecedores,
                $estoque,
                $tipo,
                $pagina,
                $tipoCliente,
                $origem
            );

            $hits = $resultadosOpensearch->body['hits']['hits'];
            if (empty($hits)) {
                return $resultados;
            }
            foreach ($hits as $hit) {
                $dados = $hit['_source'];
                if (!in_array($dados['linha_produto'], $resultados['parametros']['linhas'])) {
                    $resultados['parametros']['linhas'][] = $dados['linha_produto'];
                }

                if ($dados['sexo_produto'] === 'MA') {
                    $dados['sexo_produto'] = 'Masculino';
                } elseif ($dados['sexo_produto'] === 'FE') {
                    $dados['sexo_produto'] = 'Feminino';
                }
                if (
                    $dados['sexo_produto'] !== 'UN' &&
                    !in_array($dados['sexo_produto'], $resultados['parametros']['sexos'])
                ) {
                    $resultados['parametros']['sexos'][] = $dados['sexo_produto'];
                }

                $resultados['parametros']['numeros'] = array_merge(
                    $resultados['parametros']['numeros'] ?? [],
                    $dados['grade_produto'] ? explode(' ', $dados['grade_produto']) : [],
                    $dados['grade_fulfillment'] ? explode(' ', $dados['grade_fulfillment']) : []
                );

                $resultados['parametros']['cores'] = array_merge(
                    $resultados['parametros']['cores'] ?? [],
                    explode(' ', $dados['cor_produto'])
                );

                $resultados['parametros']['categorias'] = array_merge(
                    $resultados['parametros']['categorias'] ?? [],
                    explode(',', $dados['categoria_produto'])
                );

                if (
                    !empty($dados['reputacao_fornecedor']) &&
                    !in_array($dados['reputacao_fornecedor'], $resultados['parametros']['reputacoes'])
                ) {
                    $resultados['parametros']['reputacoes'][] = $dados['reputacao_fornecedor'];
                }

                if (!isset($resultados['parametros']['fornecedores'][$dados['id_fornecedor']])) {
                    $resultados['parametros']['fornecedores'][$dados['id_fornecedor']] = [
                        'id' => $dados['id_fornecedor'],
                        'reputacao' => $dados['reputacao_fornecedor'],
                    ];
                }

                if ($origem !== Origem::MS) {
                    if ($dados['grade_fulfillment'] && !in_array('FULFILLMENT', $resultados['parametros']['estoque'])) {
                        $resultados['parametros']['estoque'] = ['TODOS', 'FULFILLMENT'];
                    } elseif ($dados['grade_produto'] && !in_array('TODOS', $resultados['parametros']['estoque'])) {
                        $resultados['parametros']['estoque'][] = 'TODOS';
                    }
                }
            }

            sort($resultados['parametros']['numeros']);
            sort($resultados['parametros']['cores']);
            sort($resultados['parametros']['categorias']);

            $resultados['parametros']['numeros'] = array_values(array_unique($resultados['parametros']['numeros']));
            $resultados['parametros']['cores'] = array_values(array_unique($resultados['parametros']['cores']));
            $resultados['parametros']['categorias'] = array_values(
                array_unique($resultados['parametros']['categorias'])
            );

            $ids = array_column($hits, '_id');
            [$bindKeys, $binds] = ConversorArray::criaBindValues($ids);
            $where = "estoque_grade.estoque > 0
                AND produtos.id IN ($bindKeys)";
            $order = array_map(fn($item) => "produtos.id = {$item} DESC", array_keys($binds));
            $limit = sizeof($hits);
            $offset = 0;
        }

        $chaveValor = 'produtos.valor_venda_ml';
        $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
        if ($origem === Origem::MS) {
            $chaveValor = 'produtos.valor_venda_ms';
            $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
        }

        if ($origem === Origem::MS || $estoque === 'FULFILLMENT') {
            $where .= ' AND estoque_grade.id_responsavel = 1';
        }

        $binds[':id_produto_frete'] = ProdutoModel::ID_PRODUTO_FRETE;
        $binds[':id_produto_frete_expresso'] = ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO;
        $resultados['produtos'] = FacadesDB::select(
            "SELECT produtos.id,
                produtos.id_fornecedor,
                colaboradores.foto_perfil `foto_perfil_fornecedor`,
                colaboradores.razao_social `nome_fornecedor`,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) `nome`,
                $chaveValor `preco`,
                IF (produtos.promocao > 0, $chaveValorHistorico, 0) `preco_original`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) json_grades,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto`,
                reputacao_fornecedores.reputacao,
                produtos.preco_promocao `desconto`,
                produtos.quantidade_vendida
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                AND publicacoes_produtos.situacao = 'CR'
            INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                AND publicacoes.situacao = 'CR'
                AND publicacoes.tipo_publicacao = 'AU'
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
            WHERE $where AND produtos.id NOT IN (:id_produto_frete, :id_produto_frete_expresso)
            GROUP BY produtos.id
            ORDER BY " .
                implode(', ', $order) .
                "
            LIMIT $limit OFFSET $offset",
            $binds
        );

        # @issue: https://github.com/mobilestock/backend/issues/397
        $resultados['produtos'] = array_map(function ($item) use (&$resultados, $origem) {
            $melhorFabricante = $item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE;
            $resultados['parametros']['fornecedores'][$item['id_fornecedor']]['melhor_fabricante'] = $melhorFabricante;
            $resultados['parametros']['fornecedores'][$item['id_fornecedor']]['foto'] =
                $item['foto_perfil_fornecedor'] ?? "{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg";
            $resultados['parametros']['fornecedores'][$item['id_fornecedor']]['nome'] = $item['nome_fornecedor'];

            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grades']);

            $categoria = (object) [];
            if ($origem === Origem::ML && $melhorFabricante) {
                $categoria->tipo = $item['reputacao'];
                $categoria->valor = '';
            }

            $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['preco']);

            return [
                'id_produto' => $item['id'],
                'nome' => $item['nome'],
                'preco' => $item['preco'],
                'preco_original' => $item['preco_original'],
                'valor_parcela' => $valorParcela,
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                'quantidade_vendida' => $item['quantidade_vendida'],
                'foto' => $item['foto'],
                'grades' => $grades,
                'categoria' => $categoria,
            ];
        }, $resultados['produtos']);
        $resultados['parametros']['fornecedores'] = array_values($resultados['parametros']['fornecedores']);

        return $resultados;
    }

    public static function buscaProdutosEstoqueInternoFornecedor(
        PDO $conexao,
        int $idCliente,
        int $pagina,
        string $pesquisa
    ): array {
        $where = '';
        if (!empty($pesquisa)) {
            $where = " AND LOWER(CONCAT_WS(
                ' - ',
                produtos.id,
                produtos.descricao
            )) REGEXP LOWER(:pesquisa) ";
        }
        $itensPorPagina = 100;
        $offset = $pagina * $itensPorPagina;

        $prepare = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.descricao,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                )foto,
                CONCAT('[',(
                    SELECT DISTINCT GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', produtos_grade.nome_tamanho,
                        'qtd_total', COALESCE(estoque_grade.estoque, 0),
                        'estoque', COALESCE(estoque_grade.estoque, 0),
                        'vendido', COALESCE(estoque_grade.vendido, 0)
                    ))
                    FROM produtos_grade
                    LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                        AND estoque_grade.id_responsavel = :id_cliente
                        AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                    WHERE produtos_grade.id_produto = produtos.id
                    ORDER BY produtos_grade.sequencia ASC
                ),']')estoque
            FROM produtos
            WHERE produtos.id_fornecedor = :id_cliente
            AND produtos.fora_de_linha = 0
            AND produtos.bloqueado = 0
            $where
            GROUP BY produtos.id
            ORDER BY produtos.id DESC
            LIMIT $itensPorPagina OFFSET $offset;"
        );
        $prepare->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        if (!empty($pesquisa)) {
            $prepare->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $prepare->execute();
        $consulta = $prepare->fetchAll(PDO::FETCH_ASSOC);

        $consulta = array_map(function (array $item): array {
            $item['estoque'] = json_decode($item['estoque'], true);
            $item['estoque'] = array_map(function (array $grade): array {
                $grade['limite'] = 9999 - $grade['estoque'];

                return $grade;
            }, $item['estoque']);

            return $item;
        }, $consulta);

        $sql = $conexao->prepare(
            "SELECT
                FLOOR(COUNT(produtos.id) / :qtd_itens)
            FROM produtos
            WHERE produtos.id_fornecedor = :id_fornecedor
                AND produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                $where;"
        );
        $sql->bindValue(':qtd_itens', $itensPorPagina, PDO::PARAM_INT);
        $sql->bindValue(':id_fornecedor', $idCliente, PDO::PARAM_INT);
        if (!empty($pesquisa)) {
            $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $sql->execute();
        $totalPaginas = (int) $sql->fetchColumn();
        $resultado = [
            'produtos' => $consulta,
            'mais_paginas' => $totalPaginas - $pagina > 0,
        ];

        return $resultado;
    }

    public static function atualizaDataEntrada(PDO $conexao, int $idProduto)
    {
        $conexao->exec(
            "UPDATE produtos
                SET produtos.data_entrada = NOW()
            WHERE produtos.id = $idProduto"
        );
    }
    // public static function qtdProdutosParaSeparar(\PDO $conexao, int $idResponsavelEstoque): int
    // {
    //     $sql = $conexao->prepare(
    //         "SELECT COUNT(1) qtd_para_separar
    //         FROM logistica_item
    //         WHERE logistica_item.id_responsavel_estoque = :id_responsavel_estoque
    //             AND logistica_item.situacao = 'PE';"
    //     );
    //     $sql->bindValue(":id_responsavel_estoque", $idResponsavelEstoque, PDO::PARAM_INT);
    //     $sql->execute();
    //     $consulta = $sql->fetch(PDO::FETCH_ASSOC);

    //     return $consulta["qtd_para_separar"];
    // }

    public static function buscaDetalhesStorieProduto(PDO $conexao, array $produtos): array
    {
        $idsProdutos = array_map(function ($produto) {
            return (int) $produto['id'];
        }, $produtos);
        $idsProdutos = implode(',', $idsProdutos);

        $query = "SELECT
                      produtos.id,
                      (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_colaborador_publicador_padrao) fornecedor,
                      produtos.nome_comercial AS descricao,
                      produtos.valor_venda_ml preco,
                      (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id ORDER BY produtos_foto.tipo_foto = 'SM' OR produtos_foto.tipo_foto = 'MD' DESC LIMIT 1) foto
                  FROM produtos
                  WHERE produtos.id IN ($idsProdutos)
                  GROUP BY produtos.id";
        $stmt = $conexao->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function consultaFoguinho(array $produtos): array
    {
        $bind = [':situacao' => PedidoItem::SITUACAO_EM_ABERTO];
        $where = [];
        foreach ($produtos as $index => $produto) {
            $chaveIdProduto = ":id_produto_$index";
            $chaveNomeTamanho = ":nome_tamanho_$index";
            $bind[$chaveIdProduto] = $produto['id_produto'];
            $bind[$chaveNomeTamanho] = $produto['nome_tamanho'];
            $where[] = "estoque_grade.id_produto = $chaveIdProduto AND estoque_grade.nome_tamanho = $chaveNomeTamanho";
        }
        $where = implode(' OR ', $where);
        $consulta = FacadesDB::select(
            "SELECT estoque_grade.id_produto,
                estoque_grade.nome_tamanho,
                SUM(estoque_grade.estoque) AS estoque,
                (
                    SELECT COUNT(pedido_item.id_produto)
                    FROM pedido_item
                    WHERE pedido_item.id_produto = estoque_grade.id_produto
                        AND pedido_item.nome_tamanho = estoque_grade.nome_tamanho
                        AND pedido_item.situacao = :situacao
                ) AS carrinho
            FROM estoque_grade
            WHERE TRUE AND $where
            GROUP BY estoque_grade.id_produto,
                estoque_grade.nome_tamanho
            HAVING carrinho > estoque",
            $bind
        );
        return $consulta;
    }
    //    public static function buscaProdutosMaisClicados(\PDO $conexao, int $mes, int $ano, int $idFornecedor = 0)
    //    {
    //        $where = "";
    //        if ($idFornecedor != 0) $where .= " AND EXISTS(
    //                SELECT 1
    //                FROM estoque_grade
    //                WHERE estoque_grade.id_produto = produtos.id
    //                AND estoque_grade.id_responsavel = 1
    //            ) AND produtos.id_fornecedor = :id_fornecedor";
    //
    //        $sql = $conexao->prepare(
    //            "SELECT
    //                produtos.promocao,
    //                produtos.id,
    //                produtos.descricao,
    //                SUM(paginas_acessadas.acessos)acessos,
    //                colaboradores.razao_social
    //            FROM produtos
    //            INNER JOIN paginas_acessadas ON paginas_acessadas.id_produto = produtos.id
    //            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
    //            WHERE paginas_acessadas.mes = :mes
    //                AND paginas_acessadas.ano = :ano
    //                $where
    //            GROUP BY paginas_acessadas.ano, paginas_acessadas.mes, paginas_acessadas.id_produto
    //            ORDER BY paginas_acessadas.ano, paginas_acessadas.mes, acessos DESC;"
    //        );
    //        $sql->bindValue(":mes", $mes, PDO::PARAM_INT);
    //        $sql->bindValue(":ano", $ano, PDO::PARAM_INT);
    //        if ($idFornecedor != 0) $sql->bindValue(":id_fornecedor", $idFornecedor, PDO::PARAM_INT);
    //        $sql->execute();
    //        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
    //
    //        return $produtos;
    //    }
    // public static function buscaProdutosMaisSelecionados(\PDO $conexao, int $mes, int $ano, int $idFornecedor = 0)
    // {
    //     $where = "";
    //     if ($idFornecedor != 0) $where .= " AND EXISTS(
    //             SELECT 1
    //             FROM estoque_grade
    //             WHERE estoque_grade.id_produto = produtos.id
    //                 AND estoque_grade.id_responsavel = 1
    //         ) AND produtos.id_fornecedor = :id_fornecedor";

    //     $sql = $conexao->prepare(
    //         "SELECT
    //             produtos.id,
    //             produtos.promocao,
    //             produtos.descricao,
    //             colaboradores.razao_social,
    //             SUM(paginas_acessadas.adicionados)adicionados
    //         FROM produtos
    //         INNER JOIN paginas_acessadas ON paginas_acessadas.id_produto = produtos.id
    //         INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
    //         WHERE paginas_acessadas.adicionados > 0
    //             AND paginas_acessadas.mes = :mes
    //             AND paginas_acessadas.ano = :ano
    //             $where
    //         GROUP BY paginas_acessadas.ano, paginas_acessadas.mes, produtos.id
    //         ORDER BY paginas_acessadas.ano, paginas_acessadas.mes, adicionados DESC;"
    //     );
    //     $sql->bindValue(":mes", $mes, PDO::PARAM_INT);
    //     $sql->bindValue(":ano", $ano, PDO::PARAM_INT);
    //     if ($idFornecedor != 0) $sql->bindValue(":id_fornecedor", $idFornecedor, PDO::PARAM_INT);
    //     $sql->execute();
    //     $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

    //     return $produtos;
    // }
    public static function buscaQuantidadeVendas(PDO $conexao, int $mes, int $ano): array
    {
        $sql = $conexao->prepare(
            "SELECT
                COUNT(transacao_financeiras_produtos_itens.id_produto) AS `quantidade`,
                SUM(transacao_financeiras_produtos_itens.preco) AS `valor`
            FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND MONTH(transacao_financeiras_produtos_itens.data_criacao) = :mes
                AND YEAR(transacao_financeiras_produtos_itens.data_criacao) = :ano"
        );
        $sql->bindValue(':mes', $mes, PDO::PARAM_INT);
        $sql->bindValue(':ano', $ano, PDO::PARAM_INT);
        $sql->execute();
        $resultado = $sql->fetch(PDO::FETCH_ASSOC);
        return $resultado;
    }
    public static function buscaProdutosRankingVendas(PDO $conexao, int $mes, int $ano): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.descricao,
                produtos.valor_custo_produto AS `custo`,
                produtos.promocao,
                COUNT(logistica_item.uuid_produto) AS `pares`,
                SUM(logistica_item.preco) AS `valor`,
                SUM(transacao_financeiras_produtos_itens.valor_custo_produto) AS `custo_total`,
                ROUND((SUM(logistica_item.preco) / COUNT(logistica_item.id_produto)), 2) AS `preco_medio`,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) AS `razao_social`
            FROM logistica_item
            JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.`uuid_produto` = logistica_item.`uuid_produto`
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE MONTH(logistica_item.data_criacao) = :mes
                AND YEAR(logistica_item.data_criacao) = :ano
            GROUP BY logistica_item.id_produto
            ORDER BY pares DESC
            LIMIT 400;"
        );
        $sql->bindValue(':mes', $mes, PDO::PARAM_INT);
        $sql->bindValue(':ano', $ano, PDO::PARAM_INT);
        $sql->execute();

        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $produtos;
    }

    public static function buscaEtiquetasProduto(PDO $conexao, int $idProduto)
    {
        $sql = "SELECT
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY
                            produtos_foto.tipo_foto = 'MD'
                        LIMIT 1
                    ) foto,
                    produtos.id,
                    produtos.nome_comercial nome,
                    produtos.descricao,
                    produtos.cores,
                    CONCAT(
                        '[',
                        (
                            SELECT GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'cod_barras', produtos_grade.cod_barras,
                                'tamanho', produtos_grade.nome_tamanho
                                ))
                            FROM produtos_grade
                            WHERE produtos_grade.id_produto = produtos.id
                            ORDER BY produtos_grade.sequencia ASC
                        ),
                        ']'
                    ) lista
                FROM produtos
                WHERE produtos.id = :idProduto;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetch(PDO::FETCH_ASSOC);
        if (!$dados) {
            return [];
        }
        $dados['lista'] = json_decode($dados['lista'], true);
        $dados['nome'] = ConversorStrings::sanitizeString($dados['nome']);
        $dados['cores'] = ConversorStrings::sanitizeString($dados['cores']);

        return $dados;
    }
    public static function buscaDetalhesProduto(PDO $conexao, int $idProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.descricao,
                produtos.localizacao,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto
            FROM produtos
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $informacoes = $sql->fetch(PDO::FETCH_ASSOC);

        return $informacoes;
    }

    public static function buscaSaldoProdutosFornecedor(int $pagina = 1): array
    {
        $resultados = FacadesDB::select(
            "SELECT
                LOWER(IF(
                    LENGTH(produtos.nome_comercial) > 0,
                    produtos.nome_comercial,
                    produtos.descricao)
                ) nome_produto,
                produtos.permitido_reposicao eh_permitido_reposicao,
                estoque_grade.id_produto,
                estoque_grade.nome_tamanho,
                estoque_grade.estoque,
                estoque_grade.id_responsavel <> 1 eh_externo,
                COUNT(DISTINCT pedido_item.uuid) fila_espera,
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ),
                    \"{$_ENV['URL_MOBILE']}images/img-placeholder.png\"
                ) foto_produto
            FROM estoque_grade
            INNER JOIN produtos ON
                produtos.id = estoque_grade.id_produto AND
                produtos.bloqueado = 0 AND
                produtos.fora_de_linha = 0
            LEFT JOIN pedido_item ON
                pedido_item.id_produto = estoque_grade.id_produto AND
                pedido_item.nome_tamanho = estoque_grade.nome_tamanho AND
                pedido_item.tipo_adicao = 'FL'
            WHERE produtos.id_fornecedor = :idFornecedor
            GROUP BY
                estoque_grade.id
            ORDER BY
                estoque_grade.id_produto DESC,
                estoque_grade.sequencia ASC",
            ['idFornecedor' => Auth::user()->id_colaborador]
        );
        if (empty($resultados)) {
            return [];
        }

        $produtos = [];
        foreach ($resultados as $resultado) {
            $idProduto = $resultado['id_produto'];
            $nomeTamanho = $resultado['nome_tamanho'];
            $externo = $resultado['eh_externo'];
            $estoque = $externo ? $resultado['estoque'] : 0;
            $estoqueExterno = $externo ? 0 : $resultado['estoque'];
            $filaEspera = $resultado['fila_espera'];
            $itemGrade = [
                'nome_tamanho' => $nomeTamanho,
                'estoque' => $estoque,
                'estoque_externo' => $estoqueExterno,
                'fila_espera' => $filaEspera,
                'reposicao' => 0,
                'saldo' => $estoque + $estoqueExterno - $filaEspera,
            ];

            if (isset($produtos[$idProduto])) {
                if (isset($produtos[$idProduto]['grade'][$nomeTamanho])) {
                    $produtos[$idProduto]['grade'][$nomeTamanho]['estoque'] += $itemGrade['estoque'];
                    $produtos[$idProduto]['grade'][$nomeTamanho]['estoque_externo'] += $itemGrade['estoque_externo'];
                    $produtos[$idProduto]['grade'][$nomeTamanho]['fila_espera'] += $itemGrade['fila_espera'];
                    $produtos[$idProduto]['grade'][$nomeTamanho]['saldo'] += $itemGrade['saldo'];
                } else {
                    $produtos[$idProduto]['grade'][$nomeTamanho] = $itemGrade;
                }
            } else {
                $produtos[$idProduto] = [
                    'id' => $idProduto,
                    'permitido_reposicao' => $resultado['eh_permitido_reposicao'],
                    'nome' => $resultado['nome_produto'],
                    'foto' => $resultado['foto_produto'],
                    'grade' => [$nomeTamanho => $itemGrade],
                ];
            }
        }

        $previsoes = ReposicoesService::buscaPrevisaoProdutosFornecedor(Auth::user()->id_colaborador);
        foreach ($previsoes as $idProduto => $previsao) {
            if (isset($produtos[$idProduto])) {
                foreach ($previsao as $numero => $qtdReposicao) {
                    if (isset($produtos[$idProduto]['grade'][$numero]) && $qtdReposicao > 0) {
                        $produtos[$idProduto]['grade'][$numero]['reposicao'] += $qtdReposicao;
                        $produtos[$idProduto]['grade'][$numero]['saldo'] += $qtdReposicao;
                    }
                }
            }
        }

        $produtos = array_splice($produtos, 100 * ($pagina - 1), 100);

        return $produtos;
    }

    public static function filtraProdutosEstoque(PDO $conexao, string $filtro): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id,
                produtos.localizacao,
                produtos.descricao,
                colaboradores.razao_social fornecedor
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.id_responsavel = 1
            WHERE 1=1 $filtro
            GROUP BY produtos.id
            ORDER BY produtos.data_entrada DESC, produtos.descricao ASC;"
        );
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $produtos;
    }

    // public static function consultaPrevisaoDeEntregaDeColaborador(\PDO $conexao, int $idProduto, int $idColaborador): ?\stdClass
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             retorna_data_previsao(
    //                 MIN(previsoes.media_dias_entrega),
    //                 MAX(previsoes.media_dias_entrega),
    //                 NOW()
    //             )
    //         FROM colaboradores
    //         JOIN (
    //             SELECT
    //                 estoque_grade.id_responsavel
    //             FROM estoque_grade
    //             WHERE estoque_grade.id_produto = :id_produto
    //                 AND estoque_grade.estoque > 0
    //             GROUP BY estoque_grade.id_responsavel
    //             ORDER BY estoque_grade.id_responsavel ASC
    //             LIMIT 1
    //         ) AS `consulta_estoque_grade`
    //         JOIN previsoes ON previsoes.id_responsavel_estoque = `consulta_estoque_grade`.id_responsavel
    //             AND previsoes.id_cidade = colaboradores.id_cidade
    //         WHERE colaboradores.id = :id_colaborador");
    //     $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
    //     $stmt->execute();
    //     $resultado = $stmt->fetchColumn();
    //     $resultado = $resultado ? json_decode($resultado) : null;
    //     return $resultado;
    // }

    public static function consultaPrevisoesDeColaboradores(PDO $conexao, array $listaColaboradores): array
    {
        [$bindValues, $bind] = ConversorArray::criaBindValues($listaColaboradores);

        $stmt = $conexao->prepare(
            "SELECT
                CONCAT(produtos.nome_comercial, ' (', transacao_financeiras_produtos_itens.nome_tamanho, ')') AS `nome_produto`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                    	AND NOT produtos_foto.tipo_foto = 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                  ) AS `foto_produto`,
                transacao_financeiras_produtos_itens.uuid_produto,
                transacao_financeiras_metadados.valor AS `produtos_json`
            FROM transacao_financeiras
            JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
            JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
            JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            WHERE transacao_financeiras.pagador IN ($bindValues)
                AND DATE(transacao_financeiras.data_atualizacao) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND transacao_financeiras.status = 'PA'
                AND transacao_financeiras.origem_transacao = 'ML'
                AND (entregas_faturamento_item.situacao IS NULL OR entregas_faturamento_item.situacao <> 'EN')
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto"
        );
        $stmt->execute($bind);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_map(function (array $item): array {
            $item['produtos_json'] = json_decode($item['produtos_json'], true);
            $produtoJson = array_filter(
                $item['produtos_json'],
                fn($produto) => $produto['uuid_produto'] === $item['uuid_produto']
            );
            if ($produtoJson = reset($produtoJson)) {
                $item['previsoes_entrega'] = $produtoJson['previsao'] ?? null;
            } else {
                $item['previsoes_entrega'] = null;
            }
            unset($item['produtos_json']);

            return $item;
        }, $resultado);
        return $resultado;
    }

    public static function filtraProdutosPagina(int $pagina, array $filtros): array
    {
        $itensPorPag = 150;
        $offset = $itensPorPag * ($pagina - 1);

        $join = "
            LEFT JOIN produtos_foto ON NOT produtos_foto.tipo_foto = 'SM'
                AND produtos_foto.id = produtos.id
            LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                AND publicacoes_produtos.situacao = 'CR'";

        $where = '';
        $having = '';

        $binds = [];
        if ($filtros['codigo']) {
            $binds[':codigo'] = $filtros['codigo'];
            $where .= ' AND produtos.id = :codigo';
        }

        if (isset($filtros['eh_moda'])) {
            $binds[':eh_moda'] = $filtros['eh_moda'];
            $where .= ' AND produtos.eh_moda = :eh_moda';
        }

        if ($filtros['descricao']) {
            $binds[':descricao'] = "%{$filtros['descricao']}%";
            $where .= " AND CONCAT_WS(
                ' ',
                produtos.nome_comercial,
                produtos.descricao
            ) LIKE :descricao";
        }

        if ($filtros['categoria']) {
            $binds[':categoria'] = $filtros['categoria'];
            $where .= " AND EXISTS(
                SELECT 1
                FROM produtos_categorias
                WHERE produtos_categorias.id_categoria = :categoria
                    AND produtos_categorias.id_produto = produtos.id
            )";
        }

        if ($filtros['fornecedor']) {
            $binds[':fornecedor'] = $filtros['fornecedor'];
            $where .= ' AND produtos.id_fornecedor = :fornecedor';
        }

        if ($filtros['nao_avaliado']) {
            $where .= " AND NOT EXISTS(
                SELECT 1
                FROM avaliacao_produtos
                WHERE avaliacao_produtos.id_produto = produtos.id
                    AND avaliacao_produtos.qualidade > 0
            )";
        }

        if ($filtros['bloqueados']) {
            $where .= ' AND produtos.bloqueado = 1';
        }

        if ($filtros['sem_foto_pub']) {
            $having .= ' HAVING (esta_sem_foto + esta_sem_pub) > 0';
        }

        if ($filtros['fotos'] != '') {
            $binds[':fotos'] = $filtros['fotos'];
            $where .= " AND (
                SELECT COALESCE(COUNT(produtos_foto.id), 0) = :fotos
                FROM produtos_foto
                WHERE produtos_foto.id = produtos.id
            )";
        }

        $produtos = FacadesDB::select(
            "SELECT
                produtos.id,
                produtos.nome_comercial `nome`,
                DATE_FORMAT(produtos.data_cadastro, '%d/%m/%Y %H:%i:%s') AS `data_cadastro`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT CONCAT('\"', produtos_grade.nome_tamanho, '\"')),
                    ']'
                ) AS `json_grade`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT CONCAT('\"', produtos_foto.caminho, '\"')),
                    ']'
                ) AS `json_fotos`,
                (
                    SELECT razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) fornecedor,
                produtos.valor_custo_produto custo_produto,
                produtos.valor_custo_produto_fornecedor custo_fornecedor,
                produtos.valor_venda_ms,
                produtos_foto.id IS NULL AS `esta_sem_foto`,
                (
                    publicacoes_produtos.id IS NULL
                    OR SUM(
                        EXISTS(
                            SELECT 1
                            FROM publicacoes
                            WHERE publicacoes.id = publicacoes_produtos.id_publicacao
                                AND publicacoes.situacao = 'CR'
                                AND publicacoes.tipo_publicacao = 'AU'
                        )
                    ) = 0
                ) AS `esta_sem_pub`,
                produtos.promocao `tem_promocao`,
                produtos.permitido_reposicao `eh_permitido_reposicao`,
                produtos.eh_moda
            FROM produtos
            INNER JOIN produtos_grade ON produtos_grade.id_produto = produtos.id
            $join
            WHERE true {$where}
            GROUP BY produtos.id
            $having
            ORDER BY produtos.id DESC
            LIMIT $itensPorPag OFFSET $offset;",
            $binds
        );

        $produtos = array_map(function (array $produto): array {
            unset($produto['esta_sem_foto'], $produto['esta_sem_pub']);

            return $produto;
        }, $produtos);

        if ($filtros['sem_foto_pub']) {
            $sqlCount = "SELECT COUNT(tabela_produtos.id) AS `qtd_produtos`
                FROM (
                    SELECT
                        produtos.id,
                        produtos_foto.id IS NULL AS `esta_sem_foto`,
                        (
                            publicacoes_produtos.id IS NULL
                            OR SUM(
                                EXISTS(
                                    SELECT 1
                                    FROM publicacoes
                                    WHERE publicacoes.id = publicacoes_produtos.id_publicacao
                                        AND publicacoes.situacao = 'CR'
                                        AND publicacoes.tipo_publicacao = 'AU'
                                )
                            ) = 0
                        ) AS `esta_sem_pub`
                    FROM produtos
                    $join
                    WHERE true $where
                    GROUP BY produtos.id
                    $having
                ) AS `tabela_produtos`;
            ";
        } else {
            $sqlCount = "SELECT COUNT(produtos.id) AS `qtd_produtos`
                FROM produtos
                WHERE true $where;
            ";
        }

        $qtdProdutos = FacadesDB::selectOneColumn($sqlCount, $binds);

        return [
            'produtos' => $produtos,
            'qtd_produtos' => $qtdProdutos,
        ];
    }

    public static function verificaSeExisteFotoComCaminhoIgual(PDO $conexao, string $caminho): array
    {
        $sql = $conexao->prepare("SELECT 1
                                    FROM produtos_foto
                                        WHERE produtos_foto.caminho = :caminho");
        $sql->bindValue(':caminho', $caminho);
        $sql->execute();
        $response = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $response;
    }
    public static function atualizaPermissaoReporFulfillment(PDO $conexao, int $idProduto, bool $autorizado): void
    {
        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.permitido_reposicao = :permitido_reposicao
            WHERE produtos.id = :id_produto
                AND produtos.permitido_reposicao <> :permitido_reposicao;"
        );
        $sql->bindValue(':permitido_reposicao', $autorizado, PDO::PARAM_BOOL);
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível atualizar a permissão do produto, contate a equipe de T.I.');
        }
    }

    public static function limparUltimosAcessos(): void
    {
        FacadesDB::delete(
            "DELETE FROM produtos_acessos
            WHERE produtos_acessos.data < DATE_SUB(NOW(), INTERVAL 1 MONTH);"
        );
    }

    public static function atualizaDataQualquerAlteracao(array $idsProdutos): void
    {
        [$binds, $valores] = ConversorArray::criaBindValues($idsProdutos, 'id_produto');
        $rowCount = FacadesDB::update(
            "UPDATE produtos
            SET produtos.data_qualquer_alteracao = NOW()
            WHERE produtos.id IN ($binds);",
            $valores
        );
        if ($rowCount !== sizeof($idsProdutos)) {
            Log::withContext([
                'produtos' => $idsProdutos,
                'linhas_alteradas' => $rowCount,
                'quantidade_produtos' => sizeof($idsProdutos),
            ]);
            throw new Exception(
                'Row count não bateu com o tamanho do array de ids ao atualizar data de qualquer alteração'
            );
        }
    }

    public static function atualizarQuantidadeVendida(): void
    {
        $linhasAlteradas = FacadesDB::update(
            "UPDATE produtos
            SET produtos.quantidade_vendida = (
                SELECT COUNT(logistica_item.id)
                FROM logistica_item
                WHERE logistica_item.id_produto = produtos.id
            )
            WHERE produtos.bloqueado = 0"
        );
        if ($linhasAlteradas === 0) {
            throw new Exception('Não foi possível gerar a quantidade vendida dos produtos');
        }
    }

    public static function buscaPromocoesAnalise(PDO $conexao, string $pesquisa): array
    {
        $where = '';
        $bind = [];
        if ($pesquisa) {
            $where = " AND CONCAT_WS(
                ',',
                produtos.id,
                produtos.nome_comercial,
                produtos.descricao,
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.usuario_meulook
            ) LIKE :pesquisa";
            $bind[':pesquisa'] = "%$pesquisa%";
        }

        $stmt = $conexao->prepare(
            "SELECT produtos.id `id_produto`,
                IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao) `nome_produto`,
                colaboradores.id `id_colaborador`,
                colaboradores.razao_social `nome_colaborador`,
                colaboradores.telefone `telefone_colaborador`,
                JSON_OBJECT(
                    'ms', produtos.valor_venda_ms,
                    'ml', produtos.valor_venda_ml
                ) `valores_venda`,
                JSON_OBJECT(
                    'ms', produtos.valor_venda_ms_historico,
                    'ml', produtos.valor_venda_ml_historico
                ) `valores_venda_historico`,
                produtos.data_atualizou_valor_custo,
                DATE_FORMAT(produtos.data_atualizou_valor_custo, '%d/%m/%Y %H:%i:%s') `data_atualizou_valor_custo_formatado`,
                produtos.preco_promocao `porcentagem`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto_produto`,
                colaboradores.usuario_meulook
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND TRUE IN (
                    produtos.fora_de_linha = 0,
                    estoque_grade.estoque > 0
                )
            WHERE produtos.promocao = 1
                AND produtos.bloqueado = 0
                $where
            GROUP BY produtos.id
            ORDER BY produtos.data_atualizou_valor_custo DESC"
        );
        $stmt->execute($bind);
        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($retorno)) {
            return [];
        }
        $retorno = array_map(function ($item) {
            $item['id_produto'] = (int) $item['id_produto'];
            $item['id_colaborador'] = (int) $item['id_colaborador'];
            $item['valores_venda'] = json_decode($item['valores_venda'], true);
            $item['valores_venda_historico'] = json_decode($item['valores_venda_historico'], true);
            $item['porcentagem'] = (float) $item['porcentagem'];
            $item['link_perfil'] = $_ENV['URL_MEULOOK'] . $item['usuario_meulook'];
            return $item;
        }, $retorno);
        return $retorno;
    }
    public static function sqlConsultaEstoqueProdutos(): string
    {
        $where = '';
        if (app(Origem::class)->ehMobileEntregas()) {
            $idsProdutos = [ProdutoModel::ID_PRODUTO_FRETE, ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO];
            $where = ' AND estoque_grade.id_produto IN (' . implode(',', $idsProdutos) . ')';
        }
        return "SELECT
            estoque_grade.id_produto,
            estoque_grade.nome_tamanho,
            SUM(
                IF(
                    estoque_grade.id_responsavel = 1,
                    estoque_grade.estoque,
                    NULL
                )
            ) AS `qtd_estoque_fulfillment`,
            GROUP_CONCAT(
                IF (
                    estoque_grade.id_responsavel > 1,
                    JSON_OBJECT(
                        'id_responsavel_estoque', estoque_grade.id_responsavel,
                        'qtd_estoque_externo', estoque_grade.estoque
                    ),
                    NULL
                )
            ) AS `externo`
        FROM estoque_grade
        WHERE TRUE $where
        GROUP BY estoque_grade.id_produto, estoque_grade.nome_tamanho
        ORDER BY estoque_grade.id DESC";
    }
}
