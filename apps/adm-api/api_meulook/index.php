<?php

// https://github.com/mobilestock/backend/issues/159
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('content-type: text/html; charset=utf-8');

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
        'Access-Control-Allow-Methods: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, PATCH, DELETE, PUT, POST, GET'
    );
    die();
}

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use api_meulook\Controller\Carrinho;
use api_meulook\Controller\ChatAtendimento;
use api_meulook\Controller\Colaboradores;
use api_meulook\Controller\ColaboradoresPublic;
use api_meulook\Controller\Configuracoes;
use api_meulook\Controller\Entregadores;
use api_meulook\Controller\Historico;
use api_meulook\Controller\ModoAtacado;
use api_meulook\Controller\Produtos;
use api_meulook\Controller\ProdutosPublic;
use api_meulook\Controller\Publicacoes;
use api_meulook\Controller\PublicacoesPublic;
use api_meulook\Controller\Trocas;
use MobileStock\helper\Middlewares\SetLogLevel;
use Psr\Log\LogLevel;
use Illuminate\Routing\Router;
use MobileStock\helper\Middlewares\HeaderManual;
use MobileStock\helper\RouterAdapter;

$routerAdapter = app(RouterAdapter::class);

$rotas = $routerAdapter->router;
$router = $routerAdapter->routerLaravel;

$rotas->namespace('\\api_meulook\Controller');

$router->middleware('permissao:TODOS')->get('/vendas', [Publicacoes::class, 'listaVendasPublicacoes']);
$rotas->get('/totais_comissoes', 'Publicacoes:consultaTotaisComissoes');
$rotas->get('/ultima_movimentacao/{id_colaborador}', 'ColaboradoresPublic:buscaUltimaMovimentacaoColaborador');

$router->get('/configuracoes', [Configuracoes::class, 'buscaConfiguracoes']);

$rotas->group('colaboradores');
$rotas->post('/atualiza_telefone', 'Colaboradores:atualizaTelefone');
$rotas->get('/nome_usuario', 'Colaboradores:buscaNomeUsuario');
$rotas->get('/filtro_autocomplete', 'ColaboradoresPublic:buscaAutocompleteFiltro');
//   $rotas->post('/seguidores/{usuarioMeuLook}', 'ColaboradoresPublic:buscaListaSeguidores');
$rotas->post('/seguidores', 'ColaboradoresPublic:buscaListaSeguidores');
//   $rotas->post('/seguindo/{usuarioMeuLook}', 'ColaboradoresPublic:buscaListaSeguindo');
$rotas->post('/seguindo', 'ColaboradoresPublic:buscaListaSeguindo');
// $rotas->get('/influencers_recomendados', 'Colaboradores:buscaRecomendacoesInfluencers');
$rotas->post('/recuperar_login/{id}', 'ColaboradoresPublic:recuperarLogin');
$rotas->get('/busca_permissao', 'Colaboradores:buscaPermissao');
$rotas->post('/preenche_autenticacao', 'Colaboradores:preencheAutenticacao');
$rotas->post('/bloqueia_postar_look/{id}', 'Colaboradores:bloqueiaColaboradorPostar');
$rotas->post('/desbloqueia_postar_look/{id}', 'Colaboradores:desbloqueiaColaboradorPostar');
$rotas->get('/verifica_bloqueado', 'Colaboradores:verificaSeBloqueado');
$rotas->post('/busca_por_hash', 'ColaboradoresPublic:buscaUsuarioPorHash');
$rotas->post(
    '/completar_cadastro_influencer_oficial/{id_usuario}',
    'ColaboradoresPublic:completarCadastroInfluencerOficial'
);
$rotas->post('/preencher_dados', 'Colaboradores:preencherDadosColaborador');
$rotas->post('/verificar_endereco_digitado', 'Colaboradores:verificaEnderecoDigitado');
$rotas->get('/filtra_usuarios/recuperacao_senha', 'ColaboradoresPublic:filtraUsuariosRedefinicaoSenha');

