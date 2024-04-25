<?php

require_once __DIR__ . '/cabecalho.php';

?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .page {
        padding: 0 1rem;
    }
</style>

<div class="page" id="fiscal" >
    <v-app>
    <template>
        <div class="d-flex justify-content-between" style="margin-top: 2rem;">
                <h1>Pendentes</h1>
            <v-spacer></v-spacer>
            <v-btn @click="verFinalizadas()">Finalizadas</v-btn>
        </div>
        <div>
            <v-data-table :headers="cabecalho" :items="entregas" class="elevation-3" :loading="loadingTabela" loading-text="Carregando...">
                <template v-slot:item.cliente="{ item }">
                    <span style="text-transform: capitalize;">
                        {{ item.cliente }}
                    </span>
                </template>
                <template v-slot:item.telefone="{ item }">
                    <button @click="mensagemWhatsapp(item)">
                        <v-icon color="green accent-2">mdi-whatsapp</v-icon>
                    </button>
                </template>
                <template v-slot:item.id_cliente="{ item }">
                    <v-btn @click="abreModalDados(item)">
                        <v-icon>info</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
            
            <br>
        </div>
                    
                        
        <template>
             <v-row justify="center">
                <v-dialog
                v-model="modalDados"
                max-width="600px"
                >
                <v-form @submit.prevent="insereDadosRastreio">
                <v-card>
                    <v-card-title>
                        <span class="text-h5">Rastrear</span>
                    </v-card-title>
                    <v-card-text>
                    <v-container>
                        <v-row>
                        <v-col
                            cols="12"
                            sm="6"
                            md="4"
                        >
                        <v-text-field
                            label="Entrega"
                            v-model="dadosModal.id_entrega"
                            disabled
                        ></v-text-field>
                        </v-col>
                        <v-col
                            cols="12"
                            sm="6"
                            md="4"
                        >
                            <v-text-field
                            label="Produtos"
                            v-model="dadosModal.produtos"
                            disabled
                            ></v-text-field>
                        </v-col>
                        <v-col
                            cols="12"
                            sm="6"
                            md="4"
                        >
                        <v-text-field
                            label="Volumes"
                            v-model="dadosModal.volumes"
                            disabled
                        ></v-text-field>
                        </v-col>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-autocomplete
                                        :hint="'Seleciona a transportadora'"
                                        :items="listaTransportadoras"
                                        item-text="razao_social"
                                        item-value="id_colaborador"
                                        label="Transportadora"
                                        persistent-hint
                                        dense
                                        filled
                                        label="Filled"
                                    ></v-autocomplete>
                                </v-col>
                            </v-row>
                        </v-container>
                            <v-col cols="12">
                                <v-text-field
                                label="CNPJ"
                                placeholder="CNPJ"
                                filled
                                v-model="cnpj"
                                ></v-text-field>
                            </v-col>
                            <v-col
                                cols="12">
                                <v-text-field
                                label="Nota Fiscal"
                                filled
                                required
                                ></v-text-field>
                            </v-col>
                            </v-row>
                        </v-container>
                        </v-card-text>
                        <v-card-actions>
                        <v-btn
                            color="red accent-4"
                            text
                            @click="modalDadosCliente = true"
                        >
                            Dados do cliente
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="red accent-4"
                            text
                            @click="modalDados = false"
                        >
                            Fechar
                        </v-btn>
                        <v-btn
                            color="blue darken-1"
                            text
                            type="submit"
                        >
                            Salvar
                        </v-btn>
                        </v-card-actions>
                    </v-form>
                    </v-card>
                </v-dialog>
            </v-row>
        </template>

        <template>
             <v-row justify="center">
                <v-dialog
                v-model="modalAlterar"
                max-width="600px"
                >
                <v-form @submit.prevent="alteraDadosRastreio">
                <v-card>
                    <v-card-title>
                        <span class="text-h5">Alterar</span>
                    </v-card-title>
                    <v-card-text>
                    <v-container>
                        <v-row>
                        <v-col
                            cols="12"
                        >
                        <v-text-field
                            label="Entrega"
                            v-model="dadosModalAlterar.id_entrega"
                            disabled
                        ></v-text-field>
                        </v-col>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-autocomplete
                                        :hint="'Seleciona a transportadora'"
                                        :items="listaTransportadoras"
                                        item-text="razao_social"
                                        item-value="id_colaborador"
                                        label="Transportadora"
                                        persistent-hint
                                        dense
                                        filled
                                        label="Filled"
                                    ></v-autocomplete>
                                </v-col>
                            </v-row>
                        </v-container>
                            <v-col cols="12">
                                <v-text-field
                                label="CNPJ"
                                placeholder="CNPJ"
                                filled
                                v-model="cnpj"
                                ></v-text-field>
                            </v-col>
                            <v-col
                                cols="12">
                                <v-text-field
                                label="Nota Fiscal"
                                v-model="dadosModalAlterar.nota_fiscal"
                                filled
                                required
                                ></v-text-field>
                            </v-col>
                            </v-row>
                        </v-container>
                        </v-card-text>
                        <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="red accent-4"
                            text
                            @click="modalAlterar = false"
                        >
                            Fechar
                        </v-btn>
                        <v-btn
                            color="blue darken-1"
                            text
                            type="submit"
                        >
                            Salvar
                        </v-btn>
                        </v-card-actions>
                    </v-form>
                    </v-card>
                </v-dialog>
            </v-row>
        </template>

        
        <template>
            <v-row justify="center">
                <v-dialog
                v-model="modalDadosCliente"
                max-width="500"
                >
                <v-card>
                    <v-card-title class="text-h5" style="margin-bottom: 1rem;">
                    Dados do cliente
                    </v-card-title>
                    <div style="margin-left: 1rem;">
                        <p>Razão social: {{ cliente.razao_social }}</p>
                        <p>CPF: {{ cliente.cpf }}</p>
                        <p>CNPJ: {{ cliente.cnpj }}</p>
                        <p>Endereço: {{ cliente.endereco }}</p>
                        <p>Número: {{ cliente.numero }}</p>
                        <p>Complemento: {{ cliente.complemento }}</p>
                        <p>Bairro: {{ cliente.bairro }}</p>
                        <p>Cidade: {{ cliente.cidade }}</p>
                        <p>Telefone: {{ cliente.telefone }}</p>
                        <p>UF: {{ cliente.uf }}</p>
                        <p>CEP: {{ cliente.cep }}</p>
                    </div>
                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="blue darken-1"
                        text
                        @click="modalDadosCliente = false"
                    >
                        Ok
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </v-row>
        </template>

        <v-row justify="center">
            <v-dialog
                v-model="listaFinalizadas"
                max-width="1200px">
                <v-card>
                    <v-card-title>
                        <span class="text-h5">Finalizadas</span>
                    </v-card-title>
                    <v-card-text>
                        <v-data-table :headers="cabecalhoFinalizadas" :items="finalizadas" :loading="loadingTabelaFinalizadas" class="elevation-3">
                            <template v-slot:item.alterar="{ item }">
                                <v-btn @click="abreModalAlterar(item)">
                                    <v-icon>info</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="error"
                            text
                            @click="listaFinalizadas = false">
                            Fechar
                        </v-btn>
                        <v-btn
                            color="blue darken-1"
                            text
                            @click="listaFinalizadas = false">
                            OK
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>



        <template>
            <div class="text-center ma-2">
            <v-snackbar v-if="vazio == false" v-model="snackbar" color="success"> 
                <span style="text-align: center;">
                    {{ textoSnackbar }}
                </span>
            </v-snackbar>
            <v-snackbar v-else v-model="snackbar" color="error"> 
                <span style="text-align: center;">
                    {{ textoSnackbar }}
                </span>
            </v-snackbar>            
            </div>
        </template>


    </template>
    </v-app>
</div>

<script src="js/whatsapp.js<?= $versao ?>"></script>
<script type="module" src="js/fiscal-gerenciar.js"></script>