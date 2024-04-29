<?php

namespace MobileStock\service\Publicacao;

use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\Lancamento;
use MobileStock\model\Origem;
use MobileStock\model\Publicacao\Publicacao;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use MobileStock\service\ReputacaoFornecedoresService;
use MobileStock\service\TipoFreteService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicacoesService extends Publicacao
{
    public function salva(PDO $conexao)
    {
        $gerador = new GeradorSql($this);
        $sql = $this->id ? $gerador->updateSomenteDadosPreenchidos() : $gerador->insert();

        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);

        $this->id = $this->id ? $this->id : $conexao->lastInsertId();
    }

    public function insereFoto(array $foto)
    {
        require_once __DIR__ . '/../../../controle/produtos-insere-fotos.php';
        $img_extensao = ['.jpg', '.JPG', '.jpge', '.JPGE', '.jpeg'];
        $extensao = mb_substr($foto['name'], mb_strripos($foto['name'], '.'));

        if ($foto['name'] == '' && !$foto['name']) {
            throw new \InvalidArgumentException('Imagem inválida');
        }

        if (!in_array($extensao, $img_extensao)) {
            throw new \InvalidArgumentException("Sistema permite apenas imagens com extensão '.jpg'.");
        }

        $nomeimagem =
            PREFIXO_LOCAL . 'imagem_publicacao_' . rand(0, 100) . '_' . 321 . '_' . date('dmYhms') . $extensao;
        $caminhoImagens = 'https://cdn-fotos.' . $_ENV['URL_CDN'] . '/' . $nomeimagem;

        upload($foto['tmp_name'], $nomeimagem, 800, 800);

        try {
            $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
        } catch (Exception $e) {
            throw new \DomainException('Erro ao conectar com o servidor');
        }

        try {
            $s3->putObject([
                'Bucket' => 'mobilestock-fotos',
                'Key' => $nomeimagem,
                'SourceFile' => __DIR__ . '/../../../downloads/' . $nomeimagem,
            ]);
        } catch (S3Exception $e) {
            throw new \DomainException('Erro ao enviar imagem');
        }

        unlink(__DIR__ . '/../../../downloads/' . $nomeimagem);
        return $this->foto = $caminhoImagens;
    }

    public static function buscaIdPerfil(PDO $conexao, $nomeUsuario)
    {
        $buscaIdPerfil = $conexao
            ->query("SELECT id FROM colaboradores WHERE usuario_meulook = '{$nomeUsuario}'")
            ->fetch(PDO::FETCH_ASSOC);
        return $buscaIdPerfil['id'];
    }

    public function removeFoto(string $nomeFoto)
    {
        if (mb_strpos($nomeFoto, 'cdn-s3') !== false) {
            $bucket = 'mobilestock-s3';
        } else {
            $bucket = 'mobilestock-fotos';
        }

        $key = preg_replace('/(.*br\/)/i', '', $nomeFoto);
        $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
        $s3->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    // public static function consultaPublicacaoCompleto(\PDO $conexao, int $idPublicacao): array
    // {
    //     $consulta = $conexao->query(
    //         "SELECT
    //             publicacoes.id,
    //             publicacoes.descricao,
    //             publicacoes.foto,
    //             (SELECT JSON_OBJECT(
    //                 'nome', colaboradores.razao_social,
    //                 'foto', COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'),
    //                 'tag' , COALESCE(colaboradores.usuario_meulook, '')
    //             ) FROM colaboradores WHERE colaboradores.id = publicacoes.id_colaborador LIMIT 1) cliente,
    //             DATE_FORMAT(publicacoes.data_criacao, '%d/%m/%Y - %H:%i:%s') data_criacao,
    //             CONCAT('[', GROUP_CONCAT(DISTINCT JSON_OBJECT(
    //                 'id', publicacoes_produtos.id,
    //                 'id_produto', publicacoes_produtos.id_produto,
    //                 'descricao', produtos.descricao,
    //                 'nome_comercial', produtos.nome_comercial,
    //                 'foto_produto', (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = publicacoes_produtos.id_produto ORDER BY produtos_foto.tipo_foto = 'SM' OR produtos_foto.tipo_foto = 'MD' DESC LIMIT 1),
    //                 'valor', produtos.valor_venda_ml,
    //                 'tem_estoque', EXISTS(SELECT 1 FROM estoque_grade WHERE estoque_grade.id_produto = publicacoes_produtos.id_produto AND estoque_grade.estoque > 0)
    //             )), ']') produtos,
    //             0 valor_total,
    //             0 valor_vendido,
    //             produtos.quantidade_vendida `quantidade_vendido`
    //         FROM publicacoes
    //         LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_publicacao = publicacoes.id
    //         LEFT JOIN produtos ON produtos.id = publicacoes_produtos.id_produto
    //         WHERE EXISTS
    //             (SELECT 1
    //             FROM produtos_foto
    //             WHERE produtos_foto.id = publicacoes_produtos.id_produto) AND
    //         	publicacoes.situacao = 'CR' AND
    //             produtos.id = $idPublicacao
    //         GROUP BY publicacoes.id"
    //     )->fetch(PDO::FETCH_ASSOC) ?: [];

    //     if (empty($consulta)) {
    //         return $consulta;
    //     }

    //     $consulta['cliente'] = json_decode($consulta['cliente'], true);
    //     $consulta['produtos'] = array_map(function (array $produto) {
    //         $produto['tem_estoque'] = (bool) $produto['tem_estoque'];

    //         return $produto;
    //     }, json_decode($consulta['produtos'], true));

    //     return $consulta;

    // }

    public static function buscaProdutoSemelhanteMeuLook(PDO $conexao, $id_produto)
    {
        $sql = "SELECT
        publicacoes_produtos.id_produto,
        produtos.nome_comercial,
         produtos.descricao,
         produtos_foto.caminho foto

       FROM publicacoes_produtos
       INNER JOIN produtos ON produtos.id = publicacoes_produtos.id_produto
       INNER JOIN produtos_foto ON produtos_foto.id = produtos.id AND produtos_foto.tipo_foto = 'MD'

       WHERE produtos.id <> $id_produto
        AND produtos.bloqueado = 0
           AND produtos.premio = 0
           AND LOWER(
             SUBSTRING_INDEX(produtos.descricao,' ',1)) = LOWER(
               SUBSTRING_INDEX((SELECT pr.descricao FROM produtos pr WHERE pr.id = $id_produto AND pr.id_fornecedor = produtos.id_fornecedor) ,' ',1))
               AND produtos_foto.tipo_foto = 'MD'
               GROUP BY publicacoes_produtos.id_produto;";

        $retorno = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    // public static function consultaLooksFeed(\PDO $conexao, ?int $idCliente = null, ?int $pagina = 1, ?int $produtoId = null)
    // {
    //     $where = '';
    //     $limit = '';

    //     if ($idCliente && !$produtoId) { // Se estiver logando e não estiver pesquisando por produtos
    //         // Mostrar publicações só de quem está seguindo
    //         $where .= " AND (EXISTS(
    //             SELECT colaboradores_seguidores.id
    //             FROM colaboradores_seguidores
    //             WHERE
    //                 colaboradores_seguidores.id_colaborador = $idCliente AND
    //                 colaboradores_seguidores.id_colaborador_seguindo = publicacoes.id_colaborador
    //             LIMIT 1
    //         )";

    //         // Mostrar próprias publicações
    //         $where .= " OR publicacoes.id_colaborador = $idCliente)";

    //     } else if (!$idCliente && !$produtoId) { // Se não estiver logado e não estiver pesquisando por produtos
    //         // Só mostra publicações que tiverem estoque
    //         $where .= " AND EXISTS(
    //             SELECT 1
    //             FROM estoque_grade
    //             WHERE
    //                 estoque_grade.id_produto = publicacoes_produtos.id_produto AND
    //                 estoque_grade.estoque > 0
    //             LIMIT 1
    //         )";

    //         $where .= "AND produtos.especial = 0";

    //     } else if ($produtoId) { // Se estiver pesquisando por produtos
    //         // Busca publicações que possuam tal produto
    //         if ($produtoId) $where .= " AND publicacoes_produtos.id_produto = $produtoId";

    //     }

    //     // Paginação
    //     $itensPorPagina = 100;
    //     $offset = ($pagina - 1) * $itensPorPagina;
    //     if ($pagina) $limit .= "LIMIT $itensPorPagina OFFSET $offset";

    //     $sql = "SELECT
    //         publicacoes.id,
    //         publicacoes.descricao,
    //         publicacoes.foto,
    //         publicacoes.id_colaborador,
    //         DATE_FORMAT(publicacoes.data_criacao, '%d/%m/%Y - %H:%i:%s') data_criacao,
    //         (
    //             SELECT COUNT(pedido_item_meu_look.id)
    //             FROM transacao_financeiras_produtos_itens
    //             INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
    //             INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = transacao_financeiras_produtos_itens.uuid
    //             WHERE transacao_financeiras.origem_transacao = 'ML' AND transacao_financeiras_produtos_itens.tipo_item = 'PR' AND pedido_item_meu_look.id_publicacao = publicacoes.id
    //         ) quantidade_vendido,
    //         JSON_OBJECT(
    //             'nome', COALESCE(colaboradores.usuario_meulook, colaboradores.razao_social),
    //             'foto', COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'),
    //             'tag',  COALESCE(colaboradores.usuario_meulook, '')
    //         ) cliente,
    //         CONCAT(
    //             '[', GROUP_CONCAT(
    //                 DISTINCT IF (
    //                     publicacoes_produtos.id,
    //                     JSON_OBJECT(
    //                         'id', publicacoes_produtos.id,
    //                         'id_produto', publicacoes_produtos.id_produto,
    //                         'descricao', produtos.descricao,
    //                         'foguinho', EXISTS(
    //                             SELECT 1
    //                             FROM ranking_produtos_meulook
    //                             WHERE ranking_produtos_meulook.id_produto = publicacoes_produtos.id_produto
    //                             LIMIT 1
    //                         ),
    //                         'nome_comercial', produtos.nome_comercial,
    //                         'tem_estoque', EXISTS(
    //                             SELECT 1
    //                             FROM estoque_grade
    //                             WHERE
    //                                 estoque_grade.id_produto = publicacoes_produtos.id_produto AND
    //                                 estoque_grade.estoque > 0
    //                             LIMIT 1
    //                         ),
    //                         'foto_produto', (
    //                             SELECT produtos_foto.caminho
    //                             FROM produtos_foto
    //                             WHERE produtos_foto.id = publicacoes_produtos.id_produto
    //                             ORDER BY produtos_foto.tipo_foto = 'SM' OR produtos_foto.tipo_foto = 'MD' DESC
    //                             LIMIT 1
    //                         ),
    //                         'valor', produtos.valor_venda_ml,
    //                         'valor_anterior', produtos.valor_venda_ml_historico,
    //                         'situacao_Novidade', DATEDIFF(CURDATE(), produtos.data_primeira_entrada) < 7,
    //                         'situacao_Promocao', produtos.preco_promocao > 0,
    //                         'situacao_Normal', produtos.preco_promocao = 0,
    //                         'situacao_Destaque', produtos.posicao_acessado > 0
    //                     ), ''
    //                 )
    //             ), ']'
    //         ) produtos,
    //         COALESCE (
    //             (SELECT CONCAT(
    //                 '[', GROUP_CONCAT(
    //                     JSON_OBJECT(
    //                         'id', publicacoes_comentarios.id,
    //                         'comentario', publicacoes_comentarios.comentario,
    //                         'usuario', (SELECT colaboradores.usuario_meulook FROM colaboradores WHERE colaboradores.id = publicacoes_comentarios.id_colaborador LIMIT 1)
    //                     )
    //                 ORDER BY publicacoes_comentarios.id DESC
    //                 LIMIT 3
    //             ), ']')
    //             FROM publicacoes_comentarios
    //             WHERE publicacoes_comentarios.id_publicacao = publicacoes.id
    //         ), '[]'
    //         ) comentarios
    //     FROM publicacoes
    //     INNER JOIN colaboradores ON colaboradores.id = publicacoes.id_colaborador
    //     INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_publicacao = publicacoes.id
    //     INNER JOIN produtos ON produtos.id = publicacoes_produtos.id_produto
    //     WHERE publicacoes.situacao = 'CR' AND publicacoes.tipo_publicacao IN ('ML', 'AU') AND EXISTS
    //         (SELECT 1
    //         FROM produtos_foto
    //         WHERE produtos_foto.id = publicacoes_produtos.id_produto) $where
    //     GROUP BY publicacoes.id
    //     ORDER BY publicacoes.id DESC
    //     $limit";

    //     $consulta = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    //     if (empty($consulta)) return [];

    //     for ($i = 0; $i < sizeof($consulta); $i++) {
    //         $consulta[$i]['quantidade_vendido'] = intval($consulta[$i]['quantidade_vendido']);
    //         $consulta[$i]['comentarios'] = json_decode($consulta[$i]['comentarios'], true);
    //         $consulta[$i]['cliente'] = json_decode($consulta[$i]['cliente'], true);
    //         $consulta[$i]['produtos'] = array_map(function (array $produto) {
    //             $produto['situacoes'] = ProdutosRepository::calculaSituacoesProdutoCatalogo($produto);
    //             $produto['tem_estoque'] = (bool) $produto['tem_estoque'];
    //             $produto['foguinho'] = (bool) $produto['foguinho'];
    //             return $produto;
    //         }, json_decode($consulta[$i]['produtos'], true));
    //     }

    //     return $consulta;
    // }

    // public static function consultaLooksDestaque(\PDO $conexao, ?int $pagina, ?int $idCliente)
    // {
    //     $porPagina = 100;
    //     $offset = ($pagina - 1) * $porPagina;

    //     if(!$idCliente) {
    //         $consulta = $conexao->query(
    //             "SELECT publicacoes.*, publicacoes_produtos.id_produto FROM publicacoes
    //             INNER JOIN publicacoes_produtos ON publicacoes.id=publicacoes_produtos.id_publicacao
    //             INNER JOIN produtos ON publicacoes_produtos.id_produto=produtos.id
    //             WHERE publicacoes.situacao='CR' AND publicacoes.tipo_publicacao IN ('ML', 'AU') AND produtos.especial=0 GROUP BY publicacoes.id ORDER BY publicacoes.id DESC LIMIT $porPagina OFFSET $offset;"
    //         )->fetchAll(PDO::FETCH_ASSOC);
    //         return $consulta;
    //     }

    //     $consulta = $conexao->query(
    //         "SELECT publicacoes.foto, publicacoes.id, publicacoes.id_colaborador
    //         FROM publicacoes
    //         left join publicacoes_produtos ON publicacoes_produtos.id_publicacao = publicacoes.id
    //         WHERE publicacoes.situacao = 'CR' AND publicacoes.tipo_publicacao IN ('ML', 'AU') AND EXISTS
    //             (SELECT 1
    //             FROM produtos_foto
    //             WHERE produtos_foto.id = publicacoes_produtos.id_produto)
    //         ORDER BY id DESC LIMIT $porPagina OFFSET $offset;"
    //     )->fetchAll(PDO::FETCH_ASSOC);

    //     return $consulta;

    // }

    public static function consultaStories(PDO $conexao, $idCliente)
    {
        if (!isset($idCliente)) {
            $idCliente = 0;
        }
        $consulta = $conexao
            ->query(
                "SELECT
                publicacoes.id_colaborador,
                MAX(publicacoes.id) AS ultimo_id_publicacao,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') AS img,
                colaboradores.usuario_meulook AS user,
                CONCAT('[', (SELECT GROUP_CONCAT(JSON_OBJECT(
                        'id_publicacao', publicacoes.id,
                        'url', publicacoes.foto,
                        'curtidas', publicacoes_cards.likes,
                        'curtido', (COALESCE((SELECT publicacoes_story_detalhes.id FROM publicacoes_story_detalhes WHERE publicacoes_story_detalhes.id_publicacao = publicacoes.id AND publicacoes_story_detalhes.id_colaborador = $idCliente LIMIT 1), 0)),
                        'cabecalho', (JSON_OBJECT(
                            'titulo', colaboradores.usuario_meulook,
                            'subtitulo', DATE_FORMAT(publicacoes.data_criacao, '%d/%m - %Hh %im'),
                            'foto_perfil', COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg')
                        )),
                        'cards', (SELECT publicacoes_cards.storie_json FROM publicacoes_cards WHERE publicacoes_cards.id_publicacao = publicacoes.id)
                ))), ']') stories
            FROM publicacoes
            JOIN colaboradores ON colaboradores.id = publicacoes.id_colaborador
            JOIN publicacoes_cards ON publicacoes_cards.id_publicacao = publicacoes.id
            WHERE publicacoes.tipo_publicacao = 'ST' AND publicacoes.situacao = 'CR' AND publicacoes.data_criacao >= NOW() - INTERVAL 2 DAY
            GROUP BY publicacoes.id_colaborador
            ORDER BY ultimo_id_publicacao DESC
            LIMIT 50"
            )
            ->fetchAll(PDO::FETCH_ASSOC);
        return $consulta;
    }

    public static function curteStories(PDO $conexao, int $idPublicacao, int $idCliente, $idLike = 0)
    {
        $likeExiste = $idLike != 0;
        $likeSQL = '';
        if ($likeExiste) {
            $likeSQL =
                'DELETE FROM publicacoes_story_detalhes WHERE id_publicacao = :id_publicacao AND id_colaborador = :id_cliente';
        } else {
            $likeSQL =
                'INSERT INTO publicacoes_story_detalhes (id_publicacao, id_colaborador, story_curtido) VALUES (:id_publicacao, :id_cliente, 1)';
        }
        $stmt = $conexao->prepare($likeSQL);
        $stmt->bindValue(':id_publicacao', $idPublicacao);
        $stmt->bindValue(':id_cliente', $idCliente);
        $stmt->execute();

        $idLike = $likeExiste ? 0 : $conexao->lastInsertId();

        $likeOperador = $likeExiste ? '-' : '+';
        $stmt = $conexao->prepare(
            "UPDATE publicacoes_cards SET likes = likes {$likeOperador} 1 WHERE id_publicacao = :id_publicacao"
        );
        $stmt->bindValue(':id_publicacao', $idPublicacao);
        $stmt->execute();

        return $idLike;
    }

    public static function consultaGradeProduto(string $origem, int $idProduto, ?int $idColaboradorPonto): array
    {
        $selectEstoque = '';
        $selectValor = " produtos.valor_venda_$origem ";
        $selectListaDesejo = ' FALSE ';
        $bind['id_produto'] = $idProduto;
        $idCliente = Auth::user()->id_colaborador ?? null;
        if ($idCliente) {
            $selectListaDesejo = " EXISTS(
                    SELECT 1
                    FROM produtos_lista_desejos
                    WHERE produtos_lista_desejos.id_produto = :id_produto
                        AND produtos_lista_desejos.id_colaborador = :id_cliente
                ) ";
            $bind['id_cliente'] = $idCliente;
        }
        if ($idColaboradorPonto) {
            $selectAcrescimoPadrao = "(
                    SELECT transportadores_raios.valor
                    FROM transportadores_raios
                    INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                    INNER JOIN colaboradores_enderecos ON
                        colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                        AND colaboradores_enderecos.eh_endereco_padrao = 1
                    WHERE transportadores_raios.id_cidade = colaboradores_enderecos.id_cidade
                        AND transportadores_raios.id_colaborador = :id_colaborador_ponto
                    LIMIT 1
                )";
            if ($idCliente) {
                $selectValor .= " + COALESCE(
                        (
                            SELECT transportadores_raios.valor
                            FROM transportadores_raios
                            INNER JOIN colaboradores_enderecos ON
                                colaboradores_enderecos.id_cidade = transportadores_raios.id_cidade
                                AND colaboradores_enderecos.eh_endereco_padrao = 1
                            WHERE transportadores_raios.id_colaborador = :id_colaborador_ponto
                                AND colaboradores_enderecos.id_colaborador = :id_cliente
                        ),
                        $selectAcrescimoPadrao
                    ) ";
            } else {
                $selectValor .= " + $selectAcrescimoPadrao ";
            }

            $selectEstoque = " SELECT
                    COUNT(entregas_devolucoes_item.uuid_produto) AS `quantidade`,
                    entregas_devolucoes_item.nome_tamanho,
                    produtos_grade.sequencia,
                    'EXTERNO' AS `tipo`
                FROM entregas_devolucoes_item
                INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = entregas_devolucoes_item.nome_tamanho
                    AND produtos_grade.id_produto = entregas_devolucoes_item.id_produto
                INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                WHERE entregas_devolucoes_item.origem = 'ML'
                    AND NOT entregas_devolucoes_item.tipo = 'DE'
                    AND entregas_devolucoes_item.situacao = 'PE'
                    AND entregas_devolucoes_item.id_produto = :id_produto
                    AND tipo_frete.id_colaborador = :id_colaborador_ponto
                GROUP BY entregas_devolucoes_item.nome_tamanho ";
            $bind['id_colaborador_ponto'] = $idColaboradorPonto;
        } else {
            $responsavel = '';
            if ($origem === Origem::MS) {
                $responsavel = ' AND estoque_grade.id_responsavel = 1 ';
            }

            $selectEstoque = " SELECT
                    SUM(estoque_grade.estoque) AS `quantidade`,
                    estoque_grade.nome_tamanho,
                    estoque_grade.sequencia,
                    IF (
                        estoque_grade.estoque > 0
                        AND estoque_grade.id_responsavel = 1,
                        'FULFILLMENT',
                        'EXTERNO'
                    ) AS `tipo`
                FROM estoque_grade
                WHERE estoque_grade.id_produto = :id_produto
                    $responsavel
                GROUP BY estoque_grade.nome_tamanho
                ORDER BY
                    estoque_grade.sequencia ASC,
                    estoque_grade.estoque > 0 DESC,
                    estoque_grade.id_responsavel ASC ";
        }

        $consulta = DB::selectOne(
            "SELECT
                $selectValor AS `valor`,
                $selectListaDesejo AS `eh_favorito`,
                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT(
                            'nome_tamanho', _estoque_grade.nome_tamanho,
                            'quantidade', _estoque_grade.quantidade,
                            'tipo', _estoque_grade.tipo
                        ) ORDER BY _estoque_grade.sequencia ASC),
                        ']'
                    )
                    FROM (
                        $selectEstoque
                    ) AS `_estoque_grade`
                ) AS `json_grades`
            FROM produtos
            WHERE produtos.id = :id_produto
            GROUP BY produtos.id;",
            $bind
        );
        if (empty($consulta)) {
            throw new NotFoundHttpException('Produto não encontrado');
        }

        $consulta['valor_parcela'] = CalculadorTransacao::calculaValorParcelaPadrao($consulta['valor']);
        $consulta['parcelas'] = CalculadorTransacao::PARCELAS_PADRAO;

        return $consulta;
    }

    public static function consultaProdutoPublicacao(int $idProduto): array
    {
        $consulta = DB::selectOne(
            "SELECT
                produtos.id id_produto,
                publicacoes_produtos.id `id_publicacao_produto`,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome,
                IF(LENGTH(produtos.nome_comercial) > 0, produtos.descricao, '') referencia,
                produtos.embalagem,
                produtos.forma,
                produtos.cores cor,
                (SELECT linha.nome FROM linha WHERE linha.id = produtos.id_linha) linha,
                CASE produtos.sexo
                    WHEN 'FE' THEN 'Feminino'
                    WHEN 'MA' THEN 'Masculino'
                    ELSE 'Unisex'
                END sexo,
                produtos.fora_de_linha `fora_de_linha_bool`,
                produtos.outras_informacoes descricao,
                CONCAT(
                    '[',
                    (
                        SELECT GROUP_CONCAT(
                            DISTINCT CONCAT('\"', produtos_foto.caminho, '\"')
                            ORDER BY
                                produtos_foto.tipo_foto = 'MD' DESC,
                                produtos_foto.tipo_foto = 'LG' DESC
                        )
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = produtos.id AND
                            produtos_foto.tipo_foto <> 'SM'
                    ),
                    ']'
                ) fotos_json,
                (
                    SELECT JSON_OBJECT(
                        'id', colaboradores.id,
                        'nome', colaboradores.razao_social,
                        'usuario_meulook', colaboradores.usuario_meulook,
                        'foto', colaboradores.foto_perfil
                    )
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) fabricante_json,
                (
                    SELECT ROUND(AVG(avaliacao_produtos.qualidade))
                    FROM avaliacao_produtos
                    WHERE
                        avaliacao_produtos.id_produto = produtos.id AND
                        avaliacao_produtos.origem = 'ML' AND
                        avaliacao_produtos.data_avaliacao IS NOT NULL AND
                        avaliacao_produtos.qualidade > 0
                ) nota,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE
                        avaliacao_produtos.id_produto = produtos.id AND
                        avaliacao_produtos.origem = 'ML' AND
                        avaliacao_produtos.data_avaliacao IS NOT NULL AND
                        avaliacao_produtos.qualidade > 0
                ) qtd_avaliacoes,
                produtos.quantidade_vendida,
                GROUP_CONCAT(categorias.nome SEPARATOR ' ') categorias
            FROM publicacoes_produtos
            INNER JOIN produtos ON produtos.id = publicacoes_produtos.id_produto
            INNER JOIN produtos_categorias ON produtos_categorias.id_produto = produtos.id
            INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
            WHERE
                publicacoes_produtos.situacao = 'CR' AND
                produtos.id = :idProduto
            GROUP BY produtos.id",
            [':idProduto' => $idProduto]
        );

        if (empty($consulta)) {
            return [];
        }

        return $consulta;
    }

    // public static function consultaGradeProdutoPublicacao(\PDO $conexao, int $idProdutoPublicacao, ?int $idCliente)
    // {

    //     $condicaoEstoque = $idCliente ?
    //         "estoque_grade.estoque > COALESCE((SELECT COUNT(pedido_item_meu_look.id) FROM pedido_item_meu_look WHERE pedido_item_meu_look.id_produto = publicacoes_produtos.id_produto AND pedido_item_meu_look.situacao = 'CR' AND pedido_item_meu_look.id_cliente = $idCliente AND pedido_item_meu_look.tamanho = estoque_grade.tamanho), 0)"
    //          :
    //         "estoque_grade.estoque > 0";

    //     $consulta = $conexao->query(
    //         "SELECT
    //             CONCAT('[', GROUP_CONCAT(DISTINCT JSON_OBJECT(
    //                 'tamanho', estoque_grade.tamanho,
    //                 'nome_tamanho', estoque_grade.nome_tamanho,
    //                 'situacao', IF($condicaoEstoque, 'estoque', 'esgotado')
    //             )), ']') estoque
    //         FROM publicacoes_produtos
    //         INNER JOIN (
    //             SELECT
    //                 estoque_grade.id_produto,
    //                 estoque_grade.tamanho,
    //                 SUM(estoque_grade.estoque)estoque,
    //                 estoque_grade.nome_tamanho
    //             FROM estoque_grade
    //             GROUP BY estoque_grade.id_produto, estoque_grade.tamanho
    //         )estoque_grade ON estoque_grade.id_produto = publicacoes_produtos.id_produto
    //         WHERE publicacoes_produtos.id = ".$idProdutoPublicacao
    //     )->fetch(PDO::FETCH_ASSOC) ?: [];

    //     if (empty($consulta)) {
    //         return $consulta;
    //     }

    //     $consulta['estoque'] = json_decode($consulta['estoque'], true);

    //     return $consulta;
    // }

    public static function consultaComissoesTroca(int $pagina): array
    {
        /*
         * IMPORTANTE! QUALQUER ALTERAÇÃO FEITA NA FORMULA DESSA FUNÇÃO, IMPLEMENTAR EM NA FUNÇÃO
         * `consultaTotaisComissoes` POIS ELA RETORNA AS SOMAS DOS ITENS DESSA LISTA.
         */
        $porPagina = 100;
        $consulta = DB::select(
            "SELECT 'PENDENTE' situacao_pagamento,
                lancamento_financeiro_pendente.id id_lancamento,
                DATE_FORMAT(lancamento_financeiro_pendente.data_emissao, '%d/%m/%y %H:%i:%s') data_emissao,
                lancamento_financeiro_pendente.origem origem_lancamento,
                lancamento_financeiro_pendente.valor,
                (
                    SELECT JSON_OBJECT(
                        'id', produtos.id,
                        'nome', LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)),
                        'tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                        'foto', COALESCE((
                            SELECT produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                            ORDER BY produtos_foto.tipo_foto IN ('MD','LG') DESC
                            LIMIT 1
                        ), '{$_ENV['URL_MOBILE']}images/credito.png')
                    )
                    FROM transacao_financeiras_produtos_itens
                    INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
                    WHERE transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro_pendente.numero_documento
                        AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                ) produto_json
            FROM lancamento_financeiro_pendente
            WHERE lancamento_financeiro_pendente.id_colaborador = :idCliente
                AND lancamento_financeiro_pendente.origem = 'TR'
            ORDER BY lancamento_financeiro_pendente.id DESC
            LIMIT :porPagina OFFSET :deslocamento",
            [
                'idCliente' => Auth::user()->id_colaborador,
                'porPagina' => $porPagina,
                'deslocamento' => ($pagina - 1) * $porPagina,
            ]
        );

        $total = 0;
        $consulta = array_map(function ($item) use (&$total) {
            $item['origem_lancamento'] = Lancamento::buscaTextoPelaOrigem($item['origem_lancamento']);
            $total += $item['valor'];
            return $item;
        }, $consulta);

        return [
            'itens' => $consulta,
            'total' => $total,
        ];
    }

    public static function consultaVendasPublicacoes(
        int $pagina,
        ?string $filtro,
        ?string $dataInicial,
        ?string $dataFinal
    ): array {
        if (($dataInicial || $dataFinal) && $pagina > 1) {
            return [
                'itens' => [],
                'total' => 0,
            ];
        }

        $porPagina = 100;
        $offset = ($pagina - 1) * $porPagina;
        $paginacao = "LIMIT $porPagina OFFSET $offset";

        $diasTroca = ConfiguracaoService::consultaDatasDeTroca(DB::getPdo())[0]['qtd_dias_disponiveis_troca_normal'];

        $bind = [':idCliente' => Auth::user()->id_colaborador];

        $select = '';
        $join = '';
        $where = '';
        $order = '';

        $dataPagamento = "entregas_faturamento_item.data_entrega + INTERVAL $diasTroca DAY";
        $colunaFiltrarData = $dataPagamento;

        if ($filtro === 'VENDAS_PENDENTES') {
            $colunaFiltrarData = 'transacao_financeiras_produtos_itens.data_atualizacao';
            $select = ", 'PENDENTE' situacao_pagamento,
                IF(
                    entregas_faturamento_item.situacao = 'EN',
                    DATE_FORMAT(entregas_faturamento_item.data_entrega + INTERVAL ($diasTroca + 1) DAY, '%d/%m/%y'),
                    NULL
                ) data_previsao";
            $where = " AND logistica_item.situacao IN ('PE','SE','CO')
                AND IF(COALESCE(entregas_faturamento_item.situacao, 'NULO') = 'EN',
                    DATE($dataPagamento) >= CURRENT_DATE(), 1)";
            $order = ' transacao_financeiras_produtos_itens.data_atualizacao DESC';
        } else {
            $select = ", CASE
                    WHEN logistica_item.situacao IN ('DE','DF') THEN 'DEVOLVIDO'
                    WHEN SUM(logistica_item_data_alteracao.id IS NOT NULL) THEN 'CANCELADO'
                    ELSE 'PAGO'
                END situacao_pagamento,
                DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%y %H:%i:%s') data_emissao,
                DATE_FORMAT($dataPagamento + INTERVAL 1 DAY, '%d/%m/%y') data_previsao,
                DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%y') data_atualizacao_logistica";
            $join = "LEFT JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                AND logistica_item_data_alteracao.situacao_nova = 'RE'";
            $where = " AND entregas_faturamento_item.situacao = 'EN'
                AND DATE($dataPagamento) < CURRENT_DATE()";
            $order = " $dataPagamento DESC";
        }

        if ($dataInicial || $dataFinal) {
            $paginacao = '';
            if ($dataInicial) {
                $where .= " AND DATE($colunaFiltrarData) >= :dataInicial";
                $bind[':dataInicial'] = $dataInicial;
            }
            if ($dataFinal) {
                $where .= " AND DATE($colunaFiltrarData) <= :dataFinal";
                $bind[':dataFinal'] = $dataFinal;
            }
        }

        $sql = "SELECT DATE_FORMAT(transacao_financeiras_produtos_itens.data_atualizacao, '%d/%m/%y %H:%i:%s') data_emissao,
            transacao_financeiras_produtos_itens.sigla_lancamento origem_lancamento,
            transacao_financeiras_produtos_itens.comissao_fornecedor valor,
            transacao_financeiras_produtos_itens.id_transacao,
            JSON_OBJECT(
                'id', transacao_financeiras_produtos_itens.id_produto,
                'nome', LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)),
                'tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                'foto', COALESCE((
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD','LG') DESC
                    LIMIT 1
                ), '{$_ENV['URL_MOBILE']}images/credito.png')
            ) produto_json
            $select
        FROM transacao_financeiras_produtos_itens
        INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            AND transacao_financeiras.origem_transacao = 'ML'
        INNER JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
        INNER JOIN produtos ON produtos.id = logistica_item.id_produto
        LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
        $join
        WHERE transacao_financeiras_produtos_itens.id_fornecedor = :idCliente
            AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
            AND transacao_financeiras_produtos_itens.tipo_item <> 'AC'
            $where
        GROUP BY transacao_financeiras_produtos_itens.id
        ORDER BY $order
        $paginacao";

        $consulta = DB::select($sql, $bind);

        $consulta = array_map(function ($item) {
            $item['origem_lancamento'] = Lancamento::buscaTextoPelaOrigem($item['origem_lancamento']);
            if (in_array($item['situacao_pagamento'], ['DEVOLVIDO', 'CANCELADO'])) {
                $item['data_previsao'] = $item['data_atualizacao_logistica'];
            }
            unset($item['data_atualizacao_logistica']);
            return $item;
        }, $consulta);

        $total = 0;
        if ($dataInicial || $dataFinal) {
            foreach ($consulta as $item) {
                if (in_array($item['situacao_pagamento'], ['PENDENTE', 'PAGO'])) {
                    $total += $item['valor'];
                }
            }
        }

        return [
            'itens' => $consulta,
            'total' => $total,
        ];
    }

    // public static function consultaCompradoresPublicacao(\PDO $conexao, int $idPublicacao, ?int $idCliente)
    // {
    //     $seguindoSelect = "(SELECT FALSE) seguindo";
    //     if ($idCliente){
    //         $seguindoSelect = "COALESCE((SELECT TRUE FROM colaboradores_seguidores WHERE colaboradores_seguidores.id_colaborador = $idCliente AND colaboradores_seguidores.id_colaborador_seguindo = colaboradores.id), false) seguindo";
    //     }
    //     $consulta = $conexao->query(
    //         "SELECT DISTINCT
    //             colaboradores.id,
    //             colaboradores.usuario_meulook,
    //             COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
    //             $seguindoSelect
    //             FROM transacao_financeiras_produtos_itens
    //             INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
    //             INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = transacao_financeiras_produtos_itens.uuid
    //             INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_cliente
    //             WHERE transacao_financeiras.origem_transacao = 'ML' AND transacao_financeiras_produtos_itens.tipo_item = 'PR' AND pedido_item_meu_look.id_publicacao = $idPublicacao
    //             GROUP BY transacao_financeiras_produtos_itens.uuid
    //             ORDER BY transacao_financeiras_produtos_itens.id DESC, seguindo DESC
    //             LIMIT 100;")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    //     return $consulta;
    // }

    // public static function consultaCabecalhoPublicacoesProduto(\PDO $conexao, int $idProduto, ?int $idCliente)
    // {
    //     $consulta = $conexao->query(
    //         "SELECT
    //             COALESCE(COUNT(transacao_financeiras_produtos_itens.id), 0) qtd_vendido,
    //             COALESCE((SELECT COUNT(publicacoes_produtos.id_publicacao) FROM publicacoes_produtos WHERE publicacoes_produtos.id_produto = produtos.id), 0) qtd_postado,
    //             COALESCE((SELECT 1 FROM ranking_produtos_meulook WHERE ranking_produtos_meulook.id_produto = produtos.id), 0) foguinho,
    //             '[]' compradores_em_comum
    //         FROM produtos
    //         LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_produto = produtos.id
    //         LEFT JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao AND transacao_financeiras.status = 'PA'
    //         WHERE produtos.id = $idProduto
    //         GROUP BY produtos.id"
    //     )->fetch(\PDO::FETCH_ASSOC) ?: [];

    //     $consulta['compradores_em_comum'] = json_decode($consulta['compradores_em_comum'], true);
    //     return $consulta;
    // }

    public static function consultaPublicacoesDeProntaEntrega(string $usuarioMeuLook, int $pagina): array
    {
        $itensPorPag = 100;
        $offset = ($pagina - 1) * $itensPorPag;
        $idCliente = Auth::user()->id_colaborador ?? null;

        $bind['usuario_meulook'] = $usuarioMeuLook;
        $colaborador = ColaboradorModel::buscaColaboradorPorUsuarioMeulook($usuarioMeuLook);
        $tipoPonto = TipoFreteService::tipoPonto($colaborador->id);
        $whereValor = ' AND colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador ';
        if ($idCliente && $tipoPonto === 'PM') {
            $whereValor = ' AND colaboradores_enderecos.id_colaborador = :id_cliente ';
            $bind['id_cliente'] = $idCliente;
        }

        $publicacoes = DB::select(
            "SELECT
                `estoque_ponto`.id_produto,
                `estoque_ponto`.`id_colaborador_ponto`,
                `estoque_ponto`.quantidade_vendida,
                `estoque_ponto`.nome,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = `estoque_ponto`.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto`,
                IF (
                    `estoque_ponto`.desconto > 0,
                    `estoque_ponto`.valor_venda_ml_historico,
                    0
                ) AS `preco_original`,
                `estoque_ponto`.preco,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', `estoque_ponto`.nome_tamanho,
                        'estoque', `estoque_ponto`.`quantidade`
                    ) ORDER BY produtos_grade.sequencia ASC),
                    ']'
                ) AS `json_grades`
            FROM (
                SELECT
                    entregas_devolucoes_item.id,
                    entregas_devolucoes_item.id_produto,
                    entregas_devolucoes_item.nome_tamanho,
                    COUNT(entregas_devolucoes_item.nome_tamanho) AS `quantidade`,
                    produtos.nome_comercial AS `nome`,
                    produtos.valor_venda_ml_historico,
                    produtos.valor_venda_ml + COALESCE(
                        (
                            SELECT transportadores_raios.valor
                            FROM transportadores_raios
                            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.id_cidade = transportadores_raios.id_cidade
                                AND colaboradores_enderecos.eh_endereco_padrao = 1
                                $whereValor
                            WHERE transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                                AND transportadores_raios.esta_ativo
                            LIMIT 1
                        ), 0
                    ) AS `preco`,
                    tipo_frete.id_colaborador AS `id_colaborador_ponto`,
                    produtos.quantidade_vendida,
                    produtos.preco_promocao `desconto`
                FROM entregas_devolucoes_item
                INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                INNER JOIN tipo_frete ON tipo_frete.id = entregas_devolucoes_item.id_ponto_responsavel
                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                WHERE colaboradores.usuario_meulook = :usuario_meulook
                    AND entregas_devolucoes_item.situacao = 'PE'
                    AND entregas_devolucoes_item.origem = 'ML'
                    AND NOT entregas_devolucoes_item.tipo = 'DE'
                GROUP BY entregas_devolucoes_item.id_produto, entregas_devolucoes_item.nome_tamanho
            ) AS `estoque_ponto`
            INNER JOIN produtos_grade ON produtos_grade.id_produto = `estoque_ponto`.id_produto
                AND produtos_grade.nome_tamanho = `estoque_ponto`.nome_tamanho
            INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = `estoque_ponto`.id_produto
                AND publicacoes_produtos.situacao = 'CR'
            INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                AND publicacoes.situacao = 'CR'
                AND publicacoes.tipo_publicacao = 'AU'
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = `estoque_ponto`.id_produto
            GROUP BY `estoque_ponto`.id_produto
            ORDER BY `estoque_ponto`.id ASC, `estoque_ponto`.id_produto DESC
            LIMIT $itensPorPag OFFSET $offset;",
            $bind
        );

        $publicacoes = array_map(function (array $publicacao): array {
            $publicacao['grades'] = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($publicacao['grades'], true);
            $publicacao['categoria'] = (object) [
                'tipo' => 'PRONTA_ENTREGA',
                'valor' => '',
            ];

            return $publicacao;
        }, $publicacoes);

        return $publicacoes;
    }

    public static function buscarCatalogo(int $pagina, string $origem): array
    {
        $join = '';
        $where = ' AND estoque_grade.id_responsavel = 1';
        $orderBy = '';
        $itensPorPagina = 100;

        $chaveValor = 'catalogo_fixo.valor_venda_ms';
        $chaveValorHistorico = 'catalogo_fixo.valor_venda_ms_historico';
        if ($origem === Origem::ML) {
            $chaveValor = 'catalogo_fixo.valor_venda_ml';
            $chaveValorHistorico = 'catalogo_fixo.valor_venda_ml_historico';
            $join = "LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id = catalogo_fixo.id_publicacao_produto
            AND publicacoes_produtos.situacao = 'CR'";
            $where = ' AND publicacoes_produtos.id IS NOT NULL';
        }

        if ($pagina === 1) {
            $tipo = CatalogoFixoService::TIPO_VENDA_RECENTE;
            $orderBy .= ', catalogo_fixo.vendas_recentes DESC, catalogo_fixo.pontuacao DESC';
        } else {
            $tipo = CatalogoFixoService::TIPO_MELHORES_PRODUTOS;
            $orderBy .= ', catalogo_fixo.pontuacao DESC';
            $pagina -= 1;
        }

        $offset = $itensPorPagina * ($pagina - 1);
        $where .= ' AND catalogo_fixo.tipo = :tipo';
        $publicacoes = DB::select(
            "SELECT
                catalogo_fixo.id_produto,
                catalogo_fixo.nome_produto `nome`,
                $chaveValor `preco`,
                $chaveValorHistorico `preco_original`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) json_grades,
                catalogo_fixo.foto_produto `foto`,
                catalogo_fixo.quantidade_vendida,
                reputacao_fornecedores.reputacao,
                catalogo_fixo.tipo
            FROM catalogo_fixo
            INNER JOIN estoque_grade ON estoque_grade.id_produto = catalogo_fixo.id_produto AND
                estoque_grade.estoque > 0
            $join
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = catalogo_fixo.id_fornecedor
            WHERE 1=1 $where
            GROUP BY catalogo_fixo.id
            ORDER BY 1=1 $orderBy
            LIMIT $itensPorPagina OFFSET $offset",
            [':tipo' => $tipo]
        );

        $publicacoes = array_map(function ($item) {
            $item['grades'] = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grades']);
            $item['categoria'] = (object) [];
            if ($item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE) {
                $item['categoria']->tipo = $item['reputacao'];
            }

            $item['valor_parcela'] = CalculadorTransacao::calculaValorParcelaPadrao($item['preco']);
            $item['parcelas'] = CalculadorTransacao::PARCELAS_PADRAO;

            return $item;
        }, $publicacoes);

        return $publicacoes;
    }

    public static function buscarCatalogoComRecomendacoes(PDO $conexao, array $recomendacoes): array
    {
        $listaIDs = implode(',', $recomendacoes);
        $itensPorPagina = 100;
        $constanteMelhorFabricante = CatalogoFixoService::TIPO_MELHOR_FABRICANTE;
        $query = "SELECT
                produtos.id AS id_produto,
                produtos.nome_comercial AS nome_produto,
                produtos.valor_venda_ml `valor_venda`,
                produtos.valor_venda_ml_historico `valor_venda_historico`,
                CONCAT(
                    '[',
                        GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                        ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) grades,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS foto_produto,
                produtos.quantidade_vendida,
                reputacao_fornecedores.reputacao,
                    IF(
                    reputacao_fornecedores.reputacao = '$constanteMelhorFabricante',
                    reputacao_fornecedores.reputacao,
                    ''
                ) tipo
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                AND estoque_grade.estoque > 0
            LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
            INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                AND publicacoes.situacao = 'CR'
                AND publicacoes.tipo_publicacao IN ('ML', 'AU')
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
            WHERE publicacoes_produtos.id IS NOT NULL
                AND produtos.id IN ($listaIDs)
            GROUP BY produtos.id
            ORDER BY FIELD(produtos.id, $listaIDs)
            LIMIT $itensPorPagina";

        $publicacoes = $conexao->query($query)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($publicacoes)) {
            $publicacoes = array_map(function ($item) {
                $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo(json_decode($item['grades'], true));
                $categoria = (object) [];
                if ($item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE) {
                    $categoria->tipo = $item['reputacao'];
                }
                return [
                    'id_produto' => (int) $item['id_produto'],
                    'nome' => $item['nome_produto'],
                    'preco' => (float) $item['valor_venda'],
                    'preco_original' => (float) $item['valor_venda_historico'],
                    'quantidade_vendida' => (int) $item['quantidade_vendida'],
                    'foto' => $item['foto_produto'],
                    'grades' => $grades,
                    'categoria' => $categoria,
                ];
            }, $publicacoes);
        }

        return $publicacoes;
    }

    public static function buscarCatalogoComFiltro(int $pagina, string $filtro, string $origem): array
    {
        $itensPorPagina = 100;
        $offset = ($pagina - 1) * $itensPorPagina;

        $tipo = '';
        $select = '';
        $join = '';
        $where = '';
        $orderBy = '';

        switch ($filtro) {
            case 'MELHOR_FABRICANTE':
                $where =
                    ' AND reputacao_fornecedores.reputacao = "' .
                    ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE .
                    '"';
                $tipo = 'MELHOR_FABRICANTE';
                $orderBy = ", `data_up` DESC,
                    `id_produto` DESC";
                break;
            case 'MENOR_PRECO':
                $tipo = 'MENOR_PRECO';
                $orderBy = ', `valor_venda` ASC';
                break;
            case 'PROMOCAO':
                $tipo = 'PROMOCAO';
                $where = ' AND produtos.promocao > 0';
                $orderBy = ', `desconto` DESC';

                $idsPromocaoTemporaria = DB::selectOneColumn(
                    "SELECT GROUP_CONCAT(catalogo_fixo.id_produto)
                        FROM catalogo_fixo
                        WHERE catalogo_fixo.expira_em >= NOW()
                            AND catalogo_fixo.tipo = '" .
                        CatalogoFixoService::TIPO_PROMOCAO_TEMPORARIA .
                        "'"
                );
                if (!empty($idsPromocaoTemporaria)) {
                    $where .= " AND produtos.id NOT IN ($idsPromocaoTemporaria)";
                }

                break;
            case 'LANCAMENTO':
                $tipo = 'LANCAMENTO';
                $orderBy = ', produtos.data_primeira_entrada DESC';
                break;
            default:
                throw new Exception('Filtro inválido!');
        }

        $chaveValor = 'produtos.valor_venda_ms';
        $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
        if ($origem === Origem::ML) {
            $chaveValor = 'produtos.valor_venda_ml';
            $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
            $select = ', publicacoes_produtos.id `id_publicacao_produto`';
            $join = 'INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao';
            $where .= " AND publicacoes_produtos.situacao = 'CR'
                AND publicacoes.situacao = 'CR'
                AND publicacoes.tipo_publicacao IN ('ML', 'AU')";
            if (
                $filtro !== 'MELHOR_FABRICANTE' &&
                (!Auth::check() || (Auth::check() && !EntregasFaturamentoItem::clientePossuiCompraEntregue()))
            ) {
                $where .=
                    " AND reputacao_fornecedores.reputacao <> '" . ReputacaoFornecedoresService::REPUTACAO_RUIM . "'";
            }
        } elseif ($origem === Origem::MS) {
            $where .= ' AND estoque_grade.id_responsavel = 1';
        } else {
            throw new Exception('Origem inválida');
        }

        $query = "SELECT
            produtos.id `id_produto`,
            LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) `nome_produto`,
            $chaveValor `valor_venda`,
            IF (produtos.promocao > 0, $chaveValorHistorico, NULL) `valor_venda_historico`,
            CONCAT(
                '[',
                GROUP_CONCAT(JSON_OBJECT(
                    'nome_tamanho', estoque_grade.nome_tamanho,
                    'estoque', estoque_grade.estoque
                ) ORDER BY estoque_grade.sequencia),
                ']'
            ) `json_grade_estoque`,
            (
                SELECT produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id = produtos.id
                ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                LIMIT 1
            ) `foto_produto`,
            reputacao_fornecedores.reputacao,
            produtos.quantidade_vendida,
            produtos.data_primeira_entrada,
            produtos.preco_promocao `desconto`,
            produtos.data_up
            $select
        FROM produtos
        INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            AND estoque_grade.estoque > 0
        $join
        LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
        WHERE produtos.bloqueado = 0
            $where
        GROUP BY produtos.id
        ORDER BY 1=1 $orderBy
        LIMIT $itensPorPagina OFFSET $offset";

        $publicacoes = DB::select($query);
        if (!empty($publicacoes)) {
            $publicacoes = array_map(function ($item) use ($tipo) {
                $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grade_estoque']);
                $categoria = (object) ['tipo' => $tipo, 'valor' => ''];

                if ($tipo === 'PROMOCAO') {
                    $categoria->valor = "{$item['desconto']}%";
                } else {
                    $categoria->valor = '';
                }

                if ($item['reputacao'] === ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE) {
                    $categoria->tipo = $item['reputacao'];
                }

                $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda']);

                return [
                    'id_produto' => $item['id_produto'],
                    'nome' => $item['nome_produto'],
                    'preco' => $item['valor_venda'],
                    'preco_original' => $item['valor_venda_historico'],
                    'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                    'valor_parcela' => $valorParcela,
                    'quantidade_vendida' => $item['quantidade_vendida'],
                    'foto' => $item['foto_produto'],
                    'grades' => $grades,
                    'categoria' => $categoria,
                ];
            }, $publicacoes);
        }

        return $publicacoes;
    }

    public static function buscaPromocoesTemporarias(string $origem): array
    {
        $where = ' AND estoque_grade.id_responsavel = 1';

        $chaveValor = 'produtos.valor_venda_ms';
        $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
        if ($origem === Origem::ML) {
            $chaveValor = 'produtos.valor_venda_ml';
            $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
            $where = '';
        }

        $resultados = DB::select(
            "SELECT produtos.id `id_produto`,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) `nome_produto`,
                $chaveValor `valor_venda`,
                IF (produtos.promocao > 0, $chaveValorHistorico, NULL) `valor_venda_historico`,
                produtos.preco_promocao `desconto`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) `json_grade_estoque`,
                catalogo_fixo.expira_em
            FROM catalogo_fixo
            INNER JOIN produtos ON produtos.id = catalogo_fixo.id_produto
                AND produtos.bloqueado = 0
            INNER JOIN estoque_grade ON estoque_grade.id_produto = catalogo_fixo.id_produto
                AND estoque_grade.estoque > 0
            INNER JOIN publicacoes_produtos ON publicacoes_produtos.id = catalogo_fixo.id_publicacao_produto
                AND publicacoes_produtos.situacao = 'CR'
            WHERE catalogo_fixo.tipo = '" .
                CatalogoFixoService::TIPO_PROMOCAO_TEMPORARIA .
                "'
                AND catalogo_fixo.expira_em > NOW()
                $where
            GROUP BY catalogo_fixo.id
            ORDER BY catalogo_fixo.expira_em
            LIMIT 100"
        );

        // https://github.com/mobilestock/backend/issues/153
        date_default_timezone_set('America/Sao_Paulo');

        $resultados = array_map(function ($item) {
            $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grade_estoque']);

            $dateTimeExpiracao = new Carbon($item['expira_em']);
            $valor = (new Carbon())->diffForHumans($dateTimeExpiracao, true, false);

            $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda']);

            return [
                'id_produto' => $item['id_produto'],
                'nome' => $item['nome_produto'],
                'preco' => $item['valor_venda'],
                'preco_original' => $item['valor_venda_historico'],
                'desconto' => $item['desconto'],
                'valor_parcela' => $valorParcela,
                'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                'foto' => $item['foto'],
                'grades' => $grades,
                'categoria' => [
                    'tipo' => CatalogoFixoService::TIPO_PROMOCAO_TEMPORARIA,
                    'valor' => $valor,
                ],
            ];
        }, $resultados);

        return $resultados;
    }

    // static public function contaItensValidosCatalogoFixoMeulook(PDO $conexao): int
    // {
    //     return (int) $conexao->query(
    //         "SELECT COUNT(DISTINCT catalogo_fixo.id) qtd
    //         FROM catalogo_fixo
    //         INNER JOIN publicacoes ON
    //             publicacoes.id = catalogo_fixo.id_publicacao AND
    //             publicacoes.situacao = 'CR'
    //         INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_publicacao = publicacoes.id
    //         INNER JOIN produtos ON
    //             produtos.id = publicacoes_produtos.id_produto AND
    //             produtos.bloqueado = 0
    //         INNER JOIN estoque_grade ON
    //             estoque_grade.id_produto = produtos.id AND
    //             estoque_grade.estoque > 0"
    //     )->fetch(PDO::FETCH_ASSOC)['qtd'];
    // }

    //    static public function incrementarQuantidadeAcessoAssincrono($idPublicacao)
    //    {
    //        $host = $_ENV['AMBIENTE'] == 'teste'
    //            ? $_ENV['URL_MOBILE']
    //            : 'https://127.0.0.1';
    //        $url = "$host/api_meulook/publicacoes/incrementar_visualizacao_assincrono/$idPublicacao";
    //        $cliente = new AsyncRequester($url);
    //        $cliente->get();
    //    }

    // static public function incrementarQuantidadeAcesso(\PDO $conexao, $idPublicacao)
    // {
    //     return $conexao->exec("UPDATE publicacoes
    //         SET publicacoes.quantidade_acessos = publicacoes.quantidade_acessos + 1
    //         WHERE publicacoes.id = $idPublicacao"
    //     );
    // }

    public static function consultaTotaisComissoes(PDO $conexao, int $idColaborador): array
    {
        $diasTroca = ConfiguracaoService::consultaDatasDeTroca($conexao)[0]['qtd_dias_disponiveis_troca_normal'];
        $dataPagamento = "entregas_faturamento_item.data_entrega + INTERVAL $diasTroca DAY";
        $stmt = $conexao->prepare(
            "SELECT
                (
                    SELECT COALESCE(SUM(lancamento_financeiro_pendente.valor), 0)
                    FROM lancamento_financeiro_pendente
                    WHERE lancamento_financeiro_pendente.id_colaborador = :idColaborador
                        AND lancamento_financeiro_pendente.origem = 'TR'
                ) valor_troca_agendada,
                (
                    SELECT SUM(transacao_financeiras_produtos_itens.comissao_fornecedor)
                    FROM transacao_financeiras_produtos_itens
                    LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    WHERE transacao_financeiras_produtos_itens.id_fornecedor = :idColaborador
                        AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
                        AND transacao_financeiras_produtos_itens.tipo_item <> 'AC'
                        AND logistica_item.situacao IN ('PE', 'SE', 'CO')
                        AND IF(COALESCE(entregas_faturamento_item.situacao, 'NULO') = 'EN',
                            DATE($dataPagamento) >= CURRENT_DATE(), 1)
                ) valor_venda_pendente,
                (
                    SELECT SUM(transacao_financeiras_produtos_itens.comissao_fornecedor)
                    FROM transacao_financeiras_produtos_itens
                    LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    WHERE transacao_financeiras_produtos_itens.id_fornecedor = :idColaborador
                        AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
                        AND logistica_item.situacao IN ('PE','SE','CO')
                        AND entregas_faturamento_item.situacao = 'EN'
                        AND DATE($dataPagamento) < CURRENT_DATE()
                ) valor_venda_finalizado,
                CONCAT(
                    '{$_ENV['URL_LOOKPAY']}home?xls=',
                    (SELECT usuarios.token FROM usuarios WHERE usuarios.id_colaborador = :idColaborador LIMIT 1),
                    '&amp;status=true&meulook=true'
                ) link_mobile_pay"
        );
        $stmt->execute(['idColaborador' => $idColaborador]);
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $consulta;
    }

    // public static function limpaQuantidadesAcessos(\PDO $conexao): void
    // {
    //     $conexao->query(
    //         "UPDATE publicacoes
    //         SET publicacoes.quantidade_acessos = 0
    //         WHERE publicacoes.quantidade_acessos > 0;"
    //     );
    // }

    public static function autocompletePesquisa(string $pesquisa): array
    {
        $pesquisa = ConversorStrings::tratarTermoOpensearch($pesquisa);
        $openSearchClient = new OpenSearchClient();
        $resultados = $openSearchClient->autocompletePesquisa($pesquisa);
        $resultados = $resultados->body;

        $nomes = [];
        $buckets = $resultados['aggregations']['contagem']['buckets'];

        foreach ($buckets as $item) {
            $nomeComercial = ConversorStrings::tratarTermoOpensearch($item['key']);

            # Se começa com o termo digitado (obrigatório) + letras ou números (opcional)
            # Seguido de um espaço (opcional)
            # Seguido de uma palavra (opcional)
            preg_match('/(^' . $pesquisa . '[A-z0-9]*) *([A-z0-9]*)/', $nomeComercial, $regex);
            $palavraPrincipal = $regex[1] ?? '';
            $palavraAuxiliar = $regex[2] ?? '';

            if ($palavraPrincipal === $pesquisa) {
                $nomeComercial = "{$palavraPrincipal} {$palavraAuxiliar}";
            } else {
                $nomeComercial = $palavraPrincipal;
            }

            if ($nomeComercial && !in_array($nomeComercial, $nomes)) {
                $nomes[] = $nomeComercial;
            }
        }

        return $nomes;
    }

    public static function buscaPesquisasPopulares(PDO $conexao, string $origem): array
    {
        $opensearchClient = new OpenSearchClient();
        $resultadosPesquisas = $opensearchClient->buscaPesquisasPopulares();
        $resultadosPesquisas = $resultadosPesquisas->body;
        $buckets = $resultadosPesquisas['aggregations']['contagem']['buckets'];

        $resposta = [];
        $idsExcluidos = [0];
        foreach ($buckets as $item) {
            $palavra = explode(' ', $item['key'])[0];
            $palavraSingular = mb_substr($palavra, 0, -1);
            $palavraPlural = "{$palavra}s";
            if (isset($resposta[$palavra]) || isset($resposta[$palavraSingular]) || isset($resposta[$palavraPlural])) {
                continue;
            }

            $opensearchClient->pesquisa(
                $palavra,
                'MAIS_RELEVANTE',
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                'TODOS',
                'PESQUISA',
                1,
                false,
                $origem
            );
            $hits = $opensearchClient->body['hits']['hits'];
            if (empty($hits)) {
                return [];
            }
            $ids = array_slice(array_column($hits, '_id'), 0, 5);
            [$itensIncluidos, $bindIncluidos] = ConversorArray::criaBindValues($ids, 'incluido');
            $order = implode(
                ',',
                array_map(fn($keyIncluido) => "produtos_foto.id = $keyIncluido DESC", array_keys($bindIncluidos))
            );

            [$itensExcluidos, $bindExcluidos] = ConversorArray::criaBindValues($idsExcluidos, 'excluido');

            $stmt = $conexao->prepare(
                "SELECT produtos_foto.id,
                    produtos_foto.caminho
                FROM produtos_foto
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos_foto.id
                WHERE produtos_foto.id IN ($itensIncluidos)
                    AND produtos_foto.id NOT IN ($itensExcluidos)
                    AND produtos_foto.tipo_foto IN ('MD', 'LG')
                    AND estoque_grade.estoque > 0
                GROUP BY produtos_foto.id
                ORDER BY $order
                LIMIT 1"
            );
            $stmt->execute(array_merge($bindIncluidos, $bindExcluidos));
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            $idsExcluidos[] = $produto['id'];
            $resposta[$palavra] = [
                'nome' => $item['key'],
                'foto' => $produto['caminho'],
            ];
        }

        $resposta = array_values($resposta);
        return $resposta;
    }
}