$router->prefix('/colaboradores')->group(function (Router $router) {
    $router->get('/busca_usuario/{id}', [ColaboradoresPublic::class, 'buscaUsuarioPorID']);
    $router->get('/dados_reputacao/{id_colaborador}', [ColaboradoresPublic::class, 'buscaDadosReputacao']);
    $router->get('/perfil/{usuario_meulook}', [ColaboradoresPublic::class, 'buscaPerfilMeuLook']);
    $router->get('/requisitos_melhores_fabricantes', [ColaboradoresPublic::class, 'requisitosMelhoresFabricantes']);
    $router->get('/fornecedores', [ColaboradoresPublic::class, 'buscaFornecedores']);

    $router->middleware('permissao:TODOS')->group(function (Router $router) {
        $router->patch('/atualizar_metodo_envio/{id_tipo_frete}', [Colaboradores::class, 'atualizarMetodoEnvioPadrao']);
        $router->post('/edita_cadastro', [Colaboradores::class, 'editaCadastro']);
        $router->get('/busca_cadastro', [Colaboradores::class, 'buscaCadastro']);
        $router->get('/busca_saldo_detalhes', [Colaboradores::class, 'buscaSaldoEmDetalhe']);
        $router->get('/saldo', [Colaboradores::class, 'buscaSaldo']);
        $router->get('/endereco_entrega_atual', [Colaboradores::class, 'buscaEnderecoDeEntrega']);
    });
});

// https://github.com/mobilestock/backend/issues/193
$rotas->group('ponto_de_entrega');
// $rotas->get('/', 'Colaboradores:listaPontosRetirada');
$rotas->get('/busca_consumidores_ponto', 'Colaboradores:buscaConsumidoresPonto');
$rotas->get('/busca_historico_consumidor/{id}', 'Colaboradores:buscaHistoricoConsumidor');
$rotas->post('/validar_posicao_ponto', 'Colaboradores:validarPosicaoPonto');
$rotas->post('/avaliar', 'Colaboradores:avaliar');
$rotas->patch('/adiar_avaliacao', 'Colaboradores:adiarAvaliacao');
$rotas->get('/avaliacoes_ponto/{id}', 'ColaboradoresPublic:avaliacoesPonto');
$rotas->get('/busca/situacao_ponto', 'Colaboradores:buscaSituacaoPonto');

$router->prefix('/transportadores')->group(function (Router $router) {
    $router->get('/selecionado/{id_transacao}', [Colaboradores::class, 'pontoSelecionadoPraTransacao']);

    $router->middleware('permissao:CLIENTE')->group(function (Router $router) {
        $router->post('/', [Colaboradores::class, 'seTornarPonto']);
        $router->get('/avaliacoes', [Colaboradores::class, 'avaliacoesConsumidor']);
    });
    $router->middleware('permissao:TODOS')->get('/vendas_ponto', [Colaboradores::class, 'vendasAbertoPonto']);
    $router->middleware('permissao:ENTREGADOR,PONTO_RETIRADA')->post('/trocas', [Trocas::class, 'bipaTrocas']);
});

$router->prefix('/entregadores')->group(function (Router $router) {
    $router->get('/raios', [Entregadores::class, 'buscaRaios']);
    $router->post('/', [Entregadores::class, 'solicitarCadastro']);
});

$rotas->group('publicacoes');
// $rotas->get('/{id}', 'PublicacoesPublic:buscaPublicacaoCompleto');
// $rotas->post('/', 'Publicacoes:cadastro');
$rotas->post('/stories', 'Publicacoes:criaPublicacaoStorie');
$rotas->get('/stories', 'PublicacoesPublic:consultaStories');
$rotas->post('/stories/like/{id_publicacao}', 'Publicacoes:alteraCurtirStories');
//$rotas->get('/produtos_disponiveis', 'Publicacoes:produtosParaPostagem');
$rotas->delete('/{id}', 'Publicacoes:remove');

