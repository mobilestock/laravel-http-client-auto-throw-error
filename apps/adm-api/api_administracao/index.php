<?php

// https://github.com/mobilestock/backend/issues/159
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('content-type: text/html; charset=utf-8');

// error_reporting(E_USER_NOTICE);
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

// error_reporting(E_USER_NOTICE);

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

session_set_cookie_params(86400);
session_start();

use api_administracao\Controller\Cadastro;
use api_administracao\Controller\Campanhas;
use api_administracao\Controller\CidadesPublic;
use api_administracao\Controller\Colaboradores;
use api_administracao\Controller\Compras;
use api_administracao\Controller\ComunicacaoPagamentos;
use api_administracao\Controller\Configuracoes;
use api_administracao\Controller\ContasBancarias;
use api_administracao\Controller\DiasNaoTrabalhados;
use api_administracao\Controller\Fornecedor;
use api_administracao\Controller\Entregadores;
use api_administracao\Controller\TipoFrete;
use api_administracao\Controller\Trocas;
use api_administracao\Controller\Entregas;
use api_administracao\Controller\EstoqueExterno;
use api_administracao\Controller\ForcarEntrega;
use api_administracao\Controller\ForcarTroca;
use api_administracao\Controller\Fraudes;
use api_administracao\Controller\LancamentoRelatorio;
use api_administracao\Controller\Logs;
use api_administracao\Controller\MobilePay;
use api_administracao\Controller\Produtos;
use api_administracao\Controller\TaxasFrete;
use api_administracao\Controller\TransacoesAdm;
use api_administracao\Controller\Transportadores;
use api_administracao\Controller\Transporte;
use api_administracao\Controller\Usuario;
use api_estoque\Controller\Acompanhamento;
use Illuminate\Routing\Router;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\helper\RouterAdapter;
use Psr\Log\LogLevel;

$routerAdapter = app(RouterAdapter::class);

$rotas = $routerAdapter->router;
$router = $routerAdapter->routerLaravel;

$rotas->namespace('\\api_administracao\Controller')->group(null);
$rotas->get('/', 'Erro');

// /*Rotas de cancelamento*/

$rotas->get('/estoque/historico/{id_movimentacao}', 'Produtos:buscarDetalhesMovimentacao');

$rotas->post('/verifica', 'Usuario:verify_account');
$rotas->post('/nova_senha_temporaria', 'Usuario:novaSenhaTemporaria');
$rotas->get('/nome/{id}', 'Usuario:buscarNome');

$rotas->get('/lista_pares_corrigidos', 'FaturamentoPonto:listaParesCorrigidos');
/** Endpoint abaixo é utilizado pela AWS e NÃO DEVE SER COMENTADO */
$router
    ->middleware(SetLogLevel::class . ':' . LogLevel::CRITICAL)
    ->get('/extrato', [LancamentoRelatorio::class, 'extratoLancamento']);
$rotas->get('/novos/clientes', 'Colaboradores:buscaNovosClientes');

/** Rotas do cadastro */
$rotas->group('/cadastro');
$rotas->get('/', 'Cadastro:buscaCadastros');
$rotas->post('/hasPermissao', 'Cadastro:hasPermissao');
$rotas->put('/produtos_data_entrada_todos', 'Cadastro:atualizaDataEntredaProdutosSeller'); //atualiza a data_entrada de todos os produtos de um seller
$rotas->delete('/deleta/{id}', 'Cadastro:deletaUsuario');
$rotas->post('/acesso', 'Cadastro:buscaAcessoDisponivel');
$rotas->post('/acesso/delete', 'Cadastro:deletaPermissao');
$rotas->get('/busca/acesso', 'Cadastro:buscaAcessoCadastro');
$rotas->post('/acesso/tipo', 'Cadastro:editTipoAcessoPrincipal');
$rotas->get('/iugu/{idColaborador}', 'Cadastro:cadastraIgugu');
$rotas->post('/link_logado', 'Colaboradores:geraLinkLogado');
$rotas->get('/busca_permissoes/{id_colaborador}', 'Usuario:buscaPermissoes');
$rotas->get('/busca_novos_fornecedores', 'Cadastro:buscaNovosFornecedores');

$rotas->get('/busca_novos_fornecedores', 'Cadastro:buscaNovosFornecedores');
$rotas->post('/alterna_conta_bancaria_colaborador', 'Cadastro:alternaBloquearContaBancaria');

$rotas->get('/limpa_itoken/{id_colaborador}', 'Cadastro:limpaItokenCliente');
$rotas->get('/bloqueia_adiantamentos/{id_colaborador}', 'Cadastro:bloqueiaAdiantamento');
$rotas->post('/loja_med/busca/{id_colaborador}', 'Colaboradores:buscaLojaMed');
$rotas->post('/loja_med/criar', 'Colaboradores:criarLojaMed');

