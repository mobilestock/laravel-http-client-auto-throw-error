<?php

namespace App\Http\Controllers;

use App\Enum\BaseProdutosEnum;
use App\Enum\TiposRemarcacaoEnum;
use App\Models\Loja;
use App\Models\LojaPreco;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\JWTGuard;

class AdminLojaController extends Controller
{
    public function linkLogado(int $idLoja, Request $request, JsonResponse $response, Guard $auth): JsonResponse
    {
        if (App::environment('production', 'staging') && $request->bearerToken() !== env('APP_AUTH_TOKEN')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        /** @var Loja $store */
        $store = Loja::where(['id_revendedor' => $idLoja])->first();

        if (empty($store)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        /** @var JWTGuard $auth */
        $login = $auth->login($store);

        return $response->setData(compact('login'))->setStatusCode(Response::HTTP_CREATED);
    }

    public function cadastrarLoja(Request $request): void
    {
        DB::beginTransaction();
        if (App::environment('production', 'staging') && $request->bearerToken() !== env('APP_AUTH_TOKEN')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $dadosJson = $request->validate([
            'id_revendedor' => ['required', 'numeric'],
            'nome' => ['required'],
            'url' => ['required'],
        ]);
        $dadosJson = [
            ...$dadosJson,
            'base_produtos' => BaseProdutosEnum::ML,
            'tipo_remarcacao' => TiposRemarcacaoEnum::PERCENTUAL,
        ];
        $dadosLoja = new Loja($dadosJson);

        $dadosPrecos = [
            'id_revendedor' => $dadosJson['id_revendedor'],
            'remarcacao' => 30,
        ];
        $dadosLojaPreco = new LojaPreco($dadosPrecos);

        $dadosLojaPreco->save();
        $dadosLoja->save();
        DB::commit();
    }

    public function configuracoes(Request $request, Loja $loja): void
    {
        DB::beginTransaction();
        $dadosJson = $request->validate([
            'nome' => ['required'],
            'base_produtos' => ['required', new Enum(BaseProdutosEnum::class)],
            'tipo_remarcacao' => ['required', new Enum(TiposRemarcacaoEnum::class)],
            'precos.*.ate' => ['required'],
            'precos.*.remarcacao' => ['required', 'numeric'],
            'precos.*.id_remarcacao' => ['present', 'numeric', 'nullable'],
            'itens_a_deletar' => ['array'],
            'precos' => ['array'],
        ]);

        $itensParaCriar = array_filter($dadosJson['precos'], fn($preco) => $preco['id_remarcacao'] === null);
        $ItensADeletar = $request->input('itens_a_deletar');
        $itensParaAtualizar = array_filter($dadosJson['precos'], fn($preco) => $preco['id_remarcacao'] !== null);
        $itensParaAtualizar = array_map(function (array $item): LojaPreco {
            $item['id'] = $item['id_remarcacao'];
            unset($item['id_remarcacao']);
            $model = new LojaPreco($item);
            $model->exists = true;

            return $model;
        }, $itensParaAtualizar);
        $ItensADeletar = array_column($ItensADeletar, 'id_remarcacao');
        if ($ItensADeletar) {
            $loja->precos()->whereIn('id', $ItensADeletar)->delete();
        }
        $loja->precos()->createMany(array_values($itensParaCriar));
        $loja->precos()->saveMany($itensParaAtualizar);
        $loja->update($dadosJson);

        $urlTratada = Loja::chaveCache($loja->url);
        Cache::forget($urlTratada);
        Loja::consultaLoja($loja->url);
        DB::commit();
    }

    public function criarCatalogoPersonalizado(Request $request, JsonResponse $response)
    {
        $requestWeb = Http::mobileStock()->post('api_cliente/catalogo_personalizado', $request->all());
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function buscarListaCatalogosPersonalizados(JsonResponse $response)
    {
        $requestWeb = Http::mobileStock()->get('api_cliente/catalogo_personalizado/lista');
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function buscarListaCatalogosPersonalizadosPublicos(JsonResponse $response, Loja $loja)
    {
        $origem = $loja->base_produtos->value;
        $requestWeb = Http::mobileStock()->get("api_cliente/catalogo_personalizado/lista_publicos?origem=$origem");
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function buscarCatalogoPersonalizadoPorId(int $idCatalogo, JsonResponse $response, Loja $loja)
    {
        $origem = $loja->base_produtos->value;
        $requestWeb = Http::mobileStock()->get("api_cliente/catalogo_personalizado/$idCatalogo?origem=$origem");
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function editarCatalogoPersonalizado(Request $request, JsonResponse $response)
    {
        $requestWeb = Http::mobileStock()->put('api_cliente/catalogo_personalizado/editar', $request->all());
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function deletarCatalogoPersonalizado(int $idCatalogo, JsonResponse $response)
    {
        $requestWeb = Http::mobileStock()->delete("api_cliente/catalogo_personalizado/$idCatalogo");
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }

    public function adicionarProdutoCatalogo(Request $request, JsonResponse $response)
    {
        $requestWeb = Http::mobileStock()->post(
            'api_cliente/catalogo_personalizado/adicionar_produto_catalogo',
            $request->all()
        );
        return $response->setData($requestWeb->json())->setStatusCode($requestWeb->status());
    }
}
