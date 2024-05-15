<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Globals;
use MobileStock\model\Colaborador;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\LogisticaItem;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Municipio;
use MobileStock\model\Pedido\PedidoItem;
use MobileStock\model\TipoFrete;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\Ranking\RankingService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TipoFreteService extends TipoFrete
{
    public function salva(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $this->id ? $geradorSql->update() : $geradorSql->insertSemFilter();

        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);

        $this->id = $conexao->lastInsertId();
    }

    public static function buscaIdTipoFrete(int $idColaborador): int
    {
        $idTipoFrete = DB::selectOneColumn(
            "SELECT tipo_frete.id
            FROM tipo_frete
            WHERE tipo_frete.id_colaborador = :id_colaborador",
            ['id_colaborador' => $idColaborador]
        );
        if (empty($idTipoFrete)) {
            throw new NotFoundHttpException('Esse colaborador não é um ponto');
        }

        return $idTipoFrete;
    }

    public static function adicionaCentralColeta(
        PDO $conexao,
        int $idColaborador,
        int $idColaboradorPontoColeta,
        int $idUsuario
    ): void {
        $sql = $conexao->prepare(
            "UPDATE tipo_frete SET
                tipo_frete.id_usuario = :id_usuario,
                tipo_frete.id_colaborador_ponto_coleta = :id_colaborador_ponto_coleta
            WHERE tipo_frete.id_colaborador = :id_colaborador;"
        );
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':id_colaborador_ponto_coleta', $idColaboradorPontoColeta, PDO::PARAM_INT);
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() > 1) {
            throw new Exception('Erro ao atualizar central de coleta');
        }
    }

    //    public static function listaPontosMeuLook(\PDO $conexao): array
    //    {
    //        $dados = $conexao->query(
    //            "SELECT
    //                    tipo_frete.id,
    //                    tipo_frete.nome,
    //                    tipo_frete.mensagem,
    //                    tipo_frete.categoria,
    //                    tipo_frete.id_colaborador,
    //                    tipo_frete.emitir_nota_fiscal,
    //                    tipo_frete.tipo_embalagem,
    //                    tipo_frete.previsao_entrega previsao,
    //                    tipo_frete.percentual_comissao preco_ponto,
    //                    tipo_frete.horario_de_funcionamento horario,
    //                    colaboradores.cidade,
    //                    colaboradores.endereco,
    //                    colaboradores.telefone,
    //                    colaboradores.numero,
    //                    colaboradores.bairro,
    //                    colaboradores.complemento,
    //                    colaboradores.uf,
    //                    colaboradores.cep,
    //                    colaboradores.email,
    //                    colaboradores.id_cidade,
    //                    COALESCE(JSON_OBJECT('quantidade', @quantidade := COUNT(pedido_item_meu_look.id), 'valor', SUM(pedido_item_meu_look.preco)), JSON_OBJECT('quantidade', @quantidade := 0, 'valor', 0)) transacao_json,
    //                    COALESCE((
    //                        SELECT
    //                            DATE_FORMAT(entregas_log_bipagem_usuario.data_criacao, '%d/%m/%Y %H:%i')
    //                        FROM entregas
    //                        JOIN entregas_log_bipagem_usuario ON entregas_log_bipagem_usuario.id_entrega = entregas.id
    //                        WHERE entregas.id_tipo_frete = tipo_frete.id
    //                            AND entregas.situacao IN ('PT','PR','EN')
    //                            AND entregas_log_bipagem_usuario.mensagem REGEXP 'entrega para a situacao Ponto Transporte'
    //                        ORDER BY entregas_log_bipagem_usuario.data_criacao DESC
    //                        LIMIT 1
    //                    ), '-') data_ultimo_envio,
    //                    tipo_frete.categoria <> 'PE' AND @quantidade > 0 ehSelecionavel,
    //                    'Padrão' percentual_comissao_mobile,
    //                    'Padrão' percentual_comissao_mobile_fast
    //                FROM tipo_frete
    //                JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
    //                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.id_responsavel_estoque = 1 AND pedido_item_meu_look.id_ponto = colaboradores.id
    //                WHERE tipo_frete.categoria = 'ML'
    //                    AND pedido_item_meu_look.situacao = 'PA'
    //                    AND pedido_item_meu_look.id_faturamento IS NULL
    //                    AND NOT EXISTS (SELECT 1 FROM colaboradores_suspeita_fraude WHERE colaboradores_suspeita_fraude.id_colaborador = pedido_item_meu_look.id_cliente AND colaboradores_suspeita_fraude.situacao IN ('PE', 'FR'))
    //                GROUP BY tipo_frete.id
    //                ORDER BY tipo_frete.id DESC"
    //        )->fetchAll(PDO::FETCH_ASSOC);
    //
    //        $dadosFormatados = array_map(function (array $ponto) {
    //            $ponto['transacao_json'] = json_decode($ponto['transacao_json'], true);
    //            $ponto['horario'] = preg_replace("!\r?\n!", "", $ponto['horario']);
    //            $ponto['quantidade'] = $ponto['transacao_json']['quantidade'];
    //            $ponto['valor'] = (float) $ponto['transacao_json']['valor'];
    //            return $ponto;
    //        }, $dados ?: []);
    //
    //        return $dadosFormatados;
    //    }

    public function alteraCategoriaTipoFrete(): void
    {
        $binds = [
            ':categoria' => $this->categoria,
            ':id_usuario' => Auth::user()->id,
            ':id_tipo_frete' => $this->id,
        ];
        $sql = "UPDATE tipo_frete
                SET tipo_frete.categoria = :categoria, tipo_frete.id_usuario = :id_usuario
                WHERE tipo_frete.id = :id_tipo_frete;";
        if ($this->categoria == 'PE') {
            $idsQueSeraoAtualizados = DB::selectColumns(
                "SELECT colaboradores.id
                 FROM colaboradores
                 WHERE colaboradores.id_tipo_entrega_padrao = ?",
                [$this->id]
            );

            [$bindIds, $idsColaboradores] = ConversorArray::criaBindValues($idsQueSeraoAtualizados, 'id_colaborador');

            $sql .= "UPDATE colaboradores
                     SET colaboradores.id_tipo_entrega_padrao = 0
                     WHERE colaboradores.id IN ($bindIds)";
        }

        DB::update($sql, $binds + $idsColaboradores);
    }

    public function alteraDataPrevisaoTipoFrete(PDO $conexao, $idUsuario)
    {
        $data = $this->previsao_entrega === 'NULL' ? 'NULL' : "'{$this->previsao_entrega}'";
        $conexao->exec(
            "UPDATE tipo_frete SET tipo_frete.previsao_entrega = $data, id_usuario = $idUsuario WHERE tipo_frete.id = $this->id"
        );
    }

    public function buscaProdutosDoPonto(PDO $conexao)
    {
        return [];
        $prepare = $conexao->prepare(
            "SELECT (
                SELECT produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id = pedido_item_meu_look.id_produto AND
                produtos_foto.tipo_foto = 'MD' LIMIT 1
              ) foto,
                transacao_financeiras_produtos_itens.id_transacao,
                pedido_item_meu_look.nome_tamanho,
                pedido_item_meu_look.preco valor_venda,
                transacao_financeiras_produtos_itens.tipo_item,
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.uuid,
                consumidor.razao_social nome,
                consumidor.telefone,
                transacao_financeiras_produtos_itens.data_atualizacao
              FROM transacao_financeiras_produtos_itens
              INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = transacao_financeiras_produtos_itens.uuid
              INNER JOIN colaboradores consumidor ON consumidor.id = pedido_item_meu_look.id_cliente
              WHERE transacao_financeiras_produtos_itens.tipo_item = 'PR'
              AND pedido_item_meu_look.id_responsavel_estoque = 1
              AND pedido_item_meu_look.id_ponto = :id_colaborador
              GROUP BY transacao_financeiras_produtos_itens.uuid
              ORDER BY transacao_financeiras_produtos_itens.data_atualizacao, nome"
        );
        $prepare->bindValue(':id_colaborador', $this->id_colaborador, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
        return $dados;
    }

    public static function buscaPontosAtivos(): array
    {
        $pontos = DB::select(
            "SELECT
                tipo_frete.id,
                tipo_frete.id_usuario,
                tipo_frete.id_colaborador,
                tipo_frete.nome,
                tipo_frete.emitir_nota_fiscal,
                tipo_frete.categoria,
                tipo_frete.mensagem,
                tipo_frete.horario_de_funcionamento horario,
                colaboradores.email,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.uf,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.cep,
                colaboradores_enderecos.logradouro endereco,
                colaboradores_enderecos.bairro,
                transportadores_raios.valor preco_ponto,
                transportadores_raios.prazo_forcar_entrega,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro,
                pontos_coleta.id IS NOT NULL eh_ponto_coleta
            FROM tipo_frete
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador
            LEFT JOIN transportadores_raios ON transportadores_raios.esta_ativo = 1
                AND transportadores_raios.id_colaborador = colaboradores.id
            WHERE tipo_frete.categoria = 'ML'
                AND tipo_frete.tipo_ponto = 'PP'
            GROUP BY tipo_frete.id
            ORDER BY tipo_frete.id DESC;"
        );

        return $pontos;
    }

    public static function verificaPontoEntrega(PDO $conexao, $idColaborador)
    {
        $consulta = $conexao
            ->query(
                "SELECT 1
            FROM tipo_frete
            WHERE
                tipo_frete.id_colaborador = $idColaborador AND
                tipo_frete.categoria = 'ML'"
            )
            ->fetch(PDO::FETCH_ASSOC);

        return !!$consulta;
    }

    public static function buscaConsumidores(PDO $conexao, $idPonto, $busca = '')
    {
        $consulta = $conexao->prepare(
            "SELECT
                logistica_item.id_cliente,
                colaboradores.razao_social,
                colaboradores.usuario_meulook,
                colaboradores.email,
                COALESCE(colaboradores.telefone, colaboradores.telefone2) telefone
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            WHERE
                logistica_item.id_colaborador_tipo_frete = :idPonto AND
                (
                    colaboradores.razao_social REGEXP :busca OR
                    colaboradores.usuario_meulook REGEXP :busca OR
                    colaboradores.telefone REGEXP :busca OR
                    colaboradores.telefone2 REGEXP :busca
                )
            GROUP BY logistica_item.id_cliente
            ORDER BY colaboradores.razao_social"
        );
        $consulta->bindValue(':idPonto', $idPonto, PDO::PARAM_INT);
        $consulta->bindValue(':busca', $busca, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscaPorPontos(): array
    {
        $pontos = DB::select(
            "SELECT
                tipo_frete.id,
                tipo_frete.nome,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores_enderecos.cidade,
                IF(tipo_frete.categoria = 'ML', 'Ponto Ativado', 'Ponto Desativado') situacao_ponto,
                tipo_frete.categoria,
                (
                    SELECT estados.nome
                    FROM estados
                    WHERE estados.uf = colaboradores_enderecos.uf
                ) estado,
                COALESCE(tipo_frete.previsao_entrega, 'Sem prazo de entrega') previsao,
                (
                    SELECT
                        IF(entregas.id IS NULL,NULL,
                        JSON_OBJECT(
                            'atrasado', entregas.situacao = 'PT'
                                    OR (
                                    entregas.situacao = 'EN'
                                    AND entregas_faturamento_item.situacao = 'PE'
                                )
                                OR entregas_faturamento_item.situacao = 'AR',
                            'ultimo_envio', MAX(entregas.data_criacao)
                        )
                    )
                    FROM entregas
                    INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id AND entregas_faturamento_item.situacao IN ('AR','PE')
                    WHERE
                        entregas.situacao IN ('PT','EN')
                        AND entregas.id_tipo_frete = tipo_frete.id
                ) json_entrega
            FROM tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE
                tipo_frete.categoria IN ('ML', 'PE')
            ORDER BY JSON_EXTRACT(json_entrega, '$.ultimo_envio') DESC, tipo_frete.categoria = 'ML' DESC;"
        );

        $pontos = array_map(function (array $ponto): array {
            $ponto['atrasado'] = false;
            $ponto['ultimo_envio'] = '-';
            if (!empty($ponto['entrega'])) {
                $ponto = array_merge($ponto, $ponto['entrega']);
                $ponto['ultimo_envio'] = (new Carbon($ponto['ultimo_envio']))->format('d/m/Y H:i');
            }

            $ponto['qrCodeTelefone'] = Globals::geraQRCODE(
                'https://api.whatsapp.com/send/?phone=55' . $ponto['telefone']
            );
            unset($ponto['entrega']);

            return $ponto;
        }, $pontos);

        return $pontos;
    }

    public static function buscaProdutosAbertoPonto(string $situacao): array
    {
        if ($situacao === 'pagos') {
            $sql = "AND logistica_item.situacao = 'PE'";
        } else {
            $sql = "AND logistica_item.situacao = 'CO' AND logistica_item.id_entrega IS NULL";
        }

        $dados = DB::select(
            "SELECT (
                    SELECT produtos_foto.caminho FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto,
                logistica_item.id_produto,
                colaboradores.razao_social nome,
                colaboradores.telefone,
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade') AS `cidade`,
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.bairro') AS `bairro`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            INNER JOIN transacao_financeiras_metadados ON
                transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            WHERE logistica_item.id_colaborador_tipo_frete = :id_colaborador
                AND NOT EXISTS(SELECT 1
                            FROM entregas
                            WHERE entregas.id = logistica_item.id_entrega
                                AND entregas.situacao = 'EN')
                $sql
            GROUP BY logistica_item.uuid_produto
            ORDER BY logistica_item.data_atualizacao, colaboradores.razao_social",
            [
                'id_colaborador' => Auth::user()->id_colaborador,
            ]
        );

        return $dados;
    }

    public static function buscaProdutosCaminhoPonto(bool $aguardandoColeta): array
    {
        $situacao = "= 'PT'";
        if ($aguardandoColeta) {
            $situacao = "IN ('AB', 'EX')";
        }

        $resultado = DB::select(
            "SELECT
                        logistica_item.id_produto,
                        colaboradores.razao_social AS `nome`,
                        colaboradores.telefone,
                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade') AS `cidade`,
                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.bairro') AS `bairro`,
                        (
                            SELECT
                                produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = logistica_item.id_produto
                            ORDER BY produtos_foto.tipo_foto IN ('MD','LG') DESC
                            LIMIT 1
                        ) AS `foto`
                    FROM logistica_item
                    INNER JOIN entregas
                        ON entregas.situacao $situacao
                        AND logistica_item.id_entrega = entregas.id
                    INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
                    INNER JOIN transacao_financeiras_metadados ON
                        transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    WHERE
                        logistica_item.id_colaborador_tipo_frete = :id_colaborador
                    GROUP BY logistica_item.uuid_produto
                    ORDER BY
                        cidade ASC,
                        bairro ASC,
                        logistica_item.data_atualizacao,
                        colaboradores.razao_social",
            [
                'id_colaborador' => Auth::user()->id_colaborador,
            ]
        );

        return $resultado;
    }

    public static function buscaUltimaEntregaPonto(): array
    {
        $resultado = DB::select(
            "SELECT
                        entregas_faturamento_item.nome_tamanho,
                        entregas_faturamento_item.id_produto,
                        entregas_faturamento_item.origem,
                        colaboradores.razao_social AS `nome`,
                        colaboradores.telefone,
                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade') AS `cidade`,
                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.bairro') AS `bairro`,
                        (
                            SELECT
                                produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = logistica_item.id_produto
                            ORDER BY produtos_foto.tipo_foto IN ('MD','LG') DESC
                            LIMIT 1
                        ) AS `foto`
                    FROM logistica_item
                    INNER JOIN entregas_faturamento_item
                        ON entregas_faturamento_item.situacao <> 'EN'
                        AND entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                    INNER JOIN entregas
                        ON entregas.situacao = 'EN'
                        AND entregas.id = entregas_faturamento_item.id_entrega
                    INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
                    INNER JOIN transacao_financeiras_metadados ON
                        transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    WHERE
                        logistica_item.id_colaborador_tipo_frete = :id_colaborador
                    ORDER BY
                        entregas.data_entrega ASC,
                        colaboradores.razao_social ASC",
            [
                'id_colaborador' => Auth::user()->id_colaborador,
            ]
        );

        return $resultado;
    }

    public static function buscaHistoricoConsumidor(PDO $conexao, int $idColaborador, int $idPonto): array
    {
        $consulta = $conexao->prepare(
            "SELECT
                entregas.id,
                DATE_FORMAT(entregas.data_criacao, '%d/%m/%Y %H:%i') data,
                CONCAT(entregas.uuid_entrega, '_', logistica_item.id_cliente, '_ENTREGA') qrcode,
                CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                    'id', logistica_item.id_produto,
                    'nome', COALESCE(produtos.nome_comercial, produtos.descricao),
                    'tamanho', logistica_item.nome_tamanho,
                    'devolucao', troca_pendente_agendamento.uuid,
                    'pedido', logistica_item.id_transacao,
                    'data_compra', (
                        SELECT DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y')
                        FROM transacao_financeiras
                        WHERE transacao_financeiras.id = logistica_item.id_transacao
                    ),
                    'foto', (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = logistica_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ),
                    'qrcode', CONCAT('produto/', logistica_item.id_produto, '?w=', logistica_item.uuid_produto)
                )),']') produtos
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                AND entregas.data_atualizacao > CURDATE() - INTERVAL 3 MONTH
            LEFT JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = logistica_item.uuid_produto
            WHERE logistica_item.id_cliente = :idColaborador
                AND logistica_item.id_colaborador_tipo_frete = :idResponsavelPonto
            GROUP BY entregas.id
            ORDER BY entregas.id DESC;"
        );
        $consulta->execute([
            ':idColaborador' => $idColaborador,
            ':idResponsavelPonto' => $idPonto,
        ]);
        $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            return [];
        }

        return array_map(function ($item) {
            $item['qrcode'] = Globals::geraQRCODE($item['qrcode']);
            $item['produtos'] = json_decode($item['produtos'], true);

            $item['produtos'] = array_map(function ($produto) {
                $produto['qrcode'] = Globals::geraQRCODE(urlencode($produto['qrcode']));
                $produto['devolucao'] = (bool) $produto['devolucao'];
                return $produto;
            }, $item['produtos']);

            return $item;
        }, $resultado);
    }

    public static function categoriaTipoFrete(): ?string
    {
        $categoria = DB::selectOneColumn(
            "SELECT
                tipo_frete.categoria
            FROM tipo_frete
            WHERE tipo_frete.id_colaborador = :id_colaborador;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );

        return $categoria;
    }

    public static function tipoPonto(int $idColaborador): ?string
    {
        $tipoPonto = DB::selectOneColumn(
            "SELECT tipo_frete.tipo_ponto
            FROM tipo_frete
            WHERE tipo_frete.id_colaborador = :id_colaborador;",
            ['id_colaborador' => $idColaborador]
        );

        return $tipoPonto;
    }

    public static function validarPosicaoPonto(PDO $conexao, $latitude, $longitude)
    {
        $consulta = $conexao
            ->query(
                "SELECT
                tipo_frete.id,
                tipo_frete.nome,
                tipo_frete.latitude,
                tipo_frete.longitude,
                distancia_geolocalizacao(
                    $latitude,
                    $longitude,
                    tipo_frete.latitude,
                    tipo_frete.longitude
                ) distancia
            FROM tipo_frete
            WHERE
                tipo_frete.categoria = 'ML' AND
                tipo_frete.latitude IS NOT NULL AND
                tipo_frete.longitude IS NOT NULL
            ORDER BY distancia
            LIMIT 1"
            )
            ->fetch(PDO::FETCH_ASSOC);

        if (!$consulta) {
            return [
                'id' => 0,
                'nome' => 'Ponto Placeholder',
                'latitude' => 62.203988102161695,
                'longitude' => 98.89936989737001,
                'distancia' => 9876543210,
            ];
        }

        return $consulta;
    }

    // public static function buscaTipoFretePorUUID(PDO $conexao, string $uuid): int {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             tipo_frete.id
    //         FROM logistica_item
    //         JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //         WHERE logistica_item.`uuid_produto` = :uuid");
    //     $stmt->bindValue(':uuid', $uuid);
    //     $stmt->execute();
    //     $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $dados['id'];
    // }

    //    public static function buscaPontosDisponiveisParaFecharFaturamento(PDO $conexao): array
    //    {
    //        $consulta = $conexao->query(
    //            "SELECT
    //                            tipo_frete.id id_ponto
    //                        FROM pedido_item_meu_look
    //                        INNER JOIN tipo_frete ON tipo_frete.id_colaborador = pedido_item_meu_look.id_ponto
    //                        WHERE pedido_item_meu_look.situacao = 'PA'
    //                            AND tipo_frete.categoria = 'ML'
    //                            AND pedido_item_meu_look.id_ponto IS NOT NULL
    //                            AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_faturamento WHERE transacao_financeiras_faturamento.id_transacao = pedido_item_meu_look.id_transacao)
    //                        GROUP BY pedido_item_meu_look.id_ponto
    //                        HAVING COUNT(DISTINCT pedido_item_meu_look.id) >= 40"
    //        )->fetchAll(\PDO::FETCH_ASSOC);
    //
    //        return array_column($consulta, 'id_ponto');
    //    }

    public static function buscaDadosPonto(int $idTipoFrete): array
    {
        $colaborador = DB::selectOne(
            "SELECT
                tipo_frete.id_colaborador,
                tipo_frete.tipo_ponto
            FROM tipo_frete
            WHERE tipo_frete.id = :idTipoFrete;",
            ['idTipoFrete' => $idTipoFrete]
        );
        if (empty($colaborador)) {
            throw new NotFoundHttpException('Ponto não encontrado');
        }

        return $colaborador;
    }

    public static function buscaValorVendas(PDO $conexao): array
    {
        $mesAtual = RankingService::montaFiltroPeriodo($conexao, ['logistica_item.data_criacao'], 'mes-atual');
        $mesPassado = RankingService::montaFiltroPeriodo($conexao, ['logistica_item.data_criacao'], 'mes-passado');

        $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $sql = $conexao->query("SELECT
        usuarios.telefone,
        tipo_frete.nome,
        colaboradores.razao_social,
        COALESCE(SUM((
            SELECT SUM(IF(
                    logistica_item.situacao <= $situacaoFinalProcesso,
                    pedido_item_meu_look.preco,
                    0
                ))
            FROM pedido_item_meu_look
            INNER JOIN logistica_item ON logistica_item.uuid_produto = pedido_item_meu_look.uuid
            WHERE 1 = 1
 			$mesAtual
            AND pedido_item_meu_look.id_ponto = tipo_frete.id_colaborador
            AND pedido_item_meu_look.situacao = 'PA'
        )), 0) mes_atual,
        COALESCE(SUM((
            SELECT SUM(IF(
                    logistica_item.situacao <= $situacaoFinalProcesso,
                    pedido_item_meu_look.preco,
                    0
                ))
            FROM pedido_item_meu_look
            INNER JOIN logistica_item ON logistica_item.uuid_produto = pedido_item_meu_look.uuid
            WHERE 1 = 1
            $mesPassado
            AND pedido_item_meu_look.id_ponto = tipo_frete.id_colaborador
            AND pedido_item_meu_look.situacao = 'PA'
        )), 0) mes_passado
        FROM tipo_frete
        INNER JOIN usuarios ON usuarios.id_colaborador = tipo_frete.id_colaborador
        INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
        WHERE tipo_frete.categoria <> 'PE'
        GROUP BY tipo_frete.id
        ORDER BY mes_atual DESC");
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }

    public static function buscaTipoFrete(array $produtos): array
    {
        $valorFrete = 0;
        $itensEmAbertoTransportadora = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
        $qtdItensEmAbertoTransportadora = count($itensEmAbertoTransportadora);

        $existePedidoRetireAquiEmAberto = EntregaServices::existePedidoRetireAquiEmAberto();
        if ($existePedidoRetireAquiEmAberto) {
            $idsTipoFrete = [3];
        } else {
            $idsTipoFrete = [2, 3];

            if ($qtdItensEmAbertoTransportadora > 0) {
                $idsTipoFrete = [2];
            }
        }

        $custoEntregador = 0;

        $entregador = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho('PM');

        $pontoRetirada = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho('PP');

        $comissaoPontoColeta = ConfiguracaoService::buscaComissaoPontoColeta();

        if (isset($entregador['id'])) {
            foreach ($produtos as $produto) {
                $custoEntregador += round(
                    round(
                        ($produto['valor_custo_produto'] * ($comissaoPontoColeta + $entregador['porcentagem_frete'])) /
                            100,
                        2
                    ) + $entregador['valor'],
                    2
                );
            }

            $custoEntregador = round($custoEntregador, 2);

            $idsTipoFrete[] = (int) $entregador['id'];
        }

        $where = 'tipo_frete.id IN (' . implode(',', $idsTipoFrete) . ')';

        $resultado = DB::select(
            "SELECT
                    tipo_frete.id,
                    tipo_frete.titulo,
                    tipo_frete.nome,
                    tipo_frete.id_colaborador,
                    CASE
                        WHEN tipo_frete.id = 3 THEN 'RETIRAR_GRATIS'
                        WHEN tipo_frete.tipo_ponto = 'PM' THEN 'ENTREGADOR'
                        WHEN $qtdItensEmAbertoTransportadora THEN 'ADICAO'
                        WHEN tipo_frete.id = 2 THEN 'TRANSPORTADORA'
                    END tipo
                FROM tipo_frete
                WHERE $where"
        );

        $resultado[] = [
            'id' => $pontoRetirada['id'] ?? 0,
            'titulo' => 'Retirar em um ponto',
            'tipo' => 'PONTO_RETIRADA',
            'id_colaborador' => null,
        ];

        if (empty($resultado)) {
            return [];
        }
        $qtdMaximaProdutos = PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE;
        $resultado = array_map(function ($item) use (
            $custoEntregador,
            $valorFrete,
            $produtos,
            $qtdItensEmAbertoTransportadora,
            $qtdMaximaProdutos
        ) {
            $item['id'] = (int) $item['id'];
            $item['id_colaborador'] = (int) $item['id_colaborador'];
            $nota = '';
            $observacao = '';
            $valorAdicional = 0;
            switch ($item['tipo']) {
                case 'RETIRAR_GRATIS':
                    $nota = '(Opção válida também para freteiros, motoboys e transportadoras pagas por você)';
                    $observacao = 'Rua Pará de Minas, 150 - Centro - CEP 35520-090 - Nova Serrana (MG)';
                    $item['valor_frete'] = 0;
                    $item['ordem'] = 1;
                    break;
                case 'ADICAO':
                    $observacao = 'Sem custo de frete adicional';
                    if ($qtdItensEmAbertoTransportadora + count($produtos) > $qtdMaximaProdutos) {
                        $valorAdicional = Municipio::buscaValorAdicional();
                    }
                    $adicionalFrete = FreteService::calculaValorFrete(
                        $qtdItensEmAbertoTransportadora,
                        count($produtos),
                        0,
                        $valorAdicional
                    );
                    if ($adicionalFrete) {
                        $observacao = "Frete adicional: R$" . number_format($adicionalFrete, 2, ',', '.');
                    }
                    $item['valor_frete'] = $adicionalFrete;
                    $item['ordem'] = 1;
                    break;
                case 'TRANSPORTADORA':
                    $qtdFreteAdicional = max(0, count($produtos) - $qtdMaximaProdutos);
                    $valoresFrete = Municipio::buscaValorFrete($qtdFreteAdicional > 0);
                    $valorFrete = $valoresFrete['valor_frete'];
                    $valorAdicional = $valoresFrete['valor_adicional'];
                    $adicionalFrete = $qtdFreteAdicional * $valorAdicional;
                    if ($adicionalFrete > 0) {
                        $nota = "(Frete de R$ ";
                        $nota .= number_format($valorFrete, 2, ',', '.');
                        $nota .= " para sua região até {$qtdMaximaProdutos} produtos, + R$ ";
                        $nota .= number_format($valorAdicional, 2, ',', '.');
                        $nota .= ' a partir do ' . ($qtdMaximaProdutos + 1) . 'º produto)';
                        $valorFrete += $adicionalFrete;
                    }
                    $observacao = 'Frete total: R$ ' . number_format($valorFrete, 2, ',', '.');
                    $item['valor_frete'] = $valorFrete;
                    $item['ordem'] = 4;
                    break;
                case 'ENTREGADOR':
                    $observacao = 'Receba em casa por: R$ ' . number_format($custoEntregador, 2, ',', '.');
                    $item['valor_frete'] = $custoEntregador;
                    $item['ordem'] = 2;
                    break;
                case 'PONTO_RETIRADA':
                    $observacao = 'Escolha um ponto para retirar';
                    $item['ordem'] = 3;
                    break;
                default:
                    break;
            }
            $item['observacao'] = $observacao;
            $item['nota'] = $nota;
            return $item;
        }, $resultado);

        usort($resultado, function ($a, $b) {
            return $a['ordem'] - $b['ordem'];
        });

        return $resultado;
    }

    public static function listaEntregadoresComProdutos(int $pagina, string $pesquisa): array
    {
        $itensPorPag = 150;
        $offset = $itensPorPag * $pagina;
        $where = '';
        $binds = [];
        if ($pesquisa !== '') {
            $where .= " AND CONCAT_WS(
                ' ',
                entregas_faturamento_item.id_entrega,
                entregas_faturamento_item.id_transacao,
                cliente_colaboradores.id,
                cliente_colaboradores.razao_social,
                entregador_colaboradores.id,
                entregador_colaboradores.razao_social,
                cliente_colaboradores_enderecos.cidade,
                cliente_colaboradores_enderecos.uf
            ) REGEXP :pesquisa ";
        }

        $sql = "SELECT
                entregas_faturamento_item.id_entrega,
                entregas_faturamento_item.id_transacao,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.nome_tamanho,
                JSON_OBJECT(
                    'cidade', cliente_colaboradores_enderecos.cidade,
                    'uf', cliente_colaboradores_enderecos.uf,
                    'endereco', cliente_colaboradores_enderecos.logradouro,
                    'numero', cliente_colaboradores_enderecos.numero,
                    'bairro', cliente_colaboradores_enderecos.bairro
                ) AS json_entrega,
                JSON_OBJECT(
                    'id', cliente_colaboradores.id,
                    'nome', cliente_colaboradores.razao_social,
                    'telefone', cliente_colaboradores.telefone,
                    'tem_devolucao', EXISTS(
                        SELECT 1
                        FROM troca_pendente_agendamento
                        WHERE troca_pendente_agendamento.id_cliente = cliente_colaboradores.id
                    )
                ) AS json_cliente,
                JSON_OBJECT(
                    'id', entregador_colaboradores.id,
                    'nome', entregador_colaboradores.razao_social,
                    'telefone', entregador_colaboradores.telefone,
                    'esta_ativo', tipo_frete.categoria = 'PE'
                ) AS json_entregador,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'SM' ASC
                    LIMIT 1
                ) AS foto_produto,
                (
                    SELECT DATE_FORMAT(@DATA_COLETA := entregas_logs.data_criacao, '%d/%m/%Y às %H:%i')
                    FROM entregas_logs
                    WHERE entregas_logs.id_entrega = entregas.id
                        AND entregas_logs.situacao_nova = 'PT'
                    LIMIT 1
                ) AS data_coleta,
                DATEDIFF(CURDATE(), @DATA_COLETA) AS dias_coleta
            FROM tipo_frete
            INNER JOIN entregas ON entregas.id_tipo_frete = tipo_frete.id
                AND NOT entregas.situacao = 'EX'
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                AND NOT entregas_faturamento_item.situacao = 'EN'
            INNER JOIN colaboradores AS entregador_colaboradores ON entregador_colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores AS cliente_colaboradores ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
            INNER JOIN colaboradores_enderecos AS cliente_colaboradores_enderecos ON
                cliente_colaboradores_enderecos.id_colaborador = cliente_colaboradores.id
                AND cliente_colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE tipo_frete.categoria IN ('ML', 'PE')
                AND tipo_frete.tipo_ponto = 'PM'
                $where
            ORDER BY dias_coleta DESC, @DATA_COLETA
            LIMIT :itens_por_pag OFFSET :offset;";

        if ($pesquisa !== '') {
            $binds[':pesquisa'] = $pesquisa;
        }
        $binds[':itens_por_pag'] = $itensPorPag;
        $binds[':offset'] = $offset;

        $informacoes = DB::select($sql, $binds);

        $informacoes = array_map(function (array $informacao): array {
            // Informações Produto:
            $informacao['id_produto_tamanho'] = "{$informacao['id_produto']} - {$informacao['nome_tamanho']}";

            // Informações Compra:
            $informacao['id_entrega_transacao'] = "{$informacao['id_entrega']} / {$informacao['id_transacao']}";

            // Informações Entrega:
            $informacao['endereco'] = '';
            $endereco = [];

            $endereco = array_merge($endereco, [
                'na rua ' . trim(preg_replace('/Rua/i', '', $informacao['entrega']['endereco'])),
            ]);
            $endereco = array_merge($endereco, [trim($informacao['entrega']['numero'])]);
            $endereco = array_merge($endereco, [
                'bairro ' . trim(preg_replace('/Bairro/i', '', $informacao['entrega']['bairro'])),
            ]);
            $informacao['endereco'] .= implode(', ', $endereco);
            $informacao['endereco'] .= ", {$informacao['entrega']['cidade']} - {$informacao['entrega']['uf']}";
            $informacao['endereco'] = " de {$informacao['entrega']['cidade']} - {$informacao['entrega']['uf']}";
            $informacao['cidade'] = "{$informacao['entrega']['cidade']}, {$informacao['entrega']['uf']}";
            unset($informacao['entrega']);

            // Informações Entregador:
            $nomeEntregador = trim($informacao['entregador']['nome']);
            $informacao['entregador']['telefone'] = preg_replace(
                '/[^0-9]/i',
                '',
                $informacao['entregador']['telefone']
            );
            $informacao['entregador']['nome'] = "({$informacao['entregador']['id']}) $nomeEntregador";
            unset($informacao['entregador']['id']);

            // Informações Cliente:
            $nomeCliente = trim($informacao['cliente']['nome']);
            $informacao['cliente']['telefone'] = preg_replace('/[^0-9]/i', '', $informacao['cliente']['telefone']);
            $informacao['cliente']['nome'] = "({$informacao['cliente']['id']}) $nomeCliente";
            $informacao['cliente'][
                'link'
            ] = "https://api.whatsapp.com/send/?phone=55{$informacao['cliente']['telefone']}";
            $informacao['cliente']['tem_devolucao'] = (bool) $informacao['cliente']['tem_devolucao'];
            unset($informacao['cliente']['id']);

            return $informacao;
        }, $informacoes);

        $binds = [];

        $sql = "SELECT CEIL(COUNT(DISTINCT entregas_faturamento_item.id) / :itens_por_pag) qtd_pags
            FROM tipo_frete
            INNER JOIN entregas ON entregas.id_tipo_frete = tipo_frete.id
                AND NOT entregas.situacao = 'EX'
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                AND NOT entregas_faturamento_item.situacao = 'EN'
            INNER JOIN colaboradores AS entregador_colaboradores ON entregador_colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores AS cliente_colaboradores ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
            WHERE tipo_frete.categoria IN ('ML', 'PE')
                AND tipo_frete.tipo_ponto = 'PM'
                $where";

        $binds[':itens_por_pag'] = $itensPorPag;
        if ($pesquisa !== '') {
            $binds[':pesquisa'] = $pesquisa;
        }

        $totalPags = DB::selectOneColumn($sql, $binds);

        $resultado = [
            'informacoes' => $informacoes,
            'old_pesquisa' => $pesquisa,
            'mais_pags' => $totalPags - $pagina - 1 > 0,
        ];

        return $resultado;
    }

    public static function dadosPontoPorIdColaborador(int $idColaboradorTipoFrete): array
    {
        $resultado = DB::selectOne(
            "SELECT
                        tipo_frete.id,
                        tipo_frete.tipo_ponto,
                        (
                            SELECT transportadores_raios.valor
                            FROM colaboradores_enderecos
                            JOIN transportadores_raios ON transportadores_raios.id_cidade = colaboradores_enderecos.id_cidade
                            WHERE colaboradores_enderecos.id_colaborador = :idCliente
                                AND colaboradores_enderecos.eh_endereco_padrao = 1
                                AND transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                            LIMIT 1
                        ) AS `valor_frete`,
                        IF (tipo_frete.id NOT IN (:listaDeFretesEntregaCliente),
                            (
                                SELECT pontos_coleta.porcentagem_frete
                                FROM pontos_coleta
                                WHERE pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                            )
                        , 0) AS `valor_ponto_coleta`,
                        tipo_frete.id_colaborador_ponto_coleta
                    FROM tipo_frete
                    WHERE tipo_frete.id_colaborador = :idColaboradorTipoFrete
                            AND tipo_frete.categoria <> 'PE'",
            [
                'idCliente' => Auth::user()->id_colaborador,
                'idColaboradorTipoFrete' => $idColaboradorTipoFrete,
                'listaDeFretesEntregaCliente' => TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE,
            ]
        );

        if (empty($resultado)) {
            throw new Exception('Não foi possível encontrar os dados do ponto');
        }

        return $resultado;
    }

    public static function buscarTipoFrete(PDO $conexao, string $pesquisa, string $tipoPonto): array
    {
        $where = '';

        if ($tipoPonto) {
            $where = ' AND tipo_frete.tipo_ponto = :tipoPonto ';
        }

        $query =
            "SELECT
                    tipo_frete.id,
                    tipo_frete.id_colaborador,
                    CONCAT(tipo_frete.id_colaborador, ' ', tipo_frete.nome) AS `nome`,
                    (
                        SELECT COUNT(tipo_frete_grupos_item.id_tipo_frete)
                        FROM tipo_frete_grupos_item
                        WHERE tipo_frete_grupos_item.id_tipo_frete = tipo_frete.id
                    ) AS `adicionados`
                FROM tipo_frete
                WHERE
                    tipo_frete.id NOT IN (" .
            TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE .
            ")
                    AND tipo_frete.categoria <> 'PE'
                    AND (
                            tipo_frete.id_colaborador = :pesquisa
                            OR tipo_frete.nome REGEXP :pesquisa
                        )
                    $where
                LIMIT 3";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        if ($tipoPonto) {
            $stmt->bindValue(':tipoPonto', $tipoPonto, PDO::PARAM_STR);
        }
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado ?: [];
    }

    public static function buscaSituacaoPonto(PDO $conexao, int $idColaborador): string
    {
        $sql = $conexao->prepare(
            "SELECT COALESCE(tipo_frete.categoria, '') AS `status_ponto`
            FROM colaboradores
            LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :id_colaborador;"
        );
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();
        $situacao = $sql->fetchColumn();
        $situacao = self::converteCategoria($situacao);

        return $situacao;
    }

    public static function buscaListaFiltradaDePontosParados(string $visualizacao): array
    {
        $having =
            $visualizacao === 'ELEGIVEIS'
                ? " AND `ponto_mais_proximo` IS NULL AND situacao = 'ELEGIVEL'"
                : " AND `ponto_mais_proximo` IS NOT NULL OR situacao = 'REJEITADO'";
        $sql = "SELECT
                tipo_frete.id,
                tipo_frete.id_colaborador,
                usuarios.id AS `id_usuario`,
                colaboradores.razao_social,
                colaboradores.telefone,
                CONCAT(colaboradores_enderecos.cidade, ' - ', colaboradores_enderecos.uf) AS `cidade`,
                CONCAT_WS(', ', colaboradores_enderecos.logradouro, colaboradores_enderecos.bairro, colaboradores_enderecos.numero) AS `endereco`,
                colaboradores_enderecos.id_cidade,
                tipo_frete.tipo_ponto,
                JSON_OBJECT(
                    'latitude', tipo_frete.latitude,
                    'longitude', tipo_frete.longitude
                ) AS `json_localizacao`,
                IF(tipo_frete_rejeitados.id, 'REJEITADO', 'ELEGIVEL') AS `situacao`,
                configuracoes.tamanho_raio_padrao_ponto_parado,
                (
                    SELECT
                        ((distancia_geolocalizacao(
                            comparador_tipo_frete.latitude,
                            comparador_tipo_frete.longitude,
                            tipo_frete.latitude,
                            tipo_frete.longitude
                        ) * 1000) - configuracoes.tamanho_raio_padrao_ponto_parado) AS `distancia`
                    FROM tipo_frete AS `comparador_tipo_frete`
                    JOIN colaboradores_enderecos AS `comparador_colaboradores_enderecos` ON comparador_colaboradores_enderecos.id_colaborador = comparador_tipo_frete.id_colaborador
      		            AND comparador_colaboradores_enderecos.eh_endereco_padrao = 1
                        AND comparador_colaboradores_enderecos.id_cidade = colaboradores_enderecos.id_cidade
                    WHERE comparador_tipo_frete.tipo_ponto = 'PP'
                        AND comparador_tipo_frete.categoria = 'ML'
                    HAVING `distancia` < configuracoes.tamanho_raio_padrao_ponto_parado
                    ORDER BY `distancia`
                    LIMIT 1
                ) AS `ponto_mais_proximo`
            FROM tipo_frete
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            JOIN usuarios ON usuarios.id_colaborador = tipo_frete.id_colaborador
            LEFT JOIN tipo_frete_rejeitados ON tipo_frete_rejeitados.id_colaborador = tipo_frete.id_colaborador
            JOIN configuracoes
            WHERE tipo_frete.categoria = 'PE'
                AND tipo_frete.tipo_ponto = 'PP'
                AND tipo_frete.latitude <> 0
                AND tipo_frete.longitude <> 0
            HAVING 1=1 {$having}
            ORDER BY tipo_frete.id DESC
        ";
        $listaDePontos = DB::select($sql);

        return $listaDePontos;
    }

    public static function buscaListaFiltradaDeEntregadores(string $visualizacao): array
    {
        $sql = "SELECT
                tipo_frete.id,
                tipo_frete.id_colaborador,
                usuarios.id AS `id_usuario`,
                colaboradores.razao_social,
                colaboradores.telefone,
                CONCAT(colaboradores_enderecos.cidade, ' - ', colaboradores_enderecos.uf) AS `cidade`,
                CONCAT_WS(', ', colaboradores_enderecos.logradouro, colaboradores_enderecos.bairro, colaboradores_enderecos.numero) AS `endereco`,
                colaboradores_enderecos.id_cidade,
                tipo_frete.tipo_ponto,
                IF(tipo_frete_rejeitados.id, 'REJEITADO', 'ELEGIVEL') AS `situacao`,
                CONCAT('[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'id_raio', transportadores_raios.id,
                        'id_colaborador', colaboradores.id,
                        'id_cidade', transportadores_raios.id_cidade,
                        'cidade', CONCAT(municipios.nome, ' - ', municipios.uf),
                        'latitude', municipios.latitude,
                        'longitude', municipios.longitude,
                        'valor', transportadores_raios.valor,
                        'esta_ativo', transportadores_raios.esta_ativo,
                        'prazo_forcar_entrega', transportadores_raios.prazo_forcar_entrega,
                        'eh_elegivel', (
                            SELECT COUNT(*) <= 0
                            FROM transportadores_raios AS `verificacao_transportadores_raios`
                            JOIN tipo_frete AS `verificacao_tipo_frete` ON verificacao_tipo_frete.id_colaborador = verificacao_transportadores_raios.id_colaborador
                                AND verificacao_tipo_frete.tipo_ponto = 'PM'
                                AND verificacao_tipo_frete.categoria = 'ML'
                            WHERE verificacao_transportadores_raios.id_colaborador <> transportadores_raios.id_colaborador
                                AND verificacao_transportadores_raios.esta_ativo
                                AND verificacao_transportadores_raios.id_cidade = transportadores_raios.id_cidade
                                AND SQRT(
                                    POW(verificacao_transportadores_raios.latitude - transportadores_raios.latitude, 2) +
                                    POW(verificacao_transportadores_raios.longitude - transportadores_raios.longitude, 2)
                                ) * 111139 <= (verificacao_transportadores_raios.raio + transportadores_raios.raio)
                        )
                    )),
                ']') AS `json_cidades`
            FROM tipo_frete
            LEFT JOIN tipo_frete_rejeitados ON tipo_frete_rejeitados.id_colaborador = tipo_frete.id_colaborador
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            JOIN usuarios ON usuarios.id_colaborador = tipo_frete.id_colaborador
            JOIN transportadores_raios ON transportadores_raios.id_colaborador = tipo_frete.id_colaborador
            JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            WHERE tipo_frete.tipo_ponto = 'PM'
                AND tipo_frete.categoria = 'PE'
            GROUP BY tipo_frete.id
            HAVING situacao = :situacao
            ORDER BY tipo_frete.id DESC";
        $listaDeEntregadores = DB::select($sql, [
            'situacao' => $visualizacao === 'ELEGIVEIS' ? 'ELEGIVEL' : 'REJEITADO',
        ]);

        return $listaDeEntregadores;
    }

    public static function existePonto(): bool
    {
        $existeTipoFrete = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM tipo_frete
                WHERE tipo_frete.id_colaborador = :id_cliente
            ) AS `existe_tipo_frete`;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        return $existeTipoFrete;
    }

    public static function rejeitaSolicitacaoPonto(PDO $conexao, int $idColaborador, int $idUsuario): void
    {
        $stmt = $conexao->prepare(
            "INSERT INTO tipo_frete_rejeitados (
                tipo_frete_rejeitados.id_colaborador,
                tipo_frete_rejeitados.id_usuario
            ) VALUES (:idColaborador, :idUsuario)"
        );
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function gerenciaSituacaoPonto(PDO $conexao, int $idColaborador, int $idUsuario, bool $aprovar): void
    {
        $stmt = $conexao->prepare(
            "UPDATE tipo_frete
            SET
                tipo_frete.categoria = :categoria,
                tipo_frete.id_usuario = :idUsuario
            WHERE tipo_frete.id_colaborador = :idColaborador"
        );
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $aprovar ? 'ML' : 'PE', PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception('Ocorreu um erro ao atualizar ponto.');
        }
    }

    public static function listaDePedidosSemEntregas(string $pesquisa): array
    {
        $idTipoFrete = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $binds['situacao_logistica'] = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $where = '';

        if (!empty($pesquisa)) {
            if (is_numeric($pesquisa)) {
                $where = ' AND colaboradores.id = :pesquisa';
            } else {
                $where = ' AND colaboradores.razao_social REGEXP :pesquisa ';
            }

            $binds['pesquisa'] = $pesquisa;
        }

        $pedidos = DB::select(
            "SELECT
                tipo_frete.id AS `id_tipo_frete`,
                tipo_frete.categoria,
                tipo_frete.nome AS `transportador`,
                tipo_frete.id IN ($idTipoFrete) AS `eh_retirada_cliente`,
                IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                    CONCAT(metadados_municipios.nome, ' (', metadados_municipios.uf, ')'),
                    CONCAT(colaborador_municipios.nome, ' (', colaborador_municipios.uf, ')')
                ) AS `cidade`,
                SUM(
                    logistica_item.situacao < :situacao_logistica
                    AND IF (tipo_frete.id IN ($idTipoFrete), logistica_item.id_responsavel_estoque > 1, TRUE)
                ) AS `tem_mais_produtos`,
                JSON_OBJECT(
                    'id_colaborador', colaboradores.id,
                    'nome', TRIM(colaboradores.razao_social),
                    'id_cidade', IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                            metadados_municipios.id,
                            colaborador_municipios.id
                    ),
                    'id_raio', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio')
                ) AS `json_destinatario`,
                (
                    SELECT JSON_OBJECT(
                        'id_colaborador', colaboradores.id,
                        'nome', TRIM(colaboradores.razao_social)
                    )
                    FROM colaboradores
                    WHERE colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
                ) AS `json_ponto_coleta`,
                IF (
                    tipo_frete.id = 2
                        AND EXISTS(
                            SELECT 1
                            FROM pedido_item_meu_look
                            WHERE pedido_item_meu_look.uuid = logistica_item.uuid_produto
                        ),
                    'ML',
                    tipo_frete.categoria
                ) AS `categoria_cor`,
                IF (tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto) AS `tipo_entrega`,
                SUM(logistica_item.situacao = :situacao_logistica) AS `qtd_produtos`,
                CONCAT('[', GROUP_CONCAT(DISTINCT logistica_item.id_transacao), ']') AS `json_transacoes`,
                (
                    SELECT COUNT(entregas_devolucoes_item.id)
                    FROM entregas_devolucoes_item
                    WHERE entregas_devolucoes_item.situacao = 'PE'
                        AND entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
                ) AS `devolucoes_pendentes`,
                EXISTS(
                    SELECT 1
                    FROM colaboradores_suspeita_fraude
                    WHERE colaboradores_suspeita_fraude.situacao = 'PE'
                        AND colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'
                        AND colaboradores_suspeita_fraude.id_colaborador = tipo_frete.id_colaborador
                ) AS `eh_fraude`,
                (
                    SELECT
                        JSON_OBJECT(
                            'situacao', acompanhamento_temp.situacao,
                            'id', acompanhamento_temp.id
                        )
                    FROM acompanhamento_temp
                    WHERE acompanhamento_temp.id_tipo_frete = tipo_frete.id
                        AND acompanhamento_temp.id_destinatario = colaboradores.id
                        AND acompanhamento_temp.id_cidade = IF(
                            tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                            metadados_municipios.id,
                            colaborador_municipios.id
                        )
                        AND IF(acompanhamento_temp.id_raio IS NULL,
                            TRUE,
                            acompanhamento_temp.id_raio = JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio')
                        )
                ) AS `json_acompanhamento`,
                IF(transportadores_raios.apelido IS NULL, '-',
                    COALESCE(
                        CONCAT(
                            '(',JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio'), ') ', transportadores_raios.apelido
                        ),
                    JSON_EXTRACT(
                        transacao_financeiras_metadados.valor, '$.id_raio'
                        )
                    )
                ) AS `apelido_raio`
            FROM logistica_item
            INNER JOIN transacao_financeiras_metadados ON
                transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            LEFT JOIN transportadores_raios ON
                transportadores_raios.id = JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio')
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            INNER JOIN colaboradores AS `tipo_frete_colaboradores` ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores ON colaboradores.id = IF (
                        tipo_frete.id IN ($idTipoFrete),
                        logistica_item.id_cliente,
                        logistica_item.id_colaborador_tipo_frete
                    )
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = IF(
                    tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                        logistica_item.id_cliente,
                        logistica_item.id_colaborador_tipo_frete
                )
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LEFT JOIN municipios AS `metadados_municipios` ON metadados_municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
            INNER JOIN municipios AS `colaborador_municipios` ON  colaborador_municipios.id = colaboradores_enderecos.id_cidade
            WHERE logistica_item.situacao <= :situacao_logistica
                AND logistica_item.id_entrega IS NULL
                $where
            GROUP BY
                tipo_frete.id_colaborador,
                IF (tipo_frete.id IN ($idTipoFrete), logistica_item.id_cliente, TRUE),
                IF (tipo_frete.tipo_ponto = 'PM', JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio'), TRUE);",
            $binds
        );
        $pedidos = array_map(function (array $pedido): array {
            $pedido['destino'] = "({$pedido['destinatario']['id_colaborador']}) {$pedido['destinatario']['nome']}";
            $pedido['ponto_coleta'] = "({$pedido['ponto_coleta']['id_colaborador']}) {$pedido['ponto_coleta']['nome']}";

            return $pedido;
        }, $pedidos);

        return $pedidos;
    }

    public static function buscaMaisDetalhesDoPedido(int $idTipoFrete, int $idDestinatario, int $idEntrega): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $situacaoLogistica = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $situacaoExpedicao = Entregas::SITUACAO_EXPEDICAO;
        $where = '';
        $valoresBinds = [
            'situacao_logistica' => $situacaoLogistica,
            'situacao_expedicao' => $situacaoExpedicao,
            'id_tipo_frete' => $idTipoFrete,
            'id_destinatario' => $idDestinatario,
        ];

        if ($idEntrega !== 0) {
            $where = ' AND logistica_item.id_entrega = :id_entrega ';
            $valoresBinds['id_entrega'] = $idEntrega;
        } else {
            $where = ' AND logistica_item.id_entrega IS NULL ';
        }

        $detalhes = DB::selectOne(
            "SELECT
                entregas.volumes,
                colaboradores_enderecos.cep,
                colaboradores.cpf,
                colaboradores.cnpj,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.numero,
                colaboradores.telefone,
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.complemento,
                colaboradores.observacoes,
                colaboradores.razao_social,
                colaboradores.tipo_embalagem,
                colaboradores.id AS `id_colaborador`,
                COALESCE(pontos_coleta.valor_custo_frete, 0) AS `custo_frete_ponto_coleta`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'uuid_produto', logistica_item.uuid_produto,
                        'esta_pronto', logistica_item.situacao = :situacao_logistica
                    )),
                    ']'
                ) AS `json_produtos`,
                CONCAT('[', GROUP_CONCAT(DISTINCT logistica_item.id_transacao), ']') AS `json_transacoes`,
                JSON_OBJECT(
                    'id_cidade', municipios.id,
                    'cidade', municipios.nome,
                    'uf', municipios.uf
                ) AS `json_cidade`,
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT entregas.id
                        ORDER BY entregas.id
                        DESC LIMIT 3
                    )
                    FROM entregas
                    WHERE entregas.situacao > :situacao_expedicao
                        AND entregas.id_tipo_frete = :id_tipo_frete
                        AND IF (
                            entregas.id_tipo_frete IN ($idTipoFreteEntregaCliente),
                            entregas.id_cliente = :id_destinatario,
                            TRUE
                        )
                ) AS `id_entregas_anteriores`
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = IF (
                tipo_frete.id IN ($idTipoFreteEntregaCliente),
                logistica_item.id_cliente,
                tipo_frete.id_colaborador
            )
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = logistica_item.id_transacao
                AND transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
            INNER JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
            LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
            LEFT JOIN entregas ON entregas.id = logistica_item.id_entrega
            WHERE
                tipo_frete.id = :id_tipo_frete
                AND colaboradores.id = :id_destinatario
                $where
            GROUP BY
                tipo_frete.id_colaborador,
                IF (tipo_frete.id IN ($idTipoFreteEntregaCliente), logistica_item.id_cliente, TRUE);",
            $valoresBinds
        );
        if (empty($detalhes)) {
            throw new UnprocessableEntityHttpException('Não foi possível encontrar os detalhes');
        }

        $produtosProntos = array_filter($detalhes['produtos'], fn(array $produto): bool => $produto['esta_pronto']);
        $detalhes['qtd_produtos_prontos'] = count($produtosProntos);
        $detalhes['qtd_total_produtos'] = count($detalhes['produtos']);
        $detalhes['tipo_embalagem'] = Colaborador::converteTipoEmbalagem($detalhes['tipo_embalagem']);
        if (!empty($detalhes['id_entregas_anteriores'])) {
            $detalhes['id_entregas_anteriores'] = explode(',', $detalhes['id_entregas_anteriores']);
        }

        [$binds, $valoresBinds] = ConversorArray::criaBindValues($detalhes['transacoes'], 'id_transacao');
        $detalhes['valor_pedido'] = DB::selectOneColumn(
            "SELECT SUM(transacao_financeiras_produtos_itens.preco)
            FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.id_transacao IN ($binds)
                AND transacao_financeiras_produtos_itens.tipo_item <> 'FR';",
            $valoresBinds
        );

        $custoTransportadora = 0;
        if ($idTipoFrete === 2) {
            $custoTransportadora = DB::selectOneColumn(
                "SELECT SUM(transacao_financeiras_metadados.valor)
                FROM (
                    SELECT transacao_financeiras_metadados.id_transacao
                    FROM transacao_financeiras
                    INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                    WHERE transacao_financeiras.id IN ($binds)
                        AND IF (
                            transacao_financeiras.origem_transacao = 'MP',
                            transacao_financeiras_metadados.chave = 'ID_PEDIDO',
                            TRUE
                        )
                    GROUP BY IF (
                        transacao_financeiras.origem_transacao = 'MP',
                        transacao_financeiras_metadados.valor,
                        transacao_financeiras_metadados.id_transacao
                    )
                ) AS `pedido_transacao_financeiras_metadados`
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = pedido_transacao_financeiras_metadados.id_transacao
                WHERE transacao_financeiras_metadados.chave = 'VALOR_FRETE';",
                $valoresBinds
            );
        }

        $detalhes['custo_frete_transportadora'] = $custoTransportadora;
        $detalhes['valor_pedido'] += $custoTransportadora;
        unset($detalhes['transacoes'], $detalhes['produtos']);

        return $detalhes;
    }

    public static function ordenarListaPedidos(array $pedidos): array
    {
        usort($pedidos, function (array $a, array $b): int {
            $pegarContador = function (array $item): int {
                $contador = 0;
                if ($item['tipo_entrega'] === 'ENVIO_TRANSPORTADORA') {
                    $contador += 5 + !empty($item['id_entrega']);
                }
                if (!$item['tem_mais_produtos'] && $item['eh_retirada_cliente']) {
                    $contador += 2 + !empty($item['id_entrega']);
                }

                return $contador;
            };
            $contadorA = $pegarContador($a);
            $contadorB = $pegarContador($b);

            if (!empty($a['id_entrega']) && !empty($b['id_entrega'])) {
                if ($a['id_entrega'] < $b['id_entrega']) {
                    $contadorA += 1;
                }
                if ($b['id_entrega'] < $a['id_entrega']) {
                    $contadorB += 1;
                }
            }

            return -$contadorA + $contadorB;
        });

        return $pedidos;
    }

    public static function buscaInformacoesDoTransportador(int $idColaboradorTipoFrete): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $informacoes = DB::selectOne(
            "SELECT
                JSON_OBJECT(
                    'id_colaborador', pontos_coleta.id_colaborador,
                    'razao_social', pontos_coleta_colaboradores.razao_social,
                    'telefone', pontos_coleta_colaboradores.telefone,
                    'foto', COALESCE(
                        pontos_coleta_colaboradores.foto_perfil,
                        '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'
                    )
                ) AS `json_ponto_coleta`,
                JSON_OBJECT(
                    'id_colaborador', tipo_frete.id_colaborador,
                    'razao_social', tipo_frete_colaboradores.razao_social,
                    'telefone', tipo_frete_colaboradores.telefone,
                    'foto', COALESCE(
                        tipo_frete_colaboradores.foto_perfil,
                        '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'
                    ),
                    'tipo_ponto', tipo_frete.tipo_ponto,
                    'json_cidades', (
                        SELECT CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT(
                                'id_raio', transportadores_raios.id,
                                'id_cidade', transportadores_raios.id_cidade,
                                'dias_entregar_cliente', transportadores_raios.dias_entregar_cliente,
                                'dias_margem_erro', transportadores_raios.dias_margem_erro,
                                'nome', municipios.nome,
                                'uf', municipios.uf
                            )),
                            ']'
                        )
                        FROM transportadores_raios
                        INNER JOIN municipios ON municipios.id = transportadores_raios.id_cidade
                        WHERE transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                    )
                ) AS `json_transportador`
            FROM tipo_frete
            INNER JOIN colaboradores AS `tipo_frete_colaboradores` ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
            INNER JOIN colaboradores AS `pontos_coleta_colaboradores` ON pontos_coleta_colaboradores.id = pontos_coleta.id_colaborador
            WHERE tipo_frete.id NOT IN ($idTipoFreteEntregaCliente)
                AND tipo_frete.categoria = 'ML'
                AND tipo_frete.id_colaborador = :id_colaborador_tipo_frete
            GROUP BY tipo_frete.id_colaborador;",
            ['id_colaborador_tipo_frete' => $idColaboradorTipoFrete]
        );
        if (empty($informacoes)) {
            throw new NotFoundHttpException('Não foi possível encontrar as informações do transportador');
        }

        return $informacoes;
    }

    public static function buscaTransportadores(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $transportadores = DB::select(
            "SELECT
                tipo_frete.id AS `id_tipo_frete`,
                tipo_frete.tipo_ponto,
                transportadores_raios.valor,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro,
                tipo_frete.id_colaborador_ponto_coleta
            FROM colaboradores
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN transportadores_raios ON transportadores_raios.esta_ativo
                AND transportadores_raios.id_cidade = colaboradores_enderecos.id_cidade
            INNER JOIN tipo_frete ON tipo_frete.id NOT IN ($idTipoFreteEntregaCliente)
                AND tipo_frete.categoria = 'ML'
                AND tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                AND IF(
                    tipo_frete.tipo_ponto = 'PM',
                    (
                        distancia_geolocalizacao(
                            colaboradores_enderecos.latitude,
                            colaboradores_enderecos.longitude,
                            transportadores_raios.latitude,
                            transportadores_raios.longitude
                        ) * 1000
                    ) <= transportadores_raios.raio,
                    TRUE
                )
            WHERE colaboradores.id = :id_cliente
            GROUP BY tipo_frete.id;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        return $transportadores;
    }

    public static function salvaGeolocalizacao(string $latitude, string $longitude): void
    {
        $rowCount = DB::query(
            "UPDATE tipo_frete SET
                    tipo_frete.latitude = :latitude,
                    tipo_frete.longitude = :longitude
                WHERE tipo_frete.id_colaborador = :idTipoFrete",
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'idTipoFrete' => Auth::user()->id_colaborador,
            ]
        );

        if ($rowCount === 0) {
            throw new BadRequestHttpException('Erro ao salvar geolocalização');
        }
    }

    public static function buscaDadosPontoComIdColaborador(int $idColaborador = 0): array
    {
        $idColaborador = $idColaborador ?: Auth::user()->id_colaborador;
        $query = "SELECT
                tipo_frete.id,
                tipo_frete.nome,
                tipo_frete.mensagem,
                tipo_frete.horario_de_funcionamento,
                tipo_frete.latitude,
                tipo_frete.longitude,
                tipo_frete.tipo_ponto,
                colaboradores.telefone
            FROM tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            WHERE tipo_frete.id_colaborador = :idColaborador";
        $resultado = DB::selectOne($query, [
            'idColaborador' => $idColaborador,
        ]);
        return $resultado ?: [];
    }
}
