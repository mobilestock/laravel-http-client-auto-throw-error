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
        'Access-Control-Allow-Methods: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, DELETE, PUT, POST, GET'
    );
    die();
}

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

session_set_cookie_params(86400);
session_start();

use api_administracao\Controller\TipoFrete;
use api_cliente\Controller\AutenticaUsuario;
use api_cliente\Controller\Campanhas;
use api_cliente\Controller\Cancelamento;
use api_cliente\Controller\CatalogoPersonalizado;
use api_cliente\Controller\Cliente;
use api_cliente\Controller\ColaboradoresEnderecos;
use api_cliente\Controller\Configuracao;
use api_cliente\Controller\ConsumidorFinal;
use api_cliente\Controller\Historico;
use api_cliente\Controller\LinksPagamento;
use api_cliente\Controller\LinksPagamentoPublico;
use api_cliente\Controller\MobileEntregas;
use api_cliente\Controller\Painel;
use api_cliente\Controller\PedidoCliente;
use api_cliente\Controller\Produto;
use api_cliente\Controller\TipoFrete as ApiClienteTipoFrete;
use api_cliente\Controller\Trocas;
use api_cliente\Controller\Usuario;
use api_cliente\Controller\UsuarioPublic;
use api_estoque\Controller\Acompanhamento;
use Illuminate\Routing\Router;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\helper\RouterAdapter;
use Psr\Log\LogLevel;

$routerAdapter = app(RouterAdapter::class);

$rotas = $routerAdapter->router;
$router = $routerAdapter->routerLaravel;

$rotas->namespace('\\api_cliente\Controller')->group(null);
$rotas->get('/', 'Erro');
/*
 json :{"username": "","password": ""}
 retorno:{
    "status": true,
    "message": "sucesso!",
    "data": {
        "id": "XXX",
        "nome": "XXX",
        "email": "XXXXXX",
        "nivel_acesso": "X",
        "id_colaborador": "X",
        "cnpj": "xxxx",
        "telefone": "xxxxx",
        "token": "xxxx",
        "data_cadastro": "2021-03-29 13:51:02",
        "data_atualizacao": "2021-03-29 14:08:20",
        "Authorization": "xcxvxvvx"
      }
}
 */
$rotas->group('/autenticacao');
// $rotas->post("/", "AutenticaUsuario:validaUsuario");
$rotas->post('/token', 'AutenticaUsuario:validaUsuarioPorTokenTemporario');

$router
    ->middleware(SetLogLevel::class . ':' . LogLevel::EMERGENCY)
    ->prefix('/autenticacao')
    ->group(function (Router $router) {
        $router->get('/filtra_usuarios', [AutenticaUsuario::class, 'filtraUsuarioLogin']);
        $router->post('/', [AutenticaUsuario::class, 'autenticaUsuario']);
        $router->post('/enviar_link_redefinicao/{id_colaborador}', [AutenticaUsuario::class, 'enviarLinkRedefinicao']);
        $router->post('/med/autentica', [AutenticaUsuario::class, 'autenticaMed']);
    });

$rotas->group(null);
/*$rotas->get("/dadoscolaborador/{id}","Colaborador:buscaDados");*/

$rotas->get('/sexos', 'ProdutosFiltros:listaSexos');
$rotas->get('/cores_materiais', 'ProdutosFiltros:listaCoresEMateriais');
$rotas->get('/linhas', 'ProdutosFiltros:listaLinhas');
$rotas->get('/categorias', 'ProdutosFiltros:listaCategorias');
$rotas->get('/categorias_lista', 'ProdutosFiltros:listaCategorias_lista');
$rotas->get('/cliente/foto', 'Painel:buscaFotoPerfil');
$rotas->get('/verificar_dados_faltantes', 'Usuario:verificarDadosFaltantes');
$rotas->put('/completar_dados_faltantes', 'Usuario:completarDadosFaltantes');

$router->middleware('permissao:TODOS')->group(function (Router $router) {
    $router->get('/autocomplete_endereco', [ColaboradoresEnderecos::class, 'autoCompletarEnderecoDigitado']);
    $router->get('/entregas_cliente', [Historico::class, 'exibeQrcodeEntregasProntas']);
});

$router->post('/adicionar_permissao_fornecedor', [Usuario::class, 'adicionarPermissaoFornecedor']);

$router->get('/busca_tipo_frete', [ApiClienteTipoFrete::class, 'listaLocaisEntrega']);
$router->get('/entregadores_proximos', [TipoFrete::class, 'buscaEntregadoresProximos']);