$router->prefix('publicacoes')->group(function (Router $router) {
    $router->get('/catalogo', [ProdutosPublic::class, 'catalogoProdutos']);
    $router->get('/filtros', [PublicacoesPublic::class, 'filtrosCatalogo']);
    $router->get('/publicacoes_influencer/{usuarioMeuLook}', [PublicacoesPublic::class, 'buscaPublicacoesInfluencer']);
    $router->post('/gerar_catalogo_pdf', [PublicacoesPublic::class, 'gerarCatalogoPdf']);
    $router->get('/pesquisas_populares', [PublicacoesPublic::class, 'buscaPesquisasPopulares']);

    $router->prefix('/produto/{id_produto}')->group(function (Router $router) {
        $router->get('/', [PublicacoesPublic::class, 'buscaProdutoPublicacao']);
        $router->get('/detalhes', [PublicacoesPublic::class, 'buscaDetalhesProdutoPublicacao']);
    });
});

$rotas->group('carrinho');
$rotas->post('/pronta_entrega/gerir', 'Carrinho:gerirProntaEntrega');

$router->prefix('/carrinho')->group(function (Router $router) {
    $router->middleware('permissao:CLIENTE')->group(function (Router $router) {
        $router->post('/', [Carrinho::class, 'adicionaProdutoCarrinho']);
        $router->delete('/{uuid_produto}', [Carrinho::class, 'removeProdutoCarrinho']);
        $router->get('/', [Carrinho::class, 'buscaProdutosCarrinho']);
        $router->get('/entrega_disponivel', [Carrinho::class, 'buscaEntregaDisponivel']);
        $router->post('/pronta_entrega/comprar', [Carrinho::class, 'comprarProntaEntrega']);
    });

    $router->post('/foguinho', [ProdutosPublic::class, 'buscaFoguinho']);
});

$rotas->group('transacoes');
$rotas->get('/rastrear', 'Historico:rastreioTransportadora');

$router->prefix('transacoes')->group(function (Router $rotas) {
    $rotas->post('/esqueci_troca', [Trocas::class, 'criaTransacaoEsqueciTrocasPedido']);
    $rotas->get('pagamentos_abertos/esqueci_troca', [Trocas::class, 'buscaTransacoesEsqueciTroca']);

    $rotas->middleware('permissao:CLIENTE')->group(function (Router $rotas) {
        $rotas->get('/historico/{pagina}', [Historico::class, 'buscaHistoricoPedidos']);
        $rotas->post('/{id_transacao}/endereco', [Historico::class, 'alteraEnderecoEntregaDaTransacao']);
        $rotas
            ->middleware(SetLogLevel::class . ':' . LogLevel::EMERGENCY)
            ->post('/criar', [Carrinho::class, 'criarTransacao']);
    });
});

$router
    ->prefix('/negociacao')
    ->middleware('permissao:CLIENTE')
    ->group(function (Router $router) {
        $router->get('/busca_abertas', [Historico::class, 'buscaNegociacoesAbertas']);
        $router->get('/busca_itens_oferecidos/{uuid_produto}', [Historico::class, 'buscaItensOferecidos']);
        $router->post('/aceitar', [Historico::class, 'aceitarNegociacao']);
    });

$rotas->group('trocas');
$router->prefix('/trocas')->group(function (Router $router) {
    $router
        ->middleware('permissao:ENTREGADOR,PONTO_RETIRADA')
        ->delete('/{uuid_produto}', [Trocas::class, 'removeTrocaAgendada']);
    $router->middleware('permissao:CLIENTE')->group(function (Router $router) {
        $router->get('/informacao_sobre_agendamento', [Trocas::class, 'informacaoSobreTrocaAgendada']);
        $router->post('/agendamento_normal', [Trocas::class, 'insereTrocaAgendadaNormal']);
    });
});

