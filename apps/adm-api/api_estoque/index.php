<?php

// https://github.com/mobilestock/backend/issues/159

use api_cliente\Controller\MobileEntregas;
use api_estoque\Controller\Acompanhamento;
use api_estoque\Controller\Conferencia;
use api_estoque\Controller\Devolucao;
use api_estoque\Controller\Entregadores;
use api_estoque\Controller\Estoque;
use api_estoque\Controller\Expedicao;
use api_estoque\Controller\Monitoramento;
use api_estoque\Controller\ProdutosLogistica;
use api_estoque\Controller\Separacao;
use api_estoque\Controller\SeparacaoPublic;
use api_estoque\Controller\Transporte;
use Illuminate\Routing\Router;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\helper\RouterAdapter;
use Psr\Log\LogLevel;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if (mb_strpos($_SERVER['HTTP_HOST'], 'mobilestock.com.br') === true) {
    error_reporting(E_USER_NOTICE);
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Credentials: true');
    header(
        'Access-Control-Request-Method:  username,token, password, Origin, X-Requested-With, Content-Type, Accept, Authorization'
    );
    header(
        'Access-Control-Allow-Headers: username, token, auth, password, Origin, X-Requested-With, Content-Type, Accept, Authorization'
    );
    header(
        'Access-Control-Allow-Methods: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, DELETE, PUT, POST, GET'
    );
    die();
}

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$routerAdapter = app(RouterAdapter::class);

$rotas = $routerAdapter->router;
$router = $routerAdapter->routerLaravel;

$rotas->namespace('\\api_estoque\Controller')->group(null);
$rotas->get('/', 'Erro');

$rotas->get('/autenticacao', 'Autentica:validaUsuario'); // faço a autenticação do separador

$rotas->group('conferir_estoque');
$rotas->post('/analisa_localizacao/{id_localizacao}', 'Estoque:analisarLocalizacao');
$rotas->get('/produto/{cod_barras}', 'Estoque:buscarLocalizacaoDoProduto');
$rotas->delete('/limpar_analise', 'Estoque:limparAnalise');
$rotas->get('/produto_grade/{cod_barras}', 'Estoque:buscaProdutoPorCodBarras');
$rotas->patch('/atualizar_localizacao', 'Estoque:atualizaLocalizacaoProduto');
$rotas->get('/buscar_painel/{id_painel}', 'Estoque:buscarPainelLocalizacao');

$router->get('/imprimir_etiqueta_painel/{idLocalizacao}', [Estoque::class, 'imprimirEtiquetaPainel']);

$router->middleware('permissao:ADMIN')->group(function (Router $router) {
    $router->post('/imprimir_etiqueta_localizacao', [Estoque::class, 'imprimirEtiquetaLocalizacao']);
});

$rotas->group('produtos');
$rotas->put('/devolucao_entrada', 'Estoque:devolucaoEntrada');
$rotas->get('/buscar_por_uuid/{uuid_produto}', 'Estoque:buscarProdutoPorUuid');

$router
    ->prefix('/produtos')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/guardar', [Estoque::class, 'buscaDevolucoesAguardandoEntrada']);
    });

$router
    ->prefix('/separacao')
    ->middleware(SetLogLevel::class . ':' . LogLevel::EMERGENCY)
    ->group(function (Router $router) {
        $router->post('/produtos/etiquetas', [Separacao::class, 'buscaEtiquetasParaSeparacao']);
        $router->middleware('permissao:TODOS')->group(function (Router $router) {
            $router->post('/etiqueta_impressa', [Separacao::class, 'defineEtiquetaImpressa']);
        });
        $router->middleware('permissao:ADMIN')->group(function (Router $router) {
            $router->get('/lista_produtos_separacao', [SeparacaoPublic::class, 'listarEtiquetasSeparacao']);
        });
        $router->middleware('permissao:ADMIN,FORNECEDOR')->group(function (Router $router) {
            // $router
            //     ->middleware('permissao:ADMIN,FORNECEDOR')
            //     ->post('/separar/{uuidProduto}', [Separacao::class, 'separaItem']); // modifica a situacao do item para SE
            $router->get('/produtos', [Separacao::class, 'buscaItensParaSeparacao']);
        });
        $router->middleware('permissao:FORNECEDOR')->group(function (Router $router) {
            $router->get('/quantidade_demandando_separacao', [Separacao::class, 'buscaQuantidadeDemandandoSeparacao']);
        });
        $router->middleware('permissao:ADMIN,FORNECEDOR.CONFERENTE_INTERNO')->group(function (Router $router) {
            $router->get('/etiquetas_frete', [Separacao::class, 'buscaEtiquetasFreteDisponiveisDoColaborador']);
            $router->get('/busca/etiquetas_separacao_produtos_filtradas', [
                Separacao::class,
                'buscaEtiquetasSeparacaoProdutosFiltradas',
            ]);
        });
    });

