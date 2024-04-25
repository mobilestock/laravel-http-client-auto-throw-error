<?php

namespace MobileStock\jobs;

use MobileStock\helper\ConversorArray;
use MobileStock\jobs\config\AbstractJob;
use PDO;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $sql = $conexao->prepare(
                "SELECT
                    GROUP_CONCAT(DISTINCT colaboradores.id ORDER BY colaboradores.id DESC) AS `contas`,
                    COUNT(DISTINCT colaboradores.id) AS `qtd_contas`,
                    colaboradores.telefone
                FROM colaboradores
                WHERE colaboradores.telefone IS NOT NULL
                GROUP BY colaboradores.telefone
                ORDER BY qtd_contas DESC;"
            );
            $sql->execute();
            $telefones = $sql->fetchAll(PDO::FETCH_ASSOC);

            $sql = $conexao->prepare(
                "UPDATE colaboradores
                SET colaboradores.conta_principal = FALSE
                WHERE colaboradores.conta_principal = TRUE;"
            );
            $sql->execute();

            $contasPrincipais = [];
            foreach ($telefones as $telefone) {
                $telefone['contas'] = explode(',', $telefone['contas']);
                if (count($telefone['contas']) <= 1) {
                    $contasPrincipais[] = (int) reset($telefone['contas']);
                    continue;
                }

                [$bind, $valores] = ConversorArray::criaBindValues($telefone['contas'], 'id_colaborador');
                $sql = $conexao->prepare(
                    "SELECT lancamento_financeiro.id_colaborador
                    FROM lancamento_financeiro
                    WHERE lancamento_financeiro.id_colaborador IN ($bind)
                    ORDER BY lancamento_financeiro.id DESC
                    LIMIT 1;"
                );
                foreach ($valores as $key => $valor) {
                    $sql->bindValue($key, $valor, PDO::PARAM_INT);
                }
                $sql->execute();
                $idContaPrincipal = (int) $sql->fetchColumn();
                if (empty($idContaPrincipal)) {
                    $idContaPrincipal = (int) reset($telefone['contas']);
                }

                $contasPrincipais[] = $idContaPrincipal;
            }

            [$bind, $valores] = ConversorArray::criaBindValues(array_unique($contasPrincipais), 'id_colaborador');
            $sql = $conexao->prepare(
                "UPDATE colaboradores
                SET colaboradores.conta_principal = TRUE
                WHERE colaboradores.id IN ($bind);"
            );
            foreach ($valores as $key => $valor) {
                $sql->bindValue($key, $valor, PDO::PARAM_INT);
            }
            $sql->execute();

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }
};