$rotas->group('produtos');
// $rotas->get('/{id}/publicacoes/lista', 'ProdutosPublic:buscaListaPublicacoesProduto');
$rotas->post('/consulta', 'ProdutosPublic:buscaInfosProdutos');
$rotas->post('/avaliar', 'Produtos:avaliarProduto');
$rotas->get('/avaliacoes_pendentes', 'Produtos:avaliacoesPendentes');
$rotas->patch('/adiar_avaliacao/{id_avaliacao}', 'Produtos:adiarAvaliacao');
$rotas->get('/avaliacoes_produto/{id_produto}', 'ProdutosPublic:avaliacoesProduto');
$rotas->delete('/deleta_avaliacao/{id_avaliacao}', 'Produtos:deletaAvaliacao');
$rotas->post('/alterna_produto_lista_desejo/{id_produto}', 'Produtos:alternaProdutoListaDesejo');
$rotas->get('/autocomplete_pesquisa', 'ProdutosPublic:autocompletePesquisa');

$router->prefix('produtos')->group(function (Router $router) {
    $router
        ->middleware(SetLogLevel::class . ':' . LogLevel::EMERGENCY)
        ->post('/criar_registro_pesquisa', [ProdutosPublic::class, 'criarRegistroPesquisaOpensearch']);

    $router->get('/pesquisa', [ProdutosPublic::class, 'pesquisa']);

    $router->middleware('permissao:CLIENTE')->group(function (Router $router) {
        $router->get('/{id_produto}/previsao_cliente', [
            ProdutosPublic::class,
            'buscaPrevisaoDeEntregaParaColaborador',
        ]);
        $router->get('/busca_lista_desejos', [Produtos::class, 'buscaListaDesejos']);
        $router->get('/busca_metodos_envio/{id_produto?}', [ProdutosPublic::class, 'buscaMetodosEnvio']);
    });
});

$rotas->group('ranking');
// $rotas->get('/influencers_oficiais', 'RankingPublic:buscaTopInfluencersOficiais');
$rotas->get('/apuracao/{ranking}', 'RankingPublic:buscaRankingsApuracao');
$rotas->get('/quantidades_apuracao/{ranking}/{mes}', 'RankingPublic:buscaQuantidadesApuracao');
// $rotas->get('/concluido/{ranking}', 'RankingPublic:buscaUltimoRankingConcluido');
//   $rotas->get('/vendas/andamento/{idColaborador}', 'Ranking:vendasAndamentoColaborador');
$rotas->get('/vendas/apuracao/{idLancamento}', 'Ranking:vendasApuracaoColaborador');

$rotas->group('premiacao_ranking');
$rotas->post('/fechamento_ranking', 'RankingPublic:fechamentoRanking');
// $rotas->post('/pagamento_ranking', 'RankingPublic:pagamentoRanking');

$rotas->group('logging');
// $rotas->post('/link', 'Logging:logLink');
$rotas->get('/pesquisa', 'Logging:buscaLogsDePesquisas');
$rotas->post('/requisicao', 'Logging:logRequisicao');

$router
    ->prefix('/chat_atendimento')
    ->middleware(HeaderManual::class . ':header-manual,SECRET_TOKEN_WHATSAPP')
    ->group(function (Router $router) {
        $router->post('/dados_suporte', [ChatAtendimento::class, 'dadosWhatsAppAtendimento']);
        $router->post('busca_previsao', [ChatAtendimento::class, 'buscaPrevisao']);
    });

$router
    ->middleware('permissao:TODOS')
    ->prefix('/modo_atacado')
    ->group(function (Router $router) {
        $router->patch('/alterna', [ModoAtacado::class, 'alternaModoAtacado']);
        $router->get('/esta_ativo', [ModoAtacado::class, 'estaAtivo']);
    });

$routerAdapter->dispatch();
