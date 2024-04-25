<?php require_once __DIR__ . '/cabecalho.php' ?>

<style>
    .file-upload-form,
    .image-preview {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        padding: 20px;
    }

    img.preview {
        width: 3rem;
        background-color: white;
    }

    input {
        background: unset !important;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<div id="categoriasVue" class="body-novo container-fluid mt-0 pt-0">
    <v-app>
        <div>
            <v-card class="m-0" :loading="loadingCategorias">
                <v-tabs centered v-model="tabAtual">
                    <v-tab v-for="(tab, key) in tabs" :key="key">{{ tab }}</v-tab>
                </v-tabs>

                <v-card-title>
                    <v-btn elevation="0" tile @click="criarNovaLinha">
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>

                </v-card-title>
                <v-card-text>
                    <main class="overflow-auto">

                        <div v-if="tabAtual === 0">
                            <v-treeview transition dense hoverable activatable color="primary" open-on-click v-model="tree" :items="listaCategorias" :search="search" item-key="id" open-all>
                                <template v-slot:label="{ item, open }" dense>
                                    <div style="display: flex; flex-direction: row;">
                                        <div class="m-1">
                                            <v-avatar v-if="item.icone" x-small>
                                                <img :src="item.icone" :alt="item.nome">
                                            </v-avatar>

                                            <strong class="ml-2">{{item.nome}}</strong>
                                        </div>
                                        <div v-if="item.tags.length > 0" style="display: flex; align-items: center;" class="m-1 ml-5 overflow-auto">
                                            <span v-for="item in item.tags" class="m-1" dense v-if="listaTags.find(el => el.id == item)">
                                                <v-chip dense>
                                                    <span>{{ listaTags.find(el => el.id == item).nome }}</span>
                                                </v-chip>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                <template v-slot:append="{ item, open }" dense>
                                    <v-btn icon small @click="categoriaAtiva = item; categoriaAtiva.id_categoria_pai = item.id_categoria_pai || 0; dialogCategoria = true;">
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>
                                    <v-btn icon small @click="addCategoriaFilho(item)">
                                        <v-icon>mdi-plus</v-icon>
                                    </v-btn>
                                    <v-btn icon small @click="removeCategoria(item)">
                                        <v-icon>delete</v-icon>
                                    </v-btn>
                                </template>
                            </v-treeview>
                        </div>

                        <div v-else-if="tabAtual === 1">
                            <v-row>
                                <v-col cols="6">
                                    <h5>Materiais</h5>
                                    <v-data-table dense :footer-props="{ 'items-per-page-text':'Itens por página' }" :items="materiais" :headers="materiaisHeaders">
                                        <template v-slot:item.deletar="{ item }">
                                            <v-btn @click="removeTag(item)" color="error" icon>
                                                <v-icon>mdi-delete</v-icon>
                                            </v-btn>
                                        </template>
                                    </v-data-table>
                                </v-col>
                                <v-col cols="6">
                                    <h5>Cores</h5>
                                    <v-data-table dense :footer-props="{ 'items-per-page-text':'Itens por página' }" :items="cores" :headers="materiaisHeaders">
                                        <template v-slot:item.deletar="{ item }">
                                            <v-btn @click="removeTag(item)" color="error" icon>
                                                <v-icon>mdi-delete</v-icon>
                                            </v-btn>
                                        </template>
                                    </v-data-table>
                                </v-col>
                            </v-row>
                        </div>

                        <br><br><br><br><br><br><br><br><br><br><br><br>
                    </main>

                </v-card-text>
            </v-card>

            <footer>

                <v-dialog v-model="abrirModalTags" max-width="800px">
                    <v-card>
                        <v-toolbar color="primary" dark>
                            Novo

                            <v-spacer></v-spacer>

                            <v-btn icon>
                                <v-icon @click="abrirModalTags = false">
                                    mdi-close
                                </v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-form @submit.prevent="salvaTag">
                            <v-card-text>

                                <div class="w-100 text-center">
                                    <!-- <img :src="categoriaAtiva.icone" :alt="categoriaAtiva.nome" width="300" class="img-fluid"> -->
                                    <v-text-field :loading="loadingCategorias" v-model="tagAtiva.nome" label="Nome" filled></v-text-field>

                                    <v-select v-model="tagAtiva.tipo" item-text="name" item-value="id" label="Tipo tag" :items="[{id: 'CO', name: 'Cor'}, {id: 'MA', name: 'Material'}]"></v-select>
                                </div>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="error" :disabled="!dialogCategoria" type="button" text @click="abrirModalTags = false">Cancelar</v-btn>
                                <v-btn color="primary" :loading="loadingCategorias" type="submit">Salvar</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>

                <v-dialog v-model="dialogCategoria" max-width="800px">
                    <v-card>
                        <v-toolbar color="primary" dark>
                            Categoria - {{ categoriaAtiva.id }}

                            <v-spacer></v-spacer>

                            <v-btn icon @click="dialogCategoria = false">
                                <v-icon>
                                    mdi-close
                                </v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-form @submit.prevent="salvaCategoria(categoriaAtiva.id_categoria_pai)">
                            <v-card-text>

                                <div class="w-100 text-center">
                                    <!-- <img :src="categoriaAtiva.icone" :alt="categoriaAtiva.nome" width="300" class="img-fluid"> -->
                                    <v-text-field :loading="loadingCategorias" v-model="categoriaAtiva.nome" label="Nome" filled></v-text-field>
                                    <v-combobox :loading="loadingCategorias" :search-input.sync="textoTags" hide-selected item-text="nome" item-value="id" :items="listaTags" label="Tags" dense v-model="categoriaAtiva.tags" chips clearable multiple filled rounded :return-object="false">
                                        <template v-slot:selection="{ attrs, item, select, selected }">
                                            <v-chip small v-bind="attrs" close @click="select" @click:close="categoriaAtiva.tags = categoriaAtiva.tags.filter(tag => tag !== item)">
                                                {{ listaTags.find(el => el.id == item)?.nome || item  }}
                                            </v-chip>
                                        </template>
                                        <template v-slot:no-data>
                                            <v-list-item>
                                                <span class="subheading">Criar tag: </span>
                                                <v-chip label small>
                                                    {{ textoTags }}
                                                </v-chip>
                                            </v-list-item>
                                        </template>

                                        <template v-slot:item="{ item, on, attrs }">
                                            {{ item.nome }}
                                        </template>
                                    </v-combobox>

                                    <v-file-input v-model="categoriaAtiva.fotoUpload" label="Foto"></v-file-input>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div>
                                            <v-img width="100" height="100" :src="categoriaAtiva.fotoUpload !== null ? fotoPreview : 'images/img-placeholder.png'"></v-img>
                                        </div>
                                    </div>
                                </div>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="error" :disabled="!dialogCategoria" type="button" text @click="dialogCategoria = false">Cancelar</v-btn>
                                <v-btn color="primary" :loading="loadingCategorias" type="submit">Salvar</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-dialog>

                <v-snackbar v-model="snackbar.mostrar" :color="snackbar.cor">
                    <v-row class="m-0 align-items-center">
                        <h6 class="m-0">{{ snackbar.texto }}</h6>
                        <v-spacer></v-spacer>
                        <v-icon v-if="snackbar.cor === 'primary'">mdi-checkbox-marked-circle</v-icon>
                    </v-row>
                    <template v-slot:action="{ attrs }">
                        <v-btn icon v-bind="attrs" @click="snackbar.mostrar = false">
                            <v-icon>
                                mdi-close
                            </v-icon>
                        </v-btn>
                    </template>
                </v-snackbar>

                <input style="display: none;" id="imageInput" type="file" @change="previewImage" accept="image/*">
            </footer>


        </div>
    </v-app>
</div>


<script src="js/categorias.js<?= $versao ?>"></script>

<?php
require 'rodape.php';
?>