$router->prefix('/cadastro')->group(function (Router $router) {
    $router->middleware('permissao:ADMIN')->group(function (Router $router) {
        $router->get('/lista_filtrada_colaboradores', [Colaboradores::class, 'buscaColaboradoresFiltros']);
        $router->post('/salvar_observacao', [Colaboradores::class, 'salvarObservacaoColaborador']);
        $router->post('/edita_usuario', [Usuario::class, 'editaUsuario']);
        $router->post('/permissao', [Cadastro::class, 'adicionaPermissao']);
    });

    $router->middleware('permissao:ADMIN,FORNECEDOR.CONFERENTE_INTERNO')->group(function (Router $router) {
        $router->get('/colaboradores_processo_seller_externo', [
            Cadastro::class,
            'buscaColaboradoresProcessoSellerExterno',
        ]);
    });

    $router->middleware('permissao:TODOS')->group(function (Router $router) {
        $router->get('/busca/colaboradores/{id_colaborador?}', [Cadastro::class, 'buscaCadastroColaborador']);
        $router->patch('/acesso_principal', [Cadastro::class, 'editaAcessoPrincipal']);
    });
});
$router
    ->prefix('/contas_bancarias')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('', [ContasBancarias::class, 'dadosContasBancarias']);
        $router->put('/{id_conta}', [ContasBancarias::class, 'alterandoDadosBancarios']);
    });

////////////////////////////CONTROLLER Look PAY ///////////////////////////////

$rotas->group('/pay');
$rotas->post('/extrato', 'MobilePay:buscaExtrato'); //Busca Extrato do Colaborador
$rotas->post('/balance', 'MobilePay:saldo'); //Busca Extrato do Colaborador
$rotas->post('/verify_balance', 'MobilePay:verificaSaldoAntecipacao'); //Busca Extrato do Colaborador
$rotas->post('/cabecalho', 'MobilePay:buscaCabecalho'); //Busca Extrato do Colaborador
$rotas->post('/contato', 'MobilePay:buscaContato'); //Busca Colaboradores com ID Zoop
$rotas->post('/transfer', 'MobilePay:paymentTransfer'); //Cria Transferência
$rotas->post('/withdraw', 'MobilePay:withdraw'); // Cria Saque
$rotas->get('/deposit/list', 'MobilePay:buscaDepositoAberto'); // Cria Saque
$rotas->post('/validation', 'MobilePay:validationValue'); // Valida saldo com  valor
$rotas->get('/send', 'MobilePay:sendToken'); // Valida existencia de Senha
$rotas->get('/balance_max', 'MobilePay:saldoEmprestimo'); // Busca Saques pendentes
$rotas->get('/itoken', 'MobilePay:hasPassword'); // Valida existencia de Senha
$rotas->get('/help', 'MobilePay:buscaDuvida'); // Valida saldo com  valor
$rotas->get('/help/{id}', 'MobilePay:buscaDuvidas'); // Valida saldo com  valor
$rotas->post('/help/frequency', 'MobilePay:editFrequency'); // Valida saldo com  valor
$rotas->post('/help/inserir', 'MobilePay:criaDuvida'); // Valida saldo com  valor
$rotas->post('/help/responde', 'MobilePay:respondeDuvida'); // Valida saldo com  valor
$rotas->get('/bancos/{id}', 'MobilePay:buscaBancos');
$rotas->post('/itoken/create', 'MobilePay:createiToken'); // Valida existencia de Senha
$rotas->post('/contact/add', 'MobilePay:cadastraContatoMobilePay'); // Cadastra Novo Contato e Iugu
$rotas->get('/contact/history', 'MobilePay:buscaHistoryContatos'); // Cadastra Novo Contato e Iugu
$rotas->get('/iugu/has', 'MobilePay:hasIugu'); // Valida existencia de Senha
$rotas->get('/busca/withdraw', 'ComunicacaoPagamentosPublic:buscaWithdraw'); // Valida existencia de Senha
$rotas->post('/borrowing', 'MobilePay:borrowing');
$rotas->post('/fees', 'MobilePay:fees');
$rotas->get('/busca/borrowing', 'MobilePay:buscaEmprestimos');
$rotas->post('/abstracts', 'MobilePay:abstracts');
$rotas->get('/busca/saldo', 'MobilePay:buscaSaldoGeral');
$rotas->get('/busca_extrato_colaborador/{id_colaborador}', 'MobilePay:buscaExtratoColaborador');
$rotas->post('/gera_lancamento_manual/{id_cliente}', 'MobilePay:geraLancamento');
$rotas->get('/busca_lista_juros', 'MobilePay:buscaListaJuros');
$rotas->get('/busca_taxa_adiantamento', 'MobilePay:buscaTaxaAdiantamento');

$router->prefix('/pay')->group(function (Router $router) {
    $router->get('/soma_lancamentos_pendentes', [MobilePay::class, 'buscaValorTotalLancamentosPendentes']);
    $router->get('/lancamentos_futuros', [MobilePay::class, 'buscaLancamentosFuturos']);
    $router->get('/abates/{id_lancamento}', [MobilePay::class, 'abatesLancamento'])->middleware('permissao:ADMIN');
});

