<?php

namespace MobileStock\service;

use DateInterval;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\model\TrocaFilaSolicitacoes;
use PDO;

/**
 * @issue https://github.com/mobilestock/backend/issues/104
 */
class TrocaFilaSolicitacoesService extends TrocaFilaSolicitacoes
{
    public function salva(PDO $conexao): int
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        $this->id = $conexao->lastInsertId();
        return $this->id;
    }

    public function atualizar(PDO $conexao)
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->updateSomenteDadosPreenchidos();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        if ($stmt->rowCount() !== 1) {
            throw new Exception('Nenhum registro foi atualizado');
        }
        return true;
    }

    public static function buscaSolicitacaoPorId(PDO $conexao, int $id): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                troca_fila_solicitacoes.id_cliente,
                troca_fila_solicitacoes.uuid_produto,
                troca_fila_solicitacoes.descricao_defeito,
                troca_fila_solicitacoes.situacao
            FROM troca_fila_solicitacoes
            WHERE troca_fila_solicitacoes.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $consulta;
    }

    public static function buscaIdDaFilaDeTrocasPorUuid(PDO $conexao, string $uuid): int
    {
        $stmt = $conexao->prepare(
            "SELECT
                troca_fila_solicitacoes.id
            FROM troca_fila_solicitacoes
            WHERE troca_fila_solicitacoes.uuid_produto = :uuid"
        );
        $stmt->execute([':uuid' => $uuid]);
        $id = $stmt->fetchColumn();
        return $id;
    }

    public static function buscaTrocasParaDeletar(): array
    {
        $stmt = "SELECT troca_pendente_agendamento.uuid
            FROM troca_pendente_agendamento
            INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.numero_documento = troca_pendente_agendamento.uuid
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = troca_pendente_agendamento.uuid
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = troca_pendente_agendamento.uuid
            WHERE entregas_faturamento_item.situacao = 'EN'
                AND (
                    (troca_fila_solicitacoes.id IS NULL OR troca_fila_solicitacoes.situacao = 'APROVADO') AND
                    CURRENT_DATE() > DATE(troca_pendente_agendamento.data_vencimento)
                )
                AND (
                    saldo_cliente(troca_pendente_agendamento.id_cliente) +
                    COALESCE((
                        SELECT SUM(troca_pendente_agendamento.preco)
                        FROM troca_pendente_agendamento
                        INNER JOIN transacao_financeiras_produtos_trocas ON transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
                        WHERE transacao_financeiras_produtos_trocas.id_cliente = troca_pendente_agendamento.id_cliente
                            AND transacao_financeiras_produtos_trocas.uuid <> troca_pendente_agendamento.uuid
                    ), 0) +
                    COALESCE((
                        SELECT SUM(IF(lancamento_financeiro_pendente.tipo = 'P', lancamento_financeiro_pendente.valor, lancamento_financeiro_pendente.valor * -1))
                        FROM lancamento_financeiro_pendente
                        WHERE lancamento_financeiro_pendente.id_colaborador = troca_pendente_agendamento.id_cliente
                            AND lancamento_financeiro_pendente.origem IN ('PC', 'ES')
                    ), 0)
                ) >= 0
            GROUP BY troca_pendente_agendamento.uuid";

        $consulta = DB::select($stmt);

        return $consulta;
    }

    public static function buscaSolicitacoesParaAprovar(): array
    {
        $qtdDiasAprovacaoAutomatica = ConfiguracaoService::retornaQtdDiasAprovacaoAutomatica();
        $stmt = "SELECT
                troca_fila_solicitacoes.id,
                troca_fila_solicitacoes.id_cliente,
                troca_fila_solicitacoes.id_produto,
                troca_fila_solicitacoes.nome_tamanho,
                troca_fila_solicitacoes.uuid_produto,
                troca_fila_solicitacoes.descricao_defeito,
                logistica_item.preco
            FROM troca_fila_solicitacoes
            INNER JOIN logistica_item ON logistica_item.uuid_produto = troca_fila_solicitacoes.uuid_produto
            WHERE
                troca_fila_solicitacoes.situacao = 'SOLICITACAO_PENDENTE' AND (
                    CURDATE() - INTERVAL $qtdDiasAprovacaoAutomatica DAY >= DATE(troca_fila_solicitacoes.data_criacao)
                )
                    OR troca_fila_solicitacoes.situacao = 'PENDENTE_FOTO' AND (
                        CURDATE() - INTERVAL $qtdDiasAprovacaoAutomatica DAY >= DATE(troca_fila_solicitacoes.data_atualizacao)
                )";

        $consulta = DB::select($stmt);

        return $consulta;
    }

    public static function enviarNotificacaoWhatsapp(
        PDO $conexao,
        int $idTroca,
        string $origem,
        ?string $pacReverso = null
    ): bool {
        try {
            $mensagens = [];
            $messageService = new MessageService();
            $stmt = $conexao->prepare(
                "SELECT
                    cliente_colaboradores.telefone telefone_cliente,
                    vendedor_colaboradores.telefone telefone_vendedor,
                    LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome_produto,
                    troca_fila_solicitacoes.nome_tamanho,
                    logistica_item.preco,
                    troca_fila_solicitacoes.descricao_defeito,
                    troca_fila_solicitacoes.motivo_reprovacao_seller,
                    troca_fila_solicitacoes.motivo_reprovacao_disputa,
                    troca_fila_solicitacoes.motivo_reprovacao_foto,
                    troca_fila_solicitacoes.situacao,
                    troca_fila_solicitacoes.uuid_produto
                FROM troca_fila_solicitacoes
                INNER JOIN produtos ON produtos.id = troca_fila_solicitacoes.id_produto
                INNER JOIN colaboradores cliente_colaboradores ON cliente_colaboradores.id = troca_fila_solicitacoes.id_cliente
                INNER JOIN logistica_item ON logistica_item.uuid_produto = troca_fila_solicitacoes.uuid_produto
                INNER JOIN colaboradores vendedor_colaboradores ON vendedor_colaboradores.id = logistica_item.id_responsavel_estoque
                WHERE troca_fila_solicitacoes.id = :id"
            );
            $stmt->execute(['id' => $idTroca]);
            $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($consulta === false) {
                throw new Exception('Troca não encontrada!');
            }
            $telefoneVendedor = $consulta['telefone_vendedor'];
            $telefoneCliente = $consulta['telefone_cliente'];

            switch ($consulta['situacao']) {
                case 'SOLICITACAO_PENDENTE':
                    array_push($mensagens, [
                        'telefone' => $telefoneVendedor,
                        'mensagem' => "Foi feita uma solicitação de troca para um de seus produtos, favor acessar {$_ENV['URL_MOBILE']}/solicitacoes-troca.php para respondê-la.",
                    ]);
                    break;
                case 'APROVADO':
                    $mensagem = "Sua troca do produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']}\" foi aprovada! ";
                    $mensagem .=
                        $origem === 'ML'
                            ? "Com isso você ganhou um saldo no valor de R$ {$consulta['preco']} para comprar outro produto."
                            : "Quando os produtos forem devolvidos, seu crédito no valor de R$ {$consulta['preco']} será gerado automaticamente na sua conta Look Pay";

                    if ($pacReverso) {
                        $mensagem .= PHP_EOL . PHP_EOL;
                        $mensagem .= '*Siga as instruções abaixo para devolver o produto:* ';
                        $mensagem .= PHP_EOL;
                        $mensagem .= "- Anote o código do pac reverso: $pacReverso " . PHP_EOL;
                        $mensagem .=
                            '- Embale o produto na caixa original, adicionando junto as informações necessárias;';
                        $mensagem .= PHP_EOL;
                        $mensagem .= '- Leve o produto até os correios e apresente o código do pac reverso;' . PHP_EOL;
                        $mensagem .=
                            '* Não se esqueça de adicionar as informações de remetente e destinatário do produto.';
                    }
                    $mensagens[] = [
                        'telefone' => $telefoneCliente,
                        'mensagem' => $mensagem,
                    ];
                    break;
                case 'REPROVADO':
                    $mensagem = "O vendedor recusou sua solicitação de troca referente ao produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']}\" pelo motivo: \"{$consulta['motivo_reprovacao_seller']}\". ";
                    $mensagem .=
                        $origem === 'ML' ? "{$_ENV['URL_MEULOOK']}devolucoes" : " {$_ENV['URL_AREA_CLIENTE']}trocas";
                    ' Caso queira contestar, você pode abrir uma disputa pelo link: ';
                    $mensagens[] = [
                        'telefone' => $telefoneCliente,
                        'mensagem' => $mensagem,
                    ];
                    break;
                case 'EM_DISPUTA':
                    array_push($mensagens, [
                        'telefone' => $telefoneVendedor,
                        'mensagem' => "O cliente iniciou uma disputa pela troca do produto \"{$consulta['nome_produto']}\" de tamanho \"{$consulta['nome_tamanho']}\".",
                    ]);
                    break;
                case 'REPROVADA_NA_DISPUTA':
                    $mensagem = "A troca do produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']}\" que estava em disputa, foi recusada pelo seguinte motivo: \"{$consulta['motivo_reprovacao_disputa']}\".";
                    $mensagens[] = [
                        'telefone' => $telefoneCliente,
                        'mensagem' => $mensagem,
                    ];
                    $mensagens[] = [
                        'telefone' => $telefoneVendedor,
                        'mensagem' => $mensagem,
                    ];
                    break;
                case 'CANCELADO_PELO_CLIENTE':
                    $mensagem = "O cliente desistiu da troca do produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']}\", você não será mais notificado sobre ela.";
                    $mensagens[] = [
                        'telefone' => $telefoneVendedor,
                        'mensagem' => $mensagem,
                    ];
                    break;
                case 'REPROVADA_POR_FOTO':
                    $mensagem = "O vendedor recusou a troca do produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']}\". Motivo: fotos pouco detalhadas, \"{$consulta['motivo_reprovacao_foto']}\". Acesse ";
                    $mensagem .=
                        $origem === 'ML'
                            ? " {$_ENV['URL_MEULOOK']}devolucoes?w={$consulta['uuid_produto']}"
                            : "{$_ENV['URL_MOBILE']}trocas?w={$consulta['uuid_produto']}";
                    $mensagem .=
                        ' para enviar as fotos. Você tem 7 dias pra enviar novas fotos, ou a troca será recusada automaticamente.';
                    $mensagens[] = [
                        'telefone' => $telefoneCliente,
                        'mensagem' => $mensagem,
                    ];
                    break;
                case 'PENDENTE_FOTO':
                    $mensagem = "O cliente enviou mais fotos do defeito relatado no produto \"{$consulta['nome_produto']}\" tamanho \"{$consulta['nome_tamanho']} conforme solicitado. Caso não seja respondida em até 7 dias, a troca será aprovada automaticamente.";
                    $mensagens[] = [
                        'telefone' => $telefoneVendedor,
                        'mensagem' => $mensagem,
                    ];
                    break;
                default:
                    throw new Exception('Situação de solicitação troca inválida!');
            }

            foreach ($mensagens as $mensagem) {
                $messageService->sendMessageWhatsApp($mensagem['telefone'], $mensagem['mensagem']);
            }
        } catch (\Throwable $throw) {
        }

        return true;
    }

    public static function buscaTrocasPraExpirarFoto(): array
    {
        $qtdDiasAprovacaoAutomatica = ConfiguracaoService::retornaQtdDiasAprovacaoAutomatica();
        $sql = "SELECT
                troca_fila_solicitacoes.uuid_produto,
                troca_fila_solicitacoes.id
            FROM troca_fila_solicitacoes
            WHERE troca_fila_solicitacoes.situacao = 'REPROVADA_POR_FOTO'
            AND CURDATE() - interval $qtdDiasAprovacaoAutomatica DAY >= DATE(troca_fila_solicitacoes.data_atualizacao)";

        $retorno = DB::select($sql);

        return $retorno;
    }

    public static function retornaTextoSituacaoTroca(
        string $situacaoTroca,
        string $dataBaseTroca,
        string $dataAtualizacaoSolicitacao,
        string $origem,
        array $auxiliares
    ): string {
        switch ($situacaoTroca) {
            case 'RETORNO_PRODUTO_EXPIRADO':
                return 'Troca expirada';
                break;
            case 'TROCA_DISPONIVEL':
                return 'Disponível para troca';
            case 'CLIENTE_DESISTIU':
                $dataLimite = new DateTime($dataBaseTroca);
                $dataLimite->add(new DateInterval("P{$auxiliares['dias_defeito']}D"));
                $dataLimite = $dataLimite->format('d/m/y');
                return $origem === 'ADM'
                    ? "O cliente desistiu da troca, mas poderá reabri-la até {$dataLimite}"
                    : "Você desistiu da troca, mas poderá reabri-la até {$dataLimite}";
            case 'ITEM_TROCADO':
                return 'Produto trocado/devolvido';
                break;
            case 'TROCA_AGENDADA':
                $dataAprovacao = new DateTime($dataAtualizacaoSolicitacao);
                $dataAprovacao = $dataAprovacao->format('d/m/y');
                $dataLimite = new DateTime($dataBaseTroca);
                $dataLimite = $dataLimite->add(new DateInterval("P{$auxiliares['dias_normal']}D"));
                $dataLimite = $dataLimite->format('d/m/y');
                if ($dataAprovacao === $dataLimite) {
                    $dataLimite = 'HOJE';
                }
                return "Troca aprovada em {$dataAprovacao}, " .
                    ($origem === 'ML'
                        ? "Você tem até {$dataLimite} para enviar o produto"
                        : 'o cliente deverá escolher um novo produto até');
                break;
            case 'DISPUTA_REPROVOU':
                return 'Troca reprovada na disputa';
                break;
            case 'DISPUTA':
                return 'Produto em disputa para troca';
                break;
            case 'SELLER_REPROVOU':
                $dataReprovacao = new DateTime($dataBaseTroca);
                $dataReprovacao = $dataReprovacao->format('d/m/y');
                $dataLimite = new DateTime($dataBaseTroca);
                $dataLimite = $dataLimite->add(new DateInterval("P{$auxiliares['dias_defeito']}D"));
                $dataLimite = $dataLimite->format('d/m/y');
                return $origem === 'ADM'
                    ? "Troca recusada em {$dataReprovacao}"
                    : "O vendedor recusou a troca em {$dataReprovacao}, você poderá abrir uma disputa até o dia {$dataLimite}";
                break;
            case 'TROCA_PENDENTE':
                $dataLimite = new DateTime($dataAtualizacaoSolicitacao);
                $dataLimite = $dataLimite->add(new DateInterval('P' . ($auxiliares['aprovacao_automatica'] + 1) . 'D'));
                $dataLimite = $dataLimite->format('d/m/y');
                return "Aguardando retorno do vendedor. A troca será aprovada automaticamente no dia {$dataLimite} caso não seja respondida";
                break;
            case 'PASSOU_PRAZO':
                return 'Prazo de troca e devolução expirado';
                break;
            case 'DISPONIVEL_TROCA_E_DEFEITO':
                return 'Disponível troca ou devolução';
                break;
            case 'DISPONIVEL_SO_TROCA':
                return 'Disponível troca ou devolução por DEFEITO';
                break;
            case 'FOTO_REPROVOU':
                return 'O vendedor recusou a troca pois as fotos podem não ter mostrado os defeitos.
                    Caso não sejam enviadas novas fotos em até 7 dias, a troca será reprovada automaticamente';
                break;
            case 'PENDENTE_FOTO':
                return 'As fotos foram enviadas. Caso o vendedor não responda em até 7 dias a troca será aprovada automaticamente';
                break;
            default:
                return $situacaoTroca;
        }
    }
}
