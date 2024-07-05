<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\HttpClient;

/**
 * @property int $id
 * @property string $descricao
 * @property int $id_forncedor
 * @property bool $bloqueado
 * @property int $id_linha
 * @property string $data_entrada
 * @property bool $promocao
 * @property int $grade
 * @property string $forma
 * @property string $nome_comercial
 * @property float $preco_promocao
 * @property float $valor_custo_produto
 * @property int $id_usuario
 * @property int $tipo_grade
 * @property string $sexo
 * @property string $cores
 * @property bool $fora_de_linha
 * @property string $embalagem
 * @property string $outras_informacoes
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class Produto extends Model
{
    protected $fillable = [
        'descricao',
        'id_fornecedor',
        'bloqueado',
        'id_linha',
        'data_entrada',
        'promocao',
        'outras_informacoes',
        'forma',
        'embalagem',
        'nome_comercial',
        'preco_promocao',
        'valor_custo_produto',
        'id_usuario',
        'tipo_grade',
        'sexo',
        'cores',
        'fora_de_linha',
        'permitido_reposicao',
        'eh_moda',
    ];
    protected $casts = [
        'eh_moda' => 'boolean',
        'promocao' => 'boolean',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE = 82044;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO = 82042;

    protected static function boot()
    {
        parent::boot();

        self::updating(function (self $model) {
            if (!$model->isDirty('fora_de_linha') || $model->fora_de_linha) {
                return;
            }

            $ehExterno = DB::selectOneColumn(
                "SELECT EXISTS(
                SELECT 1
                FROM estoque_grade
                WHERE estoque_grade.id_responsavel <> 1
                    AND estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
            ) `existe_estoque_externo`;",
                [':id_produto' => $model->id]
            );

            if (!$ehExterno) {
                return;
            }
            $linhasAfetadas = DB::update(
                "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = 'Estoque zerado porque o produto foi colocado como fora de linha'
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;",
                [':id_produto' => $model->id]
            );

            if ($linhasAfetadas < 1) {
                throw new Exception('Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.');
            }
        });
    }

    public static function buscaTituloVideo(string $videoId): string
    {
        $http = new HttpClient();
        $url =
            'https://www.googleapis.com/youtube/v3/videos?' .
            http_build_query([
                'part' => 'snippet',
                'id' => $videoId,
                'key' => $_ENV['GOOGLE_TOKEN_PUBLICO'],
            ]);
        $http->get($url);
        $resposta = $http->body['items'][0]['snippet']['title'];
        return $resposta;
    }
}
