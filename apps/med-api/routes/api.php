<?php

use App\Http\Controllers\AdminLojaController;
use App\Http\Controllers\LojaController;
use App\Http\Controllers\MsProxy;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ConsultaDadosLoja;
use App\Http\Middleware\DefineDefaultAuthGuard;
use Illuminate\Support\Facades\Route;

Route::middleware(ConsultaDadosLoja::class)
    ->any('{any?}', [MsProxy::class, '__invoke'])
    ->where('any', 'api_.*');

Route::prefix('admin')
    ->middleware(DefineDefaultAuthGuard::class . ':lojas')
    ->group(function () {
        Route::post('link/{id}', [AdminLojaController::class, 'linkLogado']);
        Route::post('cadastrar_loja', [AdminLojaController::class, 'cadastrarLoja']);

        Route::middleware([Authenticate::class, ConsultaDadosLoja::class])->group(function () {
            Route::put('configuracoes', [AdminLojaController::class, 'configuracoes']);

            Route::prefix('catalogos_personalizados')->group(function () {
                Route::post('criar', [AdminLojaController::class, 'criarCatalogoPersonalizado']);
                Route::get('buscar_lista', [AdminLojaController::class, 'buscarListaCatalogosPersonalizados']);
                Route::get('buscar_lista_publicos', [
                    AdminLojaController::class,
                    'buscarListaCatalogosPersonalizadosPublicos',
                ]);
                Route::get('buscar_por_id/{id}', [AdminLojaController::class, 'buscarCatalogoPersonalizadoPorId']);
                Route::put('editar', [AdminLojaController::class, 'editarCatalogoPersonalizado']);
                Route::delete('deletar/{id}', [AdminLojaController::class, 'deletarCatalogoPersonalizado']);

                Route::post('adicionar_produto_catalogo', [AdminLojaController::class, 'adicionarProdutoCatalogo']);
            });
        });
    });

Route::middleware([ConsultaDadosLoja::class, DefineDefaultAuthGuard::class . ':usuarios'])->group(function () {
    Route::get('pesquisas_populares', [LojaController::class, 'pesquisasPopulares']);

    Route::prefix('pesquisa')->group(function () {
        Route::get('', [LojaController::class, 'pesquisa']);
        Route::get('autocomplete', [LojaController::class, 'autocompletePesquisa']);
        Route::post('autocomplete/telemetria', [LojaController::class, 'telemetriaAutocompletePesquisa']);
    });

    Route::prefix('produto/{id}')->group(function () {
        Route::get('', [LojaController::class, 'produto']);
        Route::post('tenho_interesse', [LojaController::class, 'tenhoInteresse']);
    });
});
