<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioConferenteInternoOuAdm();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .v-text-field__details {
        display: none;
    }
    .item_bipado {
        background-color: var(--success)!important;
        color: var(--cor-text);
    }
    div#itens_bipados {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    div#itens_bipados h1 {
        font-size: 10rem;
        border-radius: 1rem;
        color: var(--cor-texto-preto);
    }
    div.titulo-produtos-defeito-aguardando {
        justify-content: center;
        align-items: center;
        display: flex;
        padding: 1.25rem;
        flex-direction: column;
    }
    .centralizado {
        width: 33%;
        margin: auto;
    }
</style>

<div class="container-fluid" id="app">
    <v-app>

        <v-card>
            <div>
                <h5
                    class="text-danger text-center m-1"
                    v-if="taxaDevolucaoProdutoErrado && areaAtual === 'CONFERENCIA_FORNECEDOR'"
                    v-cloak
                >
                    ATENÇÃO!! Será cobrada uma multa no valor de {{ taxaDevolucaoProdutoErrado }} para cada produto enviado errado.
                </h5>
                <h5
                    class="text-danger text-center m-1"
                    v-cloak
                    v-if="areaAtual === 'CONFERENCIA_FRETE'"
                >
                    Esteja atento(a) a quais produtos você deseja enviar para determinado destino.
                </h5>
            </div>
            <br />
            <div>
                <div class="d-flex flex-row mx-1 justify-content-around">
                    <h4 class="mt-5">Colaboradores:</h4>
                    <div class="w-50">
                        <v-autocomplete
                            v-model="colaboradorEscolhido"
                            :items="listaColaboradores"
                            :loading="loading"
                            :disabled="modalErro.exibir"
                            :search-input.sync="pesquisa"
                            item-text="descricao"
                            item-value="id"
                            label="Busca nome ou telefone"
                            prepend-icon="mdi-magnify"
                            return-object
                            autocomplete="off"
                        ></v-autocomplete>
                    </div>
                    <v-divider vertical></v-divider>
                    <h4 class="mt-5">Frete:</h4>
                    <div>
                        <v-text-field
                            :loading="loading"
                            v-model="numeroFrete"
                            @input="buscarProdutoFrete()"
                            outlined
                            label="Busque pelo número do frete"
                            type="number"
                            autocomplete="off"
                        ></v-text-field>
                    </div>
                    <div v-if="colaboradorEscolhido">
                        <v-btn
                            block
                            :dark="!loading && areaAtual !== 'CONFERENCIA_FORNECEDOR'"
                            :disabled="loading || areaAtual === 'CONFERENCIA_FORNECEDOR'"
                            :loading="loading"
                            @click="alteraAreaAtual('CONFERENCIA_FORNECEDOR')"
                        >Ver Separações de Produtos</v-btn>
                        <v-btn
                            block
                            :dark="!loading && areaAtual !== 'CONFERENCIA_FRETE'"
                            :disabled="loading || areaAtual === 'CONFERENCIA_FRETE'"
                            :loading="loading"
                            @click="alteraAreaAtual('CONFERENCIA_FRETE')"
                        >Ver Fretes disponíveis</v-btn>
                    </div>
                </div>
            </div>

            <div v-if="colaboradorEscolhido">
                <div class="px-6 mt-3">
                    <v-text-field
                        v-model="input_qrcode"
                        id="inputBipagem"
                        label="Bipagem"
                        :loading="carregandoConferir || loading"
                        :disabled="modalErro.exibir || carregandoConferir || loading"
                        placeholder="Aguardando bipagem de QRCode dos produtos"
                        autofocus
                        outlined
                    ></v-text-field>
                    <div class="d-flex d-flex justify-content-between">
                        <p v-if="(CONFERENCIA_items || []).length" style="font-size: 1.2rem; margin-bottom: 0.2rem;">
                            <b style="font-size: 1.8rem;">{{ this.CONFERENCIA_items.length }}</b>&ensp;produtos para conferir
                        </p>
                        <p v-if="CONFERENCIA_itens_bipados.length" style="font-size: 1.2rem; margin-bottom: 0.2rem;">
                            <b style="font-size: 1.8rem;">{{ this.CONFERENCIA_itens_bipados.length }}</b>&ensp;
                            <span v-if="this.CONFERENCIA_itens_bipados.length === 1">produto bipado ainda não salvo</span>
                            <span v-else>produtos bipados ainda não salvos</span>
                        </p>
                    </div>
                </div>

                <v-row>
                    <div class="col-12">
                        <div class="d-flex align-center justify-space-between m-3" v-if="CONFERENCIA_items.length">
                            <v-btn
                                color="success"
                                :disabled="!CONFERENCIA_itens_bipados.length || modalErro.exibir"
                                large
                                @click="modalConfirmarBipagem = true"
                            >
                                CLIQUE AQUI QUANDO TERMINAR DE BIPAR OS PRODUTOS
                            </v-btn>
                            <v-btn
                                color="secondary"
                                @click="imprimirEtiqueta"
                                :disabled="produtosSelecionados.length == 0 || modalErro.exibir"
                            >
                                Imprimir Etiqueta
                            </v-btn>
                        </div>
                    </div>
                </v-row>
                <v-data-table
                    checkbox-color="secondary"
                    v-model="produtosSelecionados"
                    show-select
                    :loading="loading"
                    :headers="CONFERENCIA_headers"
                    :items="CONFERENCIA_items"
                    :footer-props="{'items-per-page-options': [50, 100, 200, -1]}"
                    :items-per-page="-1"
                    item-key="uuid"
                    :item-class="itemClass"
                >
                    <template v-slot:item.id_produto="{ item }">
                        <div class="fundo-destaque-tabela">
                            <p>{{item.id_produto}}</p>
                        </div>
                    </template>
                    <template v-slot:item.bipado="{ item }">
                        <div>
                            <p v-if="item.bipado">
                                Item bipado em {{item.data_bipagem}}
                                <br/>
                                <strong class="badge badge-danger text-md-subtitle-2 mt-2">Ainda não salvo</strong>
                            </p>
                            <p v-else>Produto Não Bipado</p>
                        </div>
                    </template>
                    <template v-slot:item.nome_produto="{ item }">
                        <div>
                            <img style="width: 5rem; margin-right: 1rem;" :src="item.foto" />
                            {{ item.nome_produto || item.destinatario }}
                            <b class="text-danger" v-if="!!item.negociacao_aceita">(PRODUTO SUBSTITUTO)</b>
                            <b class="badge badge-warning fa-1x" v-if="item.tem_coleta">[COLETA]</b>
                        </div>
                    </template>
                    <template v-slot:item.tamanho="{ item }">
                        <div class="fundo-destaque-tabela">
                            <p>{{ item.tamanho || item.telefone }}</p>
                        </div>
                    </template>
                    <template v-slot:item.razao_social="{ item }">
                        ({{ item.id_cliente }}) {{ item.razao_social }}
                    </template>
                    <template v-slot:item.dias_na_separacao="{ item }">
                        há {{ item.dias_na_separacao }} dias
                    </template>
                </v-data-table>
            </div>
            <br />
        </v-card>

        <!-- Dialog para registar usuario -->
        <v-dialog
            v-model="modalRegistrarUsuario"
            persistent
            max-width="37.5rem"
            max-height="37.5rem"
        >
            <v-card>
                <v-toolbar dark color="light-blue" class="d-flex justify-center">
                    <v-icon class="mr-2">mdi-account-alert</v-icon>
                    <h5 class="m-0">
                        Cadastro rápido
                    </h5>
                </v-toolbar>
                <v-card-text>
                    <h6 class="text-center mt-1">
                        Por favor, inicie um cadastro para continuar.
                    </h6>
                    <div>
                        <v-text-field
                            v-model="conferencia.telefoneUsuario"
                            label="Digite seu Telefone:"
                            outlined
                            dense
                            required
                            class="mt-3"
                            maxlength="15"
                        ></v-text-field>
                        <v-text-field
                            v-model="conferencia.nomeUsuario"
                            label="Digite seu nome completo:"
                            outlined
                            dense
                            required
                            class="mt-3"
                        ></v-text-field>
                    </div>
                </v-card-text>
                <v-card-actions class="flex-row justify-content-center">
                    <v-btn
                        dark
                        color="red"
                        class="mb-2"
                        :disabled="carregandoConferir"
                        @click="fecharModais"
                    >
                        CANCELAR
                    </v-btn>
                    <v-btn
                        dark
                        color="primary"
                        class="mb-2"
                        :disabled="carregandoConferir"
                        @click="cadastroRapidoUsuario"
                    >
                        CADASTRAR
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para confirmar a bipagem dos produtos -->
        <v-dialog
            v-model="modalConfirmarBipagem"
            fullscreen
            persistent
        >
            <v-card class="d-flex flex-column justify-center align-center" :loading="carregandoConferir">
                <v-card-text>
                    <h3 class="black--text m-4 text-center">
                        Você confirma a bipagem de
                    </h3>
                    <div id="itens_bipados">
                        <h1 class="warning p-3 black-text">
                            {{ this.CONFERENCIA_itens_bipados.length }}
                        </h1>
                    </div>
                    <h2 class="black--text m-4 text-center" v-if="this.CONFERENCIA_itens_bipados.length === 1">produto?</h2>
                    <h2 class="black--text m-4 text-center" v-else>produtos?</h2>
                    <h3
                        class="alert-danger mt-3 p-2 text-center"
                        v-cloak
                        v-if="taxaDevolucaoProdutoErrado && areaAtual === 'CONFERENCIA_FORNECEDOR'"
                    >
                        Atenção: Será cobrada uma multa no valor de <b>{{ taxaDevolucaoProdutoErrado }}</b> para cada produto enviado errado.
                    </h3>
                    <h3
                        class="alert-danger mt-3 p-2 text-center"
                        v-cloak
                        v-if="areaAtual === 'CONFERENCIA_FRETE'"
                    >
                        Esteja atento(a) a quais produtos você deseja enviar para determinado destino.
                    </h3>
                    <v-container class="centralizado" v-show="!conferencia.possivelConfirmar">
                        <h5 class="text-center">Quem está entregando os produtos?</h5>
                        <v-autocomplete
                            v-model="conferencia.colaboradorEscolhidoConfirmaBipagem"
                            :items="listaColaboradoresFrete"
                            :loading="loading"
                            :disabled="modalErro.exibir"
                            :search-input.sync="pesquisaConferente"
                            hide-no-data
                            item-text="descricao"
                            label="Busca nome ou telefone"
                            prepend-icon="mdi-magnify"
                            no-filter
                            return-object
                            autocomplete="off"
                        ></v-autocomplete>
                    </v-container>
                    <h3
                        v-show="conferencia.possivelConfirmar && !!conferencia.colaboradorEscolhidoConfirmaBipagem"
                        class="text-center"
                    >
                        USUÁRIO: {{ this.conferencia.nomeUsuario }}!
                    </h3>
                    <h4
                        v-show="conferencia.possivelConfirmar"
                        class="m-5 mb-0 text-center black--text"
                    >
                        Ao clicar no botão "Confirmar", você concorda que todos os produtos bipados estão sendo entregues em nossa central, devidamente conferido.
                    </h4>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-text v-show="carregandoConferir">
                        <h6 class="text-center">
                            Estamos executando o processo de confirmação, aguarde...
                        </h6>
                        <h6 class="text-center">
                            Assim que terminar a pagina será recarregada automaticamente.
                        </h6>
                    </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        dark
                        color="secondary"
                        :disabled="carregandoConferir"
                        @click="modalConfirmarBipagem = false"
                    >
                        Voltar para lista
                    </v-btn>
                    <v-btn
                        v-show="!conferencia.possivelConfirmar"
                        dark
                        color="primary"
                        :disabled="carregandoConferir || !!conferencia.colaboradorEscolhidoConfirmaBipagem"
                        :loading="carregandoConferir"
                        @click="modalRegistrarUsuario = true"
                    >
                        Cadastrar
                    </v-btn>
                    <v-btn
                        v-show="conferencia.possivelConfirmar"
                        dark
                        color="green"
                        :disabled="carregandoConferir"
                        :loading="carregandoConferir"
                        @click="confirmarItens"
                    >
                        Confirmar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para exibir produtos com defeitos para retirar -->
        <v-dialog
            v-model="modalProdutosDevolucaoAguardando.exibir"
            transition="dialog-bottom-transition"
            persistent
        >
            <v-card>
                <v-toolbar dark color="error">
                    <v-toolbar-title>Produtos com defeito aguardando retirada</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon dark @click="modalProdutosDevolucaoAguardando.exibir = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <div class="titulo-produtos-defeito-aguardando">
                    <span class="text-h4">
                        Você tem {{ modalProdutosDevolucaoAguardando.dados.length }}
                        <span v-if="modalProdutosDevolucaoAguardando.dados.length === 1">
                            produto
                        </span>
                        <span v-else>
                            produtos
                        </span>
                        com defeito aguardando retirada
                    </span>
                    <div class="text-h6">Os produtos ficam armazenados por até 30 dias, e após este prazo, os mesmos serão descartados.</div>
                </div>

                <div v-for="item in modalProdutosDevolucaoAguardando.dados" class="p-5 pt-0">
                    <v-card class="mx-auto" max-width="500" outlined >
                        <v-list-item>
                            <img
                                :src="item.foto_produto"
                                style="height: 5em; width: auto; margin-right: 1em;"
                                :alt="item.nome_comercial"
                            />
                            <v-list-item-content>
                                <div class="text-h7">
                                {{ item.id_produto }} {{ item.nome_comercial }}
                                </div>
                                <v-card-text>
                                    <div>
                                        <b>Data de Entrada:</b> {{ item.data }}
                                    </div>
                                    <div v-if="item.descricao_defeito">
                                        <b>Defeito relatado:</b> {{ item.descricao_defeito }}
                                    </div>
                                </v-card-text>
                            </v-list-item-content>
                            <div class="fundo-destaque-tabela">
                                <p>{{ item.nome_tamanho }}</p>
                            </div>
                        </v-list-item>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="primary"
                                :disabled="carregandoRetirarDevolucao"
                                :loading="carregandoRetirarDevolucao"
                                @click="retirarDevolucaoDefeito(item.uuid_produto)"
                            >
                            Retirei o produto
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </div>
            </v-card>
        </v-dialog>

        <!-- Dialog para exibir alerta de cadastro -->
        <v-dialog
            v-model="modalAlertaUsuarioNaoEncontrado"
            transition="dialog-bottom-transition"
            max-width="30rem"
            max-height="90rem"
        >
            <v-card>
                <v-toolbar dark color="orange" class="d-flex justify-center">
                    <v-icon class="mr-2">mdi-alert</v-icon>
                    <h5 class="m-0">ATENÇÃO</h5>
                </v-toolbar>
                <v-card-text>
                    <h6 class="text-center mt-1">
                    Nenhum cadastro encontrado, gostaria de se cadastrar?
                    </h6>
                </v-card-text>
                <div class="flex-row">
                    <v-card-actions class="justify-content-center">
                        <v-btn
                            color="secondary"
                            @click="modalAlertaUsuarioNaoEncontrado = false"
                            tabindex="-1"
                        >
                            Fechar
                        </v-btn>
                        <v-btn
                            dark
                            color="primary"
                            @click="modalRegistrarUsuario = true"
                            tabindex="-1"
                        >
                            Cadastrar
                        </v-btn>
                    </v-card-actions>
                </div>
            </v-card>
        </v-dialog>

        <!-- Dialog para exibir os erros que ocorrerem na tela -->
        <v-dialog
            v-model="modalErro.exibir"
            transition="dialog-bottom-transition"
            max-width="30rem"
            persistent
        >
            <v-card>
                <v-toolbar dark color="error">
                    <v-toolbar-title>
                        <v-icon>mdi-alert</v-icon>
                        <span>ATENÇÃO</span>
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>

                <div class="alert alert-danger m-3 mt-6 text-center">
                    {{ modalErro.mensagem }}
                </div>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        text
                        color="primary"
                        @click="fecharModalErro"
                        tabindex="-1"
                    >
                    Fechar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para configurar o ip da impressora -->
        <v-dialog
            v-model="modalIpImpressora"
            transition="dialog-bottom-transition"
            max-width="30rem"
        >
            <v-card>
                <v-toolbar dark color="info">
                    <v-toolbar-title>
                        <v-icon>mdi-information-outline</v-icon>
                        <span>ATENÇÃO</span>
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>

                <div class="m-3 mt-6 text-center">
                    <p>Configure o ip da impressora</p>
                    <v-text-field
                        v-model="ipImpressora"
                        label="Ip da impressora"
                        placeholder="Exemplo: 192.168.0.100"
                        required
                        autofocus
                        outlined
                    ></v-text-field>
                </div>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        text
                        color="primary"
                        @click="salvarIpImpressora"
                    >
                        Salvar
                    </v-btn>
                    <v-btn
                        text
                        color="error"
                        @click="modalIpImpressora = false"
                        tabindex="-1"
                    >
                    Fechar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para exibir os erro de extensão não instalada -->
        <v-dialog
            v-model="modalErroExtensao"
            transition="dialog-bottom-transition"
            max-width="30rem"
            persistent
        >
            <v-card>
                <v-toolbar dark color="error">
                    <v-toolbar-title>
                        <v-icon>mdi-alert</v-icon>
                        <span>ATENÇÃO</span>
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>

                <div class="alert alert-danger m-3 mt-6 text-center">
                    A extensão de impressão de etiquetas não está instalada ou não está ativa! <br>
                    Ela pode ser baixada <a href="https://chromewebstore.google.com/detail/zebra-printing/ndikjdigobmbieacjcgomahigeiobhbo" target="_blank">aqui</a>
                </div>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        text
                        color="primary"
                        @click="modalErroExtensao = false"
                        tabindex="-1"
                    >
                    Fechar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.mostra" :color = "snackbar.cor">
            {{ snackbar.texto }}
            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="snackbar.mostra = false">
                    Fechar
                </v-btn>
            </template>
        </v-snackbar>

    </v-app>
</div>

<script src="js/tools/formataDataHora.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/processos-seller-externo.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/tools/formataMoeda.js"></script>
