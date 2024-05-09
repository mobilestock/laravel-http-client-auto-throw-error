<?php

namespace api_administracao\Models;

use MobileStock\model\Usuario;
use MobileStock\service\ColaboradoresService;
use PDO;

class MobilePay
{
    public static function buscaContato(PDO $conn, string $char, int $idCliente)
    {
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;

        $filtro = " WHERE conta_bancaria_colaboradores.nome_titular LIKE '{$idCliente}' OR conta_bancaria_colaboradores.cpf_titular LIKE '%{$char}%'
                         AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F' AND conta_bancaria_colaboradores.conta_iugu_verificada = 'T' ";

        $query = "SELECT
                        conta_bancaria_colaboradores.agencia,
                        conta_bancaria_colaboradores.conta,
                        conta_bancaria_colaboradores.cpf_titular,
                        COALESCE((SELECT bancos.nome FROM bancos WHERE bancos.cod_banco = conta_bancaria_colaboradores.id_banco),'-')banco,
                        EXISTS(
                              SELECT 1
                              FROM usuarios
                              WHERE usuarios.id_colaborador = :idCliente
                              AND usuarios.permissao REGEXP '$permissaoFornecedor'
                        ) AS `eh_fornecedor`,
                        conta_bancaria_colaboradores.id,
                        conta_bancaria_colaboradores.id_cliente,
                        conta_bancaria_colaboradores.id_iugu,
                        conta_bancaria_colaboradores.nome_titular,
                        conta_bancaria_colaboradores.phone,
                        conta_bancaria_colaboradores.prioridade,
                        conta_bancaria_colaboradores.tipo
                    FROM conta_bancaria_colaboradores
                    $filtro";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $lista = array_map(function ($resultado) {
            $resultado['eh_fornecedor'] = (bool) $resultado['eh_fornecedor'];
            return $resultado;
        }, $lista);
        return $lista;
    }
    public static function buscaContatoPay(PDO $conn, string $char, int $idCliente)
    {
        $filtro = " WHERE usuarios.cnpj LIKE '%{$char}%' AND usuarios.id_colaborador <> $idCliente";
        $query = "SELECT usuarios.cnpj, colaboradores.id, colaboradores.razao_social FROM usuarios INNER JOIN colaboradores ON(colaboradores.id = usuarios.id_colaborador)  $filtro";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function buscaPassword(PDO $conn, int $id)
    {
        $query = "SELECT password_pay FROM usuarios WHERE usuarios.id = $id";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista['password_pay'];
    }
    public static function criaPasswordToken(PDO $conn, int $id, string $senha)
    {
        $senha = sha1($senha);
        $query = "UPDATE usuarios SET usuarios.password_pay= '{$senha}' WHERE usuarios.id = $id";
        $stmt = $conn->prepare($query);
        $resultado = $stmt->execute();
        return $resultado;
    }
    public static function existeRecebimentoPendente(PDO $conn, int $id)
    {
        $query = "SELECT COUNT(id) AS existe
                    FROM colaboradores_prioridade_pagamento
                         WHERE colaboradores_prioridade_pagamento.situacao IN ('CR', 'EP')
                              AND colaboradores_prioridade_pagamento.id_colaborador  = $id;";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['existe'];
    }

    public static function hasApiColaborador(PDO $conn, int $id_colaborador)
    {
        $query = "SELECT api_colaboradores.id_colaborador FROM api_colaboradores WHERE api_colaboradores.id_colaborador = $id_colaborador";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id_colaborador'];
    }

    public static function cabecalhoMobile(PDO $conn, string $token)
    {
        $query = "SELECT
                    usuarios.nome,
                    colaboradores.razao_social,
                    usuarios.token,
                    usuarios.id,
                    usuarios.id_colaborador,
                    usuarios.nivel_acesso
               FROM usuarios
               INNER JOIN colaboradores
                    ON colaboradores.id = usuarios.id_colaborador
               WHERE usuarios.token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function buscaHistorico(PDO $conn, array $list, $idCliente)
    {
        if (count($list) > 0) {
            $size = 0;
            $filtro = ' usuarios.id_colaborador IN(';
            foreach ($list as $key => $i) {
                $size++;
                if ($size < count($list)) {
                    $filtro .= $i['id_colaborador'] . ',';
                } else {
                    $filtro .= $i['id_colaborador'] . ') ';
                }
            }
            $query = "  SELECT usuarios.id_colaborador as id,
                              (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = usuarios.id_colaborador) as razao_social,
                              'MP' as tipo,
                              '-' as cpf,
                              '-' as banco
                              FROM usuarios
                                   WHERE $filtro

                         UNION ALL

                         SELECT colaboradores_prioridade_pagamento.id_conta_bancaria as id,
                                   conta_bancaria_colaboradores.nome_titular as razao_social,
                                   'CB' as tipo,
                                   conta_bancaria_colaboradores.cpf_titular as cpf,
                                   (SELECT nome FROM bancos WHERE conta_bancaria_colaboradores.id_banco = bancos.cod_banco)as banco
                                   FROM colaboradores_prioridade_pagamento
                                        INNER JOIN conta_bancaria_colaboradores ON(colaboradores_prioridade_pagamento.id_colaborador=$idCliente
                                             AND colaboradores_prioridade_pagamento.id_conta_bancaria= conta_bancaria_colaboradores.id)
                                             AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F' AND conta_bancaria_colaboradores.conta_iugu_verificada = 'T'
                         GROUP BY id

                    ";
        } else {
            $filtro = '';
            $lista = '';
            $query = "SELECT colaboradores_prioridade_pagamento.id_conta_bancaria as id,
               conta_bancaria_colaboradores.nome_titular as razao_social,
               'CB' as tipo,
               conta_bancaria_colaboradores.cpf_titular as cpf,
               (SELECT nome FROM bancos WHERE conta_bancaria_colaboradores.id_banco = bancos.cod_banco)as banco
               FROM colaboradores_prioridade_pagamento
                    INNER JOIN conta_bancaria_colaboradores ON(colaboradores_prioridade_pagamento.id_colaborador=$idCliente
                         AND colaboradores_prioridade_pagamento.id_conta_bancaria= conta_bancaria_colaboradores.id)
                         AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F' AND conta_bancaria_colaboradores.conta_iugu_verificada = 'T'
     GROUP BY id

";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function buscarInformaçõesSaque(PDO $conn, int $saque)
    {
        $sql = "SELECT colaboradores_prioridade_pagamento.id_colaborador,
                         api_colaboradores.iugu_token_live,
                         DATE(colaboradores_prioridade_pagamento.data_criacao)data_criacao,
                         colaboradores_prioridade_pagamento.valor_pago,
                         DATE(colaboradores_prioridade_pagamento.data_criacao + INTERVAL 7 DAY)datas
                              FROM colaboradores_prioridade_pagamento
                                   INNER JOIN api_colaboradores ON(colaboradores_prioridade_pagamento.id_colaborador = api_colaboradores.id_colaborador)
                                   WHERE colaboradores_prioridade_pagamento.id = $saque";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function exitsBankAccount(PDO $conn, int $agency, int $account, string $cpf, int $bank)
    {
        $sql = "SELECT iugu_token_live
               FROM conta_bancaria_colaboradores
                    WHERE conta_bancaria_colaboradores.cpf_titular LIKE '%$cpf%'
                         AND conta_bancaria_colaboradores.agencia LIKE '$agency'
                         AND conta_bancaria_colaboradores.conta LIKE '$account'
                         AND conta_bancaria_colaboradores.banco = $bank ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function deleteColaborador(PDO $conn)
    {
        $sql = "DELETE FROM colaboradores WHERE NOT EXISTS(SELECT 1 FROM usuarios WHERE usuarios.id_colaborador = colaboradores.id);
                DELETE FROM api_colaboradores WHERE NOT EXISTS(SELECT 1 FROM colaboradores WHERE api_colaboradores.id_colaborador = colaboradores.id)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute();
    }
    public static function buscaConfiguracao(PDO $conn)
    {
        $sql = 'SELECT configuracoes.* FROM configuracoes LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function saldo(PDO $conn, $idCliente)
    {
        $sql = "SELECT saldo_cliente($idCliente) as saldo";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function saldo_emprestimo(PDO $conn, $idCliente)
    {
        $adiantamentoBloqueado = ColaboradoresService::adiantamentoEstaBloqueado($conn, $idCliente);
        if ($adiantamentoBloqueado) {
            return ['saldo' => 0];
        }
        $sql = "SELECT saldo_cliente_emprestimo($idCliente) as saldo";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function buscaEmprestimos(PDO $conexao, int $idCliente): array
    {
        $sql = "SELECT
                    emprestimo.id,
                    emprestimo.id_favorecido,
                    emprestimo.valor_capital,
                    emprestimo.valor_atual,
                    emprestimo.situacao,
                    emprestimo.id_lancamento
               FROM emprestimo
               WHERE emprestimo.id_favorecido = :id_cliente
               AND emprestimo.situacao <> 'PA'
               ORDER BY emprestimo.data_inicio DESC";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue('id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function calculaJuros(PDO $conn, float $valor)
    {
        $sql = "SELECT ROUND((((SELECT taxa_adiantamento FROM configuracoes LIMIT 1 )/30)/100) *$valor,2) as juros ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista;
    }
    public static function buscaSaldos(PDO $conn, int $id, int $idCliente)
    {
        $sql = "
          SELECT
               lancamento_financeiro.valor,
               lancamento_financeiro.situacao,
               lancamento_financeiro.origem,
               lancamento_financeiro.tipo,
               DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y') AS data,
               lancamento_financeiro.id_lancamento_adiantamento,
               lancamento_financeiro.id,
               lancamento_financeiro.faturamento_criado_pago fat_pago
          FROM lancamento_financeiro
          WHERE
               (lancamento_financeiro.id_lancamento_adiantamento = $id OR lancamento_financeiro.id = $id)
               AND lancamento_financeiro.id_colaborador = $idCliente
               AND lancamento_financeiro.origem <> 'AU'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function saldoAnterior(PDO $conn, int $id, int $idCliente)
    {
        $sql = "SELECT COALESCE(lancamento_financeiro.numero_documento,0)anterior FROM lancamento_financeiro WHERE lancamento_financeiro.id = $id and lancamento_financeiro.id_colaborador=$idCliente";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $lista['anterior'];
    }

    public static function BuscaSaldoGeral(PDO $conexao)
    {
        $sql = "SELECT 'cliente' saldo,
                    SUM(
                            CASE WHEN lancamento_financeiro.tipo = 'R'
                                THEN lancamento_financeiro.valor
                                ELSE 0
                            END) pagar,
                    SUM(
                                CASE WHEN lancamento_financeiro.tipo = 'P'
                                    THEN lancamento_financeiro.valor
                                    ELSE 0
                                END)receber
                        FROM lancamento_financeiro
                                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
                                WHERE
                                                CASE WHEN colaboradores.tipo = 'C'
                                                        THEN 1
                                                        ELSE 0
                                                    END AND
                                        lancamento_financeiro.situacao = 1

                                        UNION ALL

                SELECT
                        'fornecedor' saldo,
                        SUM(
                                CASE WHEN lancamento_financeiro.tipo = 'R'
                                    THEN lancamento_financeiro.valor
                                    ELSE 0
                                END) pagar,
                        SUM(
                                    CASE WHEN lancamento_financeiro.tipo = 'P'
                                        THEN lancamento_financeiro.valor
                                        ELSE 0
                                    END)receber
                            FROM lancamento_financeiro
                                    INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
                                    WHERE
                                                    CASE WHEN colaboradores.tipo = 'F'
                                                            THEN 1
                                                            ELSE 0
                                                        END AND
                                            lancamento_financeiro.situacao = 1
                                    UNION ALL

                SELECT
                        'mobile' saldo,
                        SUM(
                                CASE WHEN lancamento_financeiro.tipo = 'R'
                                    THEN lancamento_financeiro.valor
                                    ELSE 0
                                END) pagar,
                    SUM(
                                CASE WHEN lancamento_financeiro.tipo = 'P'
                                    THEN lancamento_financeiro.valor
                                    ELSE 0
                                END)receber
                        FROM lancamento_financeiro
                                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
                                WHERE
                                            lancamento_financeiro.situacao = 1";
        $stm = $conexao->prepare($sql);
        $stm->execute();
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
        $saldo = [];
        $saldo['cliente'] = ((float) $resultado[0]['pagar'] - (float) $resultado[0]['receber']) * -1;
        $saldo['fornecedor'] = ((float) $resultado[1]['pagar'] - (float) $resultado[1]['receber']) * -1;
        $saldo['mobile'] = ((float) $resultado[2]['receber'] - (float) $resultado[2]['pagar']) * -1;
        //cliente positivo
        return $saldo;
    }
}
