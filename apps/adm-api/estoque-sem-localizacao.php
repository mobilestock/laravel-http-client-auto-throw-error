<?php
use MobileStock\repository\EstoqueRepository;
use MobileStock\service\ProdutoService;

require_once 'cabecalho.php';
require_once 'classes/estoque.php';
require_once 'classes/localizacao.php';
require_once 'classes/produtos.php';
require_once __DIR__ . '/regras/alertas.php';

acessoUsuarioVendedor();

if (($pesquisa = isset($_POST['pesquisa']) ? $_POST['pesquisa'] : false) || ($pesquisa = isset($_GET['cod']) ? $_GET['cod'] : false)) {
    $_SESSION['pesquisa'] = $pesquisa;

    $descricao = "SELECT produtos.id,produtos.descricao,
                    COALESCE((
                        SELECT SUM(estoque_grade.estoque+estoque_grade.vendido)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos.id
                            AND estoque_grade.id_responsavel = 1
                    ), 0) estoque,
                    (SELECT MAX(usuarios.nome) from usuarios where produtos_aguarda_entrada_estoque.usuario_resp = usuarios.id) usuario_resp, 
                    produtos.localizacao, 
                    COALESCE(SUM(produtos_aguarda_entrada_estoque.qtd), 0) qtd_aguardando_entrada,
                    COALESCE(produtos.proporcao_caixa, 1) proporcao_caixa
                FROM produtos 
                    LEFT OUTER JOIN produtos_aguarda_entrada_estoque ON (produtos.id = produtos_aguarda_entrada_estoque.id_produto AND produtos_aguarda_entrada_estoque.em_estoque = 'F')
                WHERE 1=1 ";

    $pesquisa = preg_replace('/_[0-9A-z]/', "", $pesquisa);
    if (is_numeric($pesquisa)) {
        $descricao .= " AND (
                produtos.id = (
                    SELECT produtos_grade.id_produto
                    FROM produtos_grade
                    WHERE produtos_grade.cod_barras = '$pesquisa'
                ) OR produtos.id = (
                    SELECT compras_itens_caixas.id_produto
                    FROM compras_itens_caixas
                    WHERE compras_itens_caixas.codigo_barras = '$pesquisa'
                )
            ) ";
    } else if (preg_match('/,/', $pesquisa)) {
        $descricao .= " AND (
            EXISTS(
                SELECT 1
                FROM produtos_grade
                WHERE produtos_grade.id_produto = produtos.id
                    AND produtos_grade.cod_barras IN ($pesquisa)
            ) OR EXISTS(
                SELECT 1
                FROM compras_itens_caixas
                WHERE compras_itens_caixas.id_produto = produtos.id
                    AND compras_itens_caixas.codigo_barras IN ($pesquisa)
            )
        ) ";
    } else {
        $descricao .= "AND LOWER(produtos.descricao) LIKE LOWER('%{$pesquisa}%') ";
    }
    $descricao .= "GROUP BY produtos_aguarda_entrada_estoque.id_produto ORDER BY qtd_aguardando_entrada DESC";
} else {
    $idResponsavel = idUsuarioLogado();
    $descricao = "SELECT  produtos.id,produtos.descricao,
                    (
                        SELECT SUM(estoque_grade.estoque+estoque_grade.vendido)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos.id
                            AND estoque_grade.id_responsavel = 1
                    ) estoque,
                    produtos.localizacao, 
                    produtos_aguarda_entrada_estoque.usuario_resp = $idResponsavel responsavel,
                    SUM(produtos_aguarda_entrada_estoque.qtd) qtd_aguardando_entrada,
                    SUM(produtos.id),
                    COALESCE(produtos.proporcao_caixa, 1) proporcao_caixa,
                    (SELECT usuarios.nome from usuarios where usuarios.id = produtos_aguarda_entrada_estoque.usuario_resp) usuario_resp
                FROM produtos      
                    LEFT OUTER JOIN produtos_aguarda_entrada_estoque ON produtos_aguarda_entrada_estoque.em_estoque = 'F' AND 
                        produtos_aguarda_entrada_estoque.id_produto = produtos.id
                WHERE produtos_aguarda_entrada_estoque.qtd > 0 
                GROUP by produtos.id               
                ORDER BY responsavel DESC, produtos_aguarda_entrada_estoque.data_hora ASC LIMIT 5";
}
if (isset($_POST['limpar'])) {

    unset($_SESSION['pesquisa']);
}

$locais = buscaLocalizacoes();
$estoque = buscaProdutoLocalizacao($descricao);
?>

