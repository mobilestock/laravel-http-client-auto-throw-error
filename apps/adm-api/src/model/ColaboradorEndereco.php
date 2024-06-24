<?php

namespace MobileStock\model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property int $id_usuario
 * @property int $id_colaborador
 * @property int $id_cidade
 * @property ?string $apelido
 * @property string $nome_destinatario
 * @property string $telefone_destinatario
 * @property bool $esta_verificado
 * @property bool $eh_endereco_padrao
 * @property ?string $cep
 * @property string $logradouro
 * @property string $numero
 * @property ?string $complemento
 * @property ?string $ponto_de_referencia
 * @property string $bairro
 * @property string $cidade
 * @property string $uf
 * @property float $latitude
 * @property float $longitude
 */
class ColaboradorEndereco extends Model
{
    protected $table = 'colaboradores_enderecos';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    protected $fillable = [
        'id_usuario',
        'id_colaborador',
        'id_cidade',
        'apelido',
        'nome_destinatario',
        'telefone_destinatario',
        'esta_verificado',
        'eh_endereco_padrao',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'ponto_de_referencia',
        'bairro',
        'cidade',
        'uf',
        'latitude',
        'longitude',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saving(function (self $colaboradorEndereco) {
            if ($colaboradorEndereco->isDirty('eh_endereco_padrao') && $colaboradorEndereco->eh_endereco_padrao) {
                $where = '';

                if (!empty($colaboradorEndereco->id)) {
                    $where = ' AND colaboradores_enderecos.id <> :id_endereco';
                    $binds['id_endereco'] = $colaboradorEndereco->id;
                }

                $query = "UPDATE colaboradores_enderecos SET
                        colaboradores_enderecos.eh_endereco_padrao = 0,
                        colaboradores_enderecos.id_usuario = :id_usuario
                    WHERE
                        colaboradores_enderecos.id_colaborador = :id_colaborador
                        AND colaboradores_enderecos.eh_endereco_padrao = 1
                        $where";

                $binds[':id_colaborador'] = $colaboradorEndereco->id_colaborador;
                $binds[':id_usuario'] = Auth::user()->id;

                DB::update($query, $binds);

                $colaborador = ColaboradorModel::buscaInformacoesColaborador($colaboradorEndereco->id_colaborador);
                if ($colaborador->id_tipo_entrega_padrao > 0) {
                    $colaborador->id_tipo_entrega_padrao = 0;
                    $colaborador->update();
                }
            }
        });
    }

    public static function buscarEndereco(int $idEndereco): ?self
    {
        $endereco = self::fromQuery(
            "SELECT
                colaboradores_enderecos.id,
                colaboradores_enderecos.id_colaborador,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.latitude,
                colaboradores_enderecos.longitude,
                colaboradores_enderecos.eh_endereco_padrao
            FROM colaboradores_enderecos
            WHERE colaboradores_enderecos.id = :id_endereco",
            ['id_endereco' => $idEndereco]
        )->first();

        return $endereco;
    }

    public static function buscaEnderecoPadraoColaborador(?int $idColaborador = null): self
    {
        $idColaborador ??= Auth::user()->id_colaborador;
        $endereco = self::fromQuery(
            "SELECT
                colaboradores_enderecos.id,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.id_usuario,
                colaboradores_enderecos.apelido,
                colaboradores_enderecos.nome_destinatario,
                colaboradores_enderecos.telefone_destinatario,
                colaboradores_enderecos.eh_endereco_padrao,
                colaboradores_enderecos.esta_verificado,
                colaboradores_enderecos.logradouro,
                colaboradores_enderecos.numero,
                colaboradores_enderecos.complemento,
                colaboradores_enderecos.ponto_de_referencia,
                colaboradores_enderecos.bairro,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf,
                colaboradores_enderecos.cep,
                colaboradores_enderecos.latitude,
                colaboradores_enderecos.longitude
            FROM colaboradores_enderecos
            WHERE colaboradores_enderecos.id_colaborador = :id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1",
            ['id_colaborador' => $idColaborador]
        )->first();

        if (empty($endereco)) {
            throw new BadRequestHttpException('Endereço padrão não encontrado');
        }

        return $endereco;
    }