/////////////////////////// CADASTRO DE PRODUTOS ////////////////////////////////
$rotas->group('/categorias');
$rotas->get('/', 'Categorias:listarCategorias');
$rotas->post('/', 'Categorias:salva');
$rotas->delete('/{id}', 'Categorias:remove');
$rotas->get('/tipos', 'Categorias:listaCategoriasTipos');

$rotas->group('/linhas');
$rotas->get('/', 'Linhas:listarLinhas');

$rotas->group('/tags');
$rotas->post('/', 'Tags:salva');
$rotas->get('/tipos', 'Tags:listarTagsTipos');
$rotas->delete('/tipos/{id}', 'Tags:removeTipo');

$rotas->group('/produtos');
$rotas->get('/lista_configs_pra_cadastro', 'Produtos:listaDadosPraCadastro');
$rotas->get('/busca_etiquetas_avulsa/{id}', 'Produtos:buscaEtiquetaAvulsa');
$rotas->get('/fornecedor/reposicao_antiga/{id}', 'Produtos:buscaReposicaoMaisAntiga');
$rotas->get('/busca_localizacoes', 'Produtos:buscaLocalizacao');
$rotas->post('/analisa_estoque', 'Produtos:analisaEstoque');
$rotas->get('/busca_resultado_analise', 'Produtos:buscaAnaliseEstoque');
$rotas->post('/movimenta_estoque_par', 'Produtos:MovimentaParDoEstoque');
$rotas->get('/estoque_interno', 'Produtos:buscaProdutosEstoqueInternoFornecedor');
$rotas->post('/tirar_de_linha/{id_produto}', 'Produtos:tirarProdutoDeLinha');
$rotas->get('/aguardando', 'BipagemPublic:aguardandoGet');
$rotas->get('/busca_entradas_aguardando', 'Produtos:buscaEntradasAguardando');
$rotas->post('/busca_produtos', 'Produtos:BuscaProdutos');
$rotas->get('/busca_lista_produtos_conferencia_referencia', 'Produtos:buscaListaProdutosConferenciaReferencia');
$rotas->get('/busca_detalhes_pra_conferencia_estoque/{id_produto}', 'Produtos:buscaDetalhesPraConferenciaEstoque');
$rotas->get('/buscar_grades_do_produto/{id_produto}', 'Produtos:buscarGradesDeUmProduto');
$rotas->get('/mais_vendidos', 'Produtos:maisVendidos');
$rotas->get('/busca_lista_pontuacoes', 'Produtos:buscaListaPontuacoes');
$rotas->get('/busca_explicacoes_pontuacao_produtos', 'Produtos:buscaExplicacoesPontuacaoProdutos');
$rotas->get('/busca/produtos_mais_vendidos', 'Produtos:buscaProdutosMaisVendidos');
$rotas->get('/busca/produtos_sem_entrega', 'Produtos:buscaProdutosSemEntrega');
$rotas->patch('/permissao_repor_fulfillment', 'Produtos:permissaoReporFulfillment');
$rotas->get('/busca_fatores_pontuacao', 'Produtos:buscaFatoresPontuacao');
$rotas->put('/alterar_fatores_pontuacao', 'Produtos:alterarFatoresPontuacao');

$router->prefix('/produtos')->group(function (Router $router) {
    $router->middleware('permissao:ADMIN,FORNECEDOR')->group(function (Router $router) {
        $router->post('/', [Produtos::class, 'salva']);
        $router->delete('/{id_produto}', [Produtos::class, 'remove']);
        $router->get('/busca_avaliacacoes_produto/{id_produto}', [Produtos::class, 'buscaAvaliacoesProduto']);
        $router->get('/busca_produtos_promovidos', [Produtos::class, 'buscaProdutosPromovidos']);
        $router->get('/busca_produtos_disponiveis', [Produtos::class, 'buscaProdutosDisponiveisPromocao']);
        $router->post('/salva_promocao', [Produtos::class, 'salvaPromocao']);
        $router->get('/pesquisa_produto_lista', [Produtos::class, 'pesquisaProdutoLista']);

        $router
            ->prefix('/negociacao')
            ->middleware('permissao:FORNECEDOR')
            ->group(function (Router $router) {
                $router->get('/opcoes_substituicao', [
                    Fornecedor::class,
                    'buscaProdutosParaOferecerNegociacaoSubstituicao',
                ]);
                $router->post('/abrir', [Fornecedor::class, 'abrirNegociacaoSubstituicao']);
            });
    });

    $router
        ->middleware('permissao:ADMIN,FORNECEDOR.CONFERENTE_INTERNO,FORNECEDOR')
        ->post('/movimentacao_manual', [Produtos::class, 'movimentacaoManualProduto']);

    $router->middleware('permissao:ADMIN')->group(function (Router $router) {
        $router->get('/busca_promocoes_analise', [Produtos::class, 'buscaPromocoesAnalise']);
        $router->post('/desativa_promocao_mantem_valores/{id_produto}', [
            Produtos::class,
            'desativaPromocaoMantemValores',
        ]);
        $router->get('pedidos', [Produtos::class, 'buscaProdutosPedido']);
        $router->patch('moda/{id_produto}', [Produtos::class, 'alterarEhModa']);
        $router->patch('permissao_repor_fulfillment/{id_produto}', [
            Produtos::class,
            'alterarPermissaoReporFulfillment',
        ]);
    });

    $router->get('/busca_previsao', [Produtos::class, 'buscaPrevisao']);
    $router
        ->middleware('permissao:FORNECEDOR')
        ->get('/busca_informacoes_produto_negociado/{uuid_produto}', [
            Produtos::class,
            'buscaInformacoesProdutoNegociado',
        ]);
});
/////////////////////////// ------------------- ////////////////////////////////

