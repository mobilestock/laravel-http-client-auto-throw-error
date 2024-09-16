<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioFinanceiro();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons. min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />

<div id="app">
    <v-app>
        <v-main class="pa-5">
            <h1 class="text-center">Painel Cadastro - {{ nome_colaborador }}</h1>
            <v-card class="py-10 mt-10" width="100%">
                <v-card-title>Informações do Usuário</v-card-title>
                <v-card-subtitle style="display: flex; align-items: center;">
                    <span>Informações Completas do Usuário</span>
                    <v-spacer></v-spacer>
                    <v-btn @click="buscaPermissoes()">
                        <span class="material-symbols-outlined">
                            sync_saved_locally
                        </span>
                        Gerenciar Permissões
                    </v-btn>
                </v-card-subtitle>
                <v-card-text>
                    <v-form @submit.prevent="alteraUsuario">
                        <v-container style="display: flex; justify-content: space-around;" :disabled="!editando">
                            <v-col cols="12" sm="6">
                                <v-text-field label="Colaborador" name="razao_social" v-model="informacoes_cadastro.razao_social" :rules="enderecoRegras.nome" :disabled="!editando"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field label="Senha" name="senha" :value="informacoes_cadastro.senha" :disabled="!editando" type="password"></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="4">
                                <v-select :items="regimes" name="regime" item-text="text" item-value="value" @change="defineRegime" return-object :label="informacoes_cadastro.regime == 2 ? 'Regime - Físico' : 'Regime - Jurídico'" :disabled="!editando">

                                </v-select>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field label="CNPJ" name="cnpj" type="text" :value="cnpjFormatado" :disabled="!editando" @input="formataCnpj" maxlength="18"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field label="CPF" name="cpf" :value="cpfFormatado" :disabled="!editando" @input="formataCpf" maxlength="14"></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="6">
                                <v-text-field label="Telefone" name="telefone" :value="telefoneFormatado" :disabled="!editando" @input="(valor) => {telefoneFormatado = formataTelefone(valor)}" maxlength="15"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field label="E-mail" name="email" type="email" v-model="informacoes_cadastro.email" :disabled="!editando"></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="4">
                                <v-text-field name="logradouro" label="Endereço" v-model="informacoes_cadastro.endereco" disabled></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field name="numero" label="Nº" v-model="informacoes_cadastro.numero" disabled></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field name="bairro" label="Bairro" v-model="informacoes_cadastro.bairro" disabled></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="4">
                                <v-text-field label="Cidade" name="cidade" v-model="informacoes_cadastro.cidade" disabled></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field name="estado" label="Estado" v-model="informacoes_cadastro.uf" disabled></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-text-field name="cep" label="Cep" v-model="informacoes_cadastro.cep" disabled></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="6">
                                <v-text-field name="complemento" label="Complemento" v-model="informacoes_cadastro.complemento" disabled></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field name="ponto_de_referencia" label="Ponto de Referência" v-model="informacoes_cadastro.ponto_de_referencia" disabled></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex; justify-content: space-around;">
                            <v-col cols="12" sm="6">
                                <v-text-field label="Usuário meu look" name="usuario_meulook" v-model="informacoes_cadastro.usuario_meulook" :rules="[(v) => !!v || 'O Usuário meu look é obrigatório']" :disabled="!editando"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field label="Usuário Mobile Stock" v-model="informacoes_cadastro.nome" :rules="[(v) => !!v || 'O Usuário Mobile Stock é obrigatório']" :disabled="!editando" maxlength="255" name="nome"></v-text-field>
                            </v-col>
                        </v-container>
                        <v-container style="display: flex;" class="text-center">
                            <v-col cols="12" sm="4">
                                <v-btn @click="editando = true" :disabled="editando">
                                    <span class="material-symbols-outlined">
                                        edit
                                    </span>
                                    Editar Usuário
                                </v-btn>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-btn @click="listarEnderecos" :disabled="loading || loading_buscando_enderecos" :loading="loading_buscando_enderecos">
                                    <v-icon>mdi-map-marker</v-icon>
                                    Listar Endereços
                                </v-btn>
                            </v-col>
                            <v-col cols="12" sm="4">
                                <v-btn color="indigo" type="submit" :disabled="!editando" :dark="editando">
                                    <v-icon dark>
                                        mdi-cloud-upload
                                    </v-icon>
                                    Salvar Usuário
                                </v-btn>
                            </v-col>
                        </v-container>
                    </v-form>
                </v-card-text>
            </v-card>
            <div v-if="informacoes_cadastro.eh_perfil_de_seller" class="mt-5">
                <h1 class="text-center">Este usuário também é seller</h1>
                <div class="mt-3" style="display: flex; justify-content: space-around;">
                    <v-btn color="success white--text" v-if="seller_bloqueado" @click="desbloqueiaSeller">
                        Permitir Reposição Externa
                    </v-btn>
                    <v-btn color="warning" v-else @click="bloqueiaSeller">
                        Proibir reposição externa
                    </v-btn>
                    <v-btn dark @click="dialog_confirmacaoZerarEstoque = true">Zerar Estoque Externo</v-btn>
                </div>
            </div>
        </v-main>
        <template>
            <div class="text-center">
                <v-dialog v-model="dialog_permissoes" width="750">
                    <v-card>
                        <v-card-title class="text-h5 grey lighten-2">
                            Gerenciar Permissões
                        </v-card-title>
                        <div v-for="(item, index) in permissoes_usuario">
                            <p style="display: flex; align-items: center; justify-content: center;">{{ item.text }} | {{ item.value }} &nbsp;
                                <v-btn @click="abreModalConfirmaçao(item)">
                                    <span style="color: red;" class="material-symbols-outlined">
                                        delete
                                    </span>
                                </v-btn>
                            </p>
                        </div>
                        <v-card-text>
                            <v-form @submit.prevent="gerenciaPermissoes">
                                <v-select :items="permissoes" item-text="text" item-value="value" label="Permissões Disponíveis" filled return-object class="mt-3">
                                </v-select>
                                <v-select :items="permissoes_usuario" item-text="text" item-value="value" label="Acesso Principal" filled return-object class="mt-3">
                                </v-select>
                                <v-select :items="tipo_acesso_principal" item-text="text" item-value="value" label="Tipo Acesso Principal" filled return-object class="mt-3">
                                </v-select>
                                <v-btn color="primary" type="submit">Salvar Alterações</v-btn>
                            </v-form>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" text @click="dialog_permissoes = false">
                                Fechar
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </div>
        </template>
        <template>
            <v-dialog v-model="dialog_confirmacao" width="35rem">
                <v-card>
                    <v-card-text class="text-center">
                        <p>Confirmar remoção de permissão do usuário?</p>
                        <p>Pode ser alterado novamente a qualquer momento</p>
                        <v-btn color="error" class="text-center" style="margin-top: 2.5rem;" text @click="deletaPermissao()">Confirmar</v-btn>
                    </v-card-text>
                </v-card>
            </v-dialog>
        </template>

        <!-- Dialog confirmação zerar estoque -->
        <v-dialog persistent width="35rem" v-model="dialog_confirmacaoZerarEstoque">
            <v-card>
                <v-card-title>
                    <p class="m-0 p-0 text-break text-center">
                        Tem certeza que deseja zerar o estoque externo do fornecedor
                        {{ informacoes_cadastro.razao_social?.trim() }}?
                    </p>
                </v-card-title>
                <v-card-text>
                    <p class="text-center text-danger">
                        Atenção: Essa ação <b>não afetará</b> a reputação do fornecedor.
                    </p>
                </v-card-text>
                <v-card-actions class="d-flex justify-content-around">
                    <v-btn color="var(--danger)" :dark="!carregandoZerandoEstoque" :disabled="carregandoZerandoEstoque" @click="dialog_confirmacaoZerarEstoque = false">Cancelar</v-btn>
                    <v-btn color="var(--green)" :dark="!carregandoZerandoEstoque" :disabled="carregandoZerandoEstoque" :loading="carregandoZerandoEstoque" @click="zerarEstoqueResponsavel">Confirmar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para procurar o endereço -->
        <v-dialog v-model="dialog_procurarEndereco" transition="dialog-bottom-transition" persistent width="100%" max-width="35rem">
            <v-card>
                <v-card-title class="text-h5 grey lighten-2 d-flex justify-content-between">
                    <span>Endereços do Cliente</span>
                    <v-btn small icon @click="limpar">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <div v-if="enderecoNovoEndereco">
                    <div class="align-center d-flex justify-center p-2">
                        <p class="m-0">
                            <b>Cidade do cliente:</b> <span>{{ cidade.nome }}</span>
                        </p>
                        <v-btn class="ml-3" x-small @click="alterarCidadeFn">
                            {{ alterarCidade ? 'Alterar Endereço' : 'Alterar Cidade'}}
                        </v-btn>
                    </div>

                    <div class="align-center d-flex justify-center p-2 flex-column" v-if="!alterarCidade">
                        <v-text-field label="Pesquise o endereço ou o CEP" v-model="buscarPorEndereco" class="w-100 pl-3 pr-3" :loading="loading_buscando_por_endereco"></v-text-field>
                        <div class="listaEnderecos">
                            <div v-for="item in enderecosEncontrados" v-cloak>
                                <div @click="selecionarEndereco(item)" class="alert alert-secondary enderecoItem">
                                    <span v-cloak>{{ item.endereco_formatado }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <v-autocomplete label="Busque pela cidade" :items="listaCidadesEncontradas" :search-input.sync="buscarPorCidade" :loading="loading_buscando_por_cidade" @keyup.lazy="buscarPorCidade" @change="selecionaCidade" class="w-100 pl-3 pr-3">
                        </v-autocomplete>
                    </div>
                </div>

                <div class="p-2">
                    <v-btn block dark @click="enderecoNovoEndereco = true" v-if="!enderecoNovoEndereco">NOVO ENDEREÇO</v-btn>
                </div>


                <div class="p-4" v-if="!enderecoNovoEndereco">
                    <div class="legendaIconesEndereco">
                        <div>
                            <div>
                                <v-icon color="green">mdi-map-marker-check</v-icon>
                            </div>
                            <div>
                                <span>Endereço padrão</span>
                            </div>
                        </div>
                        <div>
                            <div>
                                <v-icon color="green">mdi-map-marker-check-outline</v-icon>
                            </div>
                            <div>
                                <span>Endereço verificado</span>
                            </div>
                        </div>
                    </div>
                    <div id="listaEnderecos">
                        <div v-for="(endereco, index) in enderecosCliente" :key="index">
                            <v-card class="mb-3 dadosEndereco">
                                <v-row no-gutters>
                                    <v-col cols="1">
                                        <v-icon color="green" v-if="endereco.eh_endereco_padrao">mdi-map-marker-check</v-icon>
                                        <v-icon color="green" v-else-if="!endereco.eh_endereco_padrao"> mdi-map-marker-check-outline</v-icon>
                                    </v-col>
                                    <v-col cols="9">
                                        <div>
                                            <strong>{{ endereco.apelido }}</strong>
                                            <p>{{ endereco.logradouro }}, {{ endereco.numero }}</p>
                                            <p v-if="endereco.complemento && endereco.ponto_de_referencia">{{ endereco.complemento }} {{ endereco.ponto_de_referencia }}</p>
                                            <p>{{ endereco.bairro }} - {{ endereco.cidade }}, {{ endereco.uf }}</p>
                                        </div>
                                    </v-col>
                                    <v-col cols="2">
                                        <v-btn block color="green" v-if="!endereco.eh_endereco_padrao || !endereco.esta_verificado" :disabled="endereco.eh_endereco_padrao || !endereco.esta_verificado || loading_alterar_endereco" :loading="loading_alterando_eh_endereco_padrao === endereco.id" class="mb-2" @click="definirEnderecoPadrao(endereco)">
                                            <v-icon color="white">mdi-map-marker-check</v-icon>
                                        </v-btn>
                                        <v-btn block color="red" v-if="!endereco.eh_endereco_padrao || !endereco.esta_verificado" :disabled="endereco.eh_endereco_padrao || loading_alterar_endereco || loading_alterar_endereco" :loading="loading_apagando_endereco === endereco.id" @click="abrirModalApagarEndereco(endereco)">
                                            <v-icon color="white">mdi-delete</v-icon>
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </v-card>
                        </div>
                    </div>
                </div>


            </v-card>
        </v-dialog>

        <!-- Dialog para alterar o endereço -->
        <v-dialog v-model="dialog_alterarEndereco" transition="dialog-bottom-transition" persistent width="100%" max-width="45rem">
            <v-card :loading="loading_alterar_endereco">
                <v-card-title class="text-h5 grey lighten-2 d-flex justify-content-between">
                    <span>Editar endereço do cliente</span>
                    <v-btn small icon @click="limpar" :disabled="loading_alterar_endereco">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-form @submit.prevent="alterarEndereco">

                    <v-container style="display: flex; justify-items: space-between;">
                        <v-col cols="12" sm="6">
                            <v-text-field
                                name="editar_nome_destinatario"
                                label="Nome do destinatario"
                                v-model="nomeDestinatario"
                                :disabled="loading_alterar_endereco"
                                :rules="enderecoRegras.nome"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                name="editar_telefone"
                                label="Telefone do destinatario"
                                v-model="telefoneDestinatario"
                                :disabled="loading_alterar_endereco"
                                @input="(valor) => {telefoneDestinatario = formataTelefone(valor)}"
                                maxlength="15"
                                :rules="enderecoRegras.telefone"
                            ></v-text-field>
                        </v-col>
                    </v-container>
                    <v-container style="display: flex; justify-content: space-around;">
                        <v-col cols="12" sm="4">
                            <v-text-field label="Endereço" v-model="enderecoSelecionado.logradouro" name="editar_endereco" :rules="enderecoRegras.endereco" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-text-field label="Nº" maxlength="20" v-model="enderecoSelecionado.numero" name="editar_numero" :rules="enderecoRegras.numero" :disabled="loading_alterar_endereco">
                            </v-text-field>
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-text-field label="Bairro" v-model="enderecoSelecionado.bairro" name="editar_bairro" :rules="enderecoRegras.bairro" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                    </v-container>
                    <v-container style="display: flex; justify-content: space-around;">
                        <v-col cols="12" sm="4">
                            <v-text-field label="Cidade" v-model="enderecoSelecionado.cidade" name="editar_cidade" :rules="enderecoRegras.cidade" :disabled="loading_alterar_endereco || enderecoSelecionado.cidade?.length > 0"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-text-field label="Estado" v-model="enderecoSelecionado.uf" name="editar_estado" :rules="enderecoRegras.estado" :disabled="loading_alterar_endereco || enderecoSelecionado.uf?.length > 0"></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-text-field label="Cep" v-model="enderecoSelecionado.cep" name="editar_cep" :rules="enderecoRegras.cep" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                    </v-container>
                    <v-container style="display: flex; justify-content: space-around;">
                        <v-col col-4>
                            <v-text-field name="editar_complemento" label="Complemento" v-model="enderecoSelecionado.complemento" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                        <v-col col-4>
                            <v-text-field name="editar_ponto_de_referencia" label="Ponto de Referência" v-model="enderecoSelecionado.ponto_de_referencia" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                        <v-col col-4>
                            <v-text-field name="editar_apelido" label="Apelido" v-model="enderecoSelecionado.apelido" :disabled="loading_alterar_endereco"></v-text-field>
                        </v-col>
                    </v-container>

                    <v-divider></v-divider>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="primary" text type="submit" :disabled="loading_alterar_endereco" :loading="loading_alterar_endereco">
                            Salvar
                        </v-btn>
                    </v-card-actions>

                </v-form>

            </v-card>
        </v-dialog>

        <!-- Dialog para confirmar se deseja apagar endereço -->
        <v-dialog v-model="dialog_apagarEndereco.exibir" transition="dialog-bottom-transition" max-width="30rem" persistent>
            <v-card>
                <v-toolbar dark color="error">
                    <v-toolbar-title>
                        <v-icon>mdi-alert</v-icon>
                        <span>ATENÇÃO</span>
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>

                <div class="alert alert-danger m-3 mt-6 text-center">
                    <strong>Você tem certeza que deseja apagar este endereço?
                        <br />Esta ação não poderá ser desfeita.</strong>
                </div>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text color="error" tabindex="-1" @click="apagarEndereco(dialog_apagarEndereco.id_endereco)">
                        Apagar
                    </v-btn>
                    <v-btn text color="primary" @click="fecharModalApagarEndereco">
                        Cancelar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>


        <v-snackbar v-model="snackbar.open" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
        <v-overlay absolute :value="loading">
            <v-progress-circular indeterminate size="64" />
        </v-overlay>
        <input type="hidden" id="id_colaborador" value="<?= $_GET['id_colaborador'] ?>">
    </v-app>
</div>

<style>
    div.enderecoItem {
        cursor: pointer;
    }

    div.enderecoItem:hover {
        opacity: 0.8;
    }

    div.listaEnderecos {
        overflow-y: auto;
        max-height: 13em;
        width: 100%;
    }

    div.legendaIconesEndereco {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;

        div {
            display: flex;
            align-items: center;

            span {
                font-size: 0.9rem;
            }
        }
    }

    div.dadosEndereco {
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        padding: 0.5rem;

        p {
            margin: 0;
        }
    }

    div#listaEnderecos {
        overflow-x: auto;
        max-height: 25rem;
        padding: 0.3rem;
    }
</style>

<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/tools/formataCpfECnpj.js"></script>
<script type="module" src="js/cadastro.js" <?= $versao ?>></script>