    public static function removerEnderecoNaoVerificado(int $idColaborador): void
    {
        DB::delete(
            "DELETE FROM colaboradores_enderecos
        WHERE
            colaboradores_enderecos.id_colaborador = :id_colaborador
            AND colaboradores_enderecos.esta_verificado = 0",
            ['id_colaborador' => $idColaborador]
        );
    }

    public static function possuiEnderecoPadrao(int $idColaborador): bool
    {
        $temEnderecoPadrao = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM colaboradores_enderecos
                WHERE colaboradores_enderecos.id_colaborador = :id_cliente
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
            ) AS `tem_cidade_cadastrada`;",
            ['id_cliente' => $idColaborador]
        );

        return $temEnderecoPadrao;
    }

    public function salvarIdCidade(int $idCidade): void
    {
        $cidade = DB::selectOne(
            "SELECT
                municipios.nome,
                municipios.uf
            FROM municipios
            WHERE municipios.id = :id_cidade;",
            ['id_cidade' => $idCidade]
        );
        if (empty($cidade)) {
            throw new NotFoundHttpException('Cidade não encontrada');
        }

        $this->id_colaborador = Auth::user()->id_colaborador;
        $this->id_cidade = $idCidade;
        $this->eh_endereco_padrao = true;
        $this->cidade = $cidade['nome'];
        $this->uf = $cidade['uf'];
        $this->save();
    }

    /**
     * @return self|Collection<self>
     */
    public static function listarEnderecos(int $idColaborador, ?int $idEndereco = null)
    {
        $where = 'colaboradores_enderecos.id_colaborador = :id_colaborador';
        $bind = ['id_colaborador' => $idColaborador];

        if ($idEndereco) {
            $where .= ' AND colaboradores_enderecos.id = :id_endereco';
            $bind = array_merge($bind, ['id_endereco' => $idEndereco]);
        }

        $enderecos = self::fromQuery(
            "SELECT
                    colaboradores_enderecos.id,
                    colaboradores_enderecos.id_cidade,
                    colaboradores_enderecos.id_usuario,
                    colaboradores_enderecos.apelido,
                    colaboradores_enderecos.nome_destinatario,
                    colaboradores_enderecos.telefone_destinatario,
                    colaboradores_enderecos.eh_endereco_padrao,
                    colaboradores_enderecos.esta_verificado,
                    colaboradores_enderecos.logradouro,
                    colaboradores_enderecos.numero,
                    colaboradores_enderecos.complemento,
                    colaboradores_enderecos.ponto_de_referencia,
                    colaboradores_enderecos.bairro,
                    colaboradores_enderecos.cidade,
                    colaboradores_enderecos.uf,
                    colaboradores_enderecos.cep,
                    colaboradores_enderecos.latitude,
                    colaboradores_enderecos.longitude
                FROM colaboradores_enderecos
                WHERE $where
                    AND colaboradores_enderecos.esta_verificado = 1
                ORDER BY
                    colaboradores_enderecos.esta_verificado ASC,
                    colaboradores_enderecos.eh_endereco_padrao DESC,
                    colaboradores_enderecos.apelido IS NOT NULL ASC",
            $bind
        );

        if ($idEndereco) {
            return $enderecos->first();
        }

        return $enderecos;
    }

    public function definirEnderecoPadrao(int $idEndereco, int $idColaborador): void
    {
        $colaboradorEndereco = self::hydrate([
            [
                'id_colaborador' => $idColaborador,
                'id' => $idEndereco,
            ],
        ])->first();
        $colaboradorEndereco->eh_endereco_padrao = true;

        self::addGlobalScope(function (Builder $builder) {
            $builder->whereRaw(
                'colaboradores_enderecos.esta_verificado = 1
             AND colaboradores_enderecos.id_colaborador = ?',
                [Auth::user()->id_colaborador]
            );
        });
        $colaboradorEndereco->update();
    }

    public static function deletaLogsAlteracaoEndereco(): void
    {
        DB::delete(
            "DELETE FROM colaboradores_enderecos_logs
            WHERE colaboradores_enderecos_logs.data_criacao < DATE_SUB(NOW(), INTERVAL 6 MONTH)"
        );
    }
}