$rotas->group('/pagamento');
//$rotas->post('/sync', 'ComunicacaoPagamentos:buscaSituacao');
$rotas->get('/busca_recebiveis_pendentes', 'ComunicacaoPagamentosPublic:buscaRecebiveisPendentes');
$rotas->put(
    '/alterar_pagamento_automatico_transferencias',
    'ComunicacaoPagamentos:alterarPagamentoAutomaticoTransferenciasPara'
);
$rotas->delete('/deletar_transferencia/{id_transferencia}', 'ComunicacaoPagamentos:deletarTransferencia');
$rotas->post('/pagamento_manual', 'ComunicacaoPagamentos:pagamentoManual');

$router->prefix('/pagamento')->group(function (Router $router) {
    $router->post('/sync', [ComunicacaoPagamentos::class, 'buscaSituacao']);

    $router
        ->middleware('permissao:ADMIN')
        ->get('/informacoes_pagamento_automatico_transferencias', [
            ComunicacaoPagamentos::class,
            'buscaInformacoesPagamentoAutomaticoTransferencias',
        ]);
});

$router
    ->prefix('/transferencias')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->patch('/inteirar/{id_transferencia}', [ComunicacaoPagamentos::class, 'inteirarTransferencia']);
        $router->get('/', [ComunicacaoPagamentos::class, 'listaTransferencias']);
        $router->post('/fila', [ComunicacaoPagamentos::class, 'atualizaFilaTransferencia']);
    });
/////////////////////////// ------------------- ////////////////////////////////

$rotas->group('/compras');
$rotas->post('/entrada', 'Compras:entradaCompras');
$rotas->post('/busca_lista_compras', 'Compras:buscaListaCompras');
$rotas->get('/busca_codigo_barras_compra/{id_compra}', 'Compras:buscaCodigoBarrasCompra');
$rotas->get('/busca_dados_por_codigo_barras/{codigo_barras}', 'Compras:buscaDadosCodBarras');
$rotas->get('/busca_etiqueta_unitaria_compra/{id_compra}', 'Compras:buscaEtiquetasUnitariasCompra');
$rotas->get('/busca_etiqueta_coletiva_compra/{id_compra}', 'Compras:buscaEtiquetasColetivasCompra');
$rotas->post('/busca_historico_dados_cod_barras', 'Compras:buscaHistoricoDadosCodBarras');
$rotas->get('/busca/ultimas_entradas_compra', 'Compras:buscaUltimasEntradasCompra');

$router
    ->prefix('/compras')
    ->middleware('permissao:ADMIN,FORNECEDOR')
    ->group(function (Router $router) {
        $router->get('/produtos_reposicao_interna/{id_fornecedor}', [Compras::class, 'buscaProdutosReposicaoInterna']);
        $router->get('/busca_uma_compra/{id_compra}', [Compras::class, 'buscaUmaCompra']);
        $router->post('/salva_compra', [Compras::class, 'salvarCompra']);
        $router->delete('/remove_item/{id_compra}', [Compras::class, 'removeItemReposicao']);
        $router->patch('/concluir/{id_compra}', [Compras::class, 'concluirReposicao']);
    });

$router
    ->prefix('/entregas')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/busca_letras_pontos_mais_usados', [Entregas::class, 'letrasPontosMaisUsados']);
        $router->get('/{id_entrega}', [Entregas::class, 'buscaEntregaPorID']);
        $router->get('/busca_detalhes_entrega/{id_entrega}', [Entregas::class, 'buscaDetalhesEntrega']);
        $router->get('/busca_produtos_entrega_atrasada', [Entregas::class, 'buscaProdutosEntregaAtrasada']);
        $router->post('/forcar_entrega/{uuidProduto}', [ForcarEntrega::class, 'forcaEntrega']);
        $router->patch('/alterar_data_base_troca', [Entregas::class, 'alterarDataBaseDaTroca']);
        $router->patch('/alterar_tipo_frete', [Entregas::class, 'alterarTipoFreteDaEntrega']);
        $router->get('/itens_entregues', [Entregas::class, 'buscaItensDasEntregasEntregues']);
    });

