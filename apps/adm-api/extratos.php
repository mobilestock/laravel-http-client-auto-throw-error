<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

    <style>
        .formulario-pesquisa-extratos {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }
        .formulario-pesquisa-extratos > .pesquisa-ampla {
            flex: 1;
        }
        .formulario-pesquisa-extratos > .botao-saldo {
            float: right;
            margin-right: 0.5rem;
        }
        .agendado {
            background-color: orange;
        }
    </style>

<div id="app">
    <v-app>
        <v-main>
            <div style="padding: 0 1rem;">
            <v-card style="margin-top: 2.5rem;"  outlined elevation="2">
                <v-card-title>
                    Central débito e crédito
                </v-card-title>
                <v-card-text class="formulario-pesquisa-extratos">
                    <v-form @submit.prevent="buscaColaboradorPorId">
                        <v-text-field type="number" label="Pesquisar por id" v-model="pesquisaId" outlined></v-text-field>
                    </v-form>
                    <v-form @submit.prevent="buscaColaborador" class="pesquisa-ampla" >
                        <v-text-field  :rules="rules" label="Nome, Cpf ou Telefone ..."  outlined append-icon="search"></v-text-field>
                    </v-form>
                    <a href="novos-clientes.php">
                        <v-btn style="float: right;" color="grey darken-3" x-large>
                            <v-icon style="color: white;">mdi-thumb-up</v-icon>
                            &nbsp;<span style="color: white;">Novos usuários</span>
                        </v-btn>
                    </a>
                    <v-btn class="botao-saldo" color="grey darken-3" x-large @click="buscaSaldo()">
                        <span style="color: white;">Saldo</span>
                    </v-btn>
                </v-card-text>
            </v-card>
            <v-data-table :headers="header" :items="colaboradores" v-if="colaboradores.length > 0">
                <template v-slot:item.telefone="{ item }">
                    {{ item.telefone }}
                </template>
                <template v-slot:item.extrato="{ item }">
                    <v-btn @click="buscaDadosExtrato(item)">
                        <v-icon>info</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
            </div>
        </v-main>

        <template>
            <div class="text-center">
                <v-dialog
                v-model="dialog"
                width="1000"
                >

                <v-card>
                    <v-card-title class="text-h5 grey lighten-2">
                        Saldos
                    </v-card-title>

                    <v-card-text style="margin-top: 1.5rem;">
                    <template>
                        <v-card
                            outlined
                            style="display: flex; align-items: center;"
                        >
                            <v-list-item three-line>
                            <v-list-item-content>
                                <div class="text-overline mb-4">
                                Saldo - Clientes
                                </div>
                                <v-list-item-subtitle>{{ saldo.cliente | dinheiro }}</v-list-item-subtitle>
                            </v-list-item-content>
                            </v-list-item>
                            <v-list-item three-line>
                                <v-list-item-content>+</v-list-item-content>
                            </v-list-item>
                            <v-list-item three-line>
                            <v-list-item-content>
                                <div class="text-overline mb-4">
                                Saldo - Seller
                                </div>
                                <v-list-item-subtitle>{{ saldo.fornecedor | dinheiro }}</v-list-item-subtitle>
                            </v-list-item-content>
                            </v-list-item>
                            <v-list-item three-line>
                                <v-list-item-content>=</v-list-item-content>
                            </v-list-item>
                            <v-list-item three-line>
                            <v-list-item-content>
                                <div class="text-overline mb-4">
                                    Total
                                </div>
                                <v-list-item-subtitle>{{ saldo_total | dinheiro }}</v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        </v-card>
                    </template>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="error"
                        text
                        @click="dialog = false"
                    >
                        Fechar
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </div>
        </template>

        <template>
            <div class="text-center">
                <v-dialog
                v-model="modal"
                >

                <v-card>
                    <v-card-title class="text-h5 grey lighten-2">
                        Extrato: {{ colaboradorSelecionado.razao_social }} {{ colaboradorSelecionado.id_colaborador }} - {{ colaboradorSelecionado.cidade }}
                        <v-spacer></v-spacer>
                        <v-card-bloq class="text-h5 grey lighten-2" v-if="detalhes_extrato.adiantamento_bloqueado">
                            (Adiantamento bloqueado)
                            <v-card-bloq />
                        <v-btn
                            icon
                            color="black"
                            @click="modal = false"
                        >
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text>
                    <template>
                        <v-form id="form-filtro-data" @submit.prevent="filtraPorData">
                            <v-row style="margin-top: 1rem; align-items: center;">
                                <v-col
                                cols="12"
                                sm="6"
                                md="4"
                                >
                                <v-menu
                                    v-model="menu"
                                    :close-on-content-click="true"
                                    offset-y
                                    min-width="auto"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="dataInicialFormatada"
                                            label="Emissão - de: "
                                            prepend-icon="mdi-calendar"
                                            readonly
                                            v-bind="attrs"
                                            v-on="on"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                    v-model="dataInicial"
                                    @input="menu = false"
                                    scrollable
                                    >
                                    </v-date-picker>
                                </v-menu>
                                </v-col>
                                <v-col
                                cols="12"
                                sm="6"
                                md="4"
                                >
                                <v-menu
                                    v-model="menu2"
                                    :close-on-content-click="true"
                                    offset-y
                                    min-width="auto"
                                >
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-text-field
                                            v-model="dataFinalFormatada"
                                            label="Emissão - até:"
                                            prepend-icon="mdi-calendar"
                                            readonly
                                            v-bind="attrs"
                                            v-on="on"
                                        ></v-text-field>
                                    </template>
                                    <v-date-picker
                                    v-model="dataFinal"
                                    @input="menu2 = false"
                                    scrollable
                                    >
                                    </v-date-picker>
                                </v-menu>
                                </v-col>
                                <v-btn type="submit" dark color="red accent-4" form="form-filtro-data">Pesquisar</v-btn>
                                <v-btn @click="limpar()">Limpar</v-btn>
                                <v-spacer></v-spacer>
                                <div>
                                    <v-menu offset-y>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-btn
                                        color="blue darken-1"
                                        dark
                                        v-bind="attrs"
                                        v-on="on"
                                        >
                                        Ações
                                        </v-btn>
                                    </template>
                                    <v-list>
                                        <v-list-item>
                                            <v-list-item-title @click="listaTrocasAgendadas">Ver trocas</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title @click="lancamentosFuturos">Lançamentos futuros</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title @click="limpaItoken">Limpar Itoken</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title @click="forcaEntradaFraude">Forçar Entrada na Fraude</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item v-if="!carregandoEhRevendedor">
                                            <v-list-item-title @click="gerirModalCriarLojaMed">Cria Loja Med</v-list-item-title>
                                        </v-list-item>
                                        <v-list-item v-if="usuarioSuperAdmin">
                                            <v-list-item-title @click="bloqueiaAdiantamento">Bloq. adiantamentos</v-list-item-title>
                                        </v-list-item>
                                    </v-list>
                                    </v-menu>
                                </div>
                        </v-form>
                        </v-row>
                        <v-card-text>
                            <template>
                                <v-card outlined style="display: flex; align-items: center;">
                                    <v-list-item three-line>
                                        <v-list-item-content>
                                            <div class="text-overline mb-4">
                                                Saldo Anterior
                                            </div>
                                            <v-list-item-subtitle>
                                                {{ detalhes_extrato.periodo | dinheiro }}
                                            </v-list-item-subtitle>
                                        </v-list-item-content>
                                    </v-list-item>
                                    <v-list-item three-line>
                                        <v-list-item-content>
                                            <div class="text-overline mb-4">
                                                Total Saldo Atual
                                            </div>
                                            <v-list-item-subtitle>
                                                {{ detalhes_extrato.total | dinheiro }}
                                            </v-list-item-subtitle>
                                        </v-list-item-content>
                                    </v-list-item>
                                    <v-list-item three-line>
                                        <v-list-item-content>
                                            <div class="text-overline mb-4">
                                                Valor Total Fulfillment
                                            </div>
                                            <v-list-item-subtitle>
                                                {{ valorTotalfulfillment | dinheiro }}
                                            </v-list-item-subtitle>
                                        </v-list-item-content>
                                    </v-list-item>
                                    <v-list-item>
                                        <v-list-item-content>
                                            <div class="text-overline mb-4">
                                                Ação
                                            </div>
                                            <v-form @submit.prevent="abreModalGerarLancamento" id="form_lancamento">
                                            <v-list-item-subtitle>
                                                <v-text-field v-model="foo" type="number" append-outer-icon="add" @click:append-outer="increment" prepend-icon="remove" @click:prepend="decrement"></v-text-field>
                                            </v-list-item-subtitle>
                                        </v-list-item-content>
                                        <div style="display: block; margin-left: 3rem;">
                                                <v-checkbox
                                                    label="Débito"
                                                    color="red"
                                                    value="debito"
                                                    hide-details
                                                    :disabled="debito_ou_credito === 'P'"
                                                    @click="debito()"
                                                ></v-checkbox>
                                                <v-checkbox
                                                    label="Crédito"
                                                    color="success"
                                                    value="credito"
                                                    hide-details
                                                    :disabled="debito_ou_credito === 'R'"
                                                    @click="credito()"
                                                ></v-checkbox>
                                                <v-btn type="submit" color="blue-grey lighten-1" form="form_lancamento" :disabled="botaoGerar === false">Gerar</v-btn>
                                            </v-form>
                                        </div>
                                    </v-list-item>
                                </v-card>
                            </template>
                                <v-form @submit.prevent="adicionaSenhaTemporaria" id="form-senha-interna">
                                    <v-text-field
                                    :append-icon="show ? 'mdi-eye' : 'mdi-eye-off'"
                                    label="Senha Temporária"
                                    style="width: 15%;"
                                    :type="show ? 'text' : 'password'"
                                    @click:append="show = !show"
                                    ></v-text-field>
                                    <v-btn type="submit" dark color="green accent 4" form="form-senha-interna">Gerar</v-btn>
                                </v-form>
                        </v-card-text>
                    </template>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-data-table :headers="header_extrato" :items="extratos" class="elevation-1">
                        <template v-slot:item.id_comissao="{ item }">
                            <a :href="item.link_transacao" > {{ item.id_comissao }}</a>
                        </template>
                        <template v-slot:item.transacao_origem="{ item }">
                            <a v-if="item.transacao_origem != 0" :href="item.link_transacao">/ {{ item.transacao_origem }}</a>
                        </template>
                        <template v-slot:item.credito="{ item }">
                            <span v-if="item.tipo === 'P' || item.faturamento_criado_pago === 'T'" class="success--text">{{ item.valor | dinheiro }}</span>
                        </template>
                        <template v-slot:item.debito="{ item }">
                            <span v-if="item.tipo === 'R' || item.faturamento_criado_pago === 'T'" class="error--text">{{ item.valor | dinheiro }}</span>
                        </template>
                        <template v-slot:item.saldo_atual="{ item }">
                            <span>{{ item.saldo_atual | dinheiro }}</span>
                        </template>
                    </v-data-table>

                    <v-divider></v-divider>

                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="error"
                        text
                        @click="modal = false"
                    >
                        Fechar
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </div>
        </template>

        <template>
            <v-row justify="center">
                <v-dialog
                v-model="modal_trocas"
                scrollable
                >
                <v-card>
                    <v-card-title>Trocas</v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-data-table :headers="header_trocas" :items="trocas" class="elevation-1" :item-class="agendado">
                            <template v-slot:item.foto="{ item }">
                                <v-img :src="item.foto" style="width: 15%; height: auto;"></v-img>
                            </template>
                            <template v-slot:item.qrCode ="{ item }">
                                <v-btn @click="qrcodeProduto = item">
                                    <v-icon style="font-size: 1rem; color: gray;">mdi-qrcode</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="error"
                        text
                        @click="modal_trocas = false"
                    >
                        Fechar
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </v-row>
        </template>

        <template>
            <div class="text-center">
                <v-dialog
                v-model="modal_lancamento"
                width="550"
                >

                <v-card>
                    <v-card-title class="text-h5 grey lighten-2">
                        Escreva o motivo deste lançamento manual
                    </v-card-title>

                    <v-card-text>
                        <v-form @submit.prevent="geraLancamento" id="form_motivo_lancamento">
                            <v-text-field></v-text-field>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="success"
                        text
                        type="submit"
                        form="form_motivo_lancamento"
                    >
                        Enviar
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </div>
        </template>

        <!-- Modal para exibir o QRCode dos produtos -->
        <template>
            <div class="center">
                <v-dialog
                    max-width="35em"
                    v-model="exibirQrcodeProduto"
                    @click:outside="qrcodeProduto = null"
                >
                <v-card>
                    <v-card-title>
                        QRCode do produto
                        <v-spacer></v-spacer>
                        <v-btn icon @click="qrcodeProduto = null">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="text-center">
                        <p><b>{{ qrcodeProduto?.descricao }} - {{ qrcodeProduto?.nome_tamanho }}</b></p>
                        <img class="img-fluid" :src="qrcodeProduto?.qrcode" :alt="qrcodeProduto?.razao_social" />
                    </v-card-text>
                </v-card>
                </v-dialog>
            </div>
        </template>

        <template>
            <v-row justify="center">
                <v-dialog v-model="modal_lancamentos_futuros" scrollable max-width="500px">
                    <v-card>
                        <v-card-title v-if="`${switch1}` === 'false'">Lançamentos Futuros - {{ colaboradorSelecionado.razao_social }}</v-card-title>
                        <v-card-title v-else>Todos Os Lançamentos Futuros</v-card-title>
                        <v-divider></v-divider>
                        <v-card-text>
                        <template>
                            <v-data-table :headers="header_lancamentos" :items="lancamentos_futuros" class="elevation-1">
                                <template v-slot:item.valor="{ item }">
                                    {{ item.valor | dinheiro }}
                                </template>
                                <template v-slot:item.details="{ item }">
                                    <span v-if="`${switch1}` === 'true'">
                                        <v-btn @click="buscaLancamentosPorData(item)">
                                            <v-icon>info</v-icon>
                                        </v-btn>
                                    </span>
                                </template>
                            </v-data-table>
                        </template>
                        </v-card-text>
                        <div class="text-center">
                            <b>Crédito vendas não entregues:</b> {{ credito_vendas_nao_entregues | dinheiro }}
                        </div>
                        <v-divider></v-divider>
                        <v-card-actions>
                        <template>
                            <v-container
                                class="px-0"
                                fluid
                            >
                                <v-switch
                                v-model="switch1"
                                label="Todos os lançamentos"
                                @click="total_de_lancamentos"
                                ></v-switch>
                            </v-container>
                        </template>
                            <v-spacer></v-spacer>
                            <v-btn @click="modal_lancamentos_futuros = false" color="blue darken-1" dark text>Ok</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-row>
        </template>

        <template>
            <v-row justify="center">
                <v-dialog v-model="modal_total_de_lancamentos" scrollable max-width="500px">
                    <v-card>
                        <v-card-title>Todos os lançamentos</v-card-title>
                        <v-divider></v-divider>
                        <v-card-text>
                            <v-data-table :headers="header_todos_lancamentos" :items="lancamentos_futuros_data_especifica" class="elevation-1" sort-by="valor" sort-desc>
                                <template v-slot:item.recebedor="{ item }">
                                    <span style="text-transform: capitalize;">{{ item.recebedor }}</span>
                                </template>
                                <template v-slot:item.valor="{ item }">
                                    <span>{{ item.valor | dinheiro }}</span>
                                </template>
                            </v-data-table>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn @click="modal_total_de_lancamentos = false" color="red accent-4" dark text>Fechar</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-row>
        </template>

        <!-- Modal Criar Loja Med -->
        <v-dialog
            width="64rem"
            v-model="abrirModalCriarLojaMed"
        >
            <v-card>
                <v-card-title>
                    <h6>Criar loja para o colaborador {{ colaboradorSelecionado.id_colaborador }}</h6>
                </v-card-title>
                <v-card-text>
                    <div>
                        <v-text-field
                            label="Nome da loja"
                            outlined
                            v-model="cadastroLojaMed.nome"
                        ></v-text-field>
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                    append-icon="mdi-link"
                                    label="URL da loja"
                                    type="url"
                                    outlined
                                    v-bind="attrs"
                                    v-model="cadastroLojaMed.url"
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <span>Não há necessidade de colocar "www" ou "http"</span>
                        </v-tooltip>
                    </div>
                </v-card-text>
                <v-card-actions class="justify-content-around">
                    <v-btn
                        color="primary"
                        :dark="!carregandoCadastrarRevendedor"
                        :disabled="carregandoCadastrarRevendedor"
                        :loading="carregandoCadastrarRevendedor"
                        @click="criarLojaMed"
                    >Cadastrar</v-btn>
                    <v-btn
                        color="error"
                        :dark="!carregandoCadastrarRevendedor"
                        :disabled="carregandoCadastrarRevendedor"
                        :loading="carregandoCadastrarRevendedor"
                        @click="abrirModalCriarLojaMed = false"
                    >Cancelar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.open" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
        <v-overlay absolute :value="loading">
            <v-progress-circular indeterminate size="64"></v-progress-circular>
        </v-overlay>
    </v-app>
</div>

<script type="module" src="js/extratos.js"></script>
<script src="js/tools/formataTelefone.js"></script>
