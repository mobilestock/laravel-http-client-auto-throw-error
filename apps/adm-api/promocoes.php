<?php

require 'vendor/autoload.php';
require 'cabecalho.php';

acessoUsuarioFornecedor();
?>
<div class="container-fluid" data-app>
    <div id="promocoesVue">
        <v-app>
            <header>
                <v-card elevation="1" outlined dark color="var(--cor-fundo-azul-claro)" class="mb-5">
                    <v-card-text>
                        <p class="display-4" style="color: white;">
                            <b>Promoções</b>
                        </p>
                        <v-row style="justify-content: space-between; align-items: baseline;" >
                            <v-col cols="12" sm="auto" >
                                <v-btn @click="goBack" color="var(--cor-fundo-botao-voltar)">
                                    Voltar
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </header>

            <main>
                <v-row >
                    <v-col col="12" style="display: inline-block;width: 100%;min-width: 100%;max-width: 100%;text-align-last: start;">
                        <v-card>
                            <v-card-text>
                                <p class="headline" style="font-size: 2rem;">
                                    <b>Pares disponíveis</b>
                                </p>
                            </v-card-text>

                            <v-data-table
                                :headers="cabecalhoProdutosDisponivel"
                                :items="produtos.disponiveis"
                                :items-per-page="5"
                                :search="buscaDisponiveis"
                                class="elevation-1"
                            >
                                <template v-slot:top>
                                    <v-text-field
                                    v-model="buscaDisponiveis"
                                    label="Filtro ( nome do produto )"
                                    class="mx-4"
                                    ></v-text-field>
                                </template>
                                <template v-slot:item="{ item }">
                                    <tr>
                                        <td>
                                            <v-btn
                                                x-small
                                                v-if="item.promocao === true"
                                                dark
                                                color="#4CAF50">
                                                Selecionado
                                            </v-btn>
                                            <v-btn
                                                small
                                                v-else
                                                dark
                                                color="#607D8B"
                                                @click=";conteudoModal = item; exibeModal = !exibeModal; montaConteudoModal()">
                                                Definir
                                            </v-btn>
                                        </td>

                                        <td class="text-start">{{ item.id }}</td>

                                        <td class="text-start">
                                            {{item.dataEntrada}}
                                        </td>

                                        <td>
                                            <br/>
                                            <table class="table text-start">
                                                <tbody>
                                                    <tr class="d-flex justify-content-start">
                                                        <div>
                                                            <td v-for="num in item.grade" :key="item.tamanho" class="d-flex flex-column table-bordered p-1 text-start">
                                                                <b>{{ num.nome_tamanho }}</b>
                                                                <span>{{num.estoque}}</span>
                                                            </td>
                                                        </div>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p><b>Total: {{item.gradetotal}}</b></p>
                                        </td>
                                        <td>
                                            <v-img
                                                class="my-2"
                                                max-height="90"
                                                max-width="90"
                                                :src="item.fotoUrl"
                                            ></v-img>
                                        </td>
                                    </tr>
                                </template>
                                <template v-slot:no-data>
                                    <div>
                                        Sem dados
                                    </div>
                                </template>
                            </v-data-table>
                            <v-row style=" display: flex; justify-content: flex-end;" class="mx-4 my-2">
                                <v-col v-if="produtosSelecionadosParaPromocao?.length > 0 && travaRemocaoDeValores == false" cols="12" sm="auto">
                                    <v-btn
                                        elevation="2"
                                        dark
                                        color="var(--cor-fundo-botao-voltar)"
                                        @click="removerSelecioados"
                                    >
                                        Remover selecionados
                                    </v-btn>
                                </v-col>
                                <v-col v-if="produtosSelecionadosParaPromocao?.length > 0 && travaRemocaoDeValores == false" cols="12" sm="auto">
                                    <v-btn
                                        elevation="2"
                                        dark
                                        color="var(--cor-fundo-azul-claro)"
                                        :loading="loadingSalvaPromocao"
                                        @click="validaPromocao"
                                    >
                                        Validar Promoções
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card>
                    </v-col>

                    <v-col col="12" style="display: inline-block;width: 100%;min-width: 100%;max-width: 100%;text-align-last: start;">
                        <v-card>
                            <v-card-text>
                                <p class="headline" style="font-size: 2rem;">
                                    <b>Promoções ativas</b>
                                </p>
                            </v-card-text>

                            <v-data-table
                                :headers="cabecalhoProdutosAtivos"
                                :items="produtos.ativos"
                                :items-per-page="5"
                                :search="buscaAtivos"
                                class="elevation-1"
                            >
                                <template v-slot:top>
                                    <v-text-field
                                    v-model="buscaAtivos"
                                    label="Filtro ( nome do produto )"
                                    class="mx-4"
                                    ></v-text-field>
                                </template>
                                <template v-slot:item="{ item }">
                                    <tr>
                                        <td>
                                            <v-btn

                                                small
                                                @click="conteudoModal = item; removePromocao(); item.promocao = false"
                                                :loading="!item.promocao"
                                                dark
                                                color="#C62828">
                                                Remover
                                            </v-btn>
                                            <v-btn
                                                small
                                                class="my-1"
                                                dark
                                                color="#607D8B"
                                                @click=";conteudoModal = item; exibeModal = !exibeModal; montaConteudoModal()">
                                                Definir
                                            </v-btn>
                                        </td>

                                        <td>{{item.id}}</td>

                                        <td class="text-start">
                                            {{item.dataEntrada}}
                                        </td>

                                        <td>
                                            <br/>
                                            <table class="table text-start">
                                                <tbody>
                                                    <tr class="d-flex justify-content-start">
                                                        <td v-for=" num in item.grade" :key="item.tamanho" class="d-flex flex-column table-bordered p-1">
                                                            <b>{{ num.nome_tamanho }}</b>
                                                            <span>{{num.estoque}}</span>
                                                        </td>
                                                        </tr>
                                                </tbody>
                                            </table>
                                            <p><b>Total: {{item.gradetotal}}</b></p>
                                        </td>
                                        <td>
                                            <v-img
                                                class="my-2"
                                                max-height="90"
                                                max-width="90"
                                                :src="item.fotoUrl"
                                            ></v-img>
                                        </td>
                                    </tr>
                                </template>
                                <template v-slot:no-data>
                                    <div>
                                        Sem dados
                                    </div>
                                </template>
                            </v-data-table>
                            <v-row v-if="produtos.ativos.length > 0" style=" display: flex; justify-content: flex-end;" class="mx-4 my-2">
                                <v-col cols="12" sm="auto">
                                    <v-btn
                                        elevation="2"
                                        dark
                                        color="var(--cor-fundo-botao-voltar)"
                                        @click="removeTodasAsPromocoes"
                                        :loading="loadingRemovePromocao"
                                    >
                                        Remover todos
                                    </v-btn>
                                </v-col>
                                <v-col v-if="produtosSelecionadosParaPromocao?.length > 0 && travaRemocaoDeValores == false" cols="12" sm="auto">
                                    <v-btn
                                        elevation="2"
                                        dark
                                        color="var(--cor-fundo-azul-claro)"
                                        :loading="loadingSalvaPromocao"
                                        @click="validaPromocao"
                                    >
                                        Validar Promoções
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-card>
                    </v-col>
                </v-row>
            </main>

            <footer>
                <v-dialog
                    style="z-index: 4000"
                    v-model="exibeModal"
                    fullscreen
                    hide-overlay
                    transition="dialog-bottom-transition"
                >
                    <v-card>
                        <v-toolbar
                            dark
                            color="var(--cor-fundo-botao-voltar)"
                        >
                            <v-btn
                                icon
                                dark
                                @click="exibeModal = false"
                            >
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                            <v-toolbar-title>Sair</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-toolbar-items>
                                <div style="display: flex; justify-content: center; align-items: center;">
                                    <v-btn
                                        dark
                                        color="var(--cor-fundo-amarelo)"
                                        @click="salvaConteudo"
                                        :disabled="botaoSalvarDesabilitado"
                                    >
                                    salvar
                                    </v-btn>
                                </div>
                            </v-toolbar-items>
                        </v-toolbar>

                        <div class="container-fluid mt-3">

                            <v-row>
                                <v-col cols="12" sm="3">
                                    <v-img
                                        max-height="500"
                                        max-width="500"
                                        :src="parametrosModal.fotoProduto"
                                    >
                                    <template v-slot:placeholder>
                                        <div style="background-color: gray; z-index: 100;"></div>
                                    </template>
                                    </v-img>
                                </v-col>
                                <v-col cols="12" sm="9">
                                    <v-card elevation="1" >
                                        <div v-if="podeAplicarPromocao">
                                            <v-subheader class="mb-4" >Porcentagem da promoção</v-subheader>
                                            <v-slider
                                                class="mx-3"
                                                v-model="slider"
                                                color="var(--cor-fundo-botao-voltar)"
                                                thumb-label="always"
                                            ></v-slider>

                                            <v-row class="m-1">
                                                <v-col cols="12" sm="3">
                                                    <v-subheader class="pl-0">Mobile Stock</v-subheader>
                                                    <v-text-field
                                                        v-model="formataValorVendaMS"
                                                        label="Valor de Venda"
                                                        solo
                                                        dense
                                                        readonly
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" sm="3">
                                                    <v-subheader class="pl-0">Meulook</v-subheader>
                                                    <v-text-field
                                                        v-model="formataValorVendaML"
                                                        label="Valor de Venda"
                                                        solo
                                                        dense
                                                        readonly
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" sm="3">
                                                    <v-subheader class="pl-0">Valor base</v-subheader>
                                                    <v-text-field
                                                        v-model="formataValorBase"
                                                        label="Valor base"
                                                        solo
                                                        dense
                                                        readonly
                                                    ></v-text-field>
                                                </v-col>
                                                <!-- <v-col v-if="parametrosModal.slider == 100" cols="12" sm="3">
                                                    <v-subheader class="pl-0">Pontuação minima</v-subheader>
                                                    <v-text-field
                                                        v-model="parametrosModal.pontuacao"
                                                        label="Pontuação minima"
                                                        :rules="[rules.valorMin(parametrosModal.pontuacao,100,'pontuação')]"
                                                        solo
                                                        dense
                                                    ></v-text-field>
                                                </v-col> -->

                                            </v-row>
                                        </div>
                                        <v-col v-else>
                                            <h2>Requisitos para ativar essa promoção:</h2>
                                            <ul class="px-3">
                                                <li v-if="this.parametrosModal.faltaUmaEntregaParaAtivarPromocao">
                                                    Falta <span class="text-bold text-danger">1</span> venda entregue
                                                </li>
                                                <li v-if="this.parametrosModal.tempoRestanteAtivarPromocao !== ''">
                                                    Aguarde <span class="text-bold text-danger">{{ parametrosModal.tempoRestanteAtivarPromocao }}</span>
                                                </li>
                                            </ul>
                                        </v-col>

                                        <v-col>
                                            <h2>Atenção</h2>
                                            <p>Fique atento às regras de promoção:</p>
                                            <ul class="px-3">
                                                <li>
                                                    <b>TODAS A PROMOÇÕES PODERÃO PASSAR POR ANÁLISE DE VERACIDADE.</b>
                                                </li>
                                                <li>O produto precisa ter no mínimo <b>UMA VENDA ENTREGUE</b> com o preço normal.</li>
                                                <li>Alterar o valor do produto enquanto a promoção está <b>ATIVA</b>, desativará a promoção.</li>
                                                <li>Uma promoção desativada <b>SÓ PODERÁ SER REATIVADA EM {{ informacoesAplicarPromocao.horasEsperaReativarPromocao }}h</b> e com <b>MAIS UMA VENDA ENTREGUE</b> no preço normal.</li>
                                            </ul>
                                            <h2>Promoção Relâmpago</h2>
                                            <p>A promoção relâmpago é um destaque adicional para o seu produto ter mais visibilidade.</p>
                                            <ul class="px-3">
                                                <li>
                                                    <b>AS REGRAS ACIMA TAMBÉM SE APLICAM NA PROMOÇÃO RELÂMPAGO</b>
                                                </li>
                                                <li>O produto aparecerá no topo das promoções por <b>{{ informacoesAplicarPromocao.horasDuracaoPromocaoTemporaria }}h</b>.</li>
                                                <li>É necessário um mínimo de <b>{{ informacoesAplicarPromocao.porcentagemMinimaDescontoPromocaoTemporaria }}%</b> de desconto na promoção.</li>
                                                <li>Vendedores com reputação <b>RUIM</b> não poderão participar.</li>
                                            </ul>
                                        </v-col>
                                    </v-card>
                                </v-col>
                            </v-row>
                        </div>


                    </v-card>
                </v-dialog>


                <v-dialog
                        v-model="modalDeAlerta"
                        max-width="290"
                    >
                    <v-card>
                        <v-card-title class="headline">
                            Erro
                        </v-card-title>

                        <v-card-text>
                            {{ mensagemDeAlerta}}
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>

                            <v-btn
                                color="green darken-1"
                                text
                                @click="modalDeAlerta = false"
                            >
                                Ok
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <v-overlay :value="overlay">
                    <v-progress-circular indeterminate size="64"></v-progress-circular>
                </v-overlay>

            </footer>
        </v-app>
    </div>
</div>

<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/promocoes.js"></script>

<?php require 'rodape.php';
?>
