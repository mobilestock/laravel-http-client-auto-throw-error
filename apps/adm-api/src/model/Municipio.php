<?php

namespace MobileStock\model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property float $valor_frete
 * @property float $valor_adicional
 * @property bool $tem_frete_expresso
 * @property int $dias_entrega
 */

class Municipio extends Model
{
    protected $fillable = ['valor_frete', 'valor_adicional', 'tem_frete_expresso', 'dias_entrega'];

    /**
     * @see https://github.com/mobilestock/backend/issues/127
     */
    public static function buscaValorFrete(bool $adicao): array
    {
        $consultaValorAdicional = $adicao ? ',municipios.valor_adicional' : '';
        $dados = DB::selectOne(
            "SELECT
                municipios.valor_frete
                $consultaValorAdicional
            FROM municipios
            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.id_cidade = municipios.id
            WHERE colaboradores_enderecos.id_colaborador = :id_cliente
                AND colaboradores_enderecos.eh_endereco_padrao = 1",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        if (empty($dados['valor_adicional'])) {
            $dados['valor_adicional'] = 0;
        }

        return $dados;
    }

    public static function buscaValorAdicional(): float
    {
        $valorAdicional = DB::selectOneColumn(
            "SELECT municipios.valor_adicional
            FROM municipios
            INNER JOIN colaboradores_enderecos ON colaboradores_enderecos.id_cidade = municipios.id
            WHERE colaboradores_enderecos.id_colaborador = :id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );
        return $valorAdicional;
    }

    /**
     * @return Collection<self>
     */
    public static function buscaFretes(string $estado)
    {
        $idTransportadora = TipoFrete::ID_COLABORADOR_TRANSPORTADORA;
        $dadosFrete = self::fromQuery(
            "SELECT municipios.id,
                    CONCAT(municipios.nome, ' (', municipios.uf, ')') AS `nome`,
                    municipios.uf,
                    municipios.valor_frete,
                    municipios.valor_adicional,
                    (municipios.id_colaborador_frete_expresso != :id_transportadora) AS `tem_frete_expresso`,
                    municipios.id_colaborador_frete_expresso,
                    colaboradores.razao_social AS `razao_social_frete_expresso`,
                    municipios.dias_entrega
                FROM municipios
                INNER JOIN colaboradores ON colaboradores.id = municipios.id_colaborador_frete_expresso
                INNER JOIN estados ON estados.uf = municipios.uf
                WHERE estados.uf = :estado
                ORDER BY municipios.valor_frete DESC",
            [':estado' => $estado, ':id_transportadora' => $idTransportadora]
        );

        return $dadosFrete;
    }

    public static function buscaCidade(int $idCidade): self
    {
        $dadosFrete = self::fromQuery(
            "SELECT municipios.id,
                municipios.valor_frete,
                municipios.valor_adicional,
                municipios.dias_entrega,
            FROM municipios
            WHERE municipios.id = :idCidade",
            [':idCidade' => $idCidade]
        )->firstOrFail();

        $dadosFrete['tem_frete_expresso'] = !empty($dadosFrete['colaboradores_frete_expresso']);

        return $dadosFrete;
    }

    public static function buscaEstados(): array
    {
        $estados = DB::selectColumns(
            "SELECT municipios.uf
            FROM municipios
            GROUP BY municipios.uf;"
        );

        return $estados;
    }

    public static function verificaSeCidadeAtendeFreteExpresso(int $idCidade, int $idEntregadorFreteExpresso): bool
    {
        $idsColaboradores = DB::selectOneColumn(
            "SELECT municipios.entregadores_frete_expresso
            FROM municipios
            WHERE municipios.id = :idCidade",
            [':idCidade' => $idCidade]
        );

        $idsColaboradores = explode(',', $idsColaboradores);

        return in_array($idEntregadorFreteExpresso, $idsColaboradores);
    }
}
