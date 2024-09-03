<?php

namespace App\Models;

use App\Casts\OnlyNumbers;
use App\Enum\BaseProdutosEnum;
use App\Enum\TiposRemarcacaoEnum;
use DateInterval;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\Loja
 *
 * @property int $id_revendedor
 * @property BaseProdutosEnum $base_produtos
 * @property string $nome
 * @property string $url
 * @property string $telefone
 * @property TiposRemarcacaoEnum $tipo_remarcacao
 * @property \Illuminate\Support\Carbon|null $data_criacao
 * @property \Illuminate\Support\Carbon|null $data_atualizacao
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Loja newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Loja newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Loja query()
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereBaseProdutos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereDataAtualizacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereDataCriacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereIdRevendedor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja wherePercentualRemarcacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loja whereUrl($value)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LojaPreco> $precos
 * @property-read int|null $precos_count
 *
 * @mixin \Eloquent
 */
class Loja extends Model implements JWTSubject, AuthenticatableInterface
{
    use Authenticatable;

    protected $primaryKey = 'id_revendedor';

    public $auth;
    public $token;

    protected $casts = [
        'base_produtos' => BaseProdutosEnum::class,
        'tipo_remarcacao' => TiposRemarcacaoEnum::class,
        'telefone' => OnlyNumbers::class,
    ];

    protected $fillable = ['id_revendedor', 'telefone', 'base_produtos', 'nome', 'url', 'tipo_remarcacao'];

    public function getJWTIdentifier()
    {
        return $this->{$this->primaryKey};
    }

    public function getJWTCustomClaims(): array
    {
        $dados = $this->only('id_revendedor', 'url');
        $dados['permissao'] = 'LOJA';

        return $dados;
    }

    public function aplicaRemarcacao(float $valor): float
    {
        foreach ($this->precos as $key => $preco) {
            $ate = $preco->ate;
            if (is_null($preco->ate)) {
                $ate = PHP_INT_MAX;
            }

            if ($this->attributes['tipo_remarcacao'] === 'PERCENTUAL' && $ate > $valor) {
                return round($valor + round(($valor * $preco->remarcacao) / 100), 2);
            }

            if ($valor === 0.0) {
                return 0;
            }
            if ($this->attributes['tipo_remarcacao'] === 'VALOR' && $ate > $valor) {
                return round($valor + $preco->remarcacao, 2);
            }
        }
        throw new Exception('Não foi possível aplicar a remarcação');
    }

    public function precos(): HasMany
    {
        return $this->hasMany(LojaPreco::class, 'id_revendedor');
    }

    public static function chaveCache(string $url): string
    {
        $urlTratada = str_replace('.', '_', $url);
        $urlTratada = mb_strtolower($urlTratada);

        return "loja.{$urlTratada}";
    }

    public static function consultaLoja(string $urlLoja): void
    {
        $urlTratada = self::chaveCache($urlLoja);
        // os preços precisam estar ordenados desta maneira para aplicar a remarcação
        $loja = Cache::remember("$urlTratada", new DateInterval('P1D'), function () use ($urlLoja) {
            $loja = self::fromQuery(
                "SELECT
                    lojas.id_revendedor,
                    lojas.nome,
                    lojas.url,
                    lojas.base_produtos,
                    lojas.tipo_remarcacao,
                    lojas.data_criacao,
                    lojas.data_atualizacao,
                    lojas.telefone,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'remarcacao', lojas_precos.remarcacao,
                                'ate', lojas_precos.ate,
                                'id_remarcacao', lojas_precos.id
                            )
                            ORDER BY ate IS NULL ASC, lojas_precos.ate ASC
                        ),
                        ']'
                    ) AS `json_precos`
                FROM lojas
                INNER JOIN lojas_precos ON lojas_precos.id_revendedor = lojas.id_revendedor
                WHERE lojas.url = :urlLoja
                GROUP BY lojas.id_revendedor;",
                ['urlLoja' => $urlLoja]
            )->first();

            if (empty($loja)) {
                abort(Response::HTTP_NOT_FOUND, 'Loja não encontrada');
            }

            $models = LojaPreco::hydrate($loja->precos);
            unset($loja->precos);
            $loja->setRelation('precos', $models);
            $loja->setHidden(['data_criacao', 'data_atualizacao']);

            $dadosAutenticacao = Http::mobileStock()
                ->post('api_cliente/autenticacao/med/autentica', [
                    'app_auth_token' => env('APP_AUTH_TOKEN'),
                    'id_revendedor' => $loja->id_revendedor,
                ])
                ->json();
            $loja->token = $dadosAutenticacao['token'];
            $loja->auth = $dadosAutenticacao['auth'];
            return $loja;
        });
        app()->instance(Loja::class, $loja);
    }
}
