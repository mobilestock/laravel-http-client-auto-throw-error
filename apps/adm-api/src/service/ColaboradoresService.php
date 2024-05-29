<?php

namespace MobileStock\service;

use DateTime;
use DomainException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\LogisticaItem;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\Usuario;
use MobileStock\service\Ranking\RankingService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property string|array $situacao_fraude
 * @property string $origem_transacao
 */
class ColaboradoresService
{
    public static function validaImagemExplicita(string $foto)
    {
        $key = Globals::MODERATE_CONTENT_TOKEN;
        $curl = curl_init("https://api.moderatecontent.com/moderate/?key=$key&url=$foto");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resposta = json_decode(curl_exec($curl), true);
        return $resposta;
    }

    public function listaColaboradores(string $tipo)
    {
        $query = "SELECT id, razao_social FROM colaboradores WHERE tipo='{$tipo}';";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscaColaboradorApi(PDO $conexao, int $id)
    {
        $query = 'SELECT id_zoop FROM api_colaboradores WHERE id_colaborador=:id';
        $stm = $conexao->prepare($query);
        $stm->bindValue('id', $id, PDO::PARAM_INT);
        $stm->execute();
        return $stm->fetch(PDO::FETCH_ASSOC);
    }

    public function criarSellerIndividual(
        int $id_colaborador,
        string $first_name,
        string $last_name,
        string $taxpayer_id,
        string $line1,
        string $neighborhood,
        string $city,
        string $state,
        string $postal_code
    ) {
        return [
            'id_colaborador' => $id_colaborador,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'taxpayer_id' => $taxpayer_id,
            'line1' => $line1,
            'neighborhood' => $neighborhood,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
        ];
    }

    public function criarSellerBusines(
        int $id_colaborador,
        string $first_name,
        string $last_name,
        string $taxpayer_id,
        string $line1,
        string $neighborhood,
        string $city,
        string $state,
        string $postal_code,
        string $ein
    ) {
        return [
            'id_colaborador' => $id_colaborador,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'ein' => $ein,
            'taxpayer_id' => $taxpayer_id,
            'line1' => $line1,
            'neighborhood' => $neighborhood,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
        ];
    }

    public function busca(PDO $conexao, string $tabela, array $fields, array $where)
    {
        $query = 'SELECT 1=1';
        foreach ($fields as $key => $f) {
            $query .= ", {$f}";
        }
        $query .= " FROM {$tabela} WHERE 1=1";
        foreach ($where as $key => $w) {
            $query .= "{$w}";
        }
        $stm = $conexao->prepare($query);
        $stm->execute();
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    // public static function buscaUltimoFreteUsado(PDO $conexao, int $idCliente): array
    // {
    //     $sql = "SELECT
    //                 tipo_frete.id,
    //                 tipo_frete.id_colaborador
    //             FROM entregas_faturamento_item
    //             LEFT JOIN entregas
    //                 ON entregas.id_cliente = entregas_faturamento_item.id_cliente
    //                 AND entregas.id = entregas_faturamento_item.id_entrega
    //             JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
    //             WHERE entregas_faturamento_item.id_cliente = :idCliente
    //                 AND entregas_faturamento_item.origem = 'MS'
    //             ORDER BY entregas.id DESC
    //             LIMIT 1";
    //     $prepare = $conexao->prepare($sql);
    //     $prepare->bindValue(":idCliente",$idCliente,PDO::PARAM_INT);
    //     $prepare->execute();
    //     $resultado = $prepare->fetch(PDO::FETCH_ASSOC);
    //     return $resultado ?: [];
    // }
    public static function buscaTelefoneCliente($id_cliente)
    {
        $query = "SELECT
        TRIM(
        COALESCE(
          colaboradores.telefone,
          colaboradores.telefone2,
          (SELECT usuarios.telefone FROM usuarios WHERE id_colaborador = $id_cliente)
        )) telefoneCliente FROM colaboradores
        WHERE colaboradores.id = $id_cliente AND LENGTH('telefoneCliente') > 1";

        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $lista = $resultado->fetch(PDO::FETCH_ASSOC);
        return $lista['telefoneCliente'];
    }

    public static function buscaNomeUsuarioMeulookPorId(PDO $conexao, int $idColaborador): string
    {
        $sql = "SELECT
                colaboradores.usuario_meulook nome_usuario
            FROM
                colaboradores
            WHERE
                colaboradores.id = :id_colaborador";

        $resultado = $conexao->prepare($sql);
        $resultado->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $resultado->execute();
        $nomeUsuario = $resultado->fetchColumn();

        if (!$nomeUsuario) {
            throw new NotFoundHttpException('Usuário não encontrado');
        }
        return $nomeUsuario;
    }

    /**
     * Essa função é usada para definir o tipo de autenticação do usuário.
     * A query deverá ter JOIN ou FROM da tabela usuarios.
     */
    public static function caseTipoAutenticacao(string $origem): string
    {
        $caseNenhumaSenha = "WHEN usuarios.permissao IN ('10', '10,13') OR usuarios.senha IS NULL THEN 'NENHUMA'";
        if ($origem === 'LP') {
            $caseNenhumaSenha = "WHEN usuarios.senha IS NULL THEN 'NENHUMA'";
        } elseif (in_array($origem, ['ADM', 'APP_ENTREGA', 'APP_INTERNO'])) {
            $caseNenhumaSenha = "WHEN FALSE THEN 'NENHUMA'";
        }

        $caseCompleto = "CASE
            $caseNenhumaSenha
            ELSE 'SENHA'
        END tipo_autenticacao";

        return $caseCompleto;
    }

    public static function consultaUsuarioLogin(string $telefone): array
    {
        $origem = app(Origem::class);
        $sqlCaseTipoAutenticacao = self::caseTipoAutenticacao($origem);

        $whereOrigem = '';
        if ($origem->ehAplicativoEntregas()) {
            $whereOrigem = " AND usuarios.permissao REGEXP '" . Usuario::VERIFICA_PERMISSAO_ACESSO_APP_ENTREGAS . "'";
        }

        $consulta = DB::select(
            "SELECT
                usuarios.id_colaborador,
                usuarios.id AS `id_usuario`,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                COALESCE(colaboradores.usuario_meulook, colaboradores.razao_social) usuario_meulook,
                colaboradores.telefone,
                colaboradores.razao_social,
                $sqlCaseTipoAutenticacao
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            WHERE colaboradores.telefone = :telefone $whereOrigem
            GROUP BY colaboradores.id
            ORDER BY colaboradores.conta_principal DESC;",
            ['telefone' => $telefone]
        );

        if (empty($consulta)) {
            throw new NotFoundHttpException('Nenhum cadastro com o telefone informado encontrado');
        }

        return $consulta;
    }

    // public static function consultaInfluencersDestaque(\PDO $conexao, $idColaborador)
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             compartilhador.id,
    //             compartilhador.usuario_meulook,
    //             compartilhador.foto_perfil,
    //             SUM(pedido_item_meu_look.preco) valor,
    //             (
    //                SELECT CONCAT(municipios.nome, ', ', municipios.uf)
    //                FROM municipios
    //                 WHERE municipios.id = compartilhador.id_cidade
    //             ) cidade
    //         FROM pedido_item_meu_look
    //         INNER JOIN produtos ON produtos.id = pedido_item_meu_look.id_produto
    //         INNER JOIN colaboradores fabricante ON fabricante.id = produtos.id_fornecedor
    //         INNER JOIN colaboradores compartilhador ON
    //             compartilhador.id = pedido_item_meu_look.id_colaborador_compartilhador_link AND
    //             compartilhador.id <> COALESCE((SELECT configuracoes.id_colaborador_padrao_link FROM configuracoes LIMIT 1), 10541)
    //         INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid = pedido_item_meu_look.uuid
    //         INNER JOIN transacao_financeiras ON
    //             transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao AND
    //             transacao_financeiras.`status` = 'PA' AND
    //             transacao_financeiras.data_atualizacao >= NOW() - INTERVAL 30 DAY
    //         WHERE fabricante.id = :idColaborador
    //         GROUP BY id
    //         ORDER BY valor DESC"
    //     );
    //     $stmt->bindValue(':idColaborador', $idColaborador);
    //     $stmt->execute();
    //     $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $consulta;
    // }

    public static function buscaDadosParaTornarPonto(): array
    {
        $consulta = DB::select(
            "SELECT
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.complemento,
                colaboradores_enderecos.cep,
                IF(
                    colaboradores.email <> (SELECT configuracoes.email_padrao_colaboradores FROM configuracoes LIMIT 1),
                    colaboradores.email,
                    ''
                ) email,
                COALESCE(LENGTH(usuarios.senha) > 0, FALSE) tem_senha
            FROM colaboradores
            INNER JOIN colaboradores_enderecos ON
            colaboradores_enderecos.id_colaborador = colaboradores.id
            AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :id_cliente",
            ['id_cliente' => Auth::id()]
        );

        return $consulta;
    }

    //    /**
    //     * @deprecated
    //     *
    //     * Método não será mais utilizado, código mantido para fins de necessidades futuras.
    //     */
    //    public static function notificaPontosColetaTransportadora(\PDO $conexao)
    //    {
    //        // não fiz subselect pois creio que vai ser usado na mensagem.
    //        $atraso_padrao = $conexao->query("SELECT atraso_padrao_mensagem_coleta_transportadora FROM configuracoes LIMIT 1")->fetchColumn();
    //
    //        $consulta = $conexao->query(
    //            "SELECT
    //                pontos_disponiveis.id_colaborador_transportadora,
    //                pontos_disponiveis.nome_transportadora,
    //                CONCAT('[', GROUP_CONCAT(JSON_OBJECT('id', pontos_disponiveis.id_colaborador_ponto,
    //                                                    'nome', pontos_disponiveis.razao_social,
    //                                                    'telefone', pontos_disponiveis.telefone)), ']') pontos_elegiveis
    //            FROM
    //            (SELECT
    //                colaboradores.id id_colaborador_transportadora,
    //                colaboradores.razao_social nome_transportadora,
    //                colaborador_ponto.id id_colaborador_ponto,
    //                colaborador_ponto.razao_social,
    //                COALESCE(colaborador_ponto.telefone, colaborador_ponto.telefone2) telefone
    //            FROM colaboradores
    //            INNER JOIN transportadoras_cidades ON transportadoras_cidades.id_transportadora = colaboradores.id
    //            INNER JOIN transportadoras_horarios ON transportadoras_horarios.id_transportadora = colaboradores.id
    //            JOIN tipo_frete ON tipo_frete.categoria = 'ML'
    //            INNER JOIN colaboradores colaborador_ponto ON colaborador_ponto.id = tipo_frete.id_colaborador
    //            WHERE colaboradores.tipo = 'T'
    //                AND HOUR(transportadoras_horarios.sexta - INTERVAL $atraso_padrao HOUR) = HOUR(NOW())
    //                AND colaborador_ponto.id_cidade = transportadoras_cidades.id_cidade
    //            GROUP BY colaboradores.id, colaborador_ponto.id) pontos_disponiveis
    //            WHERE calcula_valor_min_permite_emitir_faturamento_ponto(pontos_disponiveis.id_colaborador_ponto) - calcula_valor_atual_permite_emitir_faturamento_ponto(pontos_disponiveis.id_colaborador_ponto) <= 0
    //            GROUP BY pontos_disponiveis.id_colaborador_transportadora"
    //        )->fetchAll(PDO::FETCH_ASSOC);
    //
    //        $msgService = new MessageService();
    //        $consulta = array_map(function($item) use ($msgService, $atraso_padrao) {
    //            $item['pontos_elegiveis'] = json_decode($item['pontos_elegiveis'], true);
    //            for ($i = 0; $i < count($item['pontos_elegiveis']); $i++) {
    //                $nomeTransportadora = trim($item['nome_transportadora']);
    //                $nomePonto = trim($item['pontos_elegiveis'][$i]['nome']);
    //                $msgService->sendMessageWhatsApp($item['pontos_elegiveis'][$i]['telefone'], "Atenção, {$nomePonto}❗⚠️⚠️⚠️\n\nLibere os pedidos meulook para expedição, a transportadora {$nomeTransportadora} passará em alguns instantes para coletar.");
    //            }
    //            return $item;
    //        }, $consulta);
    //        return true;
    //    }

    public static function buscaColaboradoresFraudulentos(
        int $pagina,
        int $itensPorPagina,
        string $pesquisa,
        bool $orderDesc
    ): array {
        $offset = ($pagina - 1) * $itensPorPagina;
        if ($itensPorPagina > PHP_INT_MAX) {
            $offset = 0;
        }
        $bind = [];

        $ordenamento = '';
        $condicao = '';

        if ($pesquisa) {
            $condicao = " AND LOWER(CONCAT_WS(
                ' ',
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores.id,
                colaboradores_enderecos.uf,
                colaboradores_enderecos.cidade
            )) REGEXP LOWER(:pesquisa) ";
            $bind['pesquisa'] = $pesquisa;
        }

        if ($orderDesc) {
            $ordenamento = 'DESC';
        }

        $colaboradores = DB::select(
            "SELECT
                        JSON_OBJECT(
                           'nome', colaboradores.razao_social,
                           'telefone', colaboradores.telefone,
                           'id', colaboradores.id,
                           'cidade', CONCAT(colaboradores_enderecos.uf , ' - ', colaboradores_enderecos.cidade),
                           'id_cidade', municipios.id
                       ) json_colaborador,
                        (SELECT JSON_OBJECT(
                            'data_primeira_compra_para_order_by', MIN(transacao_financeiras.data_atualizacao),
                            'data_primeira_compra', DATE_FORMAT(MIN(transacao_financeiras.data_atualizacao), '%d/%m/%Y %H:%i:%s'),
                            'valor_liquido', SUM(transacao_financeiras.valor_liquido)
                        )
                            FROM transacao_financeiras
                            WHERE transacao_financeiras.pagador = colaboradores_suspeita_fraude.id_colaborador
                                AND transacao_financeiras.status = 'PA'
                                AND transacao_financeiras.metodo_pagamento = 'CA'
                        ) json_transacoes,
                        (
                            SELECT CONCAT('(', tipo_frete.id_colaborador, ') ', tipo_frete.nome )
                            FROM tipo_frete
                            WHERE tipo_frete.id = colaboradores.id_tipo_entrega_padrao
                            LIMIT 1
                        ) ponto_ultima_compra,
                       colaboradores_suspeita_fraude.situacao situacao_fraude,
                       colaboradores_suspeita_fraude.origem_transacao,
                       transacao_financeiras_logs_criacao.user_agent user_agent,
                       transacao_financeiras_logs_criacao.ip ip,
                        COUNT(DISTINCT(transacao_financeiras_tentativas_pagamento.id)) qtd_tentativas_cartao
                    FROM colaboradores_suspeita_fraude
                    INNER JOIN colaboradores ON colaboradores.id = colaboradores_suspeita_fraude.id_colaborador
                    INNER JOIN colaboradores_enderecos ON
                        colaboradores_enderecos.id_colaborador = colaboradores.id AND
                        colaboradores_enderecos.eh_endereco_padrao = 1
                    INNER JOIN transacao_financeiras_logs_criacao ON transacao_financeiras_logs_criacao.id_colaborador = colaboradores_suspeita_fraude.id_colaborador
                    INNER JOIN transacao_financeiras_tentativas_pagamento ON JSON_EXTRACT(transacao_financeiras_tentativas_pagamento.transacao_json, '$.pagador') = colaboradores_suspeita_fraude.id_colaborador
                        AND JSON_EXTRACT(transacao_financeiras_tentativas_pagamento.transacao_json, '$.metodo_pagamento') = 'CA'
                    LEFT JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
                    WHERE 1=1
                    $condicao
                    AND colaboradores_suspeita_fraude.origem = 'CARTAO'
                    GROUP BY json_colaborador
                    ORDER BY colaboradores_suspeita_fraude.situacao = 'PE' DESC, JSON_EXTRACT(json_transacoes, '$.data_primeira_compra_para_order_by') $ordenamento
                    LIMIT $itensPorPagina OFFSET $offset;",
            $bind
        );

        if ($pesquisa) {
            $condicao = " AND LOWER(CONCAT_WS(
                ' - ',
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores.id
            )) REGEXP LOWER(:pesquisa)";
            $bind['pesquisa'] = $pesquisa;
        }
        $total = DB::selectOneColumn(
            "SELECT COUNT(colaboradores.id)
                   FROM colaboradores_suspeita_fraude
                   INNER JOIN colaboradores ON colaboradores.id = colaboradores_suspeita_fraude.id_colaborador
                   WHERE 1=1
                    AND colaboradores_suspeita_fraude.origem = 'CARTAO'
                    $condicao",
            $bind
        );

        $map = array_map(function (array $item) {
            $item['user_agent'] = strtok(mb_substr($item['user_agent'], 13), ')');
            $item['origem_transacao'] = self::converteSiglaOrigemTransacaoFraude($item['origem_transacao']);

            return $item;
        }, $colaboradores);

        $mapCaracteristicasEmComum = array_map(function (array $item): array {
            if ($item['situacao_fraude'] === 'PE') {
                $colaboradoresComCaracteristicasEmComum = DB::select(
                    "SELECT
                    :id_colaborador suspeito
                FROM colaboradores_suspeita_fraude
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores_suspeita_fraude.id AND
                    colaboradores_enderecos.eh_endereco_padrao = 1
                INNER JOIN transacao_financeiras_logs_criacao ON
                    transacao_financeiras_logs_criacao.id_colaborador = colaboradores_enderecos.id_colaborador
                WHERE colaboradores_suspeita_fraude.situacao NOT IN ('LG', 'LT')
                AND colaboradores_enderecos.id_cidade = :id_cidade
                AND transacao_financeiras_logs_criacao.user_agent LIKE :user_agent
                AND colaboradores_enderecos.id_colaborador <> :id_colaborador
                GROUP BY transacao_financeiras_logs_criacao.id_colaborador;",
                    [
                        'id_colaborador' => $item['colaborador']['id'],
                        'id_cidade' => $item['colaborador']['id_cidade'],
                        'user_agent' => "%{$item['user_agent']}%",
                    ]
                );

                return $colaboradoresComCaracteristicasEmComum;
            } else {
                return [];
            }
        }, $map);

        return [
            'qtd_itens' => (int) $total,
            'colaboradores' => $map,
            'itens_por_pagina' => $itensPorPagina,
            'colaboradores_com_caracteristicas_em_comum' => $mapCaracteristicasEmComum,
        ];
    }

    public static function buscaColaboradoresSuspeitos(PDO $conexao)
    {
        $query = "SELECT
                        *
                    FROM (
                        SELECT
                            colaboradores.razao_social,
                            GROUP_CONCAT(transacao_financeiras_logs_criacao.id_transacao) transacoes,
                            transacao_financeiras_logs_criacao.id_colaborador,
                            transacao_financeiras_logs_criacao.ip,
                            transacao_financeiras_logs_criacao.user_agent,
                            transacao_financeiras_logs_criacao.latitude,
                            transacao_financeiras_logs_criacao.longitude,
                            DATE_FORMAT(transacao_financeiras_logs_criacao.data_criacao, '%d/%m/%Y %H:%i') data_criacao,
                            (SELECT COUNT(logs_transacao.id_transacao) FROM transacao_financeiras_logs_criacao AS logs_transacao WHERE logs_transacao.user_agent = transacao_financeiras_logs_criacao.user_agent) suspeitas
                        FROM transacao_financeiras
                        JOIN transacao_financeiras_logs_criacao ON transacao_financeiras_logs_criacao.id_transacao = transacao_financeiras.id
                        JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
                        WHERE transacao_financeiras.`status` = 'PA'
                        AND transacao_financeiras.metodo_pagamento = 'CA'
                        AND transacao_financeiras.origem_transacao = 'ML'
                        AND transacao_financeiras.data_atualizacao > NOW() - INTERVAL 30 DAY
                        AND NOT EXISTS(
                                    SELECT 1
                                    FROM entregas_faturamento_item
                                    WHERE entregas_faturamento_item.id_cliente = transacao_financeiras.pagador AND entregas_faturamento_item.situacao = 'EN')
                        GROUP BY transacao_financeiras_logs_criacao.id_colaborador
                        ORDER BY transacao_financeiras_logs_criacao.data_criacao DESC
                    ) resultados
                    WHERE resultados.suspeitas > 1";

        $consulta = $conexao->query($query)->fetchAll(PDO::FETCH_ASSOC);
        return $consulta;
    }

    public static function buscaSellerExterno(int $pagina): array
    {
        $itensPorPag = 50;
        $offset = $pagina * $itensPorPag;

        $diasAtrasoParaSeparacao = ConfiguracaoService::buscaDiasAtrasoParaSeparacao();
        $diasAtrasoParaConferencia = ConfiguracaoService::buscaDiasAtrasoParaConferencia() + $diasAtrasoParaSeparacao;
        $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;
        $sellers = DB::select(
            "SELECT
            colaboradores.id,
            colaboradores.razao_social,
            colaboradores.telefone,
            (
                SELECT municipios.nome
                FROM municipios
                WHERE colaboradores_enderecos.id_cidade = municipios.id
            ) cidade,
            (
                SELECT estados.nome
                FROM estados
                WHERE estados.uf = colaboradores_enderecos.uf
            ) estado,
            entregas_faturamento_item.id IS NULL eh_novato,
            DATEDIFF(CURDATE(), logistica_item.data_criacao) dias_atrasados,
            SUM(DISTINCT
                (
                    (
                        DATE(logistica_item.data_criacao) <= DATE_SUB(CURDATE(), INTERVAL :dias_atraso_para_separacao DAY)
                        AND logistica_item.situacao = 'PE'
                    )
                    OR (
                        DATE(logistica_item.data_criacao) <= DATE_SUB(CURDATE(), INTERVAL :dias_atraso_para_conferencia DAY)
                        AND logistica_item.situacao = 'SE'
                    )
                )
            ) esta_atrasado
            FROM colaboradores
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                AND usuarios.permissao REGEXP :permissao_fornecedor
            INNER JOIN logistica_item ON logistica_item.id_responsavel_estoque = colaboradores.id
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.situacao = 'EN'
                AND entregas_faturamento_item.id_responsavel_estoque = logistica_item.id_responsavel_estoque
            WHERE logistica_item.id_responsavel_estoque > 1
                AND logistica_item.situacao < :situacao
            GROUP BY colaboradores.id
            ORDER BY esta_atrasado DESC, dias_atrasados DESC
            LIMIT :itens_por_pag OFFSET :offset;",
            [
                'dias_atraso_para_separacao' => $diasAtrasoParaSeparacao,
                'dias_atraso_para_conferencia' => $diasAtrasoParaConferencia,
                'situacao' => $situacaoFinalProcesso,
                'permissao_fornecedor' => $permissaoFornecedor,
                'itens_por_pag' => $itensPorPag,
                'offset' => $offset,
            ]
        );
        $sellers = array_map(function (array $seller): array {
            $seller['telefone'] = Str::formatarTelefone($seller['telefone']);

            return $seller;
        }, $sellers);

        $totalPags = DB::selectOneColumn(
            "SELECT CEIL(COUNT(DISTINCT logistica_item.id_responsavel_estoque)/:itens_por_pag) qtd_fornecedores
            FROM logistica_item
            WHERE logistica_item.id_responsavel_estoque > 1
                AND logistica_item.situacao < :situacao;",
            [
                'itens_por_pag' => $itensPorPag,
                'situacao' => $situacaoFinalProcesso,
            ]
        );

        $resultado = [
            'sellers' => $sellers,
            'mais_pags' => $totalPags - $pagina - 1 > 0,
        ];

        return $resultado;
    }

    public static function buscaPedidosSeller(PDO $conexao, int $idResponsavelEstoque): array
    {
        $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $sql = $conexao->prepare(
            "SELECT
                GROUP_CONCAT(DISTINCT logistica_item.id_transacao) transacoes,
                COUNT(logistica_item.uuid_produto) qtd_pares,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = logistica_item.id_colaborador_tipo_frete
                ) nome_ponto,
                SUM(logistica_item.preco) valor_total
            FROM logistica_item
            WHERE logistica_item.id_responsavel_estoque = :id_fornecedor
                AND logistica_item.situacao < :situacao
            GROUP BY logistica_item.id_colaborador_tipo_frete;"
        );
        $sql->bindValue(':id_fornecedor', $idResponsavelEstoque, PDO::PARAM_INT);
        $sql->bindValue(':situacao', $situacaoFinalProcesso, PDO::PARAM_INT);
        $sql->execute();
        $resultado['pedidos'] = $sql->fetchAll(PDO::FETCH_ASSOC);
        $resultado['pedidos'] = array_map(function (array $ponto): array {
            $ponto['transacoes'] = array_map('intval', explode(',', $ponto['transacoes']));
            $ponto['qtd_pares'] = (int) $ponto['qtd_pares'];
            $ponto['valor_total'] = (float) $ponto['valor_total'];
            $ponto['nome_ponto'] = trim($ponto['nome_ponto']);

            return $ponto;
        }, $resultado['pedidos']);

        $query = $conexao->prepare(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.telefone,
                COALESCE(reputacao_fornecedores.reputacao, 'NOVATO') situacao,
                COALESCE(reputacao_fornecedores.vendas_canceladas_recentes, 0) vendas_canceladas_recentes
            FROM colaboradores
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :id_fornecedor;"
        );
        $query->bindValue(':id_fornecedor', $idResponsavelEstoque, PDO::PARAM_INT);
        $query->execute();
        $resultado['seller'] = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($resultado['seller'])) {
            throw new Exception("Seller's não encontrados");
        }

        $resultado['seller']['id'] = (int) $resultado['seller']['id'];
        $resultado['seller']['vendas_canceladas_recentes'] = (int) $resultado['seller']['vendas_canceladas_recentes'];
        $resultado['seller']['qrCodeTelefone'] = Globals::geraQRCODE(
            'https://api.whatsapp.com/send/?phone=55' . $resultado['seller']['telefone']
        );
        unset($resultado['seller']['telefone']);

        return $resultado;
    }

    /**
     * true = bloquear reposição do seller;
     * false = debloquear reposição do seller;
     * @author SavioSReis
     */
    public static function bloquearReposicaoSeller(PDO $conexao, int $idFornecedor, bool $bloquear): void
    {
        $bloqueado = (string) $bloquear ? 'T' : 'F';
        $sql = $conexao->prepare(
            "UPDATE colaboradores
            SET colaboradores.bloqueado_repor_estoque = :bloqueado
            WHERE colaboradores.id = :id_fornecedor
                AND colaboradores.bloqueado_repor_estoque <> :bloqueado;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':bloqueado', $bloqueado, PDO::PARAM_STR);
        $sql->execute();

        if ($sql->rowCount() <= 0) {
            throw new DomainException('Não foi possivel atualizar a situação do fornecedor.');
        }
    }

    public static function verificaSellerBloqueado(PDO $conexao, int $idFornecedor): bool
    {
        $sql = $conexao->prepare(
            "SELECT colaboradores.bloqueado_repor_estoque = 'T' bloqueado_repor_estoque
            FROM colaboradores
            WHERE colaboradores.id = :id_fornecedor;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->execute();
        $bloqueado = (bool) $sql->fetch(PDO::FETCH_ASSOC)['bloqueado_repor_estoque'];

        return $bloqueado;
    }

    public static function buscaReputacao(int $idColaborador): ?array
    {
        $resultado = DB::selectOne(
            "SELECT reputacao_fornecedores.id_colaborador,
                reputacao_fornecedores.vendas_entregues,
                reputacao_fornecedores.taxa_cancelamento `taxa_cancelamento_int`,
                reputacao_fornecedores.media_envio,
                reputacao_fornecedores.reputacao
            FROM reputacao_fornecedores
            WHERE reputacao_fornecedores.id_colaborador = :id_colaborador",
            ['id_colaborador' => $idColaborador]
        );
        return $resultado;
    }

    public static function buscaMediaCancelamentos(PDO $conexao, int $idFornecedor): float
    {
        $sql = $conexao->prepare(
            "SELECT reputacao_fornecedores.taxa_cancelamento
            FROM reputacao_fornecedores
            WHERE reputacao_fornecedores.id_colaborador = :id_fornecedor;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->execute();
        $mediaCancelamentos = (float) $sql->fetchColumn();

        return $mediaCancelamentos;
    }

    public static function buscaClientesNovos(PDO $conexao)
    {
        $consulta = $conexao
            ->query(
                "SELECT colaboradores.id,
            colaboradores.razao_social,
            GROUP_CONCAT(transacao_financeiras.data_atualizacao ORDER BY transacao_financeiras.id ASC LIMIT 1) data_primeira_compra,
            GROUP_CONCAT(transacao_financeiras.id ORDER BY transacao_financeiras.id ASC LIMIT 1) id_transacao_financeiras,
            GROUP_CONCAT(transacao_financeiras.valor_liquido ORDER BY transacao_financeiras.id ASC LIMIT 1) valor_liquido
            FROM transacao_financeiras
            INNER JOIN colaboradores ON transacao_financeiras.pagador = colaboradores.id
            WHERE transacao_financeiras.`status` = 'PA'
            GROUP BY transacao_financeiras.pagador
            HAVING data_primeira_compra > NOW() - INTERVAL 7 DAY
            ORDER BY transacao_financeiras.id DESC"
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        $map = array_map(function (array $item) {
            $objetoDateTime = new DateTime($item['data_primeira_compra']);
            $item['data_primeira_compra'] = $objetoDateTime->format('d/m/Y H:i:s');

            return $item;
        }, $consulta);

        return $map;
    }

    public function alteraSituacaoFraude(PDO $conexao, string $origem): void
    {
        $stmt = $conexao->prepare(
            "UPDATE colaboradores_suspeita_fraude
                SET colaboradores_suspeita_fraude.situacao = :situacao_fraude
            WHERE colaboradores_suspeita_fraude.id_colaborador = :id
            AND colaboradores_suspeita_fraude.origem = :origem"
        );
        $stmt->bindValue(':situacao_fraude', $this->situacao_fraude, PDO::PARAM_STR);
        $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        $linhas = $stmt->rowCount();

        if (!$linhas) {
            throw new DomainException('Não conseguimos atualizar a situacao da fraude do colaborador');
        }
    }

    public function buscaSituacaoFraude(PDO $conexao, array $origem): void
    {
        [$bindKeys, $bind] = ConversorArray::criaBindValues($origem);
        $stmt = $conexao->prepare(
            "SELECT
                colaboradores_suspeita_fraude.situacao,
                colaboradores_suspeita_fraude.origem
            FROM colaboradores_suspeita_fraude
            WHERE colaboradores_suspeita_fraude.id_colaborador = :id
            AND colaboradores_suspeita_fraude.origem IN ($bindKeys)"
        );
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        foreach ($bind as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        if (count($origem) === 1) {
            $this->situacao_fraude = $stmt->fetchColumn() ?: null;
        } else {
            $this->situacao_fraude = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
        }
    }

    public static function consultaDadosPagamento(PDO $conexao, int $pagador): array
    {
        $consulta = DB::selectOne(
            "SELECT
                    IF((LENGTH(COALESCE(colaboradores.email, '')) = 0), CONCAT(colaboradores.usuario_meulook, '@gmail.com.br'), colaboradores.email) email,
                    colaboradores.razao_social nome,
                    COALESCE(SUBSTRING(REGEXP_REPLACE(REPLACE(colaboradores.telefone,'+55',''),'[()+-]',''),3),'') phone,
                    COALESCE(CONCAT('0',SUBSTRING(REGEXP_REPLACE(REPLACE(colaboradores.telefone,'+55',''),'[()+-\]',''),1,2)),'') phone_prefix,
                    IF(colaboradores.regime = 1, colaboradores.cnpj, colaboradores.cpf) cpf_cnpj,
                    COALESCE(colaboradores_enderecos.cep,'') zip_code,
                    COALESCE(colaboradores_enderecos.numero,'') number,
                    COALESCE(colaboradores_enderecos.logradouro,'') street,
                    COALESCE(colaboradores_enderecos.cidade,'') city,
                    COALESCE(colaboradores_enderecos.uf,'') state,
                    COALESCE(colaboradores_enderecos.bairro,'') district
                  FROM colaboradores
                  LEFT JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                  WHERE colaboradores.id = ?
                    AND colaboradores.razao_social IS NOT NULL",
            [$pagador]
        );

        return $consulta;
    }

    public function insereFraude(PDO $conexao, string $origem, int $valorMinimo = null): void
    {
        $stmt = $conexao->prepare(
            "INSERT INTO colaboradores_suspeita_fraude
                (
                    colaboradores_suspeita_fraude.id_colaborador,
                    colaboradores_suspeita_fraude.origem,
                    colaboradores_suspeita_fraude.origem_transacao,
                    colaboradores_suspeita_fraude.valor_minimo_fraude
                )
            VALUES
                (:id, :origem, :origem_transacao, :valor_minimo_fraude)"
        );
        $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
        $stmt->bindValue(':origem_transacao', $this->origem_transacao, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':valor_minimo_fraude', $valorMinimo, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException('Erro ao inserir na fraude.');
        }
    }

    public static function buscaFornecedores(?string $pesquisa): array
    {
        $where = '';
        $binds['permissao'] = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;
        if (!empty($pesquisa)) {
            $where = " AND LOWER(CONCAT_WS(
                ' - ',
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.telefone
            )) REGEXP LOWER(:pesquisa) ";
            $binds['pesquisa'] = $pesquisa;
        }

        $consulta = DB::select(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social nome
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            WHERE usuarios.permissao REGEXP :permissao
                $where
            ORDER BY colaboradores.razao_social ASC",
            $binds
        );
        return $consulta;
    }

    public static function consultaUrlWebhook(int $idColaborador): string
    {
        $urlWebhook = DB::selectOneColumn(
            "SELECT colaboradores.url_webhook
            FROM colaboradores
            WHERE colaboradores.id = :id_colaborador",
            ['id_colaborador' => $idColaborador]
        );

        if (empty($urlWebhook)) {
            throw new DomainException('Não foi possível encontrar a url do webhook do colaborador');
        }

        return $urlWebhook;
    }

    public static function buscaColaboradoresComFiltros(string $pesquisa, ?int $nivelAcesso = null): array
    {
        $where = '';
        $bind['pesquisa'] = $pesquisa;
        if (!empty($nivelAcesso)) {
            $bind['nivel_acesso'] = $nivelAcesso;
            $where = ' AND usuarios.permissao REGEXP :nivel_acesso ';
        }

        $colaboradores = DB::select(
            "SELECT
                colaboradores.id id_colaborador,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores.cpf,
                CONCAT(colaboradores_enderecos.cidade, ' (', colaboradores_enderecos.uf, ')') cidade,
                DATE_FORMAT(colaboradores.data_criacao, '%d/%m/%Y %H:%i:%s') data_cadastro,
                (
                    SELECT GROUP_CONCAT(nivel_permissao.nome)
                    FROM nivel_permissao
                    WHERE usuarios.permissao REGEXP nivel_permissao.nivel_value
                ) permissoes,
                usuarios.nome,
                usuarios.id id_usuario
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            LEFT JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE (
                LOWER(CONCAT_WS(
                    ' ',
                    colaboradores.razao_social,
                    colaboradores.telefone,
                    colaboradores.cpf,
                    usuarios.nome
                )) REGEXP LOWER(:pesquisa)
                OR colaboradores.id = :pesquisa
            ) $where
            GROUP BY colaboradores.id
            ORDER BY colaboradores.id DESC;",
            $bind
        );

        return $colaboradores;
    }

    public static function buscaDesempenhoSellers(PDO $conexao, ?int $idCliente): array
    {
        $where = '';
        $order = ", reputacao_fornecedores.reputacao = 'MELHOR_FABRICANTE' DESC,
            reputacao_fornecedores.reputacao = 'EXCELENTE' DESC,
            reputacao_fornecedores.reputacao = 'REGULAR' DESC,
            colaboradores.razao_social";

        if ($idCliente) {
            $where .= ' AND reputacao_fornecedores.id_colaborador = :idCliente';
            $order = '';
        }

        $stmt = $conexao->prepare(
            "SELECT reputacao_fornecedores.id_colaborador,
                TRIM(colaboradores.razao_social) nome,
                colaboradores.usuario_meulook,
                reputacao_fornecedores.vendas_totais,
                reputacao_fornecedores.vendas_entregues,
                reputacao_fornecedores.vendas_canceladas_totais,
                reputacao_fornecedores.taxa_cancelamento,
                reputacao_fornecedores.media_envio,
                reputacao_fornecedores.vendas_canceladas_recentes,
                reputacao_fornecedores.valor_vendido,
                CASE reputacao_fornecedores.reputacao
                    WHEN 'MELHOR_FABRICANTE' THEN 'Melhores Fabricantes'
                    WHEN 'EXCELENTE' THEN 'Bom'
                    WHEN 'REGULAR' THEN 'Regular'
                    ELSE 'Ruim'
                END reputacao
            FROM reputacao_fornecedores
            INNER JOIN colaboradores ON colaboradores.id = reputacao_fornecedores.id_colaborador
            WHERE TRUE $where
            ORDER BY TRUE $order"
        );
        if ($idCliente) {
            $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        }
        $stmt->execute();

        if ($idCliente) {
            $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (sizeof($consulta) === 0) {
            return [];
        }

        $tratarDados = function ($item) {
            $item['id_colaborador'] = (int) $item['id_colaborador'];
            $item['media_envio'] = (int) $item['media_envio'];
            $item['taxa_cancelamento'] = (float) $item['taxa_cancelamento'];
            $item['valor_vendido'] = (float) $item['valor_vendido'];
            $item['vendas_canceladas_totais'] = (float) $item['vendas_canceladas_totais'];
            $item['vendas_canceladas_recentes'] = (int) $item['vendas_canceladas_recentes'];
            $item['vendas_entregues'] = (int) $item['vendas_entregues'];
            $item['vendas_totais'] = (int) $item['vendas_totais'];
            return $item;
        };

        if ($idCliente) {
            $consulta = $tratarDados($consulta);
        } else {
            $consulta = array_map($tratarDados, $consulta);
        }

        return $consulta;
    }

    public static function buscaUltimaMovimentacaoColaborador(PDO $conexao, int $idColaborador): string
    {
        $query = "SELECT GREATEST(
                            COALESCE(
                                (
                                    SELECT
                                        MAX(usuarios.data_atualizacao)
                                        FROM usuarios
                                        WHERE usuarios.id_colaborador = :idColaborador),
                                0),
                            COALESCE(
                                (
                                    SELECT
                                        MAX(lancamento_financeiro.data_emissao)
                                        FROM lancamento_financeiro
                                        WHERE lancamento_financeiro.id_colaborador = :idColaborador),
                                0)
                                )
                                AS ultima_movimentacao";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $consulta = $stmt->fetchColumn();
        return $consulta;
    }

    public static function buscaNovosSeller(PDO $conexao, int $pagina, string $area, array $visualizados): array
    {
        $join = '';
        $where = '';
        $situacao = '';
        $itensPorPag = 50;
        $offset = $itensPorPag * ($pagina - 1);

        if (!empty($visualizados)) {
            [$bindVisualizados, $visualizados] = ConversorArray::criaBindValues($visualizados, 'id_fornecedor');
            $where .= " AND colaboradores.id NOT IN ($bindVisualizados) ";
        }

        if ($area === 'ESTOQUE') {
            $select = "colaboradores.bloqueado_repor_estoque = 'T' AS bloqueado_repor,";
            $where .= ' AND DATE(produtos.data_cadastro) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) ';
        } else {
            $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;

            $select = ' COUNT(logistica_item.id) AS qtd_vendas, ';
            $join = "INNER JOIN logistica_item ON  logistica_item.id_produto = produtos.id
                AND logistica_item.id_responsavel_estoque = colaboradores.id
                AND logistica_item.situacao < :situacao
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.id_responsavel_estoque = colaboradores.id
                AND entregas_faturamento_item.situacao = 'EN' ";
            $where .= ' AND entregas_faturamento_item.id IS NULL ';
        }

        $sql = $conexao->prepare(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.telefone,
                $select
                COUNT(produtos.id) AS qtd_produtos
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                AND usuarios.permissao REGEXP 30
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            INNER JOIN produtos ON produtos.id_fornecedor = colaboradores.id
            $join
            WHERE reputacao_fornecedores.id IS NULL
                $where
            GROUP BY colaboradores.id
            ORDER BY colaboradores.id DESC
            LIMIT :itens_por_pag OFFSET :offset;"
        );
        $sql->bindValue(':itens_por_pag', $itensPorPag, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        if (!empty($situacao)) {
            $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        }
        if (!empty($visualizados)) {
            foreach ($visualizados as $index => $visualizado) {
                $sql->bindValue($index, $visualizado, PDO::PARAM_INT);
            }
        }
        $sql->execute();
        $fornecedores = $sql->fetchAll(PDO::FETCH_ASSOC);

        $fornecedores = array_map(function (array $fornecedor): array {
            $idColaborador = (int) $fornecedor['id'];
            $razaoSocial = trim($fornecedor['razao_social']);
            $fornecedor['razao_social'] = "($idColaborador) $razaoSocial";
            $fornecedor['id'] = $idColaborador;

            $fornecedor['telefone'] = (int) preg_replace('/[^0-9]+/i', '', $fornecedor['telefone']);
            $fornecedor['telefone'] = ConversorStrings::formataTelefone($fornecedor['telefone']);

            $fornecedor['qtd_produtos'] = (int) $fornecedor['qtd_produtos'];
            if (isset($fornecedor['bloqueado_repor'])) {
                $fornecedor['bloqueado_repor'] = (bool) $fornecedor['bloqueado_repor'];
            }

            return $fornecedor;
        }, $fornecedores);

        $sql = $conexao->prepare(
            "SELECT CEIL(COUNT(DISTINCT colaboradores.id)/:itens_por_pag) qtd_fornecedores
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                AND usuarios.permissao REGEXP 30
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            LEFT JOIN produtos ON produtos.id_fornecedor = colaboradores.id
            $join
            WHERE reputacao_fornecedores.id IS NULL
                $where;"
        );
        $sql->bindValue(':itens_por_pag', $itensPorPag, PDO::PARAM_INT);
        if (!empty($situacao)) {
            $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        }
        if (!empty($visualizados)) {
            foreach ($visualizados as $index => $visualizado) {
                $sql->bindValue($index, $visualizado, PDO::PARAM_INT);
            }
        }
        $sql->execute();
        $totalPags = (int) $sql->fetchColumn();

        $resultado = [
            'fornecedores' => $fornecedores,
            'mais_pags' => $totalPags - $pagina > 0,
        ];

        return $resultado ?: [];
    }

    public static function enviaAtalhoLogin(int $telefone, string $nome): void
    {
        $msgService = new MessageService();
        $mensagem = "Olá $nome, tudo bem?" . PHP_EOL;
        $mensagem .= 'Seja muito bem-vindo(a) ao Meulook!' . PHP_EOL;
        $mensagem .= 'Ficamos muito felizes por ter você como um novo cliente!' . PHP_EOL;
        $mensagem .= 'Aqui está um link para facilitar o seu acesso em nossa plataforma:' . PHP_EOL . PHP_EOL;
        $mensagem .= "{$_ENV['URL_MEULOOK']}entrar?telefone=$telefone";
        $msgService->sendMessageWhatsApp($telefone, $mensagem);
    }

    public static function verificaTelefoneErrado(PDO $conexao, int $idUsuario): bool
    {
        $sql = $conexao->prepare(
            "SELECT
                usuarios.telefone,
                colaboradores.telefone
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            WHERE usuarios.id = :id_usuario
            AND (
                colaboradores.telefone LIKE '0%' OR usuarios.telefone LIKE '0%'
                OR usuarios.telefone IS NULL OR colaboradores.telefone IS NULL
                OR CHAR_LENGTH(REGEXP_REPLACE(colaboradores.telefone, '[^0-9]', '')) <> 11
                OR CHAR_LENGTH(REGEXP_REPLACE(usuarios.telefone, '[^0-9]', '')) <> 11
                OR usuarios.telefone NOT REGEXP('^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$')
                OR colaboradores.telefone NOT REGEXP('^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$'));"
        );
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
        $data = !!$sql->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public static function alternaPermissaoClienteDeFazerAdiantamento(PDO $conexao, int $idColaborador): void
    {
        $sql = "UPDATE colaboradores
				SET colaboradores.adiantamento_bloqueado = NOT colaboradores.adiantamento_bloqueado
				WHERE colaboradores.id = :id_colaborador";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception(
                'Ocorreu um erro ao bloquear os adiantamentos do cliente. ' . $stmt->rowCount() . ' linhas atualizadas.'
            );
        }
    }

    public static function adiantamentoEstaBloqueado(PDO $conexao, int $idColaborador): bool
    {
        $sql =
            'SELECT colaboradores.adiantamento_bloqueado FROM colaboradores WHERE colaboradores.id = :id_colaborador';
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = (bool) $stmt->fetchColumn();

        return $resultado;
    }

    public static function converteSiglaOrigemTransacaoFraude(string $siglaOrigem): string
    {
        switch ($siglaOrigem) {
            case 'MS':
                return 'Mobile Stock';
                break;
            case 'ML':
                return 'MeuLook';
                break;
            case 'LP':
                return 'Look Pay';
                break;
            default:
                return 'Origem não catalogada';
                break;
        }
    }

    public static function buscaIdColaboradoresPorTelefone(PDO $conexao, string $telefone): array
    {
        $sql = $conexao->prepare(
            "SELECT
                usuarios.id_colaborador
            FROM usuarios
            WHERE usuarios.telefone REGEXP :telefone"
        );
        $sql->bindValue(':telefone', $telefone, PDO::PARAM_STR);
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_COLUMN);
        return $data;
    }

    /**
     * @param string $tipoEmbalagem CA|SA
     */
    public static function mudaTipoEmbalagem(PDO $conexao, int $idColaboradorDestinatario, string $tipoEmbalagem): void
    {
        $sql = $conexao->prepare("
            UPDATE colaboradores
            SET colaboradores.tipo_embalagem = :tipo_embalagem
            WHERE colaboradores.id = :id_colaborador_destinatario
        ");
        $sql->bindValue(':tipo_embalagem', $tipoEmbalagem, PDO::PARAM_STR);
        $sql->bindValue(':id_colaborador_destinatario', $idColaboradorDestinatario, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() === 0) {
            throw new Exception('Nenhum registro atualizado');
        }
    }

    public static function buscaFraudesPendentesDevolucoes(): array
    {
        $fraudatarios = DB::select(
            "SELECT
                colaboradores.id,
                DATE_FORMAT(colaboradores.data_criacao, '%d/%m/%Y %H:%i:%s') data_cadastro,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores_enderecos.cidade,
                saldo_cliente(colaboradores.id) saldo_cliente,
                (
                    SELECT saldo_cliente_bloqueado(colaboradores.id) + SUM(logistica_item.preco)
                    FROM logistica_item
                    INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = logistica_item.uuid_produto
                    WHERE logistica_item.id_cliente = colaboradores.id
                    AND entregas_devolucoes_item.situacao = 'PE'
                ) AS `valor_devolucoes_cliente`,
                (
                    SELECT SUM(logistica_item.preco)
                    FROM tipo_frete
                    INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
                    INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_devolucoes_item.uuid_produto
                    WHERE tipo_frete.id_colaborador = colaboradores.id
                    AND entregas_devolucoes_item.situacao = 'PE'
                ) valor_devolucoes_ponto,
                colaboradores_suspeita_fraude.valor_minimo_fraude as `limite`,
                colaboradores_suspeita_fraude.situacao
            FROM colaboradores
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN colaboradores_suspeita_fraude ON colaboradores_suspeita_fraude.id_colaborador = colaboradores.id
            WHERE colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'
            AND colaboradores_suspeita_fraude.situacao = 'PE'
            ORDER BY colaboradores_suspeita_fraude.id DESC"
        );

        return $fraudatarios;
    }

    public static function alteraValorMinimoPraEntrarNaFraude(PDO $conexao, int $idCliente, float $valorMinimo): void
    {
        $stmt = $conexao->prepare(
            "UPDATE colaboradores_suspeita_fraude
            SET colaboradores_suspeita_fraude.valor_minimo_fraude = :valor_minimo
            WHERE colaboradores_suspeita_fraude.id_colaborador = :id_cliente
            AND colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'"
        );
        $stmt->bindValue(':valor_minimo', $valorMinimo, PDO::PARAM_STR);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception('Ocorreu um erro ao alterar o valor mínimo para entrar na fraude.');
        }
    }

    public function buscaPossiveisFraudulentos(float $valorMinimo): array
    {
        $clientes = DB::select(
            "SELECT
                colaboradores.id id_colaborador,
                colaboradores_suspeita_fraude.valor_minimo_fraude,
                colaboradores_suspeita_fraude.id AS `esta_na_fraude`
            FROM colaboradores
            LEFT JOIN colaboradores_suspeita_fraude ON colaboradores_suspeita_fraude.id_colaborador = colaboradores.id
                AND colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'
            INNER JOIN logistica_item ON logistica_item.id_cliente = colaboradores.id
            INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = logistica_item.uuid_produto
            WHERE entregas_devolucoes_item.situacao = 'PE'
                AND (colaboradores_suspeita_fraude.situacao NOT IN ('FR', 'PE') OR colaboradores_suspeita_fraude.id IS NULL)
            GROUP BY colaboradores.id
            HAVING SUM(logistica_item.preco) + saldo_cliente_bloqueado(colaboradores.id) > IF(
                colaboradores_suspeita_fraude.valor_minimo_fraude IS NOT NULL,
                colaboradores_suspeita_fraude.valor_minimo_fraude,
                $valorMinimo
            );"
        );

        return $clientes;
    }

    public function buscaPossiveisPontosFraudulentos(float $valorMinimo): array
    {
        $pontos = DB::select(
            "SELECT
                tipo_frete.id_colaborador,
                colaboradores_suspeita_fraude.valor_minimo_fraude,
                colaboradores_suspeita_fraude.id AS `esta_na_fraude`
            FROM tipo_frete
            LEFT JOIN colaboradores_suspeita_fraude ON colaboradores_suspeita_fraude.id_colaborador = tipo_frete.id_colaborador
                AND colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'
            INNER JOIN entregas_devolucoes_item ON entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
            INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_devolucoes_item.uuid_produto
            WHERE entregas_devolucoes_item.situacao = 'PE'
                AND (colaboradores_suspeita_fraude.situacao NOT IN ('FR', 'PE') OR colaboradores_suspeita_fraude.id IS NULL)
            GROUP BY entregas_devolucoes_item.id_ponto_responsavel
            HAVING SUM(logistica_item.preco) > IF (
                colaboradores_suspeita_fraude.valor_minimo_fraude IS NOT NULL,
                colaboradores_suspeita_fraude.valor_minimo_fraude,
                $valorMinimo
            );"
        );

        return $pontos;
    }

    public function buscaPossiveisFraudulentosSaldoNegativo(PDO $conexao, float $valorMinimo): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                colaboradores.id id_colaborador,
                colaboradores_suspeita_fraude.valor_minimo_fraude,
                colaboradores_suspeita_fraude.id AS `esta_na_fraude`
            FROM colaboradores
            LEFT JOIN colaboradores_suspeita_fraude ON colaboradores_suspeita_fraude.id_colaborador = colaboradores.id
                AND NOT colaboradores_suspeita_fraude.origem = 'CARTAO'
            WHERE (colaboradores_suspeita_fraude.situacao NOT IN ('FR', 'PE') OR colaboradores_suspeita_fraude.id IS NULL)
                AND saldo_cliente(colaboradores.id) < IF (
                    colaboradores_suspeita_fraude.valor_minimo_fraude IS NOT NULL,
                    colaboradores_suspeita_fraude.valor_minimo_fraude,
                    $valorMinimo
                ) * -1
            GROUP BY colaboradores.id;"
        );
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(function (array $item): array {
            $item['esta_na_fraude'] = (bool) $item['esta_na_fraude'];

            return $item;
        }, $data);
        return $data;
    }

    public static function buscaDadosClienteWhatsAppSuporte(string $telefone): array
    {
        $colaboradores = DB::select(
            "SELECT
                colaboradores.razao_social,
                colaboradores_enderecos.cidade,
                colaboradores.id
            FROM colaboradores
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE colaboradores.telefone = :telefone
                AND colaboradores.conta_principal;",
            ['telefone' => $telefone]
        );

        return $colaboradores;
    }

    public static function salvarObservacaoColaborador(PDO $conexao, int $idColaborador, string $observacao): void
    {
        $query = "UPDATE colaboradores
                    SET colaboradores.observacoes = :observacao
                    WHERE colaboradores.id = :id_colaborador";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':observacao', $observacao, PDO::PARAM_STR);
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function buscaInformacoesFornecedor(PDO $conexao, int $idColaborador): array
    {
        $sql = $conexao->prepare(
            "SELECT
                colaboradores.id AS `id_fornecedor`,
                colaboradores.razao_social,
                colaboradores.telefone,
                COALESCE(
                    colaboradores.foto_perfil,
                    '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'
                ) AS `foto`,
                COALESCE(reputacao_fornecedores.reputacao, 'NOVATO') AS `reputacao`
            FROM colaboradores
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :id_fornecedor;"
        );
        $sql->bindValue(':id_fornecedor', $idColaborador, PDO::PARAM_INT);
        $sql->execute();
        $fornecedor = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($fornecedor)) {
            throw new NotFoundHttpException('Fornecedor não encontrado');
        }
        $fornecedor['id_fornecedor'] = (int) $fornecedor['id_fornecedor'];

        return $fornecedor;
    }

    public static function buscaColaboradoresParaBloquear(PDO $conexao): array
    {
        $taxaBloqueioFornecedor = ConfiguracaoService::buscaTaxaBloqueioFornecedor($conexao);

        $stmt = $conexao->prepare(
            "SELECT reputacao_fornecedores.id_colaborador
            FROM reputacao_fornecedores
            INNER JOIN colaboradores ON colaboradores.id = reputacao_fornecedores.id_colaborador
            WHERE reputacao_fornecedores.taxa_cancelamento > :taxa_bloqueio_fornecedor
                AND colaboradores.bloqueado_repor_estoque = 'F';"
        );
        $stmt->bindValue(':taxa_bloqueio_fornecedor', $taxaBloqueioFornecedor, PDO::PARAM_INT);
        $stmt->execute();
        $retorno = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $retorno;
    }

    public static function buscarDadosEndereco(): array
    {
        $resultado = DB::selectOne(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.telefone,
                (
                    SELECT
                        tipo_frete.categoria
                    FROM tipo_frete
                    WHERE tipo_frete.id = colaboradores.id_tipo_entrega_padrao
                ) AS tipo_entrega_padrao,
                colaboradores.id_tipo_entrega_padrao,
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.complemento,
                colaboradores_enderecos.ponto_de_referencia,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf,
                colaboradores_enderecos.cep,
                colaboradores_enderecos.esta_verificado
            FROM
                colaboradores
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = :id_cliente
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE colaboradores.id = :id_cliente",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        return $resultado;
    }

    public static function buscaCadastroColaborador(int $idColaborador): array
    {
        $colaborador = DB::selectOne(
            "SELECT
                colaboradores.cpf,
                colaboradores.cnpj,
                colaboradores.regime,
                colaboradores.telefone,
                colaboradores.foto_perfil,
                colaboradores.razao_social,
                colaboradores_enderecos.uf,
                colaboradores_enderecos.cep,
                colaboradores.usuario_meulook,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.complemento,
                colaboradores_enderecos.ponto_de_referencia,
                colaboradores_enderecos.logradouro AS `endereco`,
                usuarios.id AS `id_usuario`,
                usuarios.nome,
                usuarios.senha,
                usuarios.email,
                CONCAT('[', usuarios.permissao, ']') AS `json_permissao`,
                usuarios.nivel_acesso,
                usuarios.id_colaborador,
                usuarios.permissao REGEXP :permissao_fornecedor AS `eh_perfil_de_seller`
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            LEFT JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao
            WHERE colaboradores.id = :id_colaborador;",
            ['id_colaborador' => $idColaborador, 'permissao_fornecedor' => Usuario::VERIFICA_PERMISSAO_FORNECEDOR]
        );
        if (empty($colaborador)) {
            throw new NotFoundHttpException('Colaborador não encontrado');
        }

        return $colaborador;
    }
    public static function filtraColaboradoresDadosSimples(string $pesquisa): array
    {
        $colaboradores = DB::select(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores.cpf,
                EXISTS(
                    SELECT 1
                    FROM logistica_item
                    WHERE logistica_item.situacao = 'PE'
                        AND logistica_item.id_responsavel_estoque = colaboradores.id
                ) AS `existe_produto_separar`,
                EXISTS(
                    SELECT 1
                    FROM logistica_item
                    WHERE logistica_item.situacao = 'PE'
                        AND logistica_item.id_produto = :id_produto_frete
                        AND logistica_item.id_cliente = colaboradores.id
                ) AS `existe_frete_pendente`
            FROM colaboradores
            WHERE CONCAT_WS(
                ' ',
                colaboradores.razao_social,
                colaboradores.cpf,
                colaboradores.telefone
            ) LIKE :pesquisa
            GROUP BY colaboradores.id
            ORDER BY colaboradores.id DESC;",
            ['pesquisa' => "%$pesquisa%", 'id_produto_frete' => ProdutoModel::ID_PRODUTO_FRETE]
        );

        return $colaboradores;
    }
    public static function buscaPerfilMeuLook(string $nomeUsuario): array
    {
        $campos = '';
        $meuPerfil = ColaboradorModel::verificaEhProprioPerfil($nomeUsuario);

        if ($meuPerfil) {
            # Meus dados privados do perfil
            $filtroPeriodo = RankingService::montaFiltroPeriodo(
                DB::getPdo(),
                ['logistica_item.data_criacao'],
                'mes-atual'
            );
            $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
            $campos .= ",
                COALESCE((SELECT LENGTH(usuarios.senha) > 0 FROM usuarios WHERE usuarios.id_colaborador = colaboradores.id LIMIT 1), 0) tem_senha,
                IF(
                    colaboradores.email <> (
                        SELECT configuracoes.email_padrao_colaboradores
                        FROM configuracoes
                        LIMIT 1
                    ),
                    colaboradores.email,
                    ''
                ) email,
                JSON_OBJECT(
                    'endereco', colaboradores_enderecos.logradouro,
                    'bairro', colaboradores_enderecos.bairro,
                    'numero', colaboradores_enderecos.numero,
                    'cep', colaboradores_enderecos.cep,
                    'complemento', colaboradores_enderecos.complemento
                ) json_endereco_usuario,
                colaboradores.telefone,
                (
                    SELECT COUNT(tipo_frete.id) > 0
                    FROM tipo_frete
                    LEFT JOIN colaboradores_enderecos AS tipo_frete_colaboradores_enderecos ON
                        tipo_frete_colaboradores_enderecos.id = tipo_frete.id_colaborador
                    WHERE
                        tipo_frete.categoria = 'ML' AND
                        tipo_frete_colaboradores_enderecos.id_cidade = colaboradores_enderecos.id_cidade
                ) esta_disponivel_na_cidade,
                (
                    SELECT JSON_OBJECT(
                        'ponto_percentual_atual_barrinha', ROUND(COALESCE((SELECT
                            SUM(IF(
                                logistica_item.situacao <= $situacaoFinalProcesso,
                                pedido_item_meu_look.preco,
                                0
                            )) / (
                                SELECT valor_minimo_vendas_ponto_frete_gratis
                                FROM configuracoes
                                LIMIT 1
                            ) * 100
                        FROM pedido_item_meu_look
                        INNER JOIN logistica_item ON logistica_item.uuid_produto = pedido_item_meu_look.uuid
                        WHERE pedido_item_meu_look.id_ponto = colaboradores.id
                            $filtroPeriodo
                        ), 0), 2)
                    )
                ) json_info_faturamento_ponto";
        }

        $consulta = DB::selectOne(
            "SELECT
                usuarios.id_colaborador,
                colaboradores.usuario_meulook,
                colaboradores.razao_social,
                colaboradores_enderecos.id_cidade,
                CONCAT(municipios.nome, ', ', municipios.uf) endereco,
                colaboradores.foto_perfil,
                colaboradores.bloqueado_criar_look = 'T' esta_bloqueado_criar_look,
                colaboradores.nome_instagram instagram,
                usuarios.permissao REGEXP :permissao_fornecedor eh_perfil_de_seller,
                ROUND(COALESCE(
                IF (
                    tipo_frete.id IS NULL,
                    NULL,
                    (
                        SELECT AVG(avaliacao_tipo_frete.nota_atendimento)
                        FROM avaliacao_tipo_frete
                        WHERE
                            avaliacao_tipo_frete.id_tipo_frete = tipo_frete.id AND
                            avaliacao_tipo_frete.nota_atendimento > 0 AND
                            avaliacao_tipo_frete.nota_localizacao > 0
                    )
                    ), 0
                )) nota_ponto,
                CASE
                    WHEN tipo_frete.categoria = 'PE' THEN 'PENDENTE'
                    WHEN tipo_frete.categoria IN ('ML', 'MS') THEN 'ATIVO'
                    ELSE 'SOLICITAR'
                END status_ponto_entrega,
                IF (
                    tipo_frete.categoria IN ('ML', 'MS'),
                    JSON_OBJECT(
                        'ponto_endereco', tipo_frete.mensagem,
                        'ponto_horario',tipo_frete.horario_de_funcionamento,
                        'ponto_telefone', colaboradores.telefone
                    ),
                    NULL
                ) json_ponto_entrega_detalhes,
                reputacao_fornecedores.reputacao = :tipo_melhor_fabricante eh_melhor_fabricante
                $campos
            FROM colaboradores
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            INNER JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
            LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = colaboradores.id
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE colaboradores.usuario_meulook = :usuario_meulook;",
            [
                'usuario_meulook' => $nomeUsuario,
                'permissao_fornecedor' => Usuario::VERIFICA_PERMISSAO_FORNECEDOR,
                'tipo_melhor_fabricante' => CatalogoFixoService::TIPO_MELHOR_FABRICANTE,
            ]
        );

        if (empty($consulta)) {
            throw new NotFoundHttpException('Usuário não existe');
        }
        if ($consulta['nota_ponto'] === 0) {
            unset($consulta['nota_ponto']);
        }

        return $consulta;
    }

    public static function consultaDadosColaborador(int $idCliente): array
    {
        $colaborador = DB::selectOne(
            "SELECT
                colaboradores.razao_social nome,
                colaboradores.telefone,
                colaboradores.usuario_meulook,
                tipo_frete.id IS NOT NULL AND tipo_frete.tipo_ponto = 'PP' eh_ponto_retirada,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                COALESCE(tipo_frete.horario_de_funcionamento, '') horario_de_funcionamento,
                COALESCE(tipo_frete.nome, '') nome_ponto,
                JSON_OBJECT(
                    'id', colaboradores_enderecos.id_cidade,
                    'nome', CONCAT(municipios.nome, ' - ', municipios.uf),
                    'latitude', colaboradores_enderecos.latitude,
                    'longitude', colaboradores_enderecos.longitude
                ) json_cidade
            FROM colaboradores
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = colaboradores.id
            LEFT JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
            WHERE colaboradores.id = :id_colaborador;",
            ['id_colaborador' => $idCliente]
        );
        if (empty($colaborador)) {
            throw new NotFoundHttpException('Colaborador não existe');
        }

        return $colaborador;
    }

    public static function verificaDadosClienteCriarTransacao(): void
    {
        $mensagem = DB::selectOneColumn(
            "SELECT
                CASE
                    WHEN colaboradores.id_tipo_entrega_padrao = 0 THEN 'Para finalizar um pedido é necessário selecionar um ponto de entrega'
                    WHEN COALESCE(colaboradores_enderecos.id_cidade, 0) = 0 THEN 'Para finalizar um pedido é necessário ter uma cidade preenchida'
                    ELSE 'correto'
                END mensagem
            FROM colaboradores
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE colaboradores.id = :id_cliente",
            ['id_cliente' => Auth::user()->id_colaborador]
        );
        if ($mensagem !== 'correto') {
            throw new InvalidArgumentException($mensagem);
        }
    }

    public static function colaboradorEhFraudatario(): bool
    {
        $ehFraudatario = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores_suspeita_fraude
                WHERE colaboradores_suspeita_fraude.situacao = 'FR'
                    AND colaboradores_suspeita_fraude.id_colaborador = :id_colaborador
            ) AS `eh_fraudatario`;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );

        return $ehFraudatario;
    }

    public static function atualizarTelefoneNosEnderecos(): void
    {
        $query = "SELECT
                colaboradores.telefone AS `novo_telefone`,
                colaboradores_enderecos.id AS `id_endereco`
            FROM colaboradores_log
            INNER JOIN colaboradores ON colaboradores.id = colaboradores_log.id_colaborador
            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.id_colaborador = colaboradores.id
            WHERE
                colaboradores.telefone <> JSON_VALUE(colaboradores_log.mensagem, '$.OLD_telefone')
                AND colaboradores_enderecos.telefone_destinatario <> JSON_VALUE(colaboradores_log.mensagem, '$.NEW_telefone')
                AND colaboradores_enderecos.telefone_destinatario = JSON_VALUE(colaboradores_log.mensagem, '$.OLD_telefone')
                AND colaboradores.telefone <> colaboradores_enderecos.telefone_destinatario
            GROUP BY colaboradores_enderecos.id
            ORDER BY colaboradores_log.id DESC;";

        $enderecos = DB::select($query);

        foreach ($enderecos as $endereco) {
            $colaboradorEndereco = new ColaboradorEndereco();
            $colaboradorEndereco->exists = true;
            $colaboradorEndereco->id = $endereco['id_endereco'];
            $colaboradorEndereco->telefone_destinatario = $endereco['novo_telefone'];
            $colaboradorEndereco->update();
        }
    }

    public static function calculaTendenciaCompra(): void
    {
        $clientesTransacoes = DB::select(
            "SELECT
                logistica_item.id_cliente,
                colaboradores.porcentagem_compras_moda,
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        DISTINCT
                        logistica_item.id_transacao
                        ORDER BY logistica_item.id_transacao DESC
                        LIMIT 10
                    ),
                    ']'
                ) AS `json_transacoes`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            WHERE colaboradores.bloqueado = 0
            GROUP BY logistica_item.id_cliente
            "
        );

        foreach ($clientesTransacoes as $cliente) {
            [$binds, $valores] = ConversorArray::criaBindValues($cliente['transacoes'], 'id_transacao');
            $produtos = DB::select(
                "SELECT
                    logistica_item.id_produto,
                    produtos.eh_moda
                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                WHERE logistica_item.id_transacao IN ($binds)
                GROUP BY logistica_item.id_produto",
                $valores
            );
            $totalProdutos = count($produtos);
            $produtosModa = array_filter($produtos, fn(array $produto): bool => $produto['eh_moda']);
            $porcentagemCompra = $totalProdutos > 0 ? (int) round((count($produtosModa) / $totalProdutos) * 100) : 0;
            if ($cliente['porcentagem_compras_moda'] === $porcentagemCompra) {
                continue;
            }

            $colaborador = new ColaboradorModel();
            $colaborador->exists = true;
            $colaborador->id = $cliente['id_cliente'];
            $colaborador->porcentagem_compras_moda = $porcentagemCompra;
            $colaborador->update();
        }
    }
}
