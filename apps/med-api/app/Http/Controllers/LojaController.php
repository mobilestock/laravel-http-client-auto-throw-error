<?php

namespace App\Http\Controllers;

use App\Models\Loja;
use App\Models\Usuario;
use App\Rules\PhoneNumberRule;
use DateTime;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

class LojaController extends Controller
{
    public function pesquisa(Request $request, Loja $loja, JsonResponse $response)
    {
        $resposta = Http::mobileStock()
            ->get('api_meulook/produtos/pesquisa', [...$request->all(), 'origem' => $loja->base_produtos->value])
            ->json();

        $resposta['produtos'] = array_map(function (array $produto) use ($loja): array {
            $produto['preco'] = $loja->aplicaRemarcacao($produto['preco']);
            $produto['preco_original'] = $loja->aplicaRemarcacao($produto['preco_original']);
            unset($produto['valor_parcela'], $produto['parcelas']);

            return $produto;
        }, $resposta['produtos']);

        return $response->setData($resposta);
    }

    public function autocompletePesquisa(Request $request, JsonResponse $response)
    {
        $resposta = Http::mobileStock()->get('api_meulook/produtos/autocomplete_pesquisa', $request->all())->json();

        return $response->setData($resposta);
    }

    public function telemetriaAutocompletePesquisa(Request $request)
    {
        Http::mobileStock()->post('api_meulook/produtos/criar_registro_pesquisa', $request->all());
    }

    public function produto(int $id, JsonResponse $response)
    {
        $produto = Http::mobileStock()
            ->get("api_meulook/publicacoes/produto/$id")
            ->json();
        unset($produto['valor_parcela'], $produto['parcelas']);

        return $response->setData($produto);
    }

    public function tenhoInteresse(int $id, Request $request, Loja $loja, Guard $auth)
    {
        DB::beginTransaction();
        /** @var JWTGuard $auth */

        // TODO: Se crescer colocar em lugar central
        try {
            $permissao = $auth->getPayload()['permissao'];
            if ($permissao !== 'CLIENTE') {
                abort(Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException) {
        }

        if (!($usuario = $request->user())) {
            $dados = $request->validate([
                'nome' => ['required'],
                'telefone' => ['required', new PhoneNumberRule()],
            ]);
            $dados = new Usuario($dados);
            $usuario = Usuario::firstOrCreate($dados->toArray());

            $login = $auth->login($usuario);
        } else {
            $dados = $usuario->only('nome', 'telefone');
        }
        /** @var Usuario $usuario */
        /** @var SqsQueue $sqsQueue */
        $sqsQueue = Queue::connection('sqs');

        $selectFoto = DB::tableMS('produtos_foto')
            ->select('produtos_foto.caminho')
            ->whereRaw("NOT produtos_foto.tipo_foto = 'SM' AND produtos_foto.id = produtos.id")
            ->limit(1)
            ->toSql();

        $informacoesProduto = DB::tableMS('produtos')
            ->where(['id' => $id])
            ->select(["valor_venda_{$loja->base_produtos->value} AS valor", DB::raw("($selectFoto) AS foto")])
            ->first();

        $informacoesProduto['valor'] = $loja->aplicaRemarcacao($informacoesProduto['valor']);
        $informacoesProduto['tamanhos'] = $request->validate(['tamanhos' => ['required', 'array', 'min:1']])[
            'tamanhos'
        ];
        $quantidadeTamanhos = count($informacoesProduto['tamanhos']);

        if ($loja->base_produtos->value === 'ML') {
            $arrayProduto = array_map(
                fn(string $tamanho) => [
                    'id_produto' => $id,
                    'nome_tamanho' => $tamanho,
                    'observacao' => json_encode($dados, true),
                ],
                $informacoesProduto['tamanhos']
            );
            Http::mobileStock()->post('api_meulook/carrinho', [
                'produtos' => $arrayProduto,
                'id_cliente' => $loja->id_revendedor,
            ]);
        } elseif ($loja->base_produtos->value === 'MS') {
            $arrayGrades = array_map(
                fn(string $tamanho) => [
                    'qtd' => 1,
                    'nome_tamanho' => $tamanho,
                    'tipo_adicao' => 'PR',
                ],
                $informacoesProduto['tamanhos']
            );

            $arrayProduto[] = [
                'id_produto' => $id,
                'observacao' => json_encode($dados, true),
                'grade' => $arrayGrades,
            ];

            Http::mobileStock()->post('api_cliente/pedido/produtos', ['produtos' => $arrayProduto]);
        }
        $informacoesProduto['tamanhos'] = implode(', ', $informacoesProduto['tamanhos']);

        $mensagem = "O(A) cliente {$dados['nome']} tem interesse ";
        $mensagem .= $quantidadeTamanhos > 1 ? 'nos tamanhos' : 'no tamanho';
        $mensagem .= " *{$informacoesProduto['tamanhos']}* ";
        $mensagem .= "do produto *$id* ";
        $mensagem .= "no valor de R\${$informacoesProduto['valor']}!";
        $mensagem .= PHP_EOL . PHP_EOL;
        $mensagem .= "Entre em contato com o(a) cliente pelo WhatsApp: https://api.whatsapp.com/send?phone=55{$dados['telefone']}";

        $sqsQueue->getSqs()->sendMessage([
            'QueueUrl' => $sqsQueue->getQueue(null),
            'MessageBody' => json_encode([
                'endpoint' => 'sendImageFromURL',
                'target' => $loja->telefone,
                'text' => <<<MSG
$mensagem
MSG
                ,
                'url' => $informacoesProduto['foto'],
            ]),
            'MessageGroupId' => (new DateTime())->format('dmyhm'),
            'MessageDeduplicationId' => Str::random(),
        ]);
        DB::commit();

        return empty($login) ? null : compact('login');
    }

    public function pesquisasPopulares(Loja $loja, JsonResponse $response)
    {
        $origem = $loja->base_produtos->value;
        $pesquisasPopulares = Http::mobileStock()
            ->get("api_meulook/publicacoes/pesquisas_populares?origem={$origem}")
            ->json();
        return $response->setData($pesquisasPopulares);
    }
}
