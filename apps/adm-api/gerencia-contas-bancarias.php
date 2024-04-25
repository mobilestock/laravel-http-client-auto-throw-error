<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

    <link href="http://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">

<v-app id="contasBancariaColaboradores">
    <template>
        <div>
            <h1 class="text-center">
                Dados Bancários dos Colaboradores
            </h1>
        </div>

        <div class="align-center d-flex justify-center pl-5">
            <div class="w-100">
                <v-text-field
                    solo label="Filtrar tabela"
                    append-icon="mdi-magnify-scan"
                    v-model="filtro"
                    hide-details
                    class="elevation-0 m-2 p-2"
                    single-line>
                </v-text-field>
            </div>
        </div>

        <v-data-table
            :headers="headerDados"
            :items="listaContas"
            :loading="isLoading"
            :search="filtro"
        >
            <template v-slot:item.cpf_titular='{ item }'>
                {{ cpfCnpjFormatado(item.cpf_titular) }}
            </template>

            <template v-slot:item.nome_titular='{ item }'>
                {{ item.nome_titular }}
            </template>

            <template v-slot:item.id_banco='{ item }'>
                {{ item.banco }} - ( {{ item.id_banco }} )
            </template>

            <template v-slot:item.editar='{ item }'>
                <v-btn color="warning" @click="abrirModalDados(item)">
                    <v-icon >mdi-tooltip-edit</v-icon>
                </v-btn>
            </template>
        </v-data-table>
    </template>

    <!-- Modal dados -->
    <v-dialog
        v-model="modalEditaDados"
        persistent
        transition="dialog-bottom-transition"
        width="auto"
    >
        <v-card>
            <v-card-title class="justify-content-center">Editar Dados Bancários:</v-card-title>

            <span class="align-center justify-center m-2 p-2">
                Nome titular:
            </span>
            <v-text-field
                solo
                class="align-center d-flex justify-center m-2 p-2"
                type="nome"
                v-model="nomeAlterado"
            ></v-text-field>

            <span class="align-center justify-center m-2 p-2">
                Código Banco:
            </span>
            <v-text-field
                solo
                class="align-center d-flex justify-center m-2 p-2"
                type="nome"
                v-model="codigoAlterado"
            ></v-text-field>

            <span class="align-center justify-center m-2 p-2">
                Agência:
            </span>
            <v-text-field
                solo
                class="align-center d-flex justify-center m-2 p-2"
                type="nome"
                v-model="agenciaAlterada"
            ></v-text-field>

            <span class="align-center justify-center m-2 p-2">
                Conta:
            </span>
            <v-text-field
                solo
                class="align-center d-flex justify-center m-2 p-2"
                type="nome"
                v-model="contaAlterada"
                width="auto"
            ></v-text-field>

            <v-card-actions class="d-flex justify-content-around">
                <v-btn color="primary" text @click="modalEditaDados = false">Fechar</v-btn>
                <v-btn class="bg-danger" @click="editaDados" >Editar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/gerencia-contas-bancarias.js<?= $versao ?>"></script>
<script src="js/tools/formataCpfECnpj.js"></script>