$rotas->group('/expedicao');
$rotas->patch('/manipular_entrega_fechada', 'Expedicao:manipularEntregaFechada');

$rotas->get('/entrega/produtos', 'Expedicao:itensInseridosNaEntrega'); // busca itens conferidos

$router
    ->prefix('/expedicao')
    ->middleware([SetLogLevel::class . ':' . LogLevel::EMERGENCY])
    ->group(function (Router $router) {
        $router->middleware('permissao:ADMIN,ENTREGADOR,PONTO_RETIRADA')->group(function (Router $router) {
            $router->get('/busca_status', [Expedicao::class, 'buscaStatusEntregas']);
            $router->get('/lista_entregas_item_disponiveis_para_receber', [
                Expedicao::class,
                'ListaEntregaFaturamentoItem',
            ]);
            $router->get('/lista_entregas_item_disponiveis_para_entregar/{idColaborador}/{uuidProduto}', [
                Expedicao::class,
                'buscaProdutosDisponiveisParaEntregarAoCliente',
            ]);
            $router->put('/descobre_cliente/{idClienteOuUuid}', [Expedicao::class, 'descobreCliente']);
        });
        $router->middleware('permissao:TODOS')->group(function (Router $router) {
            $router->post('/confirma_entrega_item', [Expedicao::class, 'confirmaEntregaDeProdutosAoCliente']);
        });
        $router->middleware(['permissao:ADMIN'])->group(function (Router $router) {
            $router->patch('/encerrar_entrega/{id_entrega}', [Expedicao::class, 'encerrarEntrega']);
            $router->post('/nova_entrega', [Expedicao::class, 'criaEntregaOuMesclaComEntregaExistente']);
            $router->get('/descobrir/{uuidProduto}', [Conferencia::class, 'descobrirItemParaEntrarNaConferencia']);
            $router->get('/imprimir_etiqueta_dados_envio/{etiquetaExpedicao}', [
                Expedicao::class,
                'buscarDadosEtiquetaEnvio',
            ]);
            $router->post('/recalcular_etiquetas', [Expedicao::class, 'recalcularEtiquetas']);
            $router->get('/buscar_entregas_fechadas_temp', [Expedicao::class, 'buscarEntregasFechadasTemp']);
            $router->get('/busca_volumes_entrega/{idEntrega}', [Expedicao::class, 'buscaVolumesDaEntrega']);
            $router->get('/lista_etiquetas/{idEntrega}/{volume}', [Expedicao::class, 'ListaEtiquetasJsonPorEntrega']);
            $router->get('/entregas_cliente/{id_entrega}', [Expedicao::class, 'consultaEntregaId']);
            $router->get('/logistica_pendente/{identificador}', [Expedicao::class, 'verificaLogisticaPendente']);
        });

        $router->middleware(['permissao:ENTREGADOR,ADMIN'])->group(function (Router $router) {
            $router->put('/confirma_bipagem_volumes/{idEntrega}', [Expedicao::class, 'confirmaBipagemDeVolumes']);
            $router->get('/lista/entregas_volumes/{idColaborador}', [
                Expedicao::class,
                'buscaEntregasVolumesDoColaborador',
            ]);
        });

        $router
            ->post('/confirma_chegada', [Expedicao::class, 'confirmaChegadaDeProdutos'])
            ->middleware('permissao:PONTO_RETIRADA');
    });

$router
    ->prefix('/monitoramento')
    ->middleware('permissao:ADMIN,PONTO_RETIRADA,ENTREGADOR')
    ->group(function (Router $router) {
        $router->get('/trocas', [Monitoramento::class, 'buscaTrocasPendentes']);
        $router->get('/entrega', [Monitoramento::class, 'buscaProdutosEntrega']);
        $router->get('/quantidades', [Monitoramento::class, 'buscaProdutosQuantidade']);
        $router->get('/chegada', [Monitoramento::class, 'buscaProdutosChegada']);
        $router->post('/enviar_mensagem', [Monitoramento::class, 'enviarMensagemWhatsApp']);
    });

$rotas->group('/devolucao');
$rotas->put('/descontar', 'Devolucao:descontarDevolucoes');
$rotas->put('/altera_devolucao', 'Devolucao:alteraTipoDeDevolucao');

