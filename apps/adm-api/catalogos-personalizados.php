<?php
require_once __DIR__ . '/cabecalho.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<v-app id="catalogos-personalizados">
    <div class="px-3">
        <h1>Catálogos Personalizados e Filtros</h1>
        <p v-if="configuracoes.tempoCache > 0">
            As alterações feitas nessa tela podem durar
            até <b>{{ configuracoes.tempoCache }} minutos</b> para aparecem em produção
        </p>
        <br />
        <h2>Ordem dos Filtros</h2>
        <span>Arraste os filtros para alterar a ordem de exibição.</span>
        <v-chip-group column>
            <v-chip
                v-for="(filtro, index) in filtros"
                :key="filtro.id + '-' + index"
                :draggable="!carregandoTrocarOrdem"
                :disabled="carregandoTrocarOrdem"
                @dragstart="onDragStart(index)"
                @dragover.prevent="onDragOver(index)"
                @drop="onDrop(index)"
                :dark="!parseInt(filtro.id)"
                :disabled="carregandoFiltros"
            >
                {{ filtro.nome }}
            </v-chip>
        </v-chip-group>
        <br />
        <h2>Catálogos Personalizados</h2>
        <v-data-table
            :headers="cabecalho"
            :items="catalogos"
            :items-per-page="15"
            class="elevation-1"
            :disabled="carregandoCatalogos"
        >
            <template v-slot:item.esta_ativo="{ item }">
                <v-icon color="green" v-if="item.esta_ativo">
                    mdi-check
                </v-icon>
                <v-icon color="red" v-else>
                    mdi-close
                </v-icon>
            </template>
            <template v-slot:item.acoes="{ item }">
                <v-tooltip v-if="item.tipo === 'PUBLICO'" top>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            :disabled="carregandoAtivarDesativar"
                            v-on="on"
                            @click="ativarDesativarCatalogo(item)"
                            icon
                        >
                            <v-icon color="orange" v-if="item.esta_ativo">
                                mdi-eye-off
                            </v-icon>
                            <v-icon color="green" v-else>
                                mdi-eye
                            </v-icon>
                        </v-btn>
                    </template>
                    <span>
                        {{ item.esta_ativo ? 'Desativar' : 'Ativar' }}
                    </span>
                </v-tooltip>
                <v-tooltip v-else top>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn @click="abrirDialogDuplicarCatalogo(item)" icon v-on="on">
                            <v-icon color="blue">
                                mdi-content-duplicate
                            </v-icon>
                        </v-btn>
                    </template>
                    <span>
                        Duplicar catálogo
                    </span>
                </v-tooltip>
                <v-btn v-if="item.id_colaborador == meuId" @click="abrirDialogDeletarCatalogo(item)" icon>
                    <v-icon color="red">
                        mdi-delete
                    </v-icon>
                </v-btn>
            </template>
            <template v-slot:item.link="{ item }">
                <div v-if="item.quantidade_produtos > 0">
                    <v-btn small @click="abrirLinkExterno(item.link_ms)">MS</v-btn>
                    <v-btn small @click="abrirLinkExterno(item.link_ml)">ML</v-btn>
                </div>
            </template>
        </v-data-table>
    </div>

    <v-dialog v-model="dialogDuplicarCatalogo.mostrar" width="500">
        <v-card>
            <v-card-title>
                <span class="headline">Duplicar catálogo</span>
            </v-card-title>
            <v-container class="px-5">
                <v-text-field label="Nome" v-model="dialogDuplicarCatalogo.nome" required></v-text-field>
                <p class="mt-3 mb-5">
                    <b>
                        Plataformas onde os catálogos aparecerão como filtros
                    </b>
                </p>
                <v-checkbox
                    v-for="plataforma in plataformas"
                    v-model="dialogDuplicarCatalogo.json_plataformas_filtros"
                    class="m-0"
                    :label="plataforma.nome"
                    :value="plataforma.valor"
                    :key="plataforma.valor"
                ></v-checkbox>
            </v-container>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="red" text @click="dialogDuplicarCatalogo.mostrar = false">
                    Cancelar
                </v-btn>
                <v-btn
                    color="blue"
                    text
                    @click="criarCatalogo(dialogDuplicarCatalogo)"
                    :disabled="dialogDuplicarCatalogo.carregando"
                >
                    Criar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDeletarCatalogo.mostrar" width="500">
        <v-card>
            <v-card-title>
                <span class="headline">Atenção!</span>
            </v-card-title>
            <v-container>
                Deseja realmente deletar o catalogo "{{ dialogDeletarCatalogo.nome }}"?
            </v-container>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue" text @click="dialogDeletarCatalogo.mostrar = false">
                    Fechar
                </v-btn>
                <v-btn
                    color="red"
                    text
                    @click="deletarCatalogo(dialogDeletarCatalogo)"
                    :disabled="dialogDeletarCatalogo.carregando"
                >
                    DELETAR
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.mostrar">{{ snackbar.mensagem }}</v-snackbar>
</v-app>

<script type="module" src="js/catalogos-personalizados.js"></script>