$rotas->group('/tipo_frete');
$rotas->get('/buscar', 'TipoFrete:buscarTipoFrete');
$rotas->get('/buscar_grupos', 'TipoFrete:buscarGruposTipoFrete');
$rotas->post('/criar_grupo', 'TipoFrete:criarGrupoTipoFrete');
$rotas->patch('/mudar_situacao_grupo', 'TipoFrete:mudarSituacaoGrupoTipoFrete');
$rotas->delete('/apagar_grupo/{id_grupo}', 'TipoFrete:apagarGrupoTipoFrete');
$rotas->get('/buscar_detalhes_grupo/{id_grupo}', 'TipoFrete:buscarDetalhesGrupoTipoFrete');
$rotas->patch('/editar_grupo', 'TipoFrete:editarGrupoTipoFrete');
$rotas->get('/lista_id_tipo_frete/{id_grupo_entrega}', 'TipoFrete:listarTipoFretePorGrupo');

$router->prefix('/tipo_frete')->group(function (Router $router) {
    $router->middleware('permissao:TODOS')->get('/buscar_centrais', [TipoFrete::class, 'buscarCentrais']);

    $router->middleware('permissao:ADMIN')->group(function (Router $router) {
        $router->get('/fila_aprovacao', [TipoFrete::class, 'listaFilaAprovacao']);
        $router->get('/busca_lista_pedidos', [TipoFrete::class, 'buscaListaPedidos']);
        $router->get('/busca_mais_detalhes_pedido', [TipoFrete::class, 'buscaMaisDetalhesDoPedido']);
        $router->get('/listar_destinos_grupo/{id_grupo}', [TipoFrete::class, 'listarDestinosDoGrupo']);
        $router->get('/listar_grupos/{id_tipo_frete}', [TipoFrete::class, 'listarGruposPorTipoFrete']);
    });
});

$router
    ->prefix('/transportadores')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->post('/situacao', [Transportadores::class, 'atualizaSituacao']);
    });

$router->prefix('/ponto_coleta')->group(function (Router $router) {
    $router->middleware('permissao:ADMIN,ENTREGADOR,PONTO_RETIRADA')->group(function (Router $router) {
        $router->prefix('/agenda')->group(function (Router $router) {
            $router->get('/buscar', [TipoFrete::class, 'buscarAgendaPontosColeta']);
            $router->post('/criar_horario', [TipoFrete::class, 'criarHorarioAgendaPontoColeta']);
            $router->delete('/remover_horario/{id_agendamento}', [TipoFrete::class, 'removerHorarioAgendaPontoColeta']);
        });

        $router->get('/busca_lista', [TipoFrete::class, 'buscaListaPontosColeta']);
        $router->get('/pesquisar_pontos_coleta', [TipoFrete::class, 'pesquisarNaListaPontosDeColeta']);
    });

    $router->middleware('permissao:ADMIN')->group(function (Router $router) {
        $router->put('/novos_prazos', [TipoFrete::class, 'salvaNovosPrazosPontoColeta']);
        $router->patch('/atualizar_tarifa', [TipoFrete::class, 'atualizarTarifaPontoColeta']);
    });
});

$router
    ->prefix('/acompanhamento')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->post('/acompanhar_em_grupo', [Acompanhamento::class, 'adicionarAcompanhamentoDestinoGrupo']);
    });

$rotas->group('/cidades');
$rotas->get('/', 'CidadesPublic:lista');
$rotas->get('/meu_look', 'Cidades:listaMeuLook');
$rotas->post('/', 'Cidades:cadastroMeuLook');

$router->prefix('/cidades')->group(function (Router $router) {
    $router->get('/pontos', [CidadesPublic::class, 'listaMeuLookPontos']);
    $router->get('/{id_cidade}/cobertura/{id_colaborador}', [CidadesPublic::class, 'listaCobertura']);
});

$rotas->group('pontos_de_entrega');
// $rotas->get('/', 'TipoFrete:listaPontosMeuLook');
$rotas->post('/altera_previsao', 'TipoFrete:alteraPrevisaoTipoFrete');
$rotas->post('/produtos', 'TipoFrete:buscaProdutosDoPonto');
$rotas->get('/busca_valor_vendido_tipo_frete', 'TipoFrete:buscaQuantidadeVendida');
$rotas->put('/muda_tipo_embalagem', 'Colaboradores:mudaTipoEmbalagem');

$router
    ->prefix('/pontos_de_entrega')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/lista_pontos', [TipoFrete::class, 'buscaListaPontos']);
        $router->get('/busca/lista_entregadores_com_produtos', [TipoFrete::class, 'listaEntregadoresComProdutos']);
        $router->get('/status_produto/{idPonto}', [TipoFrete::class, 'buscaProdutosPorPonto']);
        $router->get('/busca/detalhes_tarifa_ponto_coleta', [TipoFrete::class, 'buscaDetalhesTarifaPontoColeta']);
    });

$router
    ->prefix('/ponto_retirada')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/ativos', [TipoFrete::class, 'listaPontosAtivos']);
        $router->put('/', [TipoFrete::class, 'alteraPonto']);
    });

$rotas->group('/estoque_externo');
$rotas->get('/busca_detalhes_por_seller/{id_responsavel_estoque}', 'EstoqueExterno:buscaDetalhesSeller');
$rotas->get('/busca/monitoramento_vendidos', 'EstoqueExterno:monitoramentoVendidos');
$rotas->post('/busca_info_produtos', 'EstoqueExterno:buscaDetalhesProdutos');