$router
    ->prefix('/devolucao')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->put('/bip/{uuidProduto}', [Devolucao::class, 'bipaDevolucao']);
        $router->get('/lista_por_ponto', [Devolucao::class, 'listaDevolucoesPonto']);
        $router->get('/busca_produto_sem_agendamento/{uuidProduto}', [Devolucao::class, 'buscarProdutoSemAgendamento']);
        $router->post('/confirmar_troca_mobilestock', [Devolucao::class, 'confirmarTrocaMobileStock']);
        $router->post('/confirmar_troca_meulook_sem_agendamento', [
            Devolucao::class,
            'confirmarTrocaMeuLookSemAgendamento',
        ]);
        $router->post('/gera_pac_reverso', [Devolucao::class, 'geraPacReversoParaDevolucaoDePonto']);
        $router->post('/gerar_etiqueta_devolucao', [Devolucao::class, 'gerarEtiquetaDevolucao']);
        $router->get('/buscaRelacao', [Devolucao::class, 'buscaRelacaoPontoDevolucoes']);
    });

$rotas->group('/conferencia');
$rotas->get('/conferidos/sem_entrega', 'Conferencia:itensDisponiveisParaAdicionarNaEntrega'); // busca itens conferidos
$rotas->get('/itens_entregues', 'Conferencia:buscaItensEntreguesCentral'); // Itens que foram entregues na central.

$router
    ->prefix('/transportadores')
    ->middleware('permissao:ENTREGADOR')
    ->group(function (Router $router) {
        $router->get('/sou_ponto_coleta', [Entregadores::class, 'verificaColaboradorPontoDeColeta']);
        $router->patch('/recebi_produto/{uuidProduto}', [Devolucao::class, 'recebiProdutoDoEntregador']);
        $router->get('/lista/devolucoes/{idColaborador}', [Devolucao::class, 'listaDevolucoesQueNaoChegaramACentral']);
        $router->get('/lista/entregadores', [Entregadores::class, 'listaEntregadores']);
        $router->get('/lista/cidades/entregador/{idColaborador}', [
            Entregadores::class,
            'listaCidadesAtendidasPeloEntregador',
        ]);
        $router->get('/lista/area_entrega/entregador/{idColaborador}/{idCidade?}', [
            Entregadores::class,
            'listaAreaEntregaEntregador',
        ]);
    });
$rotas->group('/cidades_comissao');
$rotas->get('/lista', 'Entregadores:buscaValorAdicionalCidade');
$rotas->post('/muda_bonus', 'Entregadores:alteraPrecoAdicionalCidade');

$rotas->group('/transportadoras');
$rotas->post('/solicitacao', 'Transporte:solicitaCadastroTransportadora');

$router->prefix('/transportadoras')->group(function (Router $router) {
    $router
        ->prefix('/fretes')
        ->middleware('permissao:TODOS')
        ->group(function (Router $router) {
            $router->get('/', [Transporte::class, 'listaFretes']);
            $router->get('/caminho', [Transporte::class, 'listaFretesACaminho']);
            $router->get('/entregues', [Transporte::class, 'listaFretesEntregues']);
        });
});

$router
    ->prefix('/acompanhamento')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/listar_acompanhamentos', [Acompanhamento::class, 'listarAcompanhamentoDestino']);
        $router->get('/listar_para_separar', [Acompanhamento::class, 'listarAcompanhamentoParaSeparar']);
        $router->get('/listar_conferidos', [Acompanhamento::class, 'listarAcompanhamentoConferidos']);
        $router->get('/listar_entregas_abertas', [Acompanhamento::class, 'listarAcompanhamentoEntregasAbertas']);
    });

$router
    ->prefix('/mobile_entregas')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/coletas_pendentes', [MobileEntregas::class, 'buscarColetasPendentes']);
    });

$router
    ->prefix('/produtos_logistica')
    ->middleware('permissao:ADMIN,FORNECEDOR.CONFERENTE_INTERNO')
    ->group(function (Router $router) {
        $router->post('/guardar', [ProdutosLogistica::class, 'guardarProdutos']);
        $router->post('/conferir/{uuid}', [Conferencia::class, 'conferir']);
        $router->post('/etiquetas_localizacao/{localizacao}', [
            ProdutosLogistica::class,
            'imprimirEtiquetasSkuPorLocalizacao',
        ]);
        $router->get('/guardar/{sku}', [ProdutosLogistica::class, 'buscarAguardandoEntrada']);
    });

$routerAdapter->dispatch();
