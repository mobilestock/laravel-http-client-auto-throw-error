<?php
namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\AvaliacaoTipoFrete;
use MobileStock\model\TipoFrete;
use PDO;

class AvaliacaoTipoFreteService extends AvaliacaoTipoFrete
{
    public function inserir(PDO $conexao)
    {
        $dadosExtrair = $this->extrair();
        $keys = array_keys(array_filter($dadosExtrair));
        $sql =
            'INSERT INTO avaliacao_tipo_frete (' .
            implode(',', $keys) .
            ') VALUES (' .
            implode(',', array_map(fn($k) => ":{$k}", $keys)) .
            ');';
        $bind = [];
        foreach ($keys as $key) {
            $bind[$key] = $dadosExtrair[$key];
        }
        $stmt = $conexao->prepare($sql);
        return $stmt->execute($bind);
    }

    public function atualizar(PDO $conexao, int $id)
    {
        $dadosExtrair = $this->extrair();
        $keys = array_keys(array_filter($dadosExtrair));
        $sql =
            'UPDATE avaliacao_tipo_frete SET ' .
            implode(', ', array_map(fn($k) => "{$k} = :{$k}", $keys)) .
            " WHERE avaliacao_tipo_frete.id = {$id}";
        $bind = [];
        foreach ($keys as $key) {
            $bind[$key] = $dadosExtrair[$key];
        }
        $stmt = $conexao->prepare($sql);
        return $stmt->execute($bind);
    }

    public static function buscaAvaliacaoColaborador(PDO $conexao, int $idColaborador, int $idPonto)
    {
        $stmt = $conexao->prepare(
            "SELECT
                avaliacao_tipo_frete.id,
                avaliacao_tipo_frete.id_colaborador,
                avaliacao_tipo_frete.id_tipo_frete,
                avaliacao_tipo_frete.nota_atendimento,
                avaliacao_tipo_frete.nota_localizacao,
                avaliacao_tipo_frete.comentario,
                avaliacao_tipo_frete.visualizado_em
            FROM avaliacao_tipo_frete
            WHERE
                avaliacao_tipo_frete.id_colaborador = :idColaborador AND
                avaliacao_tipo_frete.id_tipo_frete = :idTipoFrete"
        );
        $stmt->execute([
            ':idColaborador' => $idColaborador,
            ':idTipoFrete' => $idPonto,
        ]);
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $consulta;
    }

    public static function buscaAvaliacaoPendenteColaborador(): ?array
    {
        $idTipoFrete = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                avaliacao_tipo_frete.id,
                avaliacao_tipo_frete.id_tipo_frete id_ponto,
                tipo_frete.nome,
                COALESCE(
                    IF(LENGTH(tipo_frete.foto) > 0, tipo_frete.foto, NULL),
                    IF(LENGTH(colaboradores.foto_perfil) > 0, colaboradores.foto_perfil, NULL),
                    CONCAT('{$_ENV['URL_MOBILE']}', 'images/avatar-padrao-mobile.jpg')
                ) foto,
                avaliacao_tipo_frete.comentario,
                avaliacao_tipo_frete.nota_atendimento,
                avaliacao_tipo_frete.nota_localizacao,
                CONCAT(
                    colaboradores_enderecos.logradouro, ', ',
                    colaboradores_enderecos.numero, ', ',
                    colaboradores_enderecos.bairro, ' - ',
                    municipios.nome, ' (',
                    municipios.uf, ')'
                ) endereco
            FROM avaliacao_tipo_frete
            INNER JOIN tipo_frete ON tipo_frete.id = avaliacao_tipo_frete.id_tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
            WHERE
                avaliacao_tipo_frete.visualizado_em IS NULL AND
                avaliacao_tipo_frete.id_colaborador = :idColaborador AND
                tipo_frete.id NOT IN ($idTipoFrete)
            ORDER BY avaliacao_tipo_frete.id
            LIMIT 1";

        $consulta = DB::selectOne($query, [':idColaborador' => Auth::user()->id_colaborador]);

        return $consulta ?? null;
    }

    public static function adiarAvaliacao(PDO $conexao, $idColaborador, $idPonto)
    {
        $stmt = $conexao->prepare(
            "UPDATE avaliacao_tipo_frete
            SET visualizado_em = NOW()
            WHERE
                avaliacao_tipo_frete.id_colaborador = :idColaborador AND
                avaliacao_tipo_frete.id_tipo_frete = :idPonto"
        );
        return $stmt->execute([
            ':idColaborador' => $idColaborador,
            ':idPonto' => $idPonto,
        ]);
    }

    public static function buscaAvaliacoesPonto(PDO $conexao, $idColaborador)
    {
        $stmt = $conexao->prepare(
            "SELECT
                avaliacao_tipo_frete.id,
                COALESCE(
                    colaboradores.foto_perfil,
                    '" .
                $_ENV['URL_MOBILE'] .
                "images/avatar-padrao-mobile.jpg'
                ) foto_avaliador,
                colaboradores.usuario_meulook nome_avaliador,
                avaliacao_tipo_frete.comentario,
                avaliacao_tipo_frete.nota_atendimento,
                avaliacao_tipo_frete.nota_localizacao,
                DATE_FORMAT(avaliacao_tipo_frete.criado_em, '%d/%m/%Y') criado_em
            FROM avaliacao_tipo_frete
            INNER JOIN tipo_frete ON tipo_frete.id = avaliacao_tipo_frete.id_tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = avaliacao_tipo_frete.id_colaborador
            WHERE
                tipo_frete.id_colaborador = :idColaborador AND
                avaliacao_tipo_frete.nota_atendimento > 0 AND
                avaliacao_tipo_frete.nota_localizacao  > 0
            ORDER BY avaliacao_tipo_frete.criado_em DESC"
        );
        $stmt->execute([':idColaborador' => $idColaborador]);
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($consulta)) {
            return [];
        }

        $consulta = array_map(function ($item) {
            $item['nota_atendimento'] = (int) $item['nota_atendimento'];
            $item['nota_localizacao'] = (int) $item['nota_localizacao'];
            return $item;
        }, $consulta);

        return $consulta;
    }
}