<style>
    .modal-content {
        border: unset;
        height: auto;
        min-height: 100%;
    }

    .modal-dialog {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
    }

    .checkmark[type=checkbox] {
        -ms-transform: scale(1.3);
        -moz-transform: scale(1.3);
        -webkit-transform: scale(1.3);
        -o-transform: scale(1.3);
        transform: scale(1.3);
        padding: 0.625rem;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/estoque-sem-localizacao.css">

<a href="produtos-aguardando-entrada.php" class="ml-2 mb-2 btn v-chip v-chip--clickable v-chip--link v-chip--no-color theme--dark v-size--small">
    <span class="v-chip__content">
        <i class="v-icon notranslate mdi mdi-arrow-left theme--dark"></i>
    </span>
</a>

<br>
<div id="carrega_" class="m-4 d-none">
    <div id="preloader_1">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
</div>
<nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
        <a class="nav-item nav-link active" id="nav-localizacao-tab" data-toggle="tab" href="#nav-localizacao" role="tab" aria-controls="nav-localizacao" aria-selected="true"><b>localização</b></a>
        <a class="nav-item nav-link" id="nav-relatorio-tab" data-toggle="tab" href="#nav-relatorio" role="tab" aria-controls="nav-relatorio" aria-selected="false"><b>Relatório</b></a>
        <a class="nav-item nav-link" id="nav-etiquetas-tab" data-toggle="tab" href="#nav-etiquetas" role="tab" aria-controls="nav-etiquetas" aria-selected="false"><b>Etiquetas</b></a>
        <a class="nav-item nav-link" id="nav-log-tab" data-toggle="tab" href="#nav-log" role="tab" aria-controls="nav-log" aria-selected="false"><b>Logs</b></a>
    </div>
</nav>

<div class="tab-content body-novo" id="nav-tabContent">
    <div class="tab-pane fade show active" id="nav-localizacao" role="tabpanel" aria-labelledby="nav-localizacao-tab">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3><b>Estoque sem localização</b></h3><br>

                <div class="btn-sm btn btn-danger p-2 mr-2" onclick="hrefDesempenho(<?= idUsuarioLogado() ?>)">
                    <?= ucfirst(usuarioLogado()) ?> -
                    <?= EstoqueRepository::buscaQtdTotalParesGuardadosPorEstoquista(idUsuarioLogado()); ?> pares
                </div>
            </div>
            <label>Buscar Modelo</label>

            <div id="cod_barras_div" style="display: block;"> Código de barras
                <a onclick="$('#carrega_').removeClass('d-none');" href="http://zxing.appspot.com/scan?ret=<?= $_ENV['URL_MOBILE'] ?>estoque-sem-localizacao.php?cod={CODE}">Leitor</a>
            </div>

            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <div class="input-group mb-3">
                    <input type="text" class="form-control pesquisa" placeholder="pesquisar" id="pesquisa" name="pesquisa" value="<?= isset($pesquisa) ? $pesquisa : null ?>">

                    <div class="input-group-append">
                        <button class="enviar esconde btn btn-outline-success">Buscar</button>
                    </div>

                    <?php if (isset($_POST['pesquisa']) && $_POST['pesquisa'] != "") { ?>
                        <div class="input-group-append">
                            <b><a onclick="$('#pesquisa').val('')" class="btn btn-danger" name="limpar">X</a></b>
                        </div>
                    <?php } ?>

                </div>
            </form>
            <?php

            $tamanho_vetor = sizeof($estoque);
            if ($tamanho_vetor > 0) {
            ?>
                <div class="row cabecalho">
                    <div class="col-2 col-sm-2">ID</div>
                    <div class="col-2 col-sm-3">Ref</div>
                    <div class="col-3 col-sm-2">Estoque</div>
                    <div class="col-2 col-sm-2">Entra</div>
                    <div class="col-2 col-sm-2">Local</div>
                    <div class="col-1 col-sm-1">Editar</div>
                </div>
                <div class="alternar">
                    <?php
                    usort($estoque, function ($a, $b) {
                        return $b['usuario_resp'] !== null && $b['usuario_resp'] === usuarioLogado();
                    });
                    foreach ($estoque as $key => $e) : ?>
                        <div class="itens">
                            <div class="mt-1 row corpo <?= ($e['usuario_resp'] === usuarioLogado()) ? '' : 'bg-secondary' ?>">
                                <div class="col-3 col-sm-2"><?= $e['id']; ?></div>
                                <div class="col-2 col-sm-3"><?= $e['descricao']; ?></div>
                                <div class="col-2 col-sm-2"><?= $e['estoque']; ?></div>
                                <div class="col-2 col-sm-2"><?= $e['qtd_aguardando_entrada']; ?></div>
                                <div class="col-2 col-sm-2">
                                    <?php $abre_modal_direto =  false;
                                    if ($e['localizacao']) {
                                        echo $e['localizacao'];
                                    }
                                    if ($tamanho_vetor == 1) {
                                        $abre_modal_direto =  true;
                                    } ?>
                                </div>
                                <div class="col-1 col-sm-1">
                                    <button class="btn btn-dark modal_produto" paramentro="<?= $e['id']; ?>" num_pares="<?= $e['estoque']; ?>" data-toggle="modal" data-target="#confirma-<?= $e['id']; ?>">
                                        <i class="far fa-edit"></i>
                                    </button>
                                </div>
                                <form onsubmit="overlayModal()" action="<?= $_ENV['URL_MOBILE'] ?>/api_administracao/produtos/aguardando" method="post">
                                    <input type="hidden" name="produto" value="<?= $e['id']; ?>">
                                    <div class="modal p-0" style="text-align: -webkit-center;" id="confirma-<?= $e['id']; ?>">
                                        <input type="hidden" name="numeracoes_id" id="numeracoes_id_<?= $key ?>">
                                        <div class="modal-dialog" style="z-index: 20" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header <?= $e['usuario_resp'] === usuarioLogado() ? 'bg-danger text-white' : 'text-dark' ?> ">
                                                    <h5 class="modal-title">Bipar localização</h5>
                                                    <button type="button" class="close close-modal <?= $e['usuario_resp'] === usuarioLogado() ? 'text-white' : 'text-dark' ?>" data-dismiss="modal" aria-label="Fechar">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>

                                                <div class="modal-body text-dark">
                                                    <div class="text-center" style="font-size: 1.5rem;">
                                                        Pares em estoque: <b style="background: yellow;padding-right: 0.5rem;padding-left: 0.5rem;"><?= $e['estoque']; ?></b>
                                                        <?= ($e['qtd_aguardando_entrada'] > 0) ? "<br />Pares que serão adicionados: <b style='background: red; color: white;padding-right: 0.5rem;padding-left: 0.5rem;'>" . $e['qtd_aguardando_entrada'] . "</b>" : '' ?>
                                                        <?= ($e['localizacao']) ?  "<br />Localização atual: <b style='background: black; color: white;padding-right: 0.5rem;padding-left: 0.5rem;'>" . $e['localizacao'] . "</b>" : '' ?>
                                                    </div>
                                                    <div id="overlayModal"></div>
                                                    <div class="text-center">
                                                        <img data-sizes="auto" class="lazyload" data-src="<?= buscaFotoProduto($e['id']); ?>" width="50%" id="<?= $e['id']; ?>">
                                                        <br />
                                                        <div style="font-size: 1.35rem;"> ID: <b><?= $e['id']; ?></b></div>
                                                        <div style="font-size: 1.35rem;"> Descrição: <b><?= $e['descricao']; ?></b></div>
                                                    </div>
                                                    <div class="arrow"><i class="fas fa-long-arrow-alt-down"></i>
                                                    </div>
                                                    <div class="row  justify-content-center">
                                                        <div class="col-12">
                                                            <?php
                                                            if ($e['qtd_aguardando_entrada'] > 0) {
                                                                $Lista_entrada_estoque = ProdutoService::buscalistaAguardaRetornoEstoque($e['id']); ?>
                                                                <div class="alert alert-danger" role="alert">
                                                                    <h5 class="text-center">Pares que serão adicionados no estoque:
                                                                        <?php
                                                                        $mensagem_separa_foto = '';
                                                                        foreach ($Lista_entrada_estoque as $key_entrada => $value_entrada) {
                                                                            if ($value_entrada['tipo_entrada'] <> 'Separar foto') {
                                                                        ?>
                                                                                <div class="mt-2">
                                                                                    <div class="d-flex justify-content-between align-items-center p-0 btn-group btn-group-toggle rounded-pill bg-light" style="z-index: 300; height: 3.5rem">
                                                                                        <label class="mb-0 w-50" for="tipos_<?= $key_entrada ?>_<?= $key ?>">
                                                                                            <span class="d-flex align-items-center ml-2">
                                                                                                <input class="checkmark" type="checkbox" onchange="marcaTodosTipoAtual(event, <?= $key_entrada ?>, <?= $key ?>)" id="tipos_<?= $key_entrada ?>_<?= $key ?>">
                                                                                                <div class="ml-2" id="tipo_entrada_<?= $key_entrada ?>_<?= $key ?>"><?= $value_entrada['tipo_entrada'] ?></div>
                                                                                            </span>
                                                                                        </label>
                                                                                        <div class="w-50 p-2 rounded-pill d-flex flex-row-reverse" onclick="abrePainelNumeracao(event, <?= $key_entrada ?>, <?= $key ?>)">
                                                                                            <div class="badge badge-pill badge-dark badge-lg" style="height: 2rem; width: 3rem; font-size: 1.3rem;"><?= $value_entrada['qtd'] ?></div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div id="numeracoes_<?= $key_entrada ?>_<?= $key ?>_<?= str_replace(' ', '_', $value_entrada['tipo_entrada']) ?>" class="bg-light d-none rounded ml-2 mr-2 p-1" style="transform:translateY(-3px)">
                                                                                        <?php
                                                                                        $numeracoes = (array) $value_entrada['estoque'];
                                                                                        usort($numeracoes, function ($a, $b) {
                                                                                            return $a['tamanho'] - $b['tamanho'];
                                                                                        });
                                                                                        foreach ($numeracoes as $key => $itemAguardando) : ?>
                                                                                            <label for="numeracao_<?= $itemAguardando['tamanho'] ?>_<?= $itemAguardando['id'] ?>" class="rounded-pill border border-dark bg-light p-2 <?= $key === 1 ? 'mt-3' : '' ?>">
                                                                                                <input class="checkbox-individual" id="numeracao_<?= $itemAguardando['tamanho'] ?>_<?= $itemAguardando['id'] ?>" onchange="mudaNumeracoesSelecionadas(event)" value="<?= $itemAguardando['id'] ?>" type="checkbox">
                                                                                                <b style="pointer-events:none"><?= $itemAguardando['nome_tamanho'] ?? $itemAguardando['tamanho'] ?></b>
                                                                                            </label>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                </div>
                                                                            <?php
                                                                            } else {
                                                                                $mensagem_separa_foto .= "<h5>" . $value_entrada['qtd'] . " produto Nº " . $value_entrada['tamanho'] . " deve ser separado para foto </h5>";
                                                                            }
                                                                        }
                                                                        /*
                                                                        if ($mensagem_separa_foto != '') {
                                                                            ?>
                                                                            <div class="bg-danger rounded-lg p-2 mt-3">
                                                                                <?= $mensagem_separa_foto ?>
                                                                                <button id="separarProdutoParaFoto" onclick="separarProdutoFoto(<?= $e['id'] ?>, <?= idUsuarioLogado() ?>, <?= $value_entrada['tamanho'] ?>)" type="button" class="w-100 p-2 mt-2 btn btn-outline-light rounded-pill d-block">Separar</button>
                                                                            </div>
                                                                        <?php
                                                                        }
                                                                        */
                                                                        ?>
                                                                        </h6>
                                                                </div>
                                                            <?php
                                                            }
                                                            ?>
                                                            <input type="text" name="local" class="form-control border border-dark seleciona_local" id="<?= $e['id']; ?>" value="">
                                                            <?php
                                                            if ((!$e['localizacao']) && ($tamanho_vetor == 1)) {
                                                                $Lista_estoque = buscalistaLocalizacaoVago();
                                                                if (sizeof($Lista_estoque) == 0) {
                                                            ?>Não encontro local<?php
                                                                            } else {
                                                                                ?>Locais disponíveis: <small class="text-danger">Proporção caixa/produto = <?= $e['proporcao_caixa']; ?></small><br /><?php
                                                                                                                                                                                                        $contador_ = 0;
                                                                                                                                                                                                        ?>
                                                            <div class="row cabecalho">
                                                                <div class="col-3 col-sm-3">Local</div>
                                                                <div class="col-4 col-sm-4">Capacidade</div>
                                                                <div class="col-3 col-sm-3">Atual</div>
                                                                <div class="col-2 col-sm-2">Cabe</div>
                                                            </div>
                                                            <div class="alternar"><?php
                                                                                    foreach ($Lista_estoque as $key_local => $value_local) {
                                                                                        if ($contador_ >= 6) {
                                                                                            break;
                                                                                        }
                                                                                        if ($e['proporcao_caixa'] <= 0) {
                                                                                            $e['proporcao_caixa'] = 1;
                                                                                        }
                                                                                        if (round((1 / $e['proporcao_caixa']) * $value_local['vago']) <= $e['estoque'] + $e['qtd_aguardando_entrada'] + 5) {
                                                                                            $contador_++;
                                                                                    ?>
                                                                        <div class="itens">
                                                                            <div class="row corpo ">
                                                                                <div class="col-3 col-sm-3"><?= $value_local['local'] ?></div>
                                                                                <div class="col-4 col-sm-4"><?= $value_local['num_caixa'] ?></div>
                                                                                <div class="col-3 col-sm-3"><?= $value_local['estoque'] ?></div>
                                                                                <div class="col-2 col-sm-2"><?= round((1 / $e['proporcao_caixa']) * $value_local['vago']) ?></div>
                                                                            </div>
                                                                    <?php
                                                                                        }
                                                                                    }
                                                                    ?>
                                                                        </div><?php
                                                                            }
                                                                        }
                                                                                ?>
                                                            </div>
                                                        </div>

                                                        <div class="info">Defina no campo acima, qual painel deseja posicionar este modelo e dar entrada em estoque</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php
            } elseif ($pesquisa) {
            ?>
                <h5> Produto não encontradado. <br />
                    <small>Cadastrar codigo de barras no produto</small>
                </h5>
                <span class="text-danger ">OBS: Apenas produtos na área de entrada para o estoque</span>
            <?php
            } ?>


        </div>
    </div>
    <div class="tab-pane fade container-fluid" id="nav-relatorio" role="tabpanel" aria-labelledby="nav-relatorio-tab">
        <button class="btn btn-info btn-sm btn-block mt-2" id="gera_relatorio"> Gerar relatório</button>
        <div class="mt-3" id="abre_relatorio">
        </div>
    </div>
    <div class="tab-pane fade container-fluid" id="nav-etiquetas" role="tabpanel" aria-labelledby="nav-etiquetas-tab">
        <button class="btn btn-info btn-sm btn-block mt-2" id="gera_lista_local"> Gerar Lista</button>
        <div class="mt-3" id="abre_lista_local">
        </div>
    </div>
    <div class="tab-pane fade container-fluid" id="nav-log" role="tabpanel" aria-labelledby="nav-log-tab">
        <div class="form-group ml-3 mr-3">
            <label for="pesquisa_log">Produto</label>
            <input type="search" class="form-control" id="pesquisa_log" placeholder="Digite descrição ou código do produto" value="">
        </div>
        <button class="btn btn-info btn-sm btn-block mt-2" id="gera_lista_log"> Consutar Log</button>

        <div class="mt-3" id="abre_lista_log">
        </div>
    </div>
</div>

<input type="hidden" id="enderecoSite" value="<? $_ENV['URL_MOBILE'] ?>" />
<script src="js/estoque-sem-localizacao.js<?= $versao; ?>"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script>
    if ($(window).width() < 1000) {
        $("#cod_barras_div").show();
        <?php
        if ($abre_modal_direto) : ?>
            $("#confirma-" + <?= $e['id'] ?>).modal('show');
        <?php endif; ?>
        $(".seleciona_local").on('focus', function() {
            let numeracoes = this.parentNode
                .parentNode.parentNode.parentNode
                .parentNode.parentNode.parentNode
                .querySelector('input[name="numeracoes_id"]').value;

            if (confirm(`Serão adicionados ${numeracoes ? numeracoes.split(',').length : 0} pares no estoque`)) {
                $('#carrega_').removeClass('d-none');
                let produtoId = this.id;
                window.open("http://zxing.appspot.com/scan?ret=<?= $_ENV['URL_MOBILE'] ?>api_administracao/produtos/aguardando?local_cod_barras={CODE}-" + produtoId + "-" + numeracoes, "_self")
                overlayModal();
            }
            this.blur();
        });
    } else {
        $("#cod_barras_div").hide();
    }

    const enderecoSite = window.location.href.substr(0, window.location.href.indexOf('modal=') - 1);
    $('.modal').on('shown.bs.modal', abreModalComHistorico)
    $('.close-modal').on('click', e => {
        window.history.replaceState(null, null, enderecoSite);
    })

    window.onpopstate = e => $('.modal').modal('hide');

    function hrefDesempenho(usuarioId) {
        window.location.href = `estoque-desempenho-individual.php?agrupar=day&id=${usuarioId}`;
    }

    function abreModalComHistorico() {
        let modalId = this.id.replace('confirma-', '')
        window.history.pushState('', '', enderecoSite);

        let header = this.querySelector('.modal-header');
        !header.classList.contains('bg-danger') ? alert('Esse pedido não está marcado com o seu nome, você ainda pode faze-lo, mas estará burlando o sistema') : '';
    }
</script>
<?php
require_once 'rodape.php';
?>