/**
 * query=> busca: string (obrigatoria)
 * retorno :{
 *     "status": true,
 *     "message": "sucesso!",
 *     "data": {[
 *      "id": 1,
 *      "nome": "Lançamentos"
 *    ]}
 */
/**
 * @deprecated
 * Criar filtros da pesquisa igual ao MeuLook
 */
$rotas->get('/filtros_de_ordenacao', 'ProdutosFiltros:filtrosDeOrdenacao');

$rotas->get('/filtros_de_ordenacao_logado', 'ProdutosFiltros:filtrosDeOrdenacaoLogado');

/**
 * query=> busca: string (obrigatoria)
 * retorno :{
 *     "status": true,
 *     "message": "sucesso!",
 *     "data": {[
 *      "nome": string (obrigatoria),
 *      "id": string (obrigatoria),
 *      "nome_comercial": string (obrigatoria)}
 *    }}
 */
// $rotas->get("/produtos/pesquisa_por_produto", "ProdutosFiltros:pesquisaPorNomeDescricaoId");

/*json:{ //todos os campos podem ser null
      "fornecedor": "0",
      "num_pagina": "0",
      "ordenar": "1",
      "foto_calcada":false/true,
      "id": "0",
      "descricao": "",
      "categoria": "0",
      "linha": "0"
    }
  Retorno*/
// $rotas->get("/consulta_catalogo", "Produto:consultaCatalogo");
// $rotas->get("/consulta_catalogo_mobile", "Produto:consultaCatalogoMobile");

/*não tem paramentro*/
// $rotas->get("/produtos", "Produto:lista");
/*não tem paramentro*/
/*passa o id do produto na url*/
// $rotas->get("/produto/{id}", "Produto:busca");
/*passa o id do produto na url*/
// $rotas->get("/estoque/{id}", "Produto:buscaEstoque");
/*passa o id do produto na url*/
/* Grava o acesso do produto de maneira assíncrona */
$rotas->get('/produto/{id}/grava_acesso', 'Produto:acessaProduto');

// $rotas->get("/produto/faq/{id_produto}", "Produto:buscaFaqProdutos");
// $rotas->get("/produto_fornecedor/faq/{id_fornecedor}", "Produto:buscaFaqProdutos");
// $rotas->post("/produto/faq/pergunta", "Produto:criaDuvida");
$rotas->post('/produto/faq/responde', 'Produto:respondeDuvida');
$rotas->post('/produto/sugestao', 'Produto:insereSugestaoProduto');
/*$rotas->get("/catalogo", "Catalogo:catalogo");*/

$router
    ->middleware([SetLogLevel::class . ':' . LogLevel::CRITICAL, 'permissao:CLIENTE'])
    ->prefix('/pedido')
    ->group(function (Router $router) {
        $router->post('/', [PedidoCliente::class, 'criaPedido']);
    });

/*Rotas de cancelamento*/
/*Passa o id do faturamento na url */
//$rotas->delete('/faturamento/{id}', 'Cancelamento:RemoveFaturamento');
/*Passa o id da transacao na url */
$router->delete('/cancela_transacao/{id_transacao}', [Cancelamento::class, 'removeTransacao']);
/*Passa o id do item da transação na url */
$rotas->delete('/produto_pago/{uuid}', 'Cancelamento:removeProdutoPago');
// Passa o id encriptado e id do faturamento por um array
//$rotas->delete("/cancela_transacao_faturamento/{id}", "Cancelamento:cancelamentoTransacaoFaturamento");

$rotas->group('cliente');
$rotas->get('/cidades/{uf}', 'Cliente:buscaCidade'); /*Passa o id do Colaborador retorna se existe id zoop */
$rotas->get('/cidades', 'Cliente:buscaCidade'); /*Passa o id do Colaborador retorna se existe id zoop */
$rotas->get('/estados', 'Cliente:buscaUF'); /*Passa o id do Colaborador retorna se existe id zoop */
$rotas->post('/redefinir', 'Cliente:editPassword');
$rotas->post('/photo/edit', 'Cliente:editPhoto'); /*Passa o id do Colaborador retorna se existe id zoop */
$rotas->get('/verifica_telefone_errado', 'Cliente:verificaTelefoneErrado');

