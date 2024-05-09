<?php

namespace MobileStock\service\Conferencia;

use Error;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorStrings;
use MobileStock\model\Conferencia\ConferenciaItem;
use MobileStock\model\LogisticaItem;
use MobileStock\model\TipoFrete;
use MobileStock\service\ConfiguracaoService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ConferenciaItemService extends ConferenciaItem
{
    public static function listaItensDisponiveisParaAdicionarNaEntrega(
        PDO $conexao,
        int $idColaborador,
        $pesquisa = ''
    ): array {
        $where = '';

        if (mb_strlen($pesquisa)) {
            $where = " AND (
                UPPER(produtos.nome_comercial) LIKE UPPER(CONCAT('%',:pesquisa,'%'))
                OR UPPER(produtos.cores) LIKE UPPER(CONCAT('%',:pesquisa,'%'))
                OR produtos.id = :pesquisa
                OR UPPER(colaboradores.razao_social) LIKE UPPER(CONCAT('%',:pesquisa,'%'))
            ) ";
        }
        $sql = "SELECT
                    produtos.nome_comercial nome_produto,
                    produtos.id id_produto,
                    logistica_item.nome_tamanho tamanho,
                    logistica_item.uuid_produto uuid,
                    colaboradores.id id_fornecedor,
                    colaboradores.razao_social nome_fornecedor,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto,
                    (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = logistica_item.id_usuario LIMIT 1) nome_conferidor,
                    transacao_financeiras.data_atualizacao data_venda,
                    logistica_item.data_atualizacao  data_conferencia

                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
                INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
                WHERE
                    logistica_item.situacao = 'CO'
                    AND logistica_item.id_entrega IS NULL
                    AND DATE(logistica_item.data_criacao) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                    $where
                GROUP BY logistica_item.uuid_produto
                ORDER BY logistica_item.data_atualizacao DESC;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        if (mb_strlen($pesquisa)) {
            $prepare->bindValue(':pesquisa', $pesquisa);
        }
        $prepare->execute();
        $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);

        $dadosFormatados = array_map(function ($item) {
            $item['nome_produto'] =
                $item['id_produto'] . ' - ' . ConversorStrings::sanitizeString($item['nome_produto']);
            $item['nome_fornecedor'] =
                $item['id_fornecedor'] . ' - ' . ConversorStrings::sanitizeString($item['nome_fornecedor']);
            $item['nome_conferidor'] = ConversorStrings::sanitizeString($item['nome_conferidor']);
            unset($item['id_produto'], $item['id_fornecedor']);

            return $item;
        }, $dados);

        return $dadosFormatados;
    }
    public static function listaItemsParaConferir(PDO $conexao, int $idColaborador): array
    {
        // @author Gustavo210
        // Esta query deve ser ajustada conforme a necessidade e o peso no banco
        $diasParaOCancelamento = ConfiguracaoService::buscaDiasDeCancelamentoAutomatico($conexao);
        $sql = "SELECT
                    logistica_item.id_produto,
                    produtos.nome_comercial nome_produto,
                    logistica_item.nome_tamanho,
                    logistica_item.situacao,
                    logistica_item.uuid_produto,
                    usuarios.nome nome_usuario,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY
                        produtos_foto.tipo_foto = 'MD' DESC,
                        produtos_foto.tipo_foto = 'LG' DESC
                    LIMIT 1
                    ) foto,
                    retorna_dia_util(
                        DATE_ADD(
                            transacao_financeiras.data_atualizacao,
                        INTERVAL $diasParaOCancelamento DAY
                        )
                    ) data_cancelamento,
                    logistica_item.data_atualizacao
                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                INNER JOIN usuarios ON usuarios.id = logistica_item.id_usuario
                INNER JOIN transacao_financeiras ON logistica_item.id_transacao = transacao_financeiras.id AND transacao_financeiras.status = 'PA'
                WHERE
                    logistica_item.id_responsavel_estoque = :idColaborador
                    AND transacao_financeiras.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 15 DAY)
                    AND logistica_item.situacao  IN ('SE','CO','RE')
                ORDER BY logistica_item.situacao = 'SE'DESC, data_cancelamento DESC";
        $prepare = $conexao->prepare($sql);
        $prepare->bindParam(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);

        $dadosFormatados = array_map(function ($item) {
            $item['id_produto'] = (int) $item['id_produto'];
            return $item;
        }, $dados);

        return $dadosFormatados;
    }

    public static function buscaDetalhesDoItem(string $uuidProduto): array
    {
        if (mb_strlen($uuidProduto) < 5) {
            throw new BadRequestHttpException('Falha ao buscar produto');
        }
        $sqlEntrega = "SELECT
                            entregas.id,
                            entregas.situacao
                        FROM entregas
                        INNER JOIN entregas_faturamento_item ON entregas.id = entregas_faturamento_item.id_entrega
                        WHERE
                            entregas_faturamento_item.uuid_produto = :uuidProduto;";

        $entrega = DB::selectOne($sqlEntrega, [':uuidProduto' => $uuidProduto]);

        if (!!$entrega && !in_array($entrega['situacao'], ['AB', 'EX'])) {
            throw new BadRequestHttpException("Este produto ja foi despachado na entrega {$entrega['id']}");
        }
        if (!!$entrega && $entrega['situacao'] === 'AB') {
            throw new BadRequestHttpException("Este item já esta na entrega {$entrega['id']}");
        }
        if (!!$entrega && $entrega['situacao'] === 'EX') {
            throw new BadRequestHttpException("Este produto pertence à entrega {$entrega['id']}, que já está fechada");
        }

        $query = "SELECT
                    acompanhamento_temp.situacao
                FROM acompanhamento_item_temp
                INNER JOIN acompanhamento_temp ON acompanhamento_temp.id = acompanhamento_item_temp.id_acompanhamento
                WHERE acompanhamento_item_temp.uuid_produto = :uuid_produto";

        $acompanhamento = DB::selectOneColumn($query, [':uuid_produto' => $uuidProduto]);

        if ($acompanhamento === 'PAUSADO') {
            throw new BadRequestHttpException(
                'Este produto não pode ser inserido na entrega pois a expedição foi pausada pelo cliente'
            );
        }

        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $nomeCidadeJson =
            "IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id  = 2, JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade'), colaboradores_enderecos.cidade)";
        $nomeUfJson =
            "IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id  = 2, JSON_VALUE(transacao_financeiras_metadados.valor, '$.uf'), colaboradores_enderecos.uf)";

        $sql = "SELECT
                    (
                        SELECT
                            JSON_OBJECT(
                                'id_entrega',entregas.id,
                                'nome_entregador',tipo_frete.nome,
                                'situacao',entregas.situacao,
                                'volumes',entregas.volumes,
                                'id_cliente',colaboradores.id,
                                'nome_cliente',colaboradores.razao_social,
                                'nome_ponto',tipo_frete.nome,
                                'nome_cidade', $nomeCidadeJson,
	                            'nome_uf', $nomeUfJson,
                                'json_etiquetas',
                                    CONCAT(
                                        '[',
                                        GROUP_CONCAT(
                                            DISTINCT
                                            CONCAT(
                                                '\"',
                                                entregas.id_cliente,
                                                '_',
                                                entregas.id,
                                                '_',
                                                entregas_etiquetas.volume,
                                                '\"'
                                            )
                                            ORDER BY entregas_etiquetas.volume ASC
                                        ),
                                        ']'
                                    ),
                                'json_etiquetas_antigas',
                                    CONCAT(
                                        '[',
                                        GROUP_CONCAT(
                                            DISTINCT
                                            CONCAT(
                                                '\"',
                                                entregas.uuid_entrega,
                                                '_',
                                                entregas_etiquetas.uuid_volume,
                                                '\"'
                                            )
                                            ORDER BY entregas_etiquetas.volume ASC
                                        ),
                                        ']'
                                    )
                            )
                        FROM entregas
                        INNER JOIN entregas_etiquetas ON entregas_etiquetas.id_entrega = entregas.id
                        INNER JOIN tipo_frete entregas_tipo_frete ON entregas_tipo_frete.id = entregas.id_tipo_frete
                        WHERE
                            entregas_tipo_frete.id = tipo_frete.id
                            AND IF(tipo_frete.tipo_ponto = 'PM'
                                , (
                                    entregas.id_raio = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
                                    AND entregas_tipo_frete.tipo_ponto = 'PM'
                                )
                                , IF(entregas_tipo_frete.categoria = 'MS'
                                    ,logistica_item.id_cliente = entregas.id_cliente
                                    ,entregas_tipo_frete.categoria IN ('ML','PE')
                                )
                            )
                            AND entregas.situacao = 'AB'
                        LIMIT 1
                    ) json_entrega,
                    tipo_frete.id id_tipo_frete,
                    tipo_frete.tipo_ponto,
                    JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio') json_id_raio,
                    IF (
                        tipo_frete.tipo_ponto = 'PP' OR JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') IS NULL,
                        NULL,
                        (
                            SELECT transportadores_raios.apelido
                            FROM transportadores_raios
                            WHERE transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
                        )
                    ) AS `apelido_raio`,
                    IF (tipo_frete.categoria = 'MS', colaboradores.tipo_embalagem, (
                        SELECT colaboradores.tipo_embalagem
                        FROM colaboradores
                        WHERE colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
                    )) as `tipo_embalagem`,
                    (
						SELECT
							IF(transacao_financeiras.origem_transacao = 'ML', 'ML', 'MS')
                        FROM transacao_financeiras
                        WHERE transacao_financeiras.id = logistica_item.id_transacao
                    ) AS `categoria`,
                    tipo_frete.id_colaborador id_cliente_tipo_frete,
                    logistica_item.uuid_produto,
                    logistica_item.situacao situacao_produto,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD'
                        LIMIT 1
                    ) foto,
                    (
                        SELECT
                            CONCAT(produtos.descricao,' ',produtos.cores)
                        FROM produtos
                        WHERE produtos.id = logistica_item.id_produto
                    ) nome_produto,
                    logistica_item.id_produto,
                    logistica_item.nome_tamanho,
                    logistica_item.id_responsavel_estoque,
                    tipo_frete.id IN ($idTipoFreteEntregaCliente) AS eh_entrega_cliente
                FROM logistica_item
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                WHERE logistica_item.uuid_produto = :uuidProduto
                GROUP BY logistica_item.id_colaborador_tipo_frete
                LIMIT 1";

        $dados = DB::selectOne($sql, [':uuidProduto' => $uuidProduto]);

        if (!$dados) {
            throw new BadRequestHttpException('problema ao buscar produto, notifique a equipe de TI');
        }
        $entrega = $dados['entrega'];
        unset($dados['entrega']);
        $dados = array_merge($dados, $entrega);

        if ($dados['situacao_produto'] === 'PE') {
            throw new BadRequestHttpException(
                'Para adicionar este item na entrega, primeiro você deve separar o produto'
            );
        }
        if ($dados['situacao_produto'] === 'SE') {
            throw new BadRequestHttpException('Para adicionar este item na entrega, primeiro ele deve ser conferido');
        }
        if ($dados['situacao_produto'] === 'RE') {
            throw new BadRequestHttpException('Este produto foi cancelado, favor devolver ao fornecedor');
        }
        if ($dados['situacao_produto'] === 'DE') {
            throw new BadRequestHttpException(
                'Este produto não pode ser inserido na entrega pois voltou como devolução'
            );
        }
        if ($dados['situacao_produto'] === 'DF') {
            throw new BadRequestHttpException('Este produto não pode ser inserido na entrega pois voltou como defeito');
        }
        if ($dados['situacao_produto'] === 'ES') {
            throw new BadRequestHttpException('Este produto não pode ser inserido na entrega pois voltou como estorno');
        }

        $situacaoFinalLogistica = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $idColaboradorTipoFreteEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;

        $where = ' AND logistica_item.situacao = :situacaoFinalLogistica ';
        $join = '';
        $binds = [
            ':idClienteTipoFrete' => $dados['id_cliente_tipo_frete'],
            ':situacaoFinalLogistica' => $situacaoFinalLogistica,
        ];
        switch (true) {
            case $dados['tipo_ponto'] === 'PM':
                $join = " INNER JOIN transacao_financeiras_metadados item_transacao_financeiras_metadados ON
                item_transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                AND item_transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON' ";

                $where = " AND logistica_item.situacao = :situacaoFinalLogistica
                        AND JSON_VALUE(item_transacao_financeiras_metadados.valor, '$.id_raio') = :idRaio ";
                $binds['idRaio'] = $dados['id_raio'];
                break;

            case in_array($dados['id_cliente_tipo_frete'], explode(',', $idColaboradorTipoFreteEntregaCliente)):
                $where = " AND logistica_item.id_cliente = :idCliente
                AND IF(logistica_item.id_responsavel_estoque = 1,
                     logistica_item.situacao <= :situacaoFinalLogistica,
                     logistica_item.situacao = :situacaoFinalLogistica
                 )";
                $binds['idCliente'] = $dados['id_cliente'];
                break;
        }

        $sql = "SELECT
                    logistica_item.uuid_produto,
                    logistica_item.situacao,
                    logistica_item.id_produto,
                    logistica_item.nome_tamanho,
                    produtos.nome_comercial 'nome_produto',
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) 'foto',
                    IF(logistica_item.id_responsavel_estoque = 1,
                        'FULL',
                        'EXTERNO'
                    ) 'categoria',
                    IF( logistica_item.id_responsavel_estoque = 1,
                        produtos.localizacao,
                        NULL
                    ) 'localizacao',
                    (
                        SELECT
                            CONCAT(
                                '(',
                                usuarios.id_colaborador,
                                ') ',
                                usuarios.nome
                            )
                        FROM usuarios
                        WHERE usuarios.id = logistica_item.id_usuario
                    ) 'usuario',
                    DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i') 'data_atualizacao'
                FROM logistica_item
                INNER JOIN produtos ON logistica_item.id_produto = produtos.id
                $join
                WHERE
                    logistica_item.id_colaborador_tipo_frete = :idClienteTipoFrete
                    AND logistica_item.id_entrega IS NULL
                    $where
                ORDER BY logistica_item.data_atualizacao;";
        $dados['produtos_pendentes'] = DB::select($sql, $binds);
        return $dados;
    }

    public static function buscaConferidosDoSeller(PDO $conexao, int $idColaborador): array
    {
        $sql = "SELECT
                    logistica_item.id_produto,
                    produtos.nome_comercial AS `nome_produto`,
                    produtos.cores,
                    logistica_item.nome_tamanho AS `tamanho`,
                    logistica_item.uuid_produto AS `uuid`,
                    logistica_item.id_transacao,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) AS `foto`,
                    colaboradores.id AS `id_cliente`,
                    colaboradores.razao_social AS `nome_cliente`,
                    DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y às %H:%i') AS `data_conferencia`
                FROM logistica_item
                JOIN produtos ON produtos.id = logistica_item.id_produto
                JOIN colaboradores ON logistica_item.id_colaborador_tipo_frete = colaboradores.id
                WHERE logistica_item.id_responsavel_estoque = :id_responsavel_estoque
                    AND logistica_item.situacao = 'CO'
                    AND logistica_item.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY logistica_item.data_atualizacao";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_responsavel_estoque', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $dados;
    }
}
