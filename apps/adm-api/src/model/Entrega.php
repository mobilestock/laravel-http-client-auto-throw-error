<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @property int $id
 * @property int $id_usuario
 * @property int $id_cliente
 * @property int $id_tipo_frete
 * @property int $id_transporte
 * @property int $id_raio
 * @property string $situacao
 * @property int $volumes
 * @property string $uuid_entrega
 * @property ?string $data_entrega
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class Entrega extends Model
{
    public const REGEX_ETIQUETA_CLIENTE = "/^C[0-9]+$/";
    /**
     * @deprecated
     * @issue Obsolescência programada: https://github.com/mobilestock/web/issues/3070
     */
    public const REGEX_ETIQUETA_CLIENTE_LEGADO = "/^[A-z0-9\-]{36}_[0-9]+_(TROCA|ENTREGA)$/";

    protected $fillable = ['situacao', 'id_usuario', 'id_cliente', 'id_tipo_frete', 'id_cidade'];

    protected static function boot(): void
    {
        parent::boot();

        self::creating([self::class, 'antesCriarEntrega']);
        self::updating([self::class, 'antesAtualizarEntrega']);
    }

    public function antesCriarEntrega(self $model): void
    {
        $existeOutraEntrega = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM entregas
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                WHERE entregas.id_tipo_frete = :id_tipo_frete
                    AND entregas.situacao = 'AB'
                    AND IF (
                        tipo_frete.tipo_ponto = 'PM',
                        entregas.id_raio = :id_raio,
                        entregas.id_cliente = :id_cliente
                    )
            ) AS `existe_outra_entrega`;",
            ['id_tipo_frete' => $model->id_tipo_frete, 'id_raio' => $model->id_raio, 'id_cliente' => $model->id_cliente]
        );

        if ($existeOutraEntrega) {
            throw new ConflictHttpException('Sistema não permite que exista duas entregas criadas.');
        }
    }
    public function antesAtualizarEntrega(self $model): void
    {
        if (!$model->isDirty('situacao')) {
            return;
        }

        if (
            $model->situacao === 'EN' &&
            in_array($model->id_tipo_frete, explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE))
        ) {
            $entregasItemsLista = DB::selectColumns(
                "SELECT
                    entregas_faturamento_item.uuid_produto
                FROM entregas_faturamento_item
                WHERE
                    entregas_faturamento_item.id_entrega = :idEntrega;",
                ['idEntrega' => $model->id]
            );

            if (!empty($entregasItemsLista)) {
                (new EntregasFaturamentoItem())->confirmaEntregaDeProdutos($entregasItemsLista);
            }
        } elseif ($model->situacao === 'EN' && $model->tipo_ponto === 'PM') {
            $produtos = DB::selectColumns(
                "SELECT
                    entregas_faturamento_item.uuid_produto
                FROM entregas_faturamento_item
                INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                    AND entregas.situacao IN ('EN', 'PT')
                WHERE entregas_faturamento_item.id_entrega = :idEntrega",
                ['idEntrega' => $model->id]
            );

            if (!empty($produtos)) {
                EntregasFaturamentoItem::confirmaConferencia($produtos);
            }
        }

        if (in_array($model->situacao, ['PT', 'EN'])) {
            /**
             * @issue: https://github.com/mobilestock/web/issues/3218
             */
            DB::delete(
                "DELETE FROM entregas_fechadas_temp
                WHERE entregas_fechadas_temp.id_entrega = :id_entrega;",
                ['id_entrega' => $model->id]
            );
        }

        if (in_array($model->getOriginal('situacao'), ['EX', 'AB']) && in_array($model->situacao, ['EX', 'PT', 'EN'])) {
            $where = '';
            $binds = Arr::only($model->toArray(), ['id_cliente', 'id_tipo_frete']);
            if (!empty($model->id_raio)) {
                $where = " AND EXISTS(
                    SELECT 1
                    FROM transportadores_raios
                    WHERE transportadores_raios.id = :id_raio
                        AND transportadores_raios.id_cidade = acompanhamento_temp.id_cidade
                ) ";
                $binds['id_raio'] = $model->id_raio;
            }

            DB::delete(
                "DELETE FROM acompanhamento_temp
                WHERE acompanhamento_temp.id_tipo_frete = :id_tipo_frete
                    AND acompanhamento_temp.id_destinatario = :id_cliente
                    $where;",
                $binds
            );
        }
    }
    /**
     *
     * Este método deve ser utilizado sempre que tiver interação com entrega física.
     * @param int $idEntrega
     * @param string $acao [BIPAGEM_PADRAO, FECHAR_ENTREGA]
     */
    public static function configuraNovaSituacao(int $idEntrega, string $acao): self
    {
        $entrega = self::fromQuery(
            "SELECT
                entregas.id,
                entregas.id_raio,
                entregas.id_cliente,
                entregas.id_tipo_frete,
                entregas.situacao,
                tipo_frete.tipo_ponto,
                tipo_frete.id_colaborador id_colaborador_tipo_frete,
                tipo_frete.id_colaborador_ponto_coleta
            FROM entregas
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            WHERE entregas.id = :idEntrega
        ",
            [
                'idEntrega' => $idEntrega,
            ]
        )->first();
        if (!$entrega) {
            throw new NotFoundHttpException('Não existe entrega para este id');
        }

        switch (true) {
            case Gate::allows('ENTREGADOR') &&
                $acao === 'BIPAGEM_PADRAO' &&
                $entrega->id_colaborador_tipo_frete !== Auth::user()->id_colaborador &&
                $entrega->id_colaborador_ponto_coleta !== Auth::user()->id_colaborador:
                throw new UnauthorizedHttpException('Bearer', 'Você não tem permissão para realizar esta ação');
            case Gate::allows('ADMIN') && $acao === 'FECHAR_ENTREGA' && $entrega->situacao === 'AB':
                $entrega->situacao = 'EX';
                break;
            case Gate::any(['ADMIN', 'ENTREGADOR']) &&
                $acao === 'BIPAGEM_PADRAO' &&
                in_array($entrega->situacao, ['AB', 'EX']) &&
                !$entrega->ehEntregaCliente():
                $entrega->situacao = 'PT';
                break;
            case ((Gate::allows('PONTO_RETIRADA') &&
                $entrega->id_colaborador_tipo_frete === Auth::user()->id_colaborador) ||
                (Gate::allows('ENTREGADOR') &&
                    in_array(Auth::user()->id_colaborador, [
                        $entrega->id_colaborador_ponto_coleta,
                        $entrega->id_colaborador_tipo_frete,
                    ]))) &&
                $acao === 'BIPAGEM_PADRAO' &&
                $entrega->situacao === 'PT':
                $entrega->situacao = 'EN';
                break;
            case Gate::allows('ADMIN') &&
                $acao === 'BIPAGEM_PADRAO' &&
                in_array($entrega->situacao, ['AB', 'EX']) &&
                $entrega->ehEntregaCliente():
                $entrega->situacao = 'EN';
                break;
            case Gate::allows('ADMIN') && $acao === 'BIPAGEM_PADRAO' && $entrega->situacao === 'PT':
                throw new ConflictHttpException('Esta entrega ja foi expedida.');
            case Gate::allows('ADMIN') && $acao === 'BIPAGEM_PADRAO' && $entrega->situacao === 'EN':
                throw new ConflictHttpException('Esta entrega ja foi entregue ao cliente.');
            case Gate::any(['PONTO_RETIRADA', 'ENTREGADOR']) &&
                $acao === 'BIPAGEM_PADRAO' &&
                $entrega->situacao === 'EN':
                throw new ConflictHttpException('Esta entrega ja foi bipada, bipe os itens para concluir o processo.');

            default:
                throw new Exception('Falha ao configurar entrega');
        }
        $entrega->save();
        return $entrega;
    }
    public function ehEntregaCliente(): bool
    {
        return in_array($this->id_tipo_frete, explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE));
    }
    public static function formataEtiquetaCliente(int $idCliente): string
    {
        $etiqueta = 'C' . $idCliente;
        return $etiqueta;
    }
}