$router->prefix('/cliente')->group(function (Router $router) {
    $router->post('/', [UsuarioPublic::class, 'adicionaUsuario']);

    $router->middleware('permissao:TODOS')->group(function (Router $router) {
        $router->post('/atualiza_localizacao', [Cliente::class, 'atualizaLocalizacao']);
        $router->post('/editar', [Cliente::class, 'editColaborador']);
    });

    $router
        ->middleware('permissao:TODOS')
        ->prefix('/endereco')
        ->group(function (Router $router) {
            $router->get('/buscar_dados', [Cliente::class, 'buscarDados']);
            $router->post('/novo', [ColaboradoresEnderecos::class, 'novoEndereco']);
            $router->delete('/excluir/{idEndereco}', [ColaboradoresEnderecos::class, 'excluirEndereco']);
            $router->get('/listar/{idColaborador?}', [ColaboradoresEnderecos::class, 'listarEnderecos']);
            $router->get('/{idEndereco}', [ColaboradoresEnderecos::class, 'buscarEndereco']);
            $router->post('/definir_padrao', [ColaboradoresEnderecos::class, 'definirEnderecoPadrao']);
        });
});

$router->prefix('/pedido')->group(function (Router $router) {
    $router->get('/lista', [Painel::class, 'listaProdutosPedido']);
});
// $rotas->post('/altera_cliente', 'Painel:alteraClientePedidoItem');

$rotas->group('trocas');
//$rotas->post("/inserirTrocasAgendadas", "Produto:inserirTrocaAgendada");
//$rotas->delete("/removeTrocaAgendada", "Produto:removeTrocaAgendada");
// $rotas->get("/verifica_defeito", "Produto:verificaDefeito");
$rotas->post('/abrir_disputa', 'Trocas:abrirDisputaSolicitarTroca');
$rotas->post('/reabrir_troca', 'Trocas:reabrirTroca');
$rotas->post('/insere_novas_fotos_defeito', 'Trocas:insereNovasFotosDefeito');
$router
    ->prefix('permissao:CLIENTE')
    ->prefix('/trocas')
    ->group(function (Router $router) {
        $router->get('/lista', [Trocas::class, 'listaPedidosTroca']);
        $router->get('/agendadas', [Produto::class, 'listarTrocasAgendadas']);
        $router->post('/agendamento_defeito', [Trocas::class, 'criaSolicitacaoDefeito']);
        $router->post('/desistir_troca', [Trocas::class, 'desisteTroca']);

        $router
            ->middleware(SetLogLevel::class . ':' . LogLevel::EMERGENCY)
            ->post('/gerar_pix', [Trocas::class, 'geraPixTroca']);
    });

$rotas->group('historico');
$rotas->post('/avaliacao', 'Historico:insereAvaliacao');

$router
    ->middleware('permissao:CLIENTE')
    ->prefix('historico')
    ->group(function (Router $rotas) {
        $rotas->get('/', [Historico::class, 'listaPedidos']);
        $rotas->get('/pagamentos_abertos', [Historico::class, 'pagamentosAbertos']);
        $rotas->get('/busca/produtos_pedido_sem_entrega', [Historico::class, 'buscaProdutosPedidoSemEntrega']);
        $rotas->get('/busca/produtos_pedido_com_entrega/{id_entrega}', [
            Historico::class,
            'buscaProdutosPedidoComEntrega',
        ]);
    });

$router->prefix('/campanhas')->group(function (Router $router) {
    $router->get('/', [Campanhas::class, 'buscarUltimaCampanha']);
});
/** Rotas do painel */
$rotas->group('/painel');
// $rotas->get("/", "Painel:buscaPainel");
// $rotas->post("/adiciona", "Painel:adicionaProdutoPainel");
$rotas->post('/adicionaProdutos', 'Painel:adicionaProdutoPainelStorage');
$rotas->delete('/deleta', 'Painel:deletaProdutoPainel');
// $rotas->post('/guarda_deslogado', 'Painel:guardaProdutosUsuarioDeslogadoNaSessao');
$rotas->get('/saldo', 'Painel:saldoCliente');
/*não precisa passar parametro*/
$rotas->get('/freteiros', 'Painel:listaFreteiros');
/*json:
      {
        "uf":"XX",
        "frete":"0",
        "pares":"00"
      }

      retorno
      {
      "status": true,
      "message": "sucesso!",
      "data": {
          "valor_frete": 0,
          "texto": "text",
          "localizacao": null,
          "imagem": null
      }
    }*/
