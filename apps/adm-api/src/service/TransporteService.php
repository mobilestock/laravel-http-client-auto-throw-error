<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\model\Transportes;
use MobileStock\repository\FotosRepository;
use PDO;

class TransporteService extends Transportes
{
    public function salva(PDO $conexao): bool
    {
        $transportadoraExiste =
            isset($this->id_colaborador) && $this->situacao == 'PR' && $this->tipo_transporte != 'ENTREGADOR';

        $geradorSQL = new GeradorSql($this);
        $sql = $transportadoraExiste ? $geradorSQL->updatePorCampo(['id_colaborador']) : $geradorSQL->insert();
        $bind = $geradorSQL->bind;

        $stmt = $conexao->prepare($sql);
        return $stmt->execute($bind);
    }
    public static function buscaEntregasPendentesDeDadosDeRastreio(): array
    {
        $resposta = DB::select(
            "SELECT
        DATE_FORMAT(entregas.data_criacao, '%d/%m/%Y %H:%i:%s') data_inicio,
        entregas.id id_entrega,
        entregas.volumes,
        colaboradores.razao_social cliente,
        (
            SELECT JSON_UNQUOTE(JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.cidade'))
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
            ORDER BY transacao_financeiras_metadados.id_transacao ASC
            LIMIT 1
        ) cidade,
        colaboradores.telefone,
        colaboradores.id id_cliente,
        COUNT(entregas_faturamento_item.uuid_produto) produtos,
        (
            SELECT
                colaboradores.razao_social
            FROM
                colaboradores
            WHERE
                colaboradores.id = entregas.id_transporte
        ) ultima_transportadora,
        entregas.id_transporte id_transportadora
        FROM entregas
        INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
        INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
        LEFT JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
		WHERE entregas.situacao = 'EX'
        AND tipo_frete.emitir_nota_fiscal = 1
        AND NOT EXISTS (
            SELECT 1 FROM entregas_transportadoras WHERE entregas.id = entregas_transportadoras.id_entrega
        )
        GROUP BY entregas.id
        ORDER BY entregas.data_criacao DESC;"
        );

        return $resposta;
    }

    public static function listaFretesDisponiveis(): array
    {
        $fretes = DB::select(
            "SELECT
                COUNT(entregas_faturamento_item.id) quantidade_de_items,
                COALESCE(
                    SUM(
                        (
                            SELECT
                                calcula_percentual_por_km(tipo_frete.id)
                        ) / 100 * produtos.valor_custo_produto
                    ) + transportadores_raios.valor_entrega,
                0) valor,
                municipios.nome cidade,
                municipios.uf
            FROM entregas
            INNER JOIN transportadores_raios ON transportadores_raios.id = entregas.id_raio
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            WHERE
                entregas.situacao = 'EX'
                AND tipo_frete.tipo_ponto = 'PM'
            GROUP BY transportadores_raios.id_cidade
            ORDER BY entregas_faturamento_item.data_alteracao DESC"
        );

        return $fretes;
    }

    public static function buscaFretesACaminho(): array
    {
        $dados = DB::select(
            "SELECT
                                    tipo_frete.nome,
                                    COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') AS foto_perfil,
                                    colaboradores.telefone,
                                    (
                                        SELECT transacao_financeiras_metadados.valor
                                        FROM transacao_financeiras_metadados
                                        WHERE transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                                            AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                                        ORDER BY transacao_financeiras_metadados.id_transacao ASC
                                        LIMIT 1
                                    ) AS `json_endereco`,
                                    tipo_frete.latitude,
                                    tipo_frete.longitude,
                                    tipo_frete.horario_de_funcionamento,
                                    entregas.volumes
                                FROM entregas
                                JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                                JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                                JOIN tipo_frete ON tipo_frete.id_colaborador = entregas.id_cliente
                                WHERE entregas.situacao = 'PT'
                                    AND entregas.id_transporte = :id_colaborador
                                GROUP BY entregas.id
                                ORDER BY entregas.data_atualizacao DESC;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );
        $dados = array_map(function (array $frete): array {
            $frete['cidade'] = $frete['endereco']['cidade'];
            $endereco = trim($frete['endereco']['logradouro']);
            $endereco .= ", Nº {$frete['endereco']['numero']}";
            $endereco .= ", {$frete['endereco']['bairro']}";
            $frete['endereco'] = $endereco;

            return $frete;
        }, $dados);

        return $dados;
    }
    public static function buscaFretesEntregues(): array
    {
        $entregas = DB::select(
            "SELECT
                entregas.id AS id_entrega,
                DATE_FORMAT(entregas.data_atualizacao, '%d/%m/%Y - %k:%i') AS data_entrega,
                tipo_frete.nome AS ponto_nome,
                colaboradores.telefone AS ponto_telefone,
                COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') AS ponto_foto_perfil,
                (
                    SELECT transacao_financeiras_metadados.valor
                    FROM transacao_financeiras_metadados
                    WHERE transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                        AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                    ORDER BY transacao_financeiras_metadados.id_transacao ASC
                    LIMIT 1
                ) AS json_ponto_endereco,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'uuid_produto', entregas_faturamento_item.uuid_produto,
                        'valor_custo_produto', produtos.valor_custo_produto
                    )),
                    ']'
                ) AS json_produtos,
                calcula_percentual_por_km(tipo_frete.id) / 100  AS float_percentual_km
            FROM entregas_etiquetas
            JOIN entregas ON entregas.id = entregas_etiquetas.id_entrega
            JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            JOIN tipo_frete ON tipo_frete.id_colaborador = entregas.id_cliente
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            WHERE entregas.situacao = 'EN'
                AND entregas.id_transporte = :id_colaborador
            GROUP BY entregas.id
            ORDER BY entregas.id DESC;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );
        $entregas = array_map(function (array $entrega): array {
            $entrega['ponto_cidade'] = $entrega['ponto_endereco']['cidade'];
            $endereco = trim($entrega['ponto_endereco']['logradouro']);
            $endereco .= ", Nº {$entrega['ponto_endereco']['numero']}";
            $endereco .= ", {$entrega['ponto_endereco']['bairro']}";
            $entrega['ponto_endereco'] = $endereco;
            $valores = array_map(
                fn(float $valor): float => round($valor * $entrega['percentual_km'], 2),
                array_column($entrega['produtos'], 'valor_custo_produto')
            );
            $entrega['volumes'] = [
                'quantidade' => count($entrega['produtos']),
                'valor_frete' => round(array_sum($valores), 2),
            ];
            unset($entrega['produtos'], $entrega['percentual_km']);

            return $entrega;
        }, $entregas);

        return $entregas;
    }
    public static function verificaCadastroTransportadora(PDO $conn, int $idColaborador): ?string
    {
        $stmt = $conn->prepare("SELECT
                                    transportes.situacao
                                FROM transportes
                                WHERE transportes.id_colaborador = :id_colaborador");
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $situacao = $stmt->fetch(PDO::FETCH_ASSOC)['situacao'];
        return $situacao;
    }

    public static function buscaTransportadoras(PDO $conexao): array
    {
        $sql = $conexao->prepare("SELECT
                                    transportes.id_colaborador,
                                    colaboradores.razao_social
                                FROM
                                    transportes
                                LEFT JOIN
                                    colaboradores
                                ON
                                    colaboradores.id = transportes.id_colaborador");
        $sql->execute();
        $resposta = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $resposta;
    }
    public static function buscaRastreaveis(PDO $conexao)
    {
        $sql = $conexao->prepare("SELECT
                                    DATE_FORMAT(entregas_transportadoras.data_criacao, '%d/%m/%Y %H:%i:%s') data_criacao,
                                    entregas_transportadoras.id_entrega,
                                    (
                                        SELECT razao_social FROM colaboradores WHERE colaboradores.id = id_transportadora
                                    )transportadora,
                                    entregas_transportadoras.cnpj,
                                    entregas_transportadoras.nota_fiscal
                                FROM
                                    entregas_transportadoras");
        $sql->execute();
        $resposta = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $resposta;
    }
    public static function buscaDocumentosEntregador(PDO $conexao, int $idColaborador, bool $pontoParado): array
    {
        $primeiraChave = $pontoParado ? 'foto_documento_comprovante' : 'foto_documento_veiculo';
        $primeiroTipo = $pontoParado ? 'COMPROVANTE_ENDERECO' : 'REGISTRO_VEICULO';
        $segundaChave = $pontoParado ? 'foto_documento_identidade' : 'foto_documento_habilitacao';
        $segundoTipo = $pontoParado ? 'CEDULA_IDENTIDADE' : 'CARTEIRA_HABILITACAO';

        $stmt = $conexao->prepare(
            "SELECT
                (
                    SELECT colaboradores_documentos.url_documento
                    FROM colaboradores_documentos
                    WHERE colaboradores_documentos.tipo_documento = '{$primeiroTipo}'
                        AND colaboradores_documentos.id_colaborador = :id_colaborador
                ) AS {$primeiraChave},
                (
                    SELECT colaboradores_documentos.url_documento
                    FROM colaboradores_documentos
                    WHERE colaboradores_documentos.tipo_documento = '{$segundoTipo}'
                        AND colaboradores_documentos.id_colaborador = :id_colaborador
                ) AS {$segundaChave}"
        );
        $stmt->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $resposta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!isset($resposta[$primeiraChave]) || !isset($resposta[$segundaChave])) {
            throw new Exception('Nenhum documento encontrado para o entregador selecionado.');
        }
        $resposta[$primeiraChave] = FotosRepository::gerarUrlAssinadaAwsS3(
            $resposta[$primeiraChave],
            'ARQUIVOS_PRIVADOS'
        );
        $resposta[$segundaChave] = FotosRepository::gerarUrlAssinadaAwsS3(
            $resposta[$segundaChave],
            'ARQUIVOS_PRIVADOS'
        );
        return $resposta;
    }
    public static function buscaListaEntregadores(): array
    {
        $lista = DB::select(
            "SELECT
                tipo_frete.id,
                (
                    SELECT usuarios.id
                    FROM usuarios
                    WHERE usuarios.id_colaborador = tipo_frete.id_colaborador
                ) AS id_usuario,
                tipo_frete.id_colaborador,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores_documentos.url_documento AS `foto_documento_habilitacao`,
                veiculo_colaboradores_documentos.url_documento AS `foto_documento_veiculo`,
                tipo_frete.categoria,
                CONCAT(colaboradores_enderecos.cidade, ' - ', colaboradores_enderecos.uf) AS `local`,
                CONCAT('[',
                    GROUP_CONCAT(DISTINCT
                        JSON_OBJECT(
                            'id_raio', transportadores_raios.id,
              	            'apelido', transportadores_raios.apelido,
                            'id_colaborador', colaboradores.id,
                            'id_cidade', transportadores_raios.id_cidade,
                            'cidade', CONCAT(municipios.nome, ' - ', municipios.uf),
                            'valor_entrega', transportadores_raios.valor_entrega,
                            'esta_ativo', transportadores_raios.esta_ativo,
                            'prazo_forcar_entrega', transportadores_raios.prazo_forcar_entrega,
                            'dias_entregar_cliente', transportadores_raios.dias_entregar_cliente,
                            'dias_margem_erro', transportadores_raios.dias_margem_erro,
                            'valor_coleta', transportadores_raios.valor_coleta,
                            'latitude', municipios.latitude,
                            'longitude', municipios.longitude
                        )
                    ),
                ']') AS `json_cidades`,
                COALESCE(ponto_coleta_colaboradores.razao_social, '(Nenhum)') AS `ponto_coleta`,
                pontos_coleta.id IS NOT NULL AS `eh_ponto_coleta`
            FROM tipo_frete
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            JOIN transportes ON transportes.id_colaborador = tipo_frete.id_colaborador
            JOIN transportadores_raios ON transportadores_raios.id_colaborador = tipo_frete.id_colaborador
            JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            JOIN colaboradores_documentos ON colaboradores_documentos.id_colaborador = colaboradores.id
            AND colaboradores_documentos.tipo_documento = 'CARTEIRA_HABILITACAO'
            JOIN colaboradores_documentos AS `veiculo_colaboradores_documentos` ON veiculo_colaboradores_documentos.id_colaborador = colaboradores.id
                AND veiculo_colaboradores_documentos.tipo_documento = 'REGISTRO_VEICULO'
            LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador
            LEFT JOIN colaboradores AS ponto_coleta_colaboradores ON ponto_coleta_colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
            WHERE tipo_frete.tipo_ponto = 'PM'
                AND tipo_frete.categoria = 'ML'
            GROUP BY tipo_frete.id_colaborador
            ORDER BY tipo_frete.categoria, tipo_frete.id DESC"
        );

        return $lista;
    }
    public static function buscaDadosParaControlePontoColeta(string $situacao): array
    {
        $data = DB::select(
            "SELECT
                entregas.id entrega,
                CONCAT(
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.bairro'),
                    ' - ',
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade')) cidade,
                tipo_frete.nome entregador,
                COUNT(DISTINCT entregas_faturamento_item.uuid_produto) quantidade,
                entregas.situacao,
                colaboradores.razao_social cliente,
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.logradouro') endereco,
                colaboradores.telefone,
                EXISTS(
					SELECT 1
                    FROM troca_pendente_agendamento
                    WHERE troca_pendente_agendamento.id_cliente = entregas_faturamento_item.id_cliente
                ) tem_troca
            FROM entregas
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
            INNER JOIN transacao_financeiras_metadados ON
                transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
            WHERE pontos_coleta.id_colaborador = :idColaborador
            AND entregas.situacao = :situacao
            GROUP BY entregas.id
            ORDER BY entregas.situacao DESC",
            [
                ':situacao' => $situacao,
                ':idColaborador' => Auth::user()->id_colaborador,
            ]
        );

        return $data;
    }
}
