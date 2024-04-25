<?php

require_once __DIR__ . '/vendor/autoload.php';

header('Location: ' . $_ENV['URL_AREA_CLIENTE']);
exit();
/*
// use api_estoque\Cript\Cript;
// use MobileStock\database\Conexao;
// use MobileStock\service\Lancamento\LancamentoConsultas;

// require_once "cabecalho.php";
// require_once "classes/usuarios.php";
// require_once "classes/produtos.php";
// require_once "classes/categorias.php";
// require_once "classes/estoque.php";
// require_once "classes/colaboradores.php";

// acessoUsuarioGeral();
// $categorias = buscaCategorias();
// $linhas = buscaLinhas();
// $modo_pagina = isset($tela_link) ? $tela_link : false;
// $creditos = $_SESSION['id_cliente'] ? LancamentoConsultas::consultaCreditoCliente(Conexao::criarConexao(), clienteSessao()) : 0;
//filtra linha
// $tamanho_grade = buscaTamanhoProdutoPorlinha(NULL);
?>
<style>
    .modal-content-produtos {
        border: unset;
        height: auto;
        min-height: 100%;
    }

    .modal-dialog {
        margin-top: 0px;
    }

    .modal-dialog-produtos {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
    }

    #image-container {
        max-width: 100vw;
    }

    #image-container>img {
        width: 100%;
    }

    #image-container2 {
        max-width: 100vw;
    }

    #image-container2>img {
        width: 100%;
    }
</style>
<script type="text/javascript">
    <?php
    if ($vetorForm = isset($_GET) ? $_GET : false) {
    ?>localStorage.clear();
    <?php
        foreach ($vetorForm as $key => $valor) : ?>
        localStorage.setItem('<?= $key ?>', '<?= $valor ?>');
    <?php endforeach;
    } ?>
</script>

<nav class="mx-auto bg-light">
    <div class="container-fluid">
        <?php
        if ($_SESSION['id_cliente']) {
        ?>
            <nav class="navbar justify-content-between alert-primary rounded adiciona_credito">
                Saldo <b class=" ml-1 atualizaSaldo"> </b> <small class="text-muted ml-lg-1"> Clique aqui para comprar créditos</small>
            </nav>
        <?php } ?>
        <div class="navbar-header d-flex">

            <?php if (!$modo_pagina) { ?><button type="button" class="btn btn-default" data-toggle="collapse" id="filtro" data-target="#barraDeBusca" onclick="$('#fornecedor').val('');">
                    <img src="images/filtro.svg" style="width: 25%;"> <b style="color:black"> FILTRO</b>
                </button><?php } ?>
            <button class="btn btn-default" type="button" id="ordernarPor" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <b style="color:black">ORDERNAR POR </b>
            </button>
            <div class="dropdown-menu" aria-labelledby="ordernarPor">
                <a class="dropdown-item ordenar_pagina" parametro="1" href="#"> Novidades</a>
                <a class="dropdown-item ordenar_pagina" parametro="2" href="#">Menor Preço</a>
                <a class="dropdown-item ordenar_pagina" parametro="3" href="#">Maior Desconto</a>
                <a class="dropdown-item ordenar_pagina" parametro="6" href="#">Mais Avaliados</a>
                <?php if ((isset($_SESSION["id_usuario"])) && (!$modo_pagina)) { ?>
                    <a class="dropdown-item ordenar_pagina" parametro="4" href="#">Favoritos</a>
                    <a class="dropdown-item ordenar_pagina" parametro="5" href="#">Meus Produtos</a>
                <?php } ?>
            </div>
            <button id="botao-modo-exibicao" type="button" class="btn d-flex justify-content-center align-items-center" data-toggle="tooltip" data-placement="top" title="Alternar modo de exibição" onclick="buscaProdutosCalcados()">
                <i class="far fa-object-group" style="font-size: 1.5em"></i>
            </button>


            <!-- <a class="btn btn-default ordenar_pagina" type="button" parametro="4" title="Favorito" ><i class="fas fa-star cor_dourado"></i></a> -->
        </div>
        <div <?php if (!$modo_pagina) { ?>class="collapse navbar-collapse" <?php } ?> id="barraDeBusca"><br>
            <form method="post" id="form" action="index_carrega.php">
                <input type="hidden" id="fornecedor" name="fornecedor" class="form-control visualizaFiltros input_fornecedor" value="">
                <input type="hidden" id="num_pagina" name="num_pagina" Value="0">
                <input type="hidden" id="ordenar" name="ordenar" Value="0">
                <input type="hidden" id="foto_calcada" name="foto_calcada" value="">
                <div class="row">
                    <div class="col-sm-1"><input type="search" class="form-control visualizaFiltros" name="referencia" id="referencia" value="" placeholder="ID do produto"></div>
                    <label for="referencia"> </label>
                    <div class="col-sm-1"><input type="search" class="form-control visualizaFiltros" name="cor" id="cor" value="" placeholder="Descrição"></div>


                    <!-- ini categoria  -->
                    <div class="col-sm-2">
                        <div class="selectBox">
                            <div class="select_abre" par="categoria">
                                <input type="hidden" name="categoria" id="categoria" value="">
                                <div class="div_input_control"><i class="fas fa-angle-down"></i>
                                    <div id="input_categoria">Categoria:</div>
                                </div>
                                <div class="overSelect"></div>
                            </div>
                            <div id="checkcategoria" class="checkboxes checkboxesClick">
                                <div>
                                    <?php
                                    foreach ($categorias as $key => $categoria) {
                                    ?> <label id="limpa" parametro="<?= $categoria['id'] ?>" class='adiciona-categoria conf_<?= $categoria['nome'] ?>'>
                                            <img src="<?= "images/" . $categoria['icone_imagem'] ?>" style='height:30px; width:30px' title="<?= $categoria['nome']; ?>" /> <?= $categoria['nome']; ?></label><?php
                                                                                                                                                                                                            } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- fim   -->

                    <!-- ini Linha  -->
                    <div class="col-sm-2">
                        <div class="selectBox">
                            <div class="select_abre" par="linha">
                                <input type="hidden" name="linha" id="linha" value="">
                                <div class="div_input_control"><i class="fas fa-angle-down"></i>
                                    <div id="input_linha">Linha:</div>
                                </div>
                                <div class="overSelect"></div>
                            </div>
                            <div id="checklinha" class="checkboxes checkboxesClick">
                                <div>
                                    <?php
                                    foreach ($linhas as $key => $linha) {
                                    ?> <label id="limpa" parametro_linha="<?= $linha['id'] ?>" class='adiciona-linha conf_<?= $linha['nome'] ?>'>
                                            <img src="<?= "images/" . $linha['icone_imagem'] ?>" style='height:30px; width:30px' /> <?= $linha['nome']; ?></label><?php
                                                                                                                                                                } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- fim   -->

                    <!-- ini  tamanho -->
                    <div class="col-sm-2">
                        <div class="selectBox">
                            <div class="select_abre" par="tamanho">
                                <input type="hidden" name="tamanho" id="tamanho" value="">
                                <div class="div_input_control"><i class="fas fa-angle-down"></i>
                                    <div id="input_tamanho">Numero:</div>
                                </div>
                                <div class="overSelect"></div>
                            </div>
                            <div id="checktamanho" class="checkboxes_tamanho checkboxesClick">
                                <div id='1'>
                                    <p style='text-align: center;font-weight: bold;'>Feminino</p><label id="limpa" class='adiciona-tamanho conf_33'>33</label><label id="limpa" class='adiciona-tamanho conf_34'>34</label><label id="limpa" class='adiciona-tamanho conf_35'>35</label><label id="limpa" class='adiciona-tamanho conf_36'>36</label><label id="limpa" class='adiciona-tamanho conf_37'>37</label><label id="limpa" class='adiciona-tamanho conf_38'>38</label><label id="limpa" class='adiciona-tamanho conf_39'>39</label><label id="limpa" class='adiciona-tamanho conf_40'>40</label><label id="limpa" class='adiciona-tamanho conf_41'>41</label><label id="limpa" class='adiciona-tamanho conf_42'>42</label><label id="limpa" class='adiciona-tamanho conf_43'>43</label>
                                </div>
                                <div id='3'>
                                    <p style='text-align: center;font-weight: bold;'>Masculino</p><label id="limpa" class='adiciona-tamanho conf_37'>37</label><label id="limpa" class='adiciona-tamanho conf_38'>38</label><label id="limpa" class='adiciona-tamanho conf_39'>39</label><label id="limpa" class='adiciona-tamanho conf_40'>40</label><label id="limpa" class='adiciona-tamanho conf_41'>41</label><label id="limpa" class='adiciona-tamanho conf_42'>42</label><label id="limpa" class='adiciona-tamanho conf_43'>43</label><label id="limpa" class='adiciona-tamanho conf_44'>44</label><label id="limpa" class='adiciona-tamanho conf_45'>45</label><label id="limpa" class='adiciona-tamanho conf_46'>46</label><label id="limpa" class='adiciona-tamanho conf_47'>47</label><label id="limpa" class='adiciona-tamanho conf_48'>48</label>
                                </div>
                                <div id='2'>
                                    <p style='text-align: center;font-weight: bold;'>Meninas</p><label id="limpa" class='adiciona-tamanho conf_16'>16</label><label id="limpa" class='adiciona-tamanho conf_17'>17</label><label id="limpa" class='adiciona-tamanho conf_18'>18</label><label id="limpa" class='adiciona-tamanho conf_19'>19</label><label id="limpa" class='adiciona-tamanho conf_20'>20</label><label id="limpa" class='adiciona-tamanho conf_21'>21</label><label id="limpa" class='adiciona-tamanho conf_22'>22</label><label id="limpa" class='adiciona-tamanho conf_23'>23</label><label id="limpa" class='adiciona-tamanho conf_24'>24</label><label id="limpa" class='adiciona-tamanho conf_25'>25</label><label id="limpa" class='adiciona-tamanho conf_26'>26</label><label id="limpa" class='adiciona-tamanho conf_27'>27</label><label id="limpa" class='adiciona-tamanho conf_28'>28</label><label id="limpa" class='adiciona-tamanho conf_29'>29</label><label id="limpa" class='adiciona-tamanho conf_30'>30</label><label id="limpa" class='adiciona-tamanho conf_31'>31</label><label id="limpa" class='adiciona-tamanho conf_32'>32</label><label id="limpa" class='adiciona-tamanho conf_33'>33</label><label id="limpa" class='adiciona-tamanho conf_34'>34</label><label id="limpa" class='adiciona-tamanho conf_35'>35</label><label id="limpa" class='adiciona-tamanho conf_36'>36</label>
                                </div>
                                <div id='4'>
                                    <p style='text-align: center;font-weight: bold;'>Meninos</p><label id="limpa" class='adiciona-tamanho conf_15'>15</label><label id="limpa" class='adiciona-tamanho conf_16'>16</label><label id="limpa" class='adiciona-tamanho conf_17'>17</label><label id="limpa" class='adiciona-tamanho conf_18'>18</label><label id="limpa" class='adiciona-tamanho conf_19'>19</label><label id="limpa" class='adiciona-tamanho conf_20'>20</label><label id="limpa" class='adiciona-tamanho conf_21'>21</label><label id="limpa" class='adiciona-tamanho conf_22'>22</label><label id="limpa" class='adiciona-tamanho conf_23'>23</label><label id="limpa" class='adiciona-tamanho conf_24'>24</label><label id="limpa" class='adiciona-tamanho conf_25'>25</label><label id="limpa" class='adiciona-tamanho conf_26'>26</label><label id="limpa" class='adiciona-tamanho conf_27'>27</label><label id="limpa" class='adiciona-tamanho conf_28'>28</label><label id="limpa" class='adiciona-tamanho conf_29'>29</label><label id="limpa" class='adiciona-tamanho conf_30'>30</label><label id="limpa" class='adiciona-tamanho conf_31'>31</label><label id="limpa" class='adiciona-tamanho conf_32'>32</label><label id="limpa" class='adiciona-tamanho conf_33'>33</label><label id="limpa" class='adiciona-tamanho conf_34'>34</label><label id="limpa" class='adiciona-tamanho conf_35'>35</label><label id="limpa" class='adiciona-tamanho conf_36'>36</label>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px" class="col-sm-1 text-left btn-group-toggle">
                        <label class="btn" style="cursor: pointer" title="Filtra produtos com mais de 6 unidades">
                            <i id="icone_grade" class="far fa-square"></i> <b>Grade </b>
                            <input id="grade" name="grade" type="checkbox" value="grade">
                        </label>
                    </div>
                    <!-- fim   p-1 col-xs-6 col-sm-3-->
                    <br />
                    <div class=" col-4 col-sm-1 text-center"><button class="mb-1 btn btn-block btn-danger" id="limpar"><span class="fa fa-trash"></span> </button></div>
                    <?php if ($_SESSION['id_cliente_cript'] && nivelAcessoUsuario() < 30) { ?>
                        <div class=" col-4 col-sm-1 text-center text-white"><a href="" class="btn btn-block btn-dark" id="gera_pdf_filtros" cod_cliente="<?= $_SESSION['id_cliente_cript'] ?>" cod_usuario="<?= $_SESSION["id_usuario"] ?>" telefone="<?= telefoneUsuario() ?>" nome_cliente="<?= usuarioLogado() ?>" target="_blank"><span class="far fa-file-pdf"></span></span> </a>
                        </div>
                    <?php } ?>
                    <div class=" col-4 col-sm-1 text-center"><button class="mb-1 btn btn-block btn-primary" id="buscar"><span class="fa fa-search"></span></button></div>
            </form>
            <br />
            <br />
        </div>
    </div>
    </div>
</nav>
<br>
<div class="col-sm-12 filtros_tela">
    <div class="col-xs-1 col-sm-1 text-center"><button class="btn btn-block btn-sm btn-danger" id="limpar_b"><span class="fa fa-trash"></span> Limpar todos os filtros </button><br /></div>
    <?php $limpar = "<a class='limparItemFiltro text-danger'><i class='fa fa-trash'></i></a>"; ?>
    <p par='referencia'><?= $limpar ?> Referência: <b></b></p>
    <p par='cor'><?= $limpar ?> Cor: <b></b></p>
    <p par='categoria'><?= $limpar ?> Categoria: <br /><b></b></p>
    <p par='linha'><?= $limpar ?> Linha: <br /><b></b></p>
    <p par='tamanho'><?= $limpar ?> Tamanho: <br /><b style='margin-left: 15px;'></b></p>
    <p par='ordena'><?= $limpar ?> Ordenado Por: <b></b></p>
    <p par='grade'><?= $limpar ?> <b>Busca por grades</b></p>
    <?php if ($_SESSION['id_cliente_cript']) { ?>
        <div class="col-xs-1 col-sm-1 text-center"><button class="btn btn-block btn-sm btn-dark" onclick="$('#gera_pdf_filtros').trigger('click')" id="carrega_gera_pdf"><span class="fa fa-trash"></span> Gerar PDF </button><br /></div>
    <?php } ?>
</div>




<div id="carrega">
</div>
<a id="btn-fab" href="#"><i class="fas fa-arrow-up flecha-para-cima"></i></a>

<div id="carregando">
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
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="text-right cross"> <i class="fa fa-times"></i> </div>
                <div class="card-body text-center"> <img src="https://img.icons8.com/cute-clipart/64/000000/available-updates.png" />
                    <h4><b>Devolução Inserida! </b></h4>
                    <br>
                    <p>Novas trocas foram inseridas em nosso sistema e já estão disponíveis para serem abatidas em seu próximo pedido. </p>

                </div>
                <hr>

                <div class="row cabecalho">
                    <div class="col-sm-4 col-4">#</div>
                    <div class="col-sm-4 col-4">Produto</div>
                    <div class="col-sm-4 col-4">Data</div>
                </div>
                <?php foreach ($produtos_trocados as $prod) { ?>
                    <div class="row corpo">
                        <div class="col-sm-4 col-4"><img src=<?= $prod['caminho'] ?> style="width:60%;"></div>
                        <div class="col-sm-4 col-4"><?= $prod['descricao'] ?> </div>
                        <div class="col-sm-4 col-4"><?= date('d-m-Y', strtotime($prod['data_hora'])); ?></div>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-footer btn-group justify-content-center">
                <?php  //setNotificacaoRecebida($_SESSION["id_usuario"]);
                ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <a href="cliente-painel-saldo.php" type="button" class="button btn btn-success">Conferir Trocas</a>
            </div>
        </div>
    </div>
</div>




<!-- Modal -->
<div class="modal fade" id="EntregaTransportadora" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="text-right cross"> <i class="fa fa-times"></i> </div>
                <div class="card-body text-center"><img src="https://img.icons8.com/bubbles/100/000000/truck.png" />
                    <h4><b>Pedido á caminho! </b></h4>
                    <br>
                    <p>Seu pedido acaba de ser entregue para a transportadora. </p>
                </div>
            </div>
            <div class="modal-footer btn-group justify-content-center">
                <?php  //setNotificacaoRecebida($_SESSION["id_usuario"]);
                ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="EntregaBH" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="text-right cross"> <i class="fa fa-times"></i> </div>
                <div class="card-body text-center"><img src="https://img.icons8.com/bubbles/100/000000/truck.png" />
                    <h4><b>Pedido á caminho! </b></h4>
                    <br>
                    <p>Sua mercadoria já está a caminho de Belo Horizonte, enviaremos uma mensagem pelo Whatsapp quando ele já estiver no local da retirada. </p>
                    <p><b>Avenida Amazonas, 8493</b></p>
                    <label class="text-danger">Segunda a Sexta de 09:00 às 17:00</label>
                </div>
            </div>
            <div class="modal-footer btn-group justify-content-center">
                <?php  //setNotificacaoRecebida($_SESSION["id_usuario"]);
                ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="EntregaPessoa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="text-right cross"> <i class="fa fa-times"></i> </div>
                <div class="card-body text-center"> <img src="https://img.icons8.com/bubbles/100/000000/worldwide-delivery.png" />
                    <h4><b>Pedido pronto para ser retirado! </b></h4>
                    <br>
                    <p>Seu pedido já está pronto para ser retirado. </p>

                </div>
            </div>
            <div class="modal-footer btn-group justify-content-center">
                <?php  //setNotificacaoRecebida($_SESSION["id_usuario"]);
                ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="EntregaMotorista" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="text-right cross"> <i class="fa fa-times"></i> </div>
                <div class="card-body text-center"> <img src="https://img.icons8.com/clouds/100/000000/truck.png" />
                    <h4><b>Pedido á caminho! </b></h4>
                    <br>
                    <p>Seu pedido está pronto. Lembre nosso motorista para que ele não se esqueça de levar ao local indicado. Se tiver horário específico ligue para ele com pelo menos uma hora de antecedêcia.</p>
                    <p><a href="https://wa.me/5537984166186" class="button btn btn-success" type="button" style="color:black"><b><i class="fa fa-whatsapp" aria-hidden="true"></i> Fale com nosso motorista</b></a></p>
                </div>

            </div>
            <div class="modal-footer btn-group justify-content-center">
                <?php // setNotificacaoRecebida($_SESSION["id_usuario"]);
                ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>


            </div>
        </div>
    </div>
</div>

<!-- Botão para acionar modal -->


<!-- Modal -->
<div class="modal fade" id="par_corrigido" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center" id="exampleModalLabel"><b>Produto Faltou em seu Pedido</b></h5>
                <!--<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">-->
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img class="rounded mx-auto d-block" src="https://img.icons8.com/bubbles/100/000000/error.png" />
                <p class="text-center" style="font-size:25px"><b>Produto Faltou em seu Pedido.</b></p>
                <p class="text-justify">Olá cliente! Um de seus produtos não foi encontrado em nosso estoque ou não foi aprovado em nosso Controle de Qualidade.
                    Pedimos desculpas e estamos trabalhando para que esse problema não volte a ocorrer. Um crédito do mesmo valor do produto
                    foi gerado e já está disponível. Caso desejar reembolso do valor, solicite <a href="reembolso-cliente.php?fa=<?= $notificacoes['tipo_frete'] ?>" class="btn badge badge-pill badge-warning">AQUI</a>.</p>
                <p><b>Produtos indisponíveis:</b></p>

                <div class='row cabecalho text-center'>
                    <div class='p-1 col-3'>#</div>
                    <div class='p-1 col-2'>Nome</div>
                    <div class='p-1 col-2'>Valor</div>
                    <div class='p-1 col-2'>Nº</div>
                    <div class='p-1 col-3'>Data</div>
                </div>
                <div class="row corpo text-center" style="font-size:12px">
                    <?php foreach ($lista_produtos as $produtos) : ?>
                        <div class="col-3">
                            <img class="img-fluid" src="<?= $produtos['foto']; ?>">
                        </div>
                        <div class="col-2">
                            <?= $produtos['descricao']; ?>
                        </div>
                        <div class="col-2">
                            <?= $produtos['preco']; ?>
                        </div>
                        <div class="col-2"><?= $produtos['tamanho']; ?></div>
                        <div class="col-3">
                            <?= date("d/m/Y", strtotime($produtos['data_emissao'])); ?>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer justify-content-center btn-group">
                <!--<a type="button" class="btn btn-dark" href="reembolso-cliente.php">Solicitar Reembolso</a>-->
                <a type="button" class="btn btn-block btn-danger" href="par-cliente-corrigido.php?fa=<?= $notificacoes['tipo_frete'] ?>">Ver Crédito</a>
                <?php setNotificacaoRecebidaParCorrigido($_SESSION["id_cliente"]); ?>
                <!--<button type="button" class="btn btn-primary" data-dismiss="modal">Sair</button>-->
            </div>
        </div>
    </div>
</div>


<!-- <div role="alert" aria-atomic="true" class="toast bg-info fixed-top w-100 mw-100" data-autohide="false" data-delay="10000" style="position: absolute; top: 50px;">
    <div class="toast-body p-2">
        <div class="row justify-content-center">
            <div class="col-11 p-0 m-0">
                <i class="fas fa-envelope"></i> Você possui novas mensagens! Veja <a href="lista-atendimentos-cliente.php" class="btn btn-sm badge badge-dark">AQUI</a>
            </div>

            <button class="btn ml-0 p-0 mb-0" style="font-size:10px" data-dismiss="toast" aria-label="Close">x</button>

        </div>
    </div>
</div> -->


<div class="p-0 modal fade bd-example-modal-lg" id="modal-gera-mensagem_interativa" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalLabel5" aria-hidden="true" style="text-align: -webkit-center;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span>
            </button>
            <div class="modal-header">
                <p>--------------------</p>
            </div>
            <div class="modal-body">
                <!--<img class="rounded mx-auto d-block w-50" src="images/mensagem-create.png" />
                <p class="text-center" style="font-size:30px"><b>Caro(a) Cliente</b></p>-->

                <p id="image-container2" class=" text-justify">
                    <?= ($mensagem['mensagem'] ? $mensagem['mensagem'] : "") ?>
                </p>
            </div>
            <div class="modal-footer justify-content-center btn-group btn-group">
                <!--<input type="checkbox" id="li-concordo" name="li-concordo">-->
                <input type="hidden" name="id_colaborador" value=<?= ($mensagem['mensagem'] ? clienteSessao() : "") ?>>

                <button value="<?= $mensagem['id'] ?>" type="button" class="btn  btn-danger decisao" decisao="N">Não</button>
                <button value="<?= $mensagem['id'] ?>" type="button" class="btn btn-success decisao" decisao="S">Sim</button>
            </div>
        </div>
    </div>
</div>





<div class="p-0 modal fade bd-example-modal-lg" id="modal-gera-mensagem" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalLabel5" aria-hidden="true" style="text-align: -webkit-center;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span>
            </button>
            <div class="modal-header">
            </div>
            <div class="modal-body">
                <!--<img class="rounded mx-auto d-block w-50" src="images/mensagem-create.png" />
                <p class="text-center" style="font-size:30px"><b>Caro(a) Cliente</b></p>-->

                <p id="image-container" class=" text-justify">
                    <?= ($mensagem['mensagem'] ? $mensagem['mensagem'] : "") ?>
                </p>
            </div>
            <div class="modal-footer justify-content-center btn-group">
                <!--<input type="checkbox" id="li-concordo" name="li-concordo">-->
                <input type="hidden" name="id_colaborador" value=<?= ($mensagem['mensagem'] ? clienteSessao() : "") ?>>
                <button value="<?= $mensagem['id'] ?>" name="li-concordo" id="li-concordo" type="button" class="btn btn-block btn-danger">Estou ciente</button>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelarContador" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-calendar"></i> Pedido em Aberto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <p class="justify-content-center">
            <h4 class="text-center"><b>Faltam poucos dias!</b></h4>
            </p>
            <div class="text-center">
                Detectamos que existe pedidos em aberto, dentro de poucos dias seu pedido será <b>CANCELADO</b>, realize o pagamento o quanto antes:

            </div>

            <div class="modal-body">
                <div class="row cabecalho2 text-center">
                    <div class="col-3">Pedido</div>
                    <div class="col-3">Faltam/Dias</div>
                    <div class="col-2"></div>
                    <div class="col-2"></div>

                </div>
                <?php foreach ($faturamento_aberto as $key => $contador) {
                    if ($key % 2 == 0) {
                        $estilo = "fundo-cinza";
                    } else {
                        $estilo = "fundo-branco";
                    } ?>

                    <div class="row corpo text-center <?= $fundo ?>">
                        <div class="col-3">
                            <h6><?= $contador['id'] ?></h6>
                        </div>
                        <div class="col-3 text-danger">
                            <h5><b><?= ($contador['tabela_preco'] == 3 ? 3 - intVal($contador['dias']) : 3 - intVal($contador['dias'])) ?> DIAS </b></h5>

                            <!--<span class="fa-stack fa-2x">
                                <i class="fa fa-calendar-o fa-stack-2x"></i>
                                <strong class="fa-stack-1x calendar-text"></strong>
                            </span>-->
                        </div>
                        <?php if ($contador['tabela_preco'] == 2) {
                            // $mensagem = "Olá.%20Gostaria%20de confirmar meu pagamento.";
                        ?>
                            <!-- <div class="col-6"><a href="https://wa.me/5531995882718?text=<?= $mensagem ?>" class="text-dark"><i class="fab fa-whatsapp" style="color:forestgreen"></i><u> Envie o comprovante</u></a></div>
                        <?php } else if ($contador['tabela_preco'] == 3) { ?> -->


                            <div class="col-6">

                                <a href="<?= $contador['url_boleto'] ?>" class="btn btn-block btn-sm btn-dark text-light p-1 m-1">Pagar Boleto</a>
                            </div>

                        <?php
                        } ?>


                    </div>

                <?php } ?>
                <br>
                <p class="text-danger" style="font-size:12px">Atenção! Boleto pode levar até 1 dia útil <b>(pagamentos realizados aos Sábados e/ou Domingos são debitados ás Terças Feiras).<b></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onClick="<?= (idUsuarioLogado() && nivelAcessoUsuario() == 10 ? $colaborador->atualizaDataUltimoAcesso($user->getId_colaborador()) : '') ?>">Fechar</button>

                <!--<button type="button" class="btn btn-primary">Send message</button>-->
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="fiscal" tabindex="-1" role="dialog" aria-labelledby="examplefiscal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4 class="text-center">Caro(a) Cliente!</h4><br>
                <p>A <b>Nota Fiscal</b> de seu pedido já foi emitida. Com o número da nota você também poderá rastrear sua mercadoria.</p>
                <a href="<?= $link_fiscal ?>" class="btn btn-danger btn-block" target="_blank"><b><i class="fas fa-file-download"></i> BAIXAR NOTA FISCAL</b></a>
            </div>
            <!-- <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div> -->
        </div>
    </div>
</div>




<!-- Modal -->
<div id="modal_produto" class="p-0 modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="text-align: -webkit-center;">
    <div class="modal-dialog modal-dialog-produtos modal-dialog-scrollable modal-md">
        <div class="modal-content position-relative modal-content-produtos" id="modal-relativo" name="modal-content">
            <div class="modal-header justify-content-between p-1">
                <?php echo $_SESSION['id_cliente'] ? 'Saldo<b class="atualizaSaldo"></b>' : '' ?>
                <div>
                    <div class="alert-box success_favorito"> Adicinado aos favoritos</div>
                    <div class="alert-box failure"> Removido dos favoritos </div>
                    <h5 class="modal-title" id="modalTitle">
                        <?php if (isset($_SESSION["id_usuario"])) { ?>
                            <a id="favorito" parametroFavoritoCod="" title="Favorito"><i class=""></i></a>
                        <?php } else { ?>
                        <?php } ?>
                    </h5>
                </div>
                <div class="d-flex flex-column text-center" id="modalTitleTimerContainer">
                </div>
                <div>
                    <?php if (isset($_SESSION["id_usuario"])) { ?>
                        <a type="button" class="btn" aria-label="Close" id="whatsapp-share-btt" href="">
                            <i class="fas fa-share-alt"></i>
                        </a>
                    <?php } ?>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body" style="overflow-x: hidden;">
                <div class="container-fluid" id="carrega_produto">

                </div>
                <input type="hidden" id="valor_creditos" value="<?= $creditos ?>">
                <input type="hidden" id="notif_fiscal" value="<?= $link_fiscal ?>">
                <input type="hidden" id="lembrete" value=<?= $lembrete_mensagem ?>>
                <input type="hidden" id="tipo_entrega" value=<?= $tipo_entrega ?>></input>
                <input type="hidden" id="notif_pedido_corrigido" value=<?= $notificacoes_pedido_corrigido ?>></input>
                <input type="hidden" id="notif_pago" value=<?= $notifica_pago ?>>
                <input type="hidden" id="gera_mensagem" value="<?= ($mensagem['id'] ? 1 : 0) ?>">
                <input type="hidden" id="filtros" value="<?= ($filtro == 1 && $_SESSION['modal'] != 1 ? 1 : 0) ?>">
                <input type="hidden" id="gera_mensagem" value="<?php if ($mensagem != null) {
                                                                    if ($mensagem['interativo'] == 'N') {
                                                                        echo 1;
                                                                    } else {
                                                                        echo 2;
                                                                    }
                                                                } else {
                                                                    echo 0;
                                                                } ?>">

            </div>
            <div>
                <div class="w-100 d-flex justify-content-center align-items-baseline position-fixed" style="bottom: 0; left:0; right:0;">
                    <span></span>
                    <div id="footer-modal-botoes-adicionar" class="w-100 btn-group align-items-baseline d-none">
                        <?= $creditos === 0 ?: '<button id="btn-adicionar-produto-pago" class="btn-block btn btn-success">Adicionar pago</button>' ?>
                        <button class="btn-block btn btn-primary" id="btn-adicionar-produtos-painel">Adicionar</button>
                    </div>
                    <span></span>
                </div>
            </div>





            <script>
                var Cliente = '<?php echo Cript::criptInt(clienteSessao()); ?>';
                if ($('#lembrete').val()) {
                    $("#cancelarContador").modal("show");
                }
                var tipo_entrega_notificacao = $('#tipo_entrega').val();
                var corrigido_notificacao = $('#notif_pedido_corrigido').val();
                if (tipo_entrega_notificacao > 0) {
                    if (tipo_entrega_notificacao == 2) {
                        $('#EntregaTransportadora').modal('show');
                    }
                    if (tipo_entrega_notificacao == 3 || tipo_entrega_notificacao == 1) {
                        $('#EntregaPessoa').modal('show');
                    }
                    if (tipo_entrega_notificacao == 4) {
                        $('#EntregaMotorista').modal('show');
                    }
                    if (tipo_entrega_notificacao == 5) {
                        // $('#EntregaBH').modal('show');
                        <?php setNotificacaoRecebida($_SESSION["id_usuario"]);  ?>
                    }
                    <?php setNotificacaoRecebida($_SESSION["id_usuario"]);  ?>
                }
                if (corrigido_notificacao > 0) {
                    $('#par_corrigido').modal('show');
                }
                var pago = $('#notif_pago').val();
                if (pago > 0) {
                    <img class=" rounded mx-auto d-block " src="https://img.icons8.com/bubbles/100/000000/money-transfer.png"/></div>
                            <p>Olá cliente! Sua solicitação de <b>reembolso foi aprovada com sucesso</b>! Confira se o valor consta na conta solicitada.</p>

                    $.alert({
                        title: 'Reembolso Efetuado',
                        content: '<img class=" rounded mx-auto d-block " src="https://img.icons8.com/bubbles/100/000000/money-transfer.png"/></div><p>Olá cliente! Sua solicitação de <b>reembolso foi aprovada com sucesso</b>! Confira na proxima segunda-feira se o valor consta na conta solicitada.</p>',
                    });
                    <?php setNotificacaoRecebidaPago($_SESSION["id_usuario"]); ?>
                }
                if ($('#notif_fiscal').val()) {
                    $('#fiscal').modal('show');
                }
            </script>

            <script>
                var link = "index.php?num_pagina=0";
                <?php if ($total_atendimento_pendente > 0) { ?>
                    $('.toast').toast('show');
                <?php } ?>
                <?php if ($notificacoes_troca_pendente) { ?>
                    $('#exampleModal').modal('show');
                <?php } ?>
                if ($('#gera_mensagem').val() == 1) {

                    $('#modal-gera-mensagem').modal('show');
                } else if ($('#gera_mensagem').val() == 2) {
                    $('#modal-gera-mensagem_interativa').modal('show');
                }
                if ($('#filtros').val() > 0) {
                    <?php $_SESSION['modal'] = 1; ?>
                    $('#modal-filtro').modal('show');
                }
                $('[name=preco]').click(function() {

                    if ($('input:checkbox[name^=produtos]:checked').length == 0) {
                        $('[name=preco]').removeClass('bg-danger');
                        $(this).addClass('bg-danger');
                        link = link + "&ordenar=" + $(this).val();
                    }

                })
                $('[name=produtos]').on('change', function() {
                    const id = $(this).attr('box');
                    if ($('[name=' + id + ']').hasClass('btn-dark')) {
                        $('[name=' + id + ']').removeClass('btn-dark');
                        $('[name=' + id + ']').addClass('btn-danger');
                    } else {
                        $('[name=' + id + ']').removeClass('btn-danger');
                        $('[name=' + id + ']').addClass('btn-dark');
                    }




                    if ($('input:checkbox[name^=produtos]:checked').length <= 1) {
                        link = link + "&categoria=" + $(this).val();
                    } else {
                        link = link + ',' + $(this).val();
                    }


                })
                $('[name=linha]').on('change', function() {
                    $(this).find('label').addClass('border-white border-5 active');
                    link = link + "&linha=" + $(this).val();

                })
                $('#gera-link').click(function() {
                    $('#modal-filtro').modal('hide');
                    window.location.href = link;
                })
            </script>
            <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css<?= $versao ?>">
            <!-- <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js<?= $versao ?>"></script> -->
            <script type="text/javascript" src="js/index.js<?= $versao ?>"></script>
            <?php require_once "rodape.php";
*/
?>