// $rotas->get('/ultimo_frete', 'Painel:buscaUltimoFrete');
// $rotas->post('/calculo_frete', 'Painel:consultaValorFreteCliente');
// Rota utilizada nos apps de entrega e interno
$router->get('/sentry_esta_ativo', [Configuracao::class, 'sentry']);
$router->get('/configuracoes_produto_pago', [Configuracao::class, 'configuracoesProdutoPago']);

$router
    ->prefix('/link_pagamento')
    ->middleware(SetLogLevel::class . ':' . LogLevel::CRITICAL)
    ->group(function (Router $router) {
        $router->get('/{idMd5}', [LinksPagamentoPublico::class, 'buscaInfoLink']);
        $router->post('/credito', [LinksPagamento::class, 'credito']);
    });

$router->prefix('/consumidor_final')->group(function (Router $router) {
    $router->post('/salva_consumidor_final', [ConsumidorFinal::class, 'salvaConsumidorFinal']);
});

// $rotas->group('/mensageria');
// $rotas->post('/callback_lambda', 'Mensageria:callbackLambda');

$router
    ->prefix('/ponto_retirada')
    ->middleware('permissao:TODOS')
    ->group(function (Router $router) {
        $router->get('/', [Cliente::class, 'buscaPontosRetirada']);
    });

$router
    ->prefix('/transacoes')
    ->middleware('permissao:TODOS')
    ->group(function (Router $router) {
        $router->delete('/direito_item/{uuid_produto}', [Historico::class, 'cancelamento']);
    });

$router->prefix('/catalogo_personalizado')->group(function (Router $router) {
    $router->post('/criar', [CatalogoPersonalizado::class, 'criarCatalogo']);
    $router->get('/buscar_lista', [CatalogoPersonalizado::class, 'buscarListaCatalogos']);
    $router->get('/buscar_lista_publicos', [CatalogoPersonalizado::class, 'buscarListaCatalogosPublicos']);
    $router->get('/buscar_por_id/{idCatalogo}', [CatalogoPersonalizado::class, 'buscarCatalogoPorId']);
    $router->put('/editar', [CatalogoPersonalizado::class, 'editarCatalogo']);
    $router->delete('/deletar/{idCatalogo}', [CatalogoPersonalizado::class, 'deletarCatalogo']);
    $router->post('/adicionar_produto_catalogo', [CatalogoPersonalizado::class, 'adicionarProdutoCatalogo']);
});

$router
    ->prefix('/acompanhamento')
    ->middleware('permissao:TODOS')
    ->group(function (Router $router) {
        $router->post('/acompanhar', [Acompanhamento::class, 'adicionarAcompanhamentoDestino']);
        $router->delete('/desacompanhar/{idAcompanhamento}', [Acompanhamento::class, 'removerAcompanhamentoDestino']);
        $router->post('/pausar/{uuidProduto}', [Acompanhamento::class, 'pausarAcompanhamento']);
        $router->post('/despausar', [Acompanhamento::class, 'despausarAcompanhamento']);
    });

$router
    ->prefix('/mobile_entregas')
    ->middleware('permissao:TODOS')
    ->group(function (Router $router) {
        $router->get('/detalhes_frete_endereco/{id_endereco}', [MobileEntregas::class, 'buscaDetalhesFreteDoEndereco']);
        $router->get('/detalhes_compra', [MobileEntregas::class, 'buscaDetalhesPraCompra']);
        $router->get('/historico_compras/{pagina}', [MobileEntregas::class, 'buscaHistoricoCompras']);
        $router->delete('/limpar_carrinho', [MobileEntregas::class, 'limparCarrinho']);
        $router->post('/calcular_quantidades_frete_expresso', [
            MobileEntregas::class,
            'calcularQuantidadesFreteExpresso',
        ]);
        $router->get('/colaboradores_coleta', [MobileEntregas::class, 'buscarColaboradoresParaColeta']);
        $router->post('/criar_transacao', [MobileEntregas::class, 'criarTransacao']);
        $router->get('/coletas_anteriores', [MobileEntregas::class, 'buscaColaboradoresColetasAnteriores']);
        $router->get('/relatorio_coletas', [MobileEntregas::class, 'buscaRelatorioColetas']);
    });

$router->get('/estados', [ColaboradoresEnderecos::class, 'buscaEstados']);
$router->get('/fretes_por_estado/{estado}', [ColaboradoresEnderecos::class, 'buscaFretesPorEstado']);

$routerAdapter->dispatch();