$router->prefix('/estoque_externo')->group(function (Router $router) {
    $router->get('/lista_fornecedores/{pagina}', [EstoqueExterno::class, 'listaFornecedores']);
});

$rotas->group('/fornecedor');
// $rotas->get('/busca_demanda_produtos_fornecedor', 'Fornecedor:buscaDemandaProdutosFornecedor');
// $rotas->get('/busca_lista_compra_itens_em_estoque/{lote}', 'Fornecedor:buscaListaCompraItensEmEstoque');
// $rotas->post('/busca_produtos_mais_acessados', 'Fornecedor:buscaProdutosMaisAcessados');
// $rotas->post('/busca_produtos_mais_adicionados', 'Fornecedor:buscaProdutosMaisAdicionados');
// $rotas->post('/busca_produtos_mais_vendidos','Fornecedor:buscaProdutosMaisVendidos');
$rotas->get('/saldo_produtos', 'Produtos:buscaSaldoProdutosFornecedor');
$rotas->get('/busca_media_cancelamentos_seller', 'Fornecedor:buscaMediaCancelamentosSeller');
$rotas->get('/verifica_seller_bloqueado/{id_fornecedor}', 'Fornecedor:verificaSellerBloqueado');
$rotas->post('/bloqueia_seller/{id_fornecedor}', 'Fornecedor:bloqueiaSeller');
$rotas->post('/desbloqueia_seller/{id_fornecedor}', 'Fornecedor:desbloqueiaSeller');
$rotas->get('/extrato', 'Fornecedor:buscaExtratoFornecedor');
$rotas->get('/busca_dias_para_desbloquear_botao_up', 'Fornecedor:buscaDiasParaLiberarBotaoUp');
$rotas->get('/busca_dados_dashboard_seller', 'Fornecedor:buscaDadosDashboardSeller');
$rotas->get('/desempenho_sellers', 'Fornecedor:buscaDesempenhoSellers');
$rotas->get('/busca/lista_produtos_cancelados', 'Fornecedor:buscaProdutosCancelados');
$rotas->delete('/estou_ciente_cancelamento/{id_alerta}', 'Fornecedor:estouCienteCancelamento');
$router->prefix('/fornecedor')->group(function (Router $router) {
    $router
        ->get('/busca_valor_total_fulfillment/{id_fornecedor}', [Fornecedor::class, 'buscaValorTotalFulfillment'])
        ->middleware('permissao:ADMIN');

    $router
        ->get('/busca_fornecedores', [Fornecedor::class, 'buscaFornecedores'])
        ->middleware('permissao:ADMIN,FORNECEDOR.CONFERENTE_INTERNO');

    $router->middleware('permissao:ADMIN,FORNECEDOR')->group(function (Router $router) {
        $router->get('/busca_produtos/{id_fornecedor}', [Produtos::class, 'buscaProdutosFornecedor']);
        $router->put('/zerar_estoque_responsavel/{id_fornecedor?}', [Fornecedor::class, 'zerarEstoqueResponsavel']);
        $router->get('/busca_produtos_defeituosos/{id_fornecedor}', [Fornecedor::class, 'buscaProdutosDefeituosos']);
        $router->patch('/retirar_produto_defeito/{uuid_produto}', [Trocas::class, 'retirarDevolucaoComDefeito']);
        $router->get('/estoques_detalhados', [Fornecedor::class, 'buscaEstoquesDetalhados']);
    });
});

$rotas->group('ranking');
//$rotas->get('/', 'Ranking:listarPremiacoes');
//$rotas->get('/vendas/{idLancamentoPendente}', 'Ranking:listarVendasDoLancamento');
// $rotas->get('/influencers_oficiais', 'Ranking:buscarInfluencersOficiais');
// $rotas->post('/alterar_situacao_influencer_oficial/{id_usuario}', 'Ranking:alterarSituacaoInfluencerOficial');
//$rotas->get('/premios_aplicados', 'Ranking:listarPremiosAplicados');

$rotas->group('/troca');
$rotas->post('/busca_itens_comprados_parametros', 'Trocas:buscaProdutosCompradosParametros');
$rotas->get('/trocas_pendente_confirmadas/{id_cliente}', 'Trocas:pesquisaTrocasPendentesConfirmadas');
$rotas->get('/busca_trocas_agendadas/{id_cliente}', 'Trocas:buscaTrocasAgendadas');
$rotas->post('/recusar_solicitacao_troca', 'Trocas:recusarSolicitacaoTroca');
$rotas->post('/reprova_por_foto', 'Trocas:reprovaPorFoto');
$rotas->get('/lista_trocas/{id_cliente}', 'Trocas:listaTrocas');

