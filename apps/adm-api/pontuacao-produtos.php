<?php require_once __DIR__ . '/cabecalho.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<v-app id="pontuacaoprodutos">
    <div class="container">
        <h4 class="mt-3 text-center">Pontuação dos Produtos Meulook</h4>
        <div class="row">
            <div class="col">
                <v-text-field
                    label="Buscar"
                    v-model="pesquisa"
                    :disabled="carregando"
                    :loading="carregando"
                ></v-text-field>
            </div>
            <div class="col-auto">
                <v-switch v-model="mostrarTodosSellers" label="Global"></v-switch>
            </div>
        </div>
        <div>
            <v-btn block @click="dialog.mostrar = true">Entenda como funciona</v-btn>
        </div>
        <br />
        <div>
            <div v-for="produto in produtos" :class="'shadow-sm rounded ' + (produto.eh_meu_produto ? 'bg-light' : '')">
                <div class="row px-1 pb-3">
                    <figure class="col-3 mb-0 pb-0 pr-1" style="max-width: 5rem; max-height: 5rem;">
                        <img
                            class="w-100 h-100 object-cover rounded"
                            :src="produto.foto"
                            :alt="'Foto do produto ' + produto.id_produto"
                        >
                    </figure>
                    <div class="col mb-0 pb-0 pl-1 overflow-hidden">
                        <a class="text-capitalize" :href="produto.link_produto">{{ produto.id_produto }} - {{ produto.nome }}</a>
                        <div>
                            <small>{{ produto.razao_social_seller }}</small>
                            <br />
                            <small>
                                (<a :href="produto.link_seller">{{ produto.usuario_meulook_seller }}</a>)
                            </small>
                        </div>
                    </div>
                </div>
                <div class="row px-3 mt-0">
                    <div class="col">
                        <small>Avaliações: {{ produto.pontuacao_avaliacoes | pontuacao }}</small>
                        <br />
                        <small>Reputação: {{ produto.pontuacao_seller | pontuacao }}</small>
                        <br />
                        <small>Fullfillment: {{ produto.pontuacao_fullfillment | pontuacao }}</small>
                        <br />
                        <small>Vendas: {{ produto.quantidade_vendas | pontuacao }}</small>
                    </div>
                    <div class="col">
                        <small>Troca normal: {{ produto.pontuacao_devolucao_normal | pontuacao }}</small>
                        <br />
                        <small>Troca defeito: {{ produto.pontuacao_devolucao_defeito | pontuacao }}</small>
                        <br />
                        <small>Cancelamentos: {{ produto.pontuacao_cancelamento | pontuacao }}</small>
                        <br />
                        <small>Atraso na separação: {{ produto.atraso_separacao | pontuacao }}</small>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small>
                        <b>Total: {{ produto.total }} pontos</b>
                    </small>
                </div>
                <hr />
            </div>
            <div class="py-3">
                <v-icon v-if="!ultimaPagina" v-intersect="onIntersect">mdi-dots-horizontal</v-icon>
            </div>
        </div>
    </div>
    <v-snackbar v-model="snack.mostrar">{{ snack.mensagem }}</v-snackbar>
    <v-dialog
        v-model="dialog.mostrar"
        fullscreen
        hide-overlay
        transition="dialog-bottom-transition"
        style="z-index: 9999;"
    >
        <v-card>
            <v-toolbar dark color="error">
                <v-toolbar-title>Entenda como funciona</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon dark @click="dialog.mostrar = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <div class="container">
                <p>A pontuação dos produtos é definida pelos seguintes fatores:</p>
                <ul>
                    <li>Cada avaliação com 5 entrelas: +{{ pontuacoes.avaliacao_5_estrelas }} pontos</li>
                    <li>Cada avaliação com 4 entrelas: +{{ pontuacoes.avaliacao_4_estrelas }} pontos</li>
                    <li>Se há Fullfillment: +{{ pontuacoes.possui_fulfillment }} pontos</li>
                    <li>Cada venda: +{{ pontuacoes.pontuacao_venda }} ponto</li>
                    <br />
                    <li>Reputação Melhor Fabricante: +{{ pontuacoes.reputacao_melhor_fabricante }} pontos</li>
                    <li>Reputação Boa: +{{ pontuacoes.reputacao_excelente }} pontos</li>
                    <li>Reputação Ruim: {{ pontuacoes.reputacao_ruim }} pontos</li>
                    <br />
                    <li>Devolução Normal: {{ pontuacoes.devolucao_normal }} pontos</li>
                    <li>Devolução Defeito: {{ pontuacoes.devolucao_defeito }} pontos</li>
                    <li>Cancelamentos: {{ pontuacoes.pontuacao_cancelamento }} pontos</li>
                    <li>Atraso na separação: {{ pontuacoes.atraso_separacao }} pontos</li>
                </ul>
            </div>
        </v-card>
    </v-dialog>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/pontuacao-produtos.js"></script>
