<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
  .promocao-baixa {
    color: green;
  }
  .promocao-media {
    color: orange;
  }
  .promocao-alta {
    color: red;
  }
</style>

<v-app id="analisePromocoes" class="px-3">
    <v-card>
        <v-card-title>
            Análise de Promoções
            <v-spacer></v-spacer>
            <v-text-field
                v-model="pesquisa"
                append-icon="mdi-magnify"
                label="Pesquisar"
                single-line
                hide-details
                :disabled="carregando"
            ></v-text-field>
        </v-card-title>
        <v-data-table
            :headers="cabecalhos"
            :items="itens"
            :items-per-page="50"
            :loading="carregando"
            class="elevation-1"
        >
            <template v-slot:item.foto_produto="{ item }">
                <img width="60" class="img-fluid" :src="item.foto_produto" alt="item.nome_produto" />
            </template>
            <template v-slot:item.nome_produto="{ item }">
                <div>
                    <span class="text-capitalize">{{ item.id_produto }}. {{ item.nome_produto }}</span>
                    <div>
                        Seller:
                        <a class="text-capitalize" :href="item.link_perfil" target="_blank">
                            {{ item.id_colaborador }}. {{ item.nome_colaborador }}
                        </a>
                    </div>
                </div>
            </template>
            <template v-slot:item.valores_antigo="{ item }">
                <div>
                    <span>
                        MS: <b class="text-danger">{{ item.valores_venda_historico.ms }}</b>
                    </span>
                    <br />
                    <span class="mt-1">
                        ML: <b class="text-danger">{{ item.valores_venda_historico.ml }}</b>
                    </span>
                </div>
            </template>
            <template v-slot:item.valores_novo="{ item }">
                <div>
                    <span>
                        MS: <b class="text-success">{{ item.valores_venda.ms }}</b>
                    </span>
                    <br />
                    <span class="mt-1">
                        ML: <b class="text-success">{{ item.valores_venda.ml }}</b>
                    </span>
                </div>
            </template>
            <template v-slot:item.porcentagem="{ item }">
                <p :class="item.estilo_porcentagem + ' text-center my-3'">
                    <b>{{ item.porcentagem }}%</b>
                </p>
            </template>
            <template v-slot:item.data_atualizou_valor_custo="{ item }">
                <p>{{ item.data_atualizou_valor_custo_formatado }}</p>
            </template>
            <template v-slot:item.acoes="{ item }">
                <v-btn
                    small
                    color="success"
                    @click="abrirLinkWhatsapp(item)"
                    class="mr-1"
                    v-if="item.telefone_colaborador"
                >
                    <v-icon>mdi-whatsapp</v-icon>
                </v-btn>
                <v-btn
                    small
                    color="error"
                    @click="promocaoDesativar = item"
                >
                    <v-icon left>mdi-close</v-icon>
                    Desativar
                </v-btn>
            </template>
        </v-data-table>
        <v-dialog
            :value="promocaoDesativar != null"
            max-width="550"
            persistent
        >
            <v-card v-if="promocaoDesativar != null">
                <v-card-title class="mx-auto d-block">
                    Tem certeza que deseja desativar essa promoção?
                </v-card-title>
                <v-container>
                    <img
                        width="300"
                        class="img-fluid rounded mx-auto d-block"
                        :src="promocaoDesativar.foto_produto"
                        alt="promocaoDesativar.nome_produto"
                    />
                    <br />
                    <p class="text-center text-capitalize">
                        <b>{{ promocaoDesativar.id_produto }}. {{ promocaoDesativar.nome_produto }}</b>
                    </p>
                    <p class="text-center text-capitalize">
                        Desconto: <b :class="promocaoDesativar.estilo_porcentagem">{{ promocaoDesativar.porcentagem }}%</b>
                    </p>
                    <v-container>
                        <v-row>
                            <v-btn
                                color="error"
                                class="mr-2"
                                @click="desativarPromocao"
                                text
                                :loading="carregando"
                                :disabled="carregando"
                            >
                                Desativar Promoção
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="warning"
                                class="mr-2"
                                @click="promocaoDesativar = null"
                                :loading="carregando"
                                :disabled="carregando"
                            >
                                Fechar
                            </v-btn>
                        </v-row>
                    </v-container>
                </v-container>
            </v-card>
        </v-dialog>
    </v-card>
    <v-snackbar v-model="snack.mostrar" timeout="2000">{{ snack.mensagem }}</v-snackbar>
</v-app>

<script src="js/whatsapp.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script type="module" src="js/analise-promocoes.js"></script>