$router->prefix('/troca')->group(function (Router $router) {
    $router->post('/confirmar_troca', [Trocas::class, 'confirmaTroca']);
    $router
        ->get('/detalhes_troca/{uuid_produto}', [Trocas::class, 'buscaDetalhesTrocas'])
        ->middleware('permissao:ADMIN');
    $router
        ->post('/resolver_disputa', [Trocas::class, 'resolverDisputaSolicitacaoTroca'])
        ->middleware('permissao:ADMIN,FORNECEDOR');
    $router
        ->post('/aprovar_solicitacao_troca', [Trocas::class, 'aprovarSolicitacaoTroca'])
        ->middleware('permissao:ADMIN,FORNECEDOR');
    $router->post('/forcar_troca', [ForcarTroca::class, 'forcaTroca'])->middleware('permissao:ADMIN');
    $router->get('/produtos_troca', [Trocas::class, 'buscaProdutos'])->middleware('permissao:ADMIN,CLIENTE,FORNECEDOR');
});

// $rotas->group('meulook');
// $rotas->get('/log_links', 'MeuLook:buscaLogLinkMeuLook');

// $rotas->group('/fila');
//$rotas->post("/lista-recebiveis", "ComunicacaoPagamentos:listaRecebiveis");

$rotas->group('/meios_pagamento');
$rotas->get('/', 'MeiosPagamento:consultaMeiosPagamento');
$rotas->post('/', 'MeiosPagamento:atualizaMeiosPagamento');

$rotas->group('/taxas_frete');
$rotas->get('/', 'TaxasFrete:consultaTaxasFrete');
$rotas->post('/', 'TaxasFrete:atualizaTaxasFrete');

$rotas->group('/transacoes');
$rotas->get('/', 'TransacoesAdm:listarTransacoes');
$rotas->get('/{id}/lancamentos', 'TransacoesAdm:consultaLancamentos');
$rotas->get('/{id}/transferencias', 'TransacoesAdm:consultaTransferencias');
$rotas->get('/pendentes', 'TransacoesAdm:buscaTransacoesPendentes');
$rotas->post('/transacao', 'TransacoesAdm:BuscaTransacaoFiltro');
$rotas->get('/{id}/tentativa', 'TransacoesAdm:buscaTentativaTransacao');

$router
    ->prefix('/transacoes/{id_transacao}')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/', [TransacoesAdm::class, 'consultaTransacao']);
        $router->get('/trocas', [TransacoesAdm::class, 'consultaTrocas']);
    });
//$rotas->get('/busca/produtos_entrega_pendente', 'TransacoesAdm:buscaProdutosEntregaPendente');

$rotas->group('/fraudes');
$rotas->get('/suspeitos', 'Fraudes:buscaSuspeitos');
$rotas->get('/busca/situacao_fraude', 'Fraudes:buscaSituacaoFraude');
$rotas->put('/altera_valor_limite_para_entrar_fraude', 'Fraudes:alteraValorMinimoParaEntrarFraude');
$rotas->post('/insere_manualmente_fraude', 'Fraudes:forcaEntradaFraude');

$router
    ->prefix('/fraudes')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/', [Fraudes::class, 'buscaFraudes']);
        $router->get('/devolucoes', [Fraudes::class, 'buscaFraudesDevolucoes']);

        $router->prefix('/{id_colaborador}')->group(function (Router $router) {
            $router->get('/transacoes', [Fraudes::class, 'listaTransacoesSuspeito']);
            $router->put('/', [Fraudes::class, 'alteraSituacaoFraude']);
        });
    });

$rotas->group('/entregadores');
$rotas->get('/documentos/{id_colaborador}', 'Entregadores:buscaDocumentosEntregador');
$rotas->post('/gerir_ponto_coleta', 'Entregadores:gerirPontoColeta');
$rotas->post(
    '/{id_colaborador_entregador}/alterar_ponto_coleta/{id_colaborador_coleta}',
    'Entregadores:atualizarPontoColeta'
);
$router
    ->prefix('/entregadores')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/', [Entregadores::class, 'buscaListaEntregadores']);
        $router->post('/alterar_dados_raio', [Entregadores::class, 'atualizarDadosRaioEntregador']);
        $router->post('/muda_situacao', [Entregadores::class, 'mudaSituacao']);
        $router->post('/adicionar_cidade', [Entregadores::class, 'adicionarCidade']);
        $router->patch('/atualizar_raios', [Entregadores::class, 'atualizarRaios']);
        $router->patch('/atualizar_status_raio/{id_raio}', [Entregadores::class, 'atualizarStatusRaio']);
    });

$rotas->group('/transportadoras');
$rotas->post('/rastreio', 'Transporte:insereDadosRastreioRodonaves');
$rotas->post('/rastreio/alterar', 'Transporte:alteraDadosRastreioRodonaves');
$rotas->get('/finalizadas', 'Transporte:buscaEntregasRastreaveis');
$rotas->get('/busca/transportadoras', 'Transporte:buscaTransportadoras');

$router
    ->prefix('/transportadoras')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/entregas_pendentes', [Transporte::class, 'buscaEntregasPendentes']);
    });

