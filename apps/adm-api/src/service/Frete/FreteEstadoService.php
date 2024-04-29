<?php

namespace MobileStock\service\Frete;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\model\FreteEstado;
use PDO;

class FreteEstadoService extends FreteEstado
{
    public function atualizar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->updateSomenteDadosPreenchidos();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Nenhum registro foi atualizado');
        }
    }

    public static function buscaFretes(PDO $conexao): array
    {
        $dadosFrete = $conexao
            ->query(
                "SELECT frete_estado.id,
                estados.nome,
                frete_estado.estado AS `uf`,
                frete_estado.valor_frete,
                frete_estado.valor_adicional
            FROM frete_estado
            INNER JOIN estados ON estados.uf = frete_estado.estado
            ORDER BY frete_estado.estado"
            )
            ->fetchAll(PDO::FETCH_ASSOC);
        if ($dadosFrete) {
            $dadosFrete = array_map(function ($frete) {
                $frete['valor_frete'] = (float) $frete['valor_frete'];
                $frete['valor_adicional'] = (float) $frete['valor_adicional'];
                $frete['nome'] .= " ({$frete['uf']})";
                return $frete;
            }, $dadosFrete);
        }
        return $dadosFrete;
    }

    public static function buscaValorAdicional(): float
    {
        $valorAdicional = DB::selectOneColumn(
            "SELECT frete_estado.valor_adicional
            FROM frete_estado
            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.uf = frete_estado.estado
            WHERE colaboradores_enderecos.id_colaborador = :id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );
        return $valorAdicional;
    }

    /**
     * @see https://github.com/mobilestock/backend/issues/127
     */
    public static function buscaValorFrete(int $idCliente, bool $adicao): array
    {
        $consultaValorAdicional = $adicao ? ',frete_estado.valor_adicional' : '';
        $dados = DB::selectOne(
            "SELECT
                frete_estado.valor_frete
                $consultaValorAdicional
            FROM frete_estado
            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.uf = frete_estado.estado
            WHERE colaboradores_enderecos.id_colaborador = :id_cliente
                AND colaboradores_enderecos.eh_endereco_padrao = 1",
            ['id_cliente' => $idCliente]
        );

        if (empty($dados['valor_adicional'])) {
            $dados['valor_adicional'] = 0;
        }

        return $dados;
    }
}
