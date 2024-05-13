<?php
namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property int $id_cidade
 * @property float $latitude
 * @property float $longitude
 * @property int $raio
 * @property float $valor
 * @property bool $esta_ativo
 * @property ?string $apelido
 * @property ?int $dias_entregar_cliente
 * @property int $dias_margem_erro
 * @property int $prazo_forcar_entrega
 * @property int $id_usuario
 */
class TransportadoresRaio extends Model
{
    protected $fillable = [
        'id',
        'id_colaborador',
        'id_cidade',
        'latitude',
        'longitude',
        'raio',
        'valor',
        'esta_ativo',
        'apelido',
        'dias_entregar_cliente',
        'dias_margem_erro',
        'prazo_forcar_entrega',
        'id_usuario',
    ];
    public static function buscaInformacoesPorIdRaio(int $idRaio): self
    {
        $transporteCidade = self::fromQuery(
            "SELECT
                transportadores_raios.id,
                transportadores_raios.esta_ativo
            FROM transportadores_raios
            WHERE transportadores_raios.id = :id_raio;",
            ['id_raio' => $idRaio]
        )->first();

        return $transporteCidade;
    }
    public static function buscaEntregadorMaisProximoDaCoordenada(
        int $idCidade,
        float $latitude,
        float $longitude
    ): self {
        $entregadorMaisProximo = self::fromQuery(
            "SELECT
                transportadores_raios.id_colaborador,
                transportadores_raios.id,
                transportadores_raios.raio,
                distancia_geolocalizacao(
                    :latitude,
                    :longitude,
                    transportadores_raios.latitude,
                    transportadores_raios.longitude
                ) * 1000 AS `distancia`
            FROM transportadores_raios
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                AND tipo_frete.categoria = 'ML'
                AND tipo_frete.tipo_ponto = 'PM'
            WHERE transportadores_raios.id_cidade = :id_cidade
                AND transportadores_raios.esta_ativo
            HAVING `distancia` <= transportadores_raios.raio
            ORDER BY `distancia` ASC
            LIMIT 1;",
            ['id_cidade' => $idCidade, 'latitude' => $latitude, 'longitude' => $longitude]
        )->first();
        if (empty($entregadorMaisProximo)) {
            throw new NotFoundHttpException('Não foi possível encontrar um entregador próximo');
        }

        return $entregadorMaisProximo;
    }
    public static function buscaRaiosDeCidades(int $idCidade): array
    {
        $raios = DB::select(
            "SELECT
                transportadores_raios.latitude,
                transportadores_raios.longitude,
                transportadores_raios.raio
            FROM transportadores_raios
            JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
            WHERE transportadores_raios.id_cidade = :id_cidade
                AND transportadores_raios.esta_ativo = 1
                AND transportadores_raios.raio > 0
                AND tipo_frete.categoria = 'ML'
                AND tipo_frete.tipo_ponto = 'PM'",
            ['id_cidade' => $idCidade]
        );

        return $raios;
    }
    public static function buscaRaiosDoColaboradorAtivosOuNao(int $idColaborador, bool $estaAtivo): array
    {
        $raios = DB::selectColumns(
            "SELECT transportadores_raios.id
            FROM transportadores_raios
            WHERE transportadores_raios.id_colaborador = :id_colaborador
                AND transportadores_raios.esta_ativo <> :esta_ativo;",
            ['id_colaborador' => $idColaborador, 'esta_ativo' => (int) $estaAtivo]
        );

        return $raios;
    }
    public static function consultaIdRaioDaCidadeDoColaborador(int $idColaborador, int $idCidade): ?int
    {
        $idRaio = DB::selectOneColumn(
            "SELECT transportadores_raios.id
            FROM transportadores_raios
            WHERE transportadores_raios.id_cidade = :id_cidade
                AND transportadores_raios.id_colaborador = :id_colaborador
                AND transportadores_raios.esta_ativo
            LIMIT 1;",
            ['id_cidade' => $idCidade, 'id_colaborador' => $idColaborador]
        );

        return $idRaio;
    }
    public static function removeRaiosDeOutrasCidadesDoColaboraboradorSemVerificar(
        int $idColaborador,
        int $idCidade
    ): void {
        DB::delete(
            "DELETE FROM transportadores_raios
            WHERE transportadores_raios.id_colaborador = :id_colaborador
                AND transportadores_raios.id_cidade <> :id_cidade;",
            ['id_colaborador' => $idColaborador, 'id_cidade' => $idCidade]
        );
    }
    public static function buscaRaiosDetalhadosDoColaborador(): array
    {
        $raios = DB::select(
            "SELECT
                transportadores_raios.id_cidade,
                transportadores_raios.latitude,
                transportadores_raios.longitude,
                transportadores_raios.raio,
                transportadores_raios.esta_ativo,
                municipios.nome,
                municipios.uf
            FROM transportadores_raios
            JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            WHERE transportadores_raios.id_colaborador = :id_colaborador;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );

        return $raios;
    }
    public static function buscaCoberturaDetalhadaDaCidade(int $idCidade, int $idColaborador): array
    {
        $raios = DB::select(
            "SELECT
                transportadores_raios.id AS `id_raio`,
                CONCAT(
                    '(', tipo_frete.id_colaborador, ') ',
                    tipo_frete.nome,
                    IF(tipo_frete.tipo_ponto = 'PM', COALESCE(CONCAT(' @ ', transportadores_raios.apelido), ''), '')
                ) AS `nome_tipo_frete`,
                tipo_frete.tipo_ponto,
                IF(
                    tipo_frete.tipo_ponto = 'PP',
                    configuracoes.tamanho_raio_padrao_ponto_parado,
                    transportadores_raios.raio
                ) AS `raio`,
                IF(tipo_frete.tipo_ponto = 'PP', tipo_frete.latitude, transportadores_raios.latitude) AS `latitude`,
                IF(tipo_frete.tipo_ponto = 'PP', tipo_frete.longitude, transportadores_raios.longitude) AS `longitude`,
                transportadores_raios.id_colaborador,
                transportadores_raios.id_colaborador = :id_colaborador AS `eh_colaborador_atual`
            FROM transportadores_raios
            JOIN configuracoes
            JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
            WHERE transportadores_raios.id_cidade = :id_cidade
                AND (
                    tipo_frete.categoria = 'ML' AND transportadores_raios.esta_ativo
                    OR tipo_frete.id_colaborador = :id_colaborador
                );",
            ['id_cidade' => $idCidade, 'id_colaborador' => $idColaborador]
        );

        return $raios;
    }
    public static function buscaCidadesAtendidasPeloEntregadorOuPontoDeColeta(
        int $idColaborador,
        ?int $idCidade = null
    ): array {
        $where = '';
        $binds = [];
        if ($idCidade) {
            $where = ' AND transportadores_raios.id_cidade = :idCidade ';
            $binds[':idCidade'] = $idCidade;
        }
        $sql = "SELECT
                municipios.id,
                municipios.nome,
                municipios.uf,
                municipios.latitude,
                municipios.longitude,
                (
                    SELECT
                        COUNT(entregas_faturamento_item.id)
                    FROM entregas
                    INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                    INNER JOIN transacao_financeiras_metadados ON
                        entregas_faturamento_item.id_transacao = transacao_financeiras_metadados.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    WHERE
                        entregas_faturamento_item.situacao <> 'EN'
                        AND entregas.situacao = 'EN'
                        AND entregas.id_tipo_frete = tipo_frete.id
                        AND transportadores_raios.id_cidade  = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.id_cidade')),(
                            SELECT
                                municipios.id
                            FROM municipios
                            WHERE
                                municipios.nome = JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.cidade'))
                                AND municipios.uf = JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor,'$.uf'))
                            LIMIT 1
                        ))
                ) quantidade_produtos
            FROM tipo_frete
            INNER JOIN transportadores_raios ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
            INNER JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            WHERE
                (
                    tipo_frete.id_colaborador = :idColaborador
                    OR tipo_frete.id_colaborador_ponto_coleta = :idColaborador
                )
                $where
            GROUP BY transportadores_raios.id_cidade
            ORDER BY quantidade_produtos DESC;";

        $binds[':idColaborador'] = $idColaborador;

        $dados = DB::select($sql, $binds);

        return $dados;
    }
    public static function buscaEntregadorDoSantosExpressQueAtendeColaborador(
        int $idCidade,
        float $latitude,
        float $longitude
    ): ?array {
        $resultado = DB::selectOne(
            "SELECT
                tipo_frete.id AS `id_tipo_frete`,
                tipo_frete.id_colaborador_ponto_coleta,
                transportadores_raios.id AS `id_raio`,
                transportadores_raios.raio,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro,
                transportadores_raios.valor,
                distancia_geolocalizacao(
                    :latitude,
                    :longitude,
                    transportadores_raios.latitude,
                    transportadores_raios.longitude
                ) * 1000 AS `distancia`
            FROM transportadores_raios
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador_ponto_coleta = :id_colaborador_ponto_coleta
                AND tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                AND tipo_frete.categoria = 'ML'
                AND tipo_frete.tipo_ponto = 'PM'
            WHERE transportadores_raios.id_cidade = :id_cidade
                AND transportadores_raios.esta_ativo
            HAVING distancia <= transportadores_raios.raio
            ORDER BY `distancia` ASC
            LIMIT 1;",
            [
                'id_colaborador_ponto_coleta' => TipoFrete::ID_COLABORADOR_SANTOS_EXPRESS,
                'id_cidade' => $idCidade,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]
        );

        return $resultado;
    }
}