$rotas->group('/configuracoes');
$rotas->post('/dia_nao_trabalhado', 'DiasNaoTrabalhados:salvaDiaNaoTrabalhado');
$rotas->delete('/dia_nao_trabalhado/{id_dia_nao_trabalhado}', 'DiasNaoTrabalhados:removeDiaNaoTrabalhado');
$rotas->put('/altera_porcentagem_comissoes', 'Configuracoes:alteraPorcentagensComissoes');
$rotas->get('/busca_configuracoes_frete', 'Configuracoes:buscaConfiguracoesFrete');
$rotas->put('/altera_configuracoes_frete', 'Configuracoes:alteraConfiguracoesFrete');
//   $rotas->post('/atualiza_alerta_chat_atendimento', 'Configuracoes:atualizaAlertaChatAtendimento');
$rotas->get('/busca_valor_minimo_fraude', 'Configuracoes:buscaValorMinimoEntrarFraude');
$rotas->get('/busca_porcentagem_antecipacao', 'Configuracoes:buscaPorcentagemAntecipacao');
$rotas->put('/altera_porcentagem_antecipacao', 'Configuracoes:alteraPorcentagemAntecipacao');
$rotas->get('/busca_fatores_reputacao', 'Configuracoes:buscaFatoresReputacao');
$rotas->put('/altera_fatores_reputacao', 'Configuracoes:alteraFatoresReputacao');
$rotas->put('/altera_valor_limite_para_entrar_fraude', 'Configuracoes:alteraValorMinimoParaEntrarFraude');

$router->prefix('/configuracoes')->group(function (Router $router) {
    $router->middleware('permissao:ADMIN')->group(function (Router $router) {
        $router->patch('/altera_porcentagem_comissoes_mobile_entregas', [
            Configuracoes::class,
            'alteraPorcentagemComissaoMobileEntregas',
        ]);
        $router->get('/busca_porcentagem_comissoes', [Configuracoes::class, 'buscaPorcentagensComissoes']);
        $router->get('/datas_transferencia_colaborador', [Configuracoes::class, 'buscaDiasTransferenciaColaboradores']);
        $router->put('/datas_transferencia_colaborador', [
            Configuracoes::class,
            'atualizarDiasTransferenciaColaboradores',
        ]);
        $router->put('/altera_horarios_separacao', [Configuracoes::class, 'alteraHorariosSeparacao']);
        $router->put('/alterar_ordenamento_filtros', [Configuracoes::class, 'alterarOrdenamentoFiltros']);
        $router->get('/buscar_tempo_cache_filtros', [Configuracoes::class, 'buscarTempoCacheFiltros']);
        $router->get('/dia_nao_trabalhado', [DiasNaoTrabalhados::class, 'listaDiaNaoTrabalhado']);
        $router->put('/altera_taxa_bloqueio_fornecedor', [Configuracoes::class, 'alteraTaxaBloqueioFornecedor']);
        $router->get('/busca_taxa_bloqueio_fornecedor', [Configuracoes::class, 'buscaTaxaBloqueioFornecedor']);
        $router->put('/alterar_taxa_produto_errado', [Configuracoes::class, 'alterarTaxaProdutoErrado']);
        $router->get('/paineis_impressao', [Configuracoes::class, 'buscaPaineisImpressao']);
        $router->put('/paineis_impressao', [Configuracoes::class, 'alteraPaineisImpressao']);
        $router->get('/dias_produto_parado_estoque', [Configuracoes::class, 'buscaQtdMaximaDiasProdutoParadoEstoque']);
        $router->patch('/dias_produto_parado_estoque', [Configuracoes::class, 'atualizaDiasProdutoParadoNoEstoque']);
        $router->put('/atualiza_frete_por_cidade', [TaxasFrete::class, 'atualizaFretesPorCidade']);
    });

    $router
        ->middleware('permissao:ADMIN,FORNECEDOR')
        ->get('/busca_taxa_produto_errado', [Configuracoes::class, 'buscarTaxaProdutoErrado']);

    $router
        ->middleware('permissao:ADMIN,FORNECEDOR')
        ->get('/busca_informacoes_aplicar_promocao', [Configuracoes::class, 'buscaInformacoesAplicarPromocao']);

    $router
        ->middleware('permissao:ADMIN,ENTREGADOR,PONTO_RETIRADA')
        ->get('/busca_horarios_separacao', [Configuracoes::class, 'buscaHorariosSeparacao']);
});

$router
    ->prefix('/catalogo_personalizado')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->get('/buscar', [Produtos::class, 'buscaCatalogosPersonalizados']);
        $router->put('/ativar_desativar/{idCatalogo}', [Produtos::class, 'ativarDesativarCatalogoPersonalizado']);
    });

$router
    ->prefix('/campanhas')
    ->middleware('permissao:ADMIN')
    ->group(function (Router $router) {
        $router->post('/', [Campanhas::class, 'criarCampanha']);
        $router->delete('/{idCampanha}', [Campanhas::class, 'deletarCampanha']);
    });

$router->middleware('permissao:ADMIN')->get('/logs', [Logs::class, 'consultar']);

$routerAdapter->dispatch();
