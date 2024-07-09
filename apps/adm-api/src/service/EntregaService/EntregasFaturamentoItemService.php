<?php

namespace MobileStock\service\EntregaService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Globals;
use MobileStock\helper\GradeImagens;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\model\LogisticaItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\service\MessageService;
use PDO;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntregasFaturamentoItemService
{
    public function cria(int $idEntrega, array $produtos): void
    {
        [$sqlParam, $binds] = ConversorArray::criaBindValues($produtos);
        $processoFinalDeLogistica = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;

        $idUsuario = Auth::id();

        $dados = DB::select(
            "SELECT
                $idEntrega id_entrega,
                $idUsuario id_usuario,
                logistica_item.uuid_produto,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                transacao_financeiras.id id_transacao,
                transacao_financeiras.pagador id_cliente,
                logistica_item.id_responsavel_estoque,
                IF (transacao_financeiras.origem_transacao = 'ML', 'ML', 'MS') origem
            FROM
                logistica_item
                INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto AND entregas_faturamento_item.id IS NULL
            WHERE
                logistica_item.situacao = $processoFinalDeLogistica
                AND logistica_item.id_entrega IS NULL
                AND logistica_item.uuid_produto IN ( $sqlParam )
            GROUP BY logistica_item.uuid_produto;",
            $binds
        );

        if (!$dados) {
            return;
        }

        $job = new GerenciarAcompanhamento(
            array_column($dados, 'uuid_produto'),
            GerenciarAcompanhamento::ADICIONAR_NO_ACOMPANHAMENTO
        );
        dispatch($job->afterCommit());
        DB::table('entregas_faturamento_item')->insert($dados);
    }
    public static function consultaInfoProdutoTrocaMS(PDO $conexao, string $uuidProduto): array
    {
        $sql = "SELECT
                entregas_faturamento_item.id_cliente,
                entregas_faturamento_item.id_transacao,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.nome_tamanho,
                entregas_faturamento_item.uuid_produto,
                entregas_faturamento_item.data_base_troca,
                (
                    SELECT logistica_item.preco
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
                ) AS `preco`,
                (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = entregas_faturamento_item.id_produto
                        AND produtos_grade.nome_tamanho = entregas_faturamento_item.nome_tamanho
                ) AS `cod_barras`
                FROM entregas_faturamento_item
                WHERE entregas_faturamento_item.uuid_produto = :uuid_produto";

        $prepare = $conexao->prepare($sql);
        $prepare->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $prepare->execute();
        $dados = $prepare->fetch(PDO::FETCH_ASSOC);

        if (empty($dados)) {
            throw new NotFoundHttpException('Não existe produto com esse uuid');
        }

        $dados['id_transacao'] = (int) $dados['id_transacao'];
        $dados['id_produto'] = (int) $dados['id_produto'];
        $dados['id_cliente'] = (int) $dados['id_cliente'];
        $dados['preco'] = (float) $dados['preco'];

        return $dados;
    }

    public static function buscaIdsDeEntregaEmTransporte(array $produtos): array
    {
        [$sqlParam, $binds] = ConversorArray::criaBindValues($produtos);

        $sql = "SELECT
                    entregas_faturamento_item.id_entrega
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                WHERE
                    entregas_faturamento_item.uuid_produto IN ($sqlParam)
                    AND entregas.situacao = 'PT'
                GROUP BY entregas.id;";
        $dados = DB::selectColumns($sql, $binds);

        return $dados;
    }

    public static function manipulaStringMensagemDeProduto(array $item): string
    {
        if (mb_strlen($item['nome']) > 20) {
            $nome = mb_substr($item['nome'], 0, 20);
        } else {
            $nome = $item['nome'];
        }
        return "*{$item['id']} - {$nome} [ {$item['tamanho']} ]*";
    }
    /**
     * @param array<string> $uuidProdutos
     */
    public static function buscaProdutosParaNotificarChegadaPontoParado(array $uuidProdutos): array
    {
        [$sqlParam, $binds] = ConversorArray::criaBindValues($uuidProdutos);

        $sqlNotificacaoPares = "SELECT
                colaboradores.razao_social,
                colaboradores.telefone,
                entregas_faturamento_item.id_cliente,
                (
                    SELECT
                        JSON_OBJECT(
                            'horario_de_funcionamento', tipo_frete.horario_de_funcionamento,
                            'endereco', tipo_frete.mensagem,
                            'nome', tipo_frete.nome,
                            'latitude', tipo_frete.latitude,
                            'longitude', tipo_frete.longitude,
                            'telefone', ponto_colaboradores.telefone
                        )
                    FROM tipo_frete
                    INNER JOIN colaboradores ponto_colaboradores ON ponto_colaboradores.id = tipo_frete.id_colaborador
                    WHERE
                        tipo_frete.id = entregas.id_tipo_frete
                    LIMIT 1
                ) json_ponto,
                entregas.uuid_entrega,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT (
                        SELECT
                            JSON_OBJECT(
                                'id', produtos.id,
                                'nome', produtos.nome_comercial,
                                'foto', (
                                    SELECT produtos_foto.caminho
                                    FROM produtos_foto
                                    WHERE
                                        produtos_foto.id = produtos.id
                                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                    LIMIT 1
                                ),
                                'tamanho', entregas_faturamento_item.nome_tamanho,
                                'origem', entregas_faturamento_item.origem
                            )
                        FROM produtos
                        WHERE produtos.id = entregas_faturamento_item.id_produto
                        LIMIT 1
                    )),
                    ']'
                ) json_produtos
            FROM entregas_faturamento_item
            INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete AND tipo_frete.tipo_ponto = 'PP'
            INNER JOIN colaboradores ON entregas_faturamento_item.id_cliente = colaboradores.id
            WHERE
                entregas_faturamento_item.uuid_produto IN ($sqlParam)
                AND entregas_faturamento_item.situacao = 'AR'
            GROUP BY entregas_faturamento_item.id_cliente;";

        $dados = DB::select($sqlNotificacaoPares, $binds);

        return $dados;
    }
    /**
     * @issue https://github.com/mobilestock/web/pull/3122
     */
    public static function criaImagemGradeDeFotosDeProdutos(array $produtos, string $qrCode): string
    {
        $grade = new GradeImagens(800, 800, 10, 10);
        if (sizeof($produtos) == 1) {
            $img = imagecreatefromjpeg($produtos[0]['foto']);
            $grade->adicionarImagem($img, 6, 6, 1, 3);
            $img = imagecreatefrompng($qrCode);
            $grade->adicionarImagem($img, 4, 4, 6, 0);
            imagedestroy($img);
            return $grade->renderizar();
        }
        if (sizeof($produtos) > 1 && sizeof($produtos) <= 3) {
            $posX = 0;
            $posY = 0;
            foreach ($produtos as $index => $produto) {
                if ($index == 1) {
                    $posY = 5;
                }
                if ($index == 2) {
                    $posX = 5;
                }
                $img = imagecreatefromjpeg($produto['foto']);
                $grade->adicionarImagem($img, 5, 5, $posX, $posY);
                imagedestroy($img);
            }
            $img = imagecreatefrompng($qrCode);
            $grade->adicionarImagem($img, 5, 5, 5, 0);
            imagedestroy($img);
            return $grade->renderizar();
        }
        if (sizeof($produtos) == 4) {
            $posX = 0;
            $posY = 0;
            foreach ($produtos as $index => $produto) {
                if ($index == 1) {
                    $posY = 5;
                }
                if ($index == 2) {
                    $posX = 5;
                }
                if ($index == 3) {
                    $posY = 0;
                }
                $img = imagecreatefromjpeg($produto['foto']);
                $grade->adicionarImagem($img, 5, 5, $posX, $posY);
                imagedestroy($img);
            }
            $img = imagecreatefrompng($qrCode);
            $grade->adicionarImagem($img, 3, 3, 3.5, 3.5);
            imagedestroy($img);
            return $grade->renderizar();
        }
    }

    public static function listaProdutosDisponiveisParaEntregarAoCliente(int $idColaborador): array
    {
        $sql = "SELECT
                    entregas_faturamento_item.id_entrega,
                    entregas_faturamento_item.id_cliente,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.nome_destinatario')), colaboradores.razao_social) AS `razao_social`,
                    entregas_faturamento_item.id_produto,
                    produtos.nome_comercial,
                    entregas_faturamento_item.nome_tamanho,
                    entregas_faturamento_item.uuid_produto,
                    entregas_faturamento_item.data_atualizacao,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = entregas_faturamento_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto
                FROM entregas_faturamento_item
                INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
                INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
                INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete AND tipo_frete.categoria != 'MS'
                INNER JOIN transacao_financeiras_metadados ON entregas_faturamento_item.id_transacao = transacao_financeiras_metadados.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                WHERE
                    entregas.situacao = 'EN'
                    AND IF(tipo_frete.tipo_ponto = 'PM',
                        entregas_faturamento_item.situacao IN ('PE','AR'),
                        entregas_faturamento_item.situacao = 'AR'
                    )
                    AND :idColaboradorTipoFrete IN ( tipo_frete.id_colaborador, tipo_frete.id_colaborador_ponto_coleta )
                    AND entregas_faturamento_item.id_cliente = :idColaborador
                GROUP BY uuid_produto;";
        $dados = DB::select($sql, [
            ':idColaboradorTipoFrete' => Auth::user()->id_colaborador,
            ':idColaborador' => $idColaborador,
        ]);
        return $dados;
    }
    public static function listaEntregasFaturamentoItem(): array
    {
        $sql = "SELECT
                    entregas_faturamento_item.id_entrega,
                    entregas_faturamento_item.id_cliente,
                    colaboradores.razao_social,
                    entregas_faturamento_item.id_produto,
                    produtos.nome_comercial,
                    entregas_faturamento_item.nome_tamanho,
                    entregas_faturamento_item.uuid_produto,
                    entregas_faturamento_item.data_atualizacao,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = entregas_faturamento_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto
                FROM entregas_faturamento_item
                INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
                INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
                INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                WHERE
                    entregas.situacao NOT IN ('AB','EX')
                    AND entregas_faturamento_item.situacao = 'PE'
                    AND entregas.id_cliente = :idColaborador;";

        $resultado = DB::select($sql, [
            ':idColaborador' => Auth::user()->id_colaborador,
        ]);
        return $resultado;
    }
    public function listaItensInseridosNaEntrega(PDO $conexao, string $pesquisa = ''): array
    {
        if (mb_strlen($pesquisa)) {
            $where = " entregas_faturamento_item.id_entrega REGEXP :pesquisa
                    OR entregas_faturamento_item.id_produto REGEXP :pesquisa
                    OR entregas_faturamento_item.nome_tamanho REGEXP :pesquisa";
        } else {
            $where = ' DATE(entregas_faturamento_item.data_criacao) >= DATE_SUB(NOW(),INTERVAL 2 DAY)';
        }

        $sql = "SELECT
                    entregas_faturamento_item.id_produto,
                    produtos.nome_comercial nome_produto,
                    entregas_faturamento_item.nome_tamanho,
                    entregas_faturamento_item.uuid_produto,
                    entregas_faturamento_item.id_entrega,
                    usuarios.nome nome_responsavel,
                    colaboradores.razao_social nome_fornecedor,
                    tipo_frete.nome ponto_destino,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = entregas_faturamento_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY
                            produtos_foto.tipo_foto = 'MD' DESC,
                            produtos_foto.tipo_foto = 'LG' DESC
                        LIMIT 1
                    ) foto,
                    CONCAT(
                        '[',
                        (
                            SELECT
                                GROUP_CONCAT(
                                    JSON_OBJECT(
                                        'situacao',logistica_item_data_alteracao.situacao_nova,
                                        'nome_responsavel',usuarios.nome,
                                        'data',logistica_item_data_alteracao.data_criacao
                                    )
                                    ORDER BY logistica_item_data_alteracao.data_criacao DESC
                                )
                            FROM logistica_item_data_alteracao
                            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
                            WHERE
                                entregas_faturamento_item.uuid_produto = logistica_item_data_alteracao.uuid_produto
                        ),
                        ']'
                    ) log
                FROM entregas_faturamento_item
                INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
                INNER JOIN colaboradores ON produtos.id_fornecedor = colaboradores.id
                INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega AND entregas.situacao IN ('AB', 'EX')
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                INNER JOIN usuarios ON usuarios.id = entregas_faturamento_item.id_usuario
                WHERE
                   $where
                ORDER BY entregas_faturamento_item.data_atualizacao DESC
                LIMIT 200
                ";
        $prepare = $conexao->prepare($sql);
        if (mb_strlen($pesquisa)) {
            $prepare->bindValue(':pesquisa', $pesquisa);
        }
        $prepare->execute();
        $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
        $dadosTratados = array_map(function ($item) {
            $item['id_produto'] = (int) $item['id_produto'];
            $item['id_entrega'] = (int) $item['id_entrega'];
            $item['id_localizacao'] = (int) $item['id_localizacao'];
            $log = json_decode($item['log'], true);
            $conferencia = current(array_filter($log, fn($item) => $item['situacao'] == 'CO'));
            $separacao = current(array_filter($log, fn($item) => $item['situacao'] == 'SE'));
            $item['nome_conferidor'] = $conferencia['nome_responsavel'];
            $item['data_conferencia'] = $conferencia['data'];
            $item['nome_separador'] = $separacao['nome_responsavel'];
            $item['data_separacao'] = $separacao['data'];

            unset($item['log']);
            return $item;
        }, $dados);
        return $dadosTratados;
    }

    public static function buscaInfosProdutosEntregasAtrasadas(): array
    {
        $sql = "SELECT
                _entregas.id,
                DATE_FORMAT(_entregas_log_entregas.data_envio, '%d/%m/%Y %H:%i') AS `data_envio`,
                _entregas_log_entregas.id_usuario,
                _entregas_log_entregas.nome_usuario
            FROM (
                SELECT
                    entregas.id,
                    entregas.id_tipo_frete,
  	                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade') id_cidade,
                    entregas.data_atualizacao
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                WHERE NOT entregas.situacao = 'AB'
                    AND NOT entregas_faturamento_item.situacao = 'EN'
                GROUP BY entregas.id
            ) AS `_entregas`
            INNER JOIN tipo_frete ON tipo_frete.id = _entregas.id_tipo_frete
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN transportadores_raios ON transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                AND transportadores_raios.id_cidade = IF (
                    tipo_frete.tipo_ponto = 'PM',
                    _entregas.id_cidade,
                    colaboradores_enderecos.id_cidade
                )
            LEFT JOIN (
                SELECT
                    entregas_logs.id_entrega,
                    entregas_logs.data_criacao AS `data_envio`,
                    entregas_logs.id_usuario,
                    usuarios.nome AS `nome_usuario`
                FROM entregas_logs
                INNER JOIN usuarios ON usuarios.id = entregas_logs.id_usuario
                WHERE NOT entregas_logs.situacao_anterior = 'PT'
                    AND entregas_logs.situacao_nova = 'PT'
            ) AS `_entregas_log_entregas` ON _entregas_log_entregas.id_entrega = _entregas.id
            WHERE DATEDIFF_DIAS_UTEIS(
                CURDATE(),
                IF (
                    _entregas_log_entregas.data_envio IS NULL,
                    _entregas.data_atualizacao,
                    _entregas_log_entregas.data_envio
                )
            ) >= transportadores_raios.prazo_forcar_entrega
            GROUP BY _entregas.id
            ORDER BY IF (
                _entregas_log_entregas.data_envio IS NULL,
                _entregas.id,
                _entregas_log_entregas.data_envio
            ) ASC;";

        $entregas = DB::select($sql);

        [$bind, $valores] = ConversorArray::criaBindValues(array_column($entregas, 'id'), 'id_entrega');
        $sql = "SELECT
                entregas_faturamento_item.id_entrega,
                entregas_faturamento_item.id_transacao,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.nome_tamanho,
                entregas_faturamento_item.uuid_produto,
                entregas_faturamento_item.origem,
                entregas.situacao AS `situacao_pacote`,
                DATE_FORMAT(entregas_faturamento_item.data_criacao, '%d/%m/%Y %H:%i') AS `data_adicionado_a_entrega`,
                DATE_FORMAT(entregas.data_atualizacao, '%d/%m/%Y %H:%i') AS `data_atualizacao_entrega`,
                entregas_faturamento_item.situacao AS `situacao_produto`,
                DATE_FORMAT(entregas_faturamento_item.data_atualizacao, '%d/%m/%Y %H:%i') AS `data_atualizacao_produto`,
                tipo_frete.categoria,
                tipo_frete.tipo_ponto,
                JSON_OBJECT(
                    'id_colaborador', fornecedor_colaboradores.id,
                    'nome', fornecedor_colaboradores.razao_social
                ) AS `json_fornecedor`,
                JSON_OBJECT(
                    'id_colaborador', cliente_colaboradores.id,
                    'nome', cliente_colaboradores.razao_social,
                    'telefone', cliente_colaboradores.telefone,
                    'id_cidade', colaboradores_enderecos.id_cidade,
                    'endereco', colaboradores_enderecos.logradouro,
                    'cidade', colaboradores_enderecos.cidade,
                    'numero', colaboradores_enderecos.numero,
                    'bairro', colaboradores_enderecos.bairro,
                    'cep', colaboradores_enderecos.cep,
                    'uf', colaboradores_enderecos.uf,
                    'complemento', colaboradores_enderecos.complemento,
                    'saldo', saldo_cliente(cliente_colaboradores.id)
                ) AS `json_cliente`,
                JSON_OBJECT(
                    'id_colaborador', tipo_frete_colaboradores.id,
                    'nome', tipo_frete_colaboradores.razao_social,
                    'telefone', tipo_frete_colaboradores.telefone,
                    'id_tipo_frete', tipo_frete.id,
                    'int_devolucoes_pendentes', COUNT(entregas_devolucoes_item.id)
                ) AS `json_transportador`,
                JSON_OBJECT(
                    'id_colaborador', ponto_coleta_colaboradores.id,
                    'nome', ponto_coleta_colaboradores.razao_social,
                    'telefone', ponto_coleta_colaboradores.telefone
                ) AS `json_ponto_coleta`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto_produto`
            FROM entregas
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.situacao = 'PE'
                AND entregas_devolucoes_item.id_ponto_responsavel = entregas.id_tipo_frete
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN colaboradores AS `fornecedor_colaboradores` ON fornecedor_colaboradores.id = entregas_faturamento_item.id_responsavel_estoque
            INNER JOIN colaboradores AS `cliente_colaboradores` ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
            LEFT JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = entregas_faturamento_item.id_cliente
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN colaboradores AS `tipo_frete_colaboradores` ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores AS `ponto_coleta_colaboradores` ON ponto_coleta_colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
            WHERE
                entregas_faturamento_item.id_entrega IN ($bind)
                AND (
                    entregas_faturamento_item.situacao <> 'EN'
                    OR entregas.situacao <> 'EN'
                )
            GROUP BY entregas_faturamento_item.uuid_produto
            ORDER BY entregas_faturamento_item.data_criacao ASC;";

        $produtos = DB::select($sql, $valores);

        $produtos = array_map(function (array $produto) use ($entregas): array {
            $entrega = array_filter($entregas, function (array $entrega) use ($produto): bool {
                return $entrega['id'] === $produto['id_entrega'];
            });
            $entrega = reset($entrega);
            unset($entrega['id']);
            $produto = array_merge($produto, $entrega);
            $campos = array_keys($produto);
            foreach ($campos as $campo) {
                if (in_array($campo, ['fornecedor', 'cliente', 'transportador', 'ponto_coleta'])) {
                    if (!empty($produto[$campo]['telefone'])) {
                        $produto[$campo]['whatsapp'] = Globals::geraQRCODE(
                            "https://api.whatsapp.com/send/?phone=55{$produto[$campo]['telefone']}"
                        );
                    }
                    if ($campo === 'transportador') {
                        switch (true) {
                            case $produto[$campo]['id_tipo_frete'] === 2:
                                $produto[$campo]['titulo'] = 'ENVIO_TRANSPORTADORA';
                                break;
                            case $produto[$campo]['id_tipo_frete'] === 3:
                                $produto[$campo]['titulo'] = 'CENTRAL';
                                break;
                            case $produto['tipo_ponto'] === 'PM':
                                $produto[$campo]['titulo'] = 'ENTREGADOR';
                                break;
                            case $produto['tipo_ponto'] === 'PP':
                                $produto[$campo]['titulo'] = 'PONTO_RETIRADA';
                                break;
                        }
                        unset($produto[$campo]['id_tipo_frete']);
                    }
                }
            }
            if ($produto['situacao_produto'] !== 'PE') {
                switch ($produto['situacao_produto']) {
                    case 'EN':
                        $produto['situacao_atual'] = 'Entregue ao cliente';
                        break;
                    case 'AR':
                        $produto['situacao_atual'] = 'Para retirar';
                        break;
                    default:
                        $produto['situacao_atual'] = 'Situação não encontrada';
                        break;
                }
            } else {
                switch ($produto['situacao_pacote']) {
                    case 'EN':
                        $produto['situacao_atual'] = 'Pacote entregue ao Ponto';
                        break;
                    case 'PT':
                        $produto['situacao_atual'] = 'Pacote em transporte';
                        break;
                    case 'EX':
                        $produto['situacao_atual'] = 'Pacote na expedição';
                        break;
                }
            }
            unset($produto['situacao_pacote'], $produto['situacao_produto']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function verificaQuantidadeRaiosPorProdutos(array $listaDeProdutos): void
    {
        [$index, $binds] = ConversorArray::criaBindValues($listaDeProdutos);

        $raios = DB::selectColumns(
            "SELECT JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') AS `json_id_raio`
                    FROM logistica_item
                    INNER JOIN transacao_financeiras_metadados ON
                        transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                WHERE
                    logistica_item.uuid_produto IN ($index)
            GROUP BY json_id_raio;",
            $binds
        );
        $raios = array_filter($raios, fn(?int $raio) => !empty($raio));
        if (empty($raios)) {
            throw new BadRequestException('Nenhum raio foi encontrado para os produtos informados');
        }
        if (count($raios) > 1) {
            throw new BadRequestException(
                'Não foi possível adicionar a entrega pois os produtos bipados não pertencem a um único raio'
            );
        }
    }

    public static function informacoesRelatorioEntregadores(int $idEntrega): array
    {
        $informacoes = DB::select(
            "SELECT
                entregas_faturamento_item.id_entrega,
                colaboradores.razao_social,
                colaboradores.telefone telefone_cliente,
                transacao_financeiras_metadados.valor AS json_endereco,
                COALESCE((
					SELECT 1
                    FROM troca_pendente_agendamento
                    WHERE troca_pendente_agendamento.id_cliente = entregas_faturamento_item.id_cliente
                    GROUP BY troca_pendente_agendamento.id_cliente
                ),0) AS tem_troca,
                COUNT(entregas_faturamento_item.uuid_produto) AS qtd_produtos,
                IF(entregas.id_raio IS NULL, NULL,
                    (SELECT
                         COALESCE(transportadores_raios.apelido, entregas.id_raio)
                     FROM transportadores_raios
                     WHERE transportadores_raios.id = entregas.id_raio)
                ) AS 'apelido_raio'
            FROM entregas_faturamento_item
            INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
            INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            WHERE entregas.id = :id_entrega
            GROUP BY entregas_faturamento_item.id_cliente
            ORDER BY JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.bairro') ASC;",
            ['id_entrega' => $idEntrega]
        );

        $informacoes = array_map(function (array $informacao): array {
            $informacao['razao_social'] = trim($informacao['razao_social']);
            $informacao['nome_destinatario'] =
                $informacao['endereco']['nome_destinatario'] ?? $informacao['razao_social'];
            $informacao = array_merge($informacao, $informacao['endereco']);
            $informacao['cidade'] = trim($informacao['cidade']);
            $informacao['bairro'] = trim($informacao['bairro']);
            $informacao['logradouro'] = trim($informacao['logradouro']);
            $informacao['numero'] = trim($informacao['numero']);
            $informacao['telefone_destinatario'] =
                $informacao['endereco']['telefone_destinatario'] ?? $informacao['telefone_cliente'];
            unset($informacao['endereco']);
            if ($informacao['complemento'] === 'null') {
                $informacao['complemento'] = '';
            }

            return $informacao;
        }, $informacoes);

        return $informacoes;
    }

    public static function buscaProdutosParaNotificarEntregaPontoParado(array $uuidsProdutos): void
    {
        [$sqlBinds, $binds] = ConversorArray::criaBindValues($uuidsProdutos, 'uuid_produto');

        $dadosMensagem = DB::selectOne(
            "SELECT
                tipo_frete.nome nome_entregador,
                colaboradores.razao_social nome_cliente,
                colaboradores.telefone telefone_cliente,
                REGEXP_REPLACE(entregas_faturamento_item.nome_recebedor, '^([0-9]+ - )', '') nome_recebedor,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'id', entregas_faturamento_item.id_produto,
                        'nome', COALESCE(produtos.nome_comercial, produtos.descricao, ''),
                        'tamanho', entregas_faturamento_item.nome_tamanho
                    )),
                    ']'
                ) produtos_json
            FROM entregas_faturamento_item
            INNER JOIN usuarios ON usuarios.id = entregas_faturamento_item.id_usuario
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
            INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            WHERE entregas_faturamento_item.uuid_produto IN ($sqlBinds)
                AND entregas_faturamento_item.situacao = 'EN'
                AND entregas_faturamento_item.id_produto NOT IN (:id_produto_frete, :id_produto_frete_expresso, :id_produto_frete_volume)
            GROUP BY usuarios.id;",
            $binds + [
                ':id_produto_frete' => ProdutoModel::ID_PRODUTO_FRETE,
                ':id_produto_frete_expresso' => ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                ':id_produto_frete_volume' => ProdutoModel::ID_PRODUTO_FRETE_VOLUME,
            ]
        );

        if (empty($dadosMensagem)) {
            return;
        }

        $dadosMensagem['nome_cliente'] = trim($dadosMensagem['nome_cliente']);
        $dadosMensagem['nome_recebedor'] = trim($dadosMensagem['nome_recebedor']);
        $dadosMensagem['nome_entregador'] = trim($dadosMensagem['nome_entregador']);
        $dadosMensagem['telefone_cliente'] = (int) preg_replace('/[^0-9]+/', '', $dadosMensagem['telefone_cliente']);
        $dadosMensagem['produtos'] = array_map(
            fn(array $produto): string => EntregasFaturamentoItemService::manipulaStringMensagemDeProduto($produto),
            $dadosMensagem['produtos']
        );

        $mensagem = "Olá {$dadosMensagem['nome_cliente']}." . PHP_EOL;
        $mensagem .= 'Seus produtos:' . PHP_EOL . PHP_EOL;
        $mensagem .= implode("\n", $dadosMensagem['produtos']) . PHP_EOL . PHP_EOL;

        switch (true) {
            case Gate::allows('ENTREGADOR') &&
                ($dadosMensagem['nome_recebedor'] === '' ||
                    !!preg_match("/{$dadosMensagem['nome_cliente']}/", $dadosMensagem['nome_recebedor'])):
                $mensagem .=
                    "Acabaram de ser entregues pelo entregador: {$dadosMensagem['nome_entregador']}!" . PHP_EOL;
                break;
            case Gate::allows('ENTREGADOR'):
                $mensagem .=
                    "Acabaram de ser entregues pelo entregador {$dadosMensagem['nome_entregador']} para: {$dadosMensagem['nome_recebedor']}!" .
                    PHP_EOL;
                break;
            default:
                $mensagem .= "Acabaram de ser entregues pelo ponto: {$dadosMensagem['nome_entregador']}!" . PHP_EOL;
        }

        $linkDevolucoes = "{$_ENV['URL_MEULOOK']}devolucoes";
        $mensagem .=
            PHP_EOL .
            "Você pode devolver o produto em até 7 dias caso não sirva ou não tenha gostado, clicando no link: $linkDevolucoes" .
            PHP_EOL;
        $mensagem .=
            PHP_EOL .
            'Esperamos que a sua experiência tenha sido a melhor possível e que você retorne muitas outras vezes!';
        $whatsapp = new MessageService();
        $whatsapp->sendMessageWhatsApp($dadosMensagem['telefone_cliente'], $mensagem);
    }

    public static function buscaItensDasEntregasEntregues(string $pesquisa, int $pagina): array
    {
        $porPagina = 100;
        $offset = ($pagina - 1) * $porPagina;

        $parametros = [
            ':porPagina' => $porPagina,
            ':offset' => $offset,
        ];

        $where = '';
        if ($pesquisa) {
            $parametros[':pesquisa'] = $pesquisa;
            $where .= " AND CONCAT_WS(
                ' ',
                cliente_colaboradores.razao_social,
                ponto_colaboradores.razao_social,
                produtos.nome_comercial,
                cliente_colaboradores.id,
                ponto_colaboradores.id,
                produtos.id,
                cliente_colaboradores.telefone,
                ponto_colaboradores.telefone
            ) REGEXP :pesquisa";
        }

        $consulta = DB::select(
            "SELECT entregas_faturamento_item.uuid_produto,
                CONCAT(
                    entregas_faturamento_item.id_produto,
                    ' - ',
                    LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao))
                ) produto,
                CONCAT(
                    cliente_colaboradores.id,
                    ' - ',
                    LOWER(cliente_colaboradores.razao_social)
                ) cliente,
                cliente_colaboradores.telefone cliente_telefone,
                CONCAT(
                    ponto_colaboradores.id,
                    ' - ',
                    LOWER(ponto_colaboradores.razao_social)
                ) ponto,
                ponto_colaboradores.telefone ponto_telefone,
                entregas_faturamento_item.nome_tamanho,
                DATE_FORMAT(entregas_faturamento_item.data_entrega, '%d/%m/%Y') data_retirada,
                DATE(entregas_faturamento_item.data_base_troca) data_base_troca
            FROM entregas_faturamento_item
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
            INNER JOIN colaboradores cliente_colaboradores ON cliente_colaboradores.id = entregas_faturamento_item.id_cliente
            INNER JOIN colaboradores ponto_colaboradores ON ponto_colaboradores.id = logistica_item.id_colaborador_tipo_frete
            LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto
            WHERE entregas_faturamento_item.situacao = 'EN'
                AND DATE(entregas_faturamento_item.data_entrega) > NOW() - INTERVAL 100 DAY
                    AND entregas_devolucoes_item.id IS NULL
                        $where
            ORDER BY entregas_faturamento_item.data_base_troca DESC
            LIMIT :porPagina OFFSET :offset",
            $parametros
        );
        if (empty($consulta)) {
            return [];
        }

        $consulta = array_map(function ($item) {
            $item['cliente_whatsapp'] = "https://api.whatsapp.com/send/?phone=55{$item['cliente_telefone']}";
            $item['ponto_whatsapp'] = "https://api.whatsapp.com/send/?phone=55{$item['ponto_telefone']}";

            $item['cliente_telefone'] = Str::formatarTelefone($item['cliente_telefone']);
            $item['ponto_telefone'] = Str::formatarTelefone($item['ponto_telefone']);
            return $item;
        }, $consulta);

        return $consulta;
    }

    /**
     * @param 'json'|'bool' $tipoRetorno
     */
    public static function sqlCaseBuscarLogisticaPendente(string $tipoRetorno): string
    {
        $select = '';
        $limit = '';
        $join = '';
        $where = '';

        switch ($tipoRetorno) {
            case 'json':
                $join = 'INNER JOIN produtos ON produtos.id = logistica_item.id_produto';
                $select = "CONCAT(
                                '[',
                                    GROUP_CONCAT(
                                        JSON_OBJECT(
                                            'produto_foto',(
                                                SELECT
                                                    produtos_foto.caminho
                                                FROM produtos_foto
                                                WHERE
                                                    produtos_foto.id = logistica_item.id_produto
                                                    AND produtos_foto.tipo_foto <> 'SM'
                                                ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                                LIMIT 1
                                            ),
                                            'nome_fornecedor', (
                                                SELECT colaboradores.razao_social
                                                FROM colaboradores
                                                WHERE colaboradores.id = produtos.id_fornecedor
                                            ),
                                            'id_produto',logistica_item.id_produto,
                                            'responsavel_estoque', IF(logistica_item.id_responsavel_estoque = 1, 'Fulfillment', 'Externo'),
                                            'nome_tamanho',logistica_item.nome_tamanho,
                                            'situacao',logistica_item.situacao,
                                            'uuid_produto',logistica_item.uuid_produto,
                                            'id_transacao', logistica_item.id_transacao,
                                            'localizacao', COALESCE(produtos.localizacao, '-'),
                                            'data_atualizacao', DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i:%s'),
                                            'nome_usuario', IF (logistica_item.situacao = 'PE', '-',
                                                (
                                                    SELECT usuarios.nome
                                                    FROM usuarios
                                                    WHERE usuarios.id = logistica_item.id_usuario
                                                )
                                            )
                                        )
                                    ),
                                ']'
                            )";
                break;
            case 'bool':
                $select = '1';
                $limit = 'LIMIT 1';
                $where = ' AND logistica_item.id_responsavel_estoque > 1 ';
                break;
        }

        $retorno =
            "CASE
                        WHEN tipo_frete.id IN (" .
            TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE .
            ") THEN
                        (
                            SELECT $select
                            FROM logistica_item
                            $join
                            WHERE
                                logistica_item.id_entrega IS NULL
                                $where
                                AND logistica_item.situacao <= :situacao_logistica
                                AND logistica_item.id_cliente = entregas.id_cliente
                                AND logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
                            $limit
                        )
                        WHEN tipo_frete.id NOT IN (" .
            TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE .
            ") AND tipo_frete.tipo_ponto = 'PM' THEN
                        (
                            SELECT $select
                            FROM logistica_item
                            $join
                            INNER JOIN transacao_financeiras_metadados ON
                                transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                                AND JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') = entregas.id_raio
                            WHERE
                                logistica_item.id_entrega IS NULL
                                AND logistica_item.situacao = :situacao_logistica
                                AND logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
                            $limit
                        )
                        WHEN tipo_frete.id NOT IN (" .
            TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE .
            ") AND tipo_frete.tipo_ponto = 'PP' THEN
                        (
                            SELECT $select
                            FROM logistica_item
                            $join
                            WHERE
                                logistica_item.id_entrega IS NULL
                                AND logistica_item.situacao = :situacao_logistica
                                AND logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
                            $limit
                        )
                    END";

        return $retorno;
    }

    public static function buscaCoberturaEntregador(int $idColaborador, ?int $idCidade = null): array
    {
        $where = ' AND tipo_frete.id_colaborador = :idColaborador ';
        $binds = [];

        if (!empty($idCidade)) {
            $where = " AND (
                        tipo_frete.id_colaborador = :idColaborador
                        OR tipo_frete.id_colaborador_ponto_coleta = :idColaborador
                    )
                    AND :idCidade = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.id_cidade')),(
                        SELECT
                            municipios.id
                        FROM municipios
                        WHERE
                            municipios.nome = JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.cidade'))
                            AND municipios.uf = JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.uf'))
                        LIMIT 1
                    )) ";
            $binds[':idCidade'] = $idCidade;
        }
        $sql = "SELECT
                    entregas_faturamento_item.id_cliente,
                    JSON_OBJECT(
                        'nome', tipo_frete.nome,
                        'id_colaborador', tipo_frete.id_colaborador
                    ) json_entregador,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            DISTINCT
                            JSON_OBJECT(
                                'id_produto',entregas_faturamento_item.id_produto,
                                'nome',(
                                    SELECT produtos.nome_comercial
                                    FROM produtos
                                    WHERE produtos.id = entregas_faturamento_item.id_produto
                                    LIMIT 1
                                ),
                                'nome_tamanho', entregas_faturamento_item.nome_tamanho,
                                'uuid_produto', entregas_faturamento_item.uuid_produto,
                                'foto',(
                                    SELECT
                                        produtos_foto.caminho
                                    FROM produtos_foto
                                    WHERE
                                        produtos_foto.id = entregas_faturamento_item.id_produto
                                    ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                                    LIMIT 1
                                )
                            )
                        ),
                        ']'
                    ) json_produtos_para_entregar,
                    COALESCE(
                        CONCAT(
                            '[',
                            (
                                SELECT
                                    GROUP_CONCAT(
                                        DISTINCT
                                        JSON_OBJECT(
                                        'id_produto',troca_pendente_agendamento.id_produto,
                                        'nome',(
                                            SELECT produtos.nome_comercial
                                            FROM produtos
                                            WHERE produtos.id = troca_pendente_agendamento.id_produto
                                            LIMIT 1
                                        ),
                                        'nome_tamanho', troca_pendente_agendamento.nome_tamanho,
                                        'uuid_produto', troca_pendente_agendamento.uuid,
                                        'foto',(
                                            SELECT
                                                produtos_foto.caminho
                                            FROM produtos_foto
                                            WHERE
                                                produtos_foto.id = troca_pendente_agendamento.id_produto
                                            ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                                            LIMIT 1
                                        )
                                    )
                                )
                                FROM troca_pendente_agendamento
                                WHERE
                                    troca_pendente_agendamento.id_cliente = entregas_faturamento_item.id_cliente
                                    AND troca_pendente_agendamento.tipo_agendamento = 'ML'
                            ),
                            ']'
                        ),
                        '[]'
                    ) json_produtos_para_trocar,
                    DATEDIFF_DIAS_UTEIS(
                        CURDATE(),
                        DATE(entregas.data_atualizacao)
                    ) dias_com_o_entregador,
                    transacao_financeiras_metadados.valor json_endereco_metadados,
                    JSON_OBJECT(
                        'bairro', colaboradores_enderecos.bairro,
                        'logradouro', colaboradores_enderecos.logradouro,
                        'numero', colaboradores_enderecos.numero,
                        'complemento', colaboradores_enderecos.complemento,
                        'ponto_de_referencia', colaboradores_enderecos.ponto_de_referencia,
                        'cidade',colaboradores_enderecos.cidade,
                        'uf', colaboradores_enderecos.uf
                    ) json_endereco_colaborador,
                    colaboradores.razao_social,
                    colaboradores.foto_perfil,
                    colaboradores.telefone telefone_cliente,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.latitude')),colaboradores_enderecos.latitude) AS `latitude_float`,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.longitude')),colaboradores_enderecos.longitude) AS `longitude_float`,
                    entregas_faturamento_item.uuid_produto
                FROM entregas
                JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                JOIN transacao_financeiras_metadados ON
                    entregas_faturamento_item.id_transacao = transacao_financeiras_metadados.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                WHERE
                    tipo_frete.tipo_ponto = 'PM'
                    $where
                    AND entregas_faturamento_item.situacao <> 'EN'
                    AND entregas.situacao = 'EN'
                GROUP BY entregas_faturamento_item.id_cliente
                ORDER BY colaboradores.razao_social;";

        $binds[':idColaborador'] = $idColaborador;

        $consulta = DB::select($sql, $binds);

        $dados = array_map(function ($item) {
            $item['telefone_destinatario'] =
                $item['endereco_metadados']['telefone_destinatario'] ?? $item['telefone_cliente'];
            $item['nome_destinatario'] = $item['endereco_metadados']['nome_destinatario'] ?? $item['razao_social'];

            $endereco = [];

            foreach ($item['endereco_metadados'] as $campo => $valor) {
                switch (true) {
                    case empty($valor) && !empty($item['endereco_colaborador'][$campo]):
                        $endereco[$campo] = $item['endereco_colaborador'][$campo];
                        break;
                    case empty($valor) && empty($item['endereco_colaborador'][$campo]):
                        $endereco[$campo] = null;
                        break;

                    default:
                        $endereco[$campo] = $valor;
                        break;
                }
            }
            unset($item['endereco_metadados'], $item['endereco_colaborador']);
            $item['endereco'] = "{$endereco['logradouro']}, {$endereco['numero']} - {$endereco['bairro']}";
            $item['endereco'] .= ", {$endereco['cidade']} - {$endereco['uf']}";
            if (!empty($endereco['complemento'])) {
                $item['endereco'] .= ", {$endereco['complemento']}";
            }
            if (!empty($endereco['ponto_de_referencia'])) {
                $item['endereco'] .= ", {$endereco['ponto_de_referencia']}";
            }

            return $item;
        }, $consulta);

        return $dados;
    }
}
