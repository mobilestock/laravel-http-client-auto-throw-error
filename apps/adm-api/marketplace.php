<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioVendedor();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<style>
    .v-application p {
        margin-bottom: 0.1rem !important;
    }

    .row-alerta {
        background-color: var(--cor-alerta-entrega);
    }

    .row-pendencias {
        background-color: var(--cor-alerta-pendencias);
    }

    .row-liberado {
        background-color: var(--cor-alerta-liberado);
    }

    .row-fraude {
        background-color: var(--cor-alerta-fraude);
    }

    .alerta-devolucao {
        color: black;
        text-transform: uppercase;
        font-weight: bold;
        font-size: 0.8rem;
    }

    #whatsapp:hover {
        cursor: pointer;
    }

    div.listaPontos {
        max-height: 20rem;
        overflow-y: auto;
    }
</style>

<div class="container-fluid" id="app">
    <v-app>
        <v-card>
            <br />
            <div class="ml-4" style="display: flex; justify-content: space-between;">
                <p style="font-size: 1.5rem;"><b>Entregas e Transações</b></p>
                <div style="margin-right: 2rem;">
                    <v-btn :color="cor_botao_menu('ENTREGAS')" @click="alteraTelaSelecionada('ENTREGAS')">
                        <v-icon>mdi-truck</v-icon>
                        &ensp;Entregas
                    </v-btn>
                    <v-btn :color="cor_botao_menu('TRANSACOES')" @click="alteraTelaSelecionada('TRANSACOES')">
                        <v-icon>mdi-currency-usd</v-icon>
                        &ensp;Transações
                    </v-btn>
                    <v-btn :color="cor_botao_menu('PENDENTES')" @click="alteraTelaSelecionada('PENDENTES')">
                        <v-icon>mdi-alarm</v-icon>
                        &ensp;Pendentes
                    </v-btn>
                </div>
            </div>
            <br />

            <div v-if="tela_atual == 'ENTREGAS'">
                <v-row class="mx-4 mt-4 d-flex justify-content-between align-center">
                    <div style="width: 20rem;">
                        <v-text-field hint="Pesquise por qualquer informação presente na tabela" persistent-hint solo label="Filtrar na tabela" append-icon="mdi-magnify-scan" v-model="ENTREGAS_filtro"></v-text-field>
                    </div>
                    <div style="width: 20rem;">
                        <v-text-field hint="Pesquise por ID da entrega ou destinatário" persistent-hint solo label="Pesquisar no sistema" append-icon="mdi-magnify" :disabled="loading" :loading="loading" v-model="ENTREGAS_pesquisa"></v-text-field>
                    </div>
                    <div style="width: 20rem;">
                        <v-select hint="Escolha a situação do destino ou entrega" persistent-hint solo label="Situação da entrega" :disabled="loading" :items="ENTREGAS_situacoes" :loading="loading" v-model="ENTREGAS_situacao"></v-select>
                    </div>
                </v-row>
                <v-row class="align-center d-flex justify-content-around">
                    <v-switch v-model="ENTREGAS_lista_entregas_filtrar_entregador" label="Entregadores" @click="ENTREGAS_filtrarPorEntregador(ENTREGAS_lista_entregas_filtrar_entregador)"></v-switch>
                    <v-btn @click="ENTREGAS_lista_relatorios = []">
                        Limpar Seleção
                    </v-btn>
                    <v-btn dark color="warning" :loading="loading" @click="buscaInfosRelatorio(true)">
                        Imprimir relatório completo ({{ ENTREGAS_totalComEntregas }})
                    </v-btn>
                    <v-btn color="grey darken-4" :dark="ENTREGAS_lista_relatorios?.length > 0" :disabled="ENTREGAS_lista_relatorios?.length < 1" @click="buscaInfosRelatorio(false)">
                        Imprimir relatórios ({{ ENTREGAS_lista_relatorios?.length }})
                    </v-btn>
                    <v-btn :disabled="loading" @click="buscarEntregas">
                        <v-icon>mdi-refresh</v-icon>
                    </v-btn>
                </v-row>
                <br />
                <v-data-table :loading="loading" :headers="ENTREGAS_lista_entregas_headers" :items="ENTREGAS_lista_entregas" :footer-props="{'items-per-page-options': [50, 100, 200, -1]}" :item-class="entrega_check" item-key="identificador" :search="ENTREGAS_filtro" :custom-filter="ENTREGAS_pesquisaCustomizada">
                    <template v-slot:item.id_entrega="{ item }">
                        <a target="_blank" :class="cor_ponto_entrega(item.categoria_cor)" :href="'detalhes-entrega.php?id=' + item.id_entrega" v-if="item.id_entrega">
                            {{ item.id_entrega }}
                        </a>
                        <v-tooltip top v-else>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn icon v-bind="attrs" v-on="on" @click="gerirModalTransacoes(item.transacoes)">
                                    <v-icon :color="cor_ponto_entrega(item.categoria_cor)">mdi-dropbox</v-icon>
                                </v-btn>
                            </template>
                            <span>Esse pedido ainda não possui entrega</span>
                        </v-tooltip>
                    </template>

                    <template v-slot:item.relatorio="{ item }">
                        <v-checkbox :disabled="!item.identificador" color="grey darken-4" :value="item.identificador" v-model="ENTREGAS_lista_relatorios" />
                    </template>

                    <template v-slot:item.tipo_entrega="{ item }">
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-icon :color="cor_ponto_entrega(item.categoria_cor)" v-bind="attrs" v-on="on">
                                    {{ informacoesTipoEntrega(item.tipo_entrega).icone }}</v-icon>
                            </template>
                            <span>{{ informacoesTipoEntrega(item.tipo_entrega).explicacao }}</span>
                        </v-tooltip>
                    </template>

                    <template v-slot:item.destino="{ item }">
                        <p>{{ item.destino }}
                            <small v-if="!item.eh_retirada_cliente && item.ponto_coleta !== item.destino">
                                <br />{{ item.ponto_coleta }}</small>
                        </p>
                        <span class="badge badge-dark" v-if="item.devolucoes_pendentes >= 20">({{ item.devolucoes_pendentes }} Devoluções)</span>
                    </template>

                    <template v-slot:item.data_criacao="{ item }">
                        <p v-if="item.data_criacao">{{ item.data_criacao }}</p>
                        <p v-else>-</p>
                    </template>

                    <template v-slot:item.transportador="{ item }">
                        <p>{{ item.transportador }}</p>
                        <small>{{ item.tipo_ponto }}</small>
                    </template>

                    <template v-slot:item.tem_mais_produtos="{ item }">
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn rounded small :color="cor_ponto_entrega(item.categoria_cor)" :dark="!ENTREGAS_maisProdutos(item).desativado" :disabled="ENTREGAS_maisProdutos(item).desativado" :loading="ENTREGAS_loading" v-bind="attrs" v-on="on" @click="buscaProdutosSemEntrega(item)">
                                    <v-icon>mdi-exclamation</v-icon>
                                </v-btn>
                            </template>
                            <span>{{ mensagem_mais_produtos_entrega(item) }}</span>
                        </v-tooltip>
                    </template>

                    <template v-slot:item.acompanhar="{ item }">
                        <v-btn block small :color="!item.acompanhamento?.id ? 'primary' : 'error'" :disabled="ENTREGAS_bloqueiaBotaoAcompanhar(item) || ENTREGAS_disabled_botao_acompanhar || item.acompanhamento?.situacao === 'PAUSADO'" @click="ENTREGAS_direcionaBotaoAcompanhamento(item)">
                            <span v-if="item.acompanhamento?.situacao === 'PAUSADO'">EM PAUSA</span>
                            <span v-else-if="item.acompanhamento">Desacompanhar</span>
                            <span v-else>Acompanhar</span>
                        </v-btn>
                        <small v-if="item.acompanhamento?.id">Acompanhamento: {{ item.acompanhamento?.id }}</small>
                    </template>

                    <template v-slot:item.situacao="{ item }">
                        <p v-if="item.situacao">{{ item.situacao }}</p>
                        <p v-else>-</p>
                    </template>

                    <template v-slot:item.mais_detalhes="{ item }">
                        <v-btn small :color="cor_ponto_entrega(item.categoria)" :dark="!ENTREGAS_bloqueiaBotaoAcompanhar(item)" :loading="ENTREGAS_loadingMaisDetalhes" :disabled="loading" @click="ENTREGAS_buscarMaisDetalhes(item)">
                            <v-icon>mdi-account-details</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </div>


            <div v-if="tela_atual == 'PENDENTES'">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h6 class="ml-4">Transações Pendentes</h6>
                    <div style="width: 20%; margin-top: 1.4rem; margin-right: 2rem;">
                        <v-text-field solo label="Pesquisar ID" prepend-icon="mdi-magnify" v-model="PENDENTES_pesquisa" type="number"></v-text-field>
                    </div>
                </div>
                <v-data-table :loading="loading" :headers="PENDENTES_lista_transacoes_headers" :items="PENDENTES_lista_transacoes" :footer-props="{'items-per-page-options': [50, 100, 200, -1]}" :items-per-page="50">
                    <template v-slot:item.id="{ item }">
                        <a target="_blank" :href="'transacao-detalhe.php?id=' + item.id" style="display: flex;">
                            {{ item.id }}&ensp;<v-icon style="font-size: 1rem; color: gray;">mdi-link-variant</v-icon>
                        </a>
                    </template>
                    <template v-slot:item.url_boleto="{ item }">
                        <a v-if="item.url_boleto" target="_blank" :href="item.url_boleto" style="display: flex;">
                            URL&ensp;<v-icon style="font-size: 1rem; color: gray;">mdi-link-variant</v-icon>
                        </a>
                        <p v-else>-</p>
                    </template>
                    <template v-slot:item.valor_total="{ item }">
                        R$ {{ formatarNumero(item.valor_total) }}
                    </template>
                    <template v-slot:item.valor_credito="{ item }">
                        R$ {{ formatarNumero(item.valor_credito) }}
                    </template>
                    <template v-slot:item.valor_acrescimo="{ item }">
                        R$ {{ formatarNumero(item.valor_acrescimo) }}
                    </template>
                    <template v-slot:item.valor_itens="{ item }">
                        R$ {{ formatarNumero(item.valor_itens) }}
                    </template>
                    <template v-slot:item.valor_taxas="{ item }">
                        R$ {{ formatarNumero(item.valor_taxas) }}
                    </template>
                    <template v-slot:item.valor_comissao_fornecedor="{ item }">
                        R$ {{ formatarNumero(item.valor_comissao_fornecedor) }}
                    </template>
                    <template v-slot:item.valor_liquido="{ item }">
                        R$ {{ formatarNumero(item.valor_liquido) }}
                    </template>
                </v-data-table>
            </div>


            <div v-if="tela_atual == 'TRANSACOES'">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h6 class="ml-4">Lista de Transações</h6>
                    <div style="width: 20%; margin-top: 1.4rem; margin-right: 2rem;">
                        <v-text-field solo label="Pesquisar ID ou Colaborador" prepend-icon="mdi-magnify" v-model="TRANSACOES_pesquisa"></v-text-field>
                    </div>
                </div>
                <v-data-table :loading="loading" :headers="TRANSACOES_lista_headers" :items="TRANSACOES_lista" :footer-props="{'items-per-page-options': [50, 100, 200, -1]}" :items-per-page="50">
                    <template v-slot:item.id_transacao="{ item }">
                        <a target="_blank" :href="'transacao-detalhe.php?id=' + item.id_transacao">
                            {{ item.id_transacao }} <v-icon style="font-size: 1rem; color: gray;">mdi-link-variant
                            </v-icon>
                        </a>
                    </template>
                    <template v-slot:item.metodo_pagamento="{ item }">
                        <div style="display: flex;">
                            {{ item.metodo_pagamento }}
                            <a v-if="item.url_boleto" target="_blank" :href="item.url_boleto" style="display: flex;">
                                &ensp;(URL&ensp;<v-icon style="font-size: 1rem; color: gray;">mdi-link-variant</v-icon>)
                            </a>
                        </div>
                    </template>
                    <template v-slot:item.razao_social="{ item }">
                        {{ item.razao_social }} <i>({{ item.id_cliente }})</i>
                    </template>
                    <template v-slot:item.valor_credito="{ item }">
                        R$ {{ formatarNumero(item.valor_credito) }}
                    </template>
                    <template v-slot:item.valor_acrescimo="{ item }">
                        R$ {{ formatarNumero(item.valor_acrescimo) }}
                    </template>
                    <template v-slot:item.valor_liquido="{ item }">
                        R$ {{ formatarNumero(item.valor_liquido) }}
                    </template>
                    <template v-slot:item.valor_itens="{ item }">
                        R$ {{ formatarNumero(item.valor_itens) }}
                    </template>
                    <template v-slot:item.valor_comissao_fornecedor="{ item }">
                        R$ {{ formatarNumero(item.valor_comissao_fornecedor) }}
                    </template>
                    <template v-slot:item.juros_pago_split="{ item }">
                        R$ {{ formatarNumero(item.juros_pago_split) }}
                    </template>
                </v-data-table>
            </div>


        </v-card>

        <!-- Modal produtos que podem ser inseridos na entrega -->
        <v-dialog transition="dialog-bottom-transition" v-if="ENTREGAS_modal_produtos_pendentes" v-model="ENTREGAS_modal_produtos_pendentes">
            <v-card>
                <v-toolbar dark color="var(--cor-fundo-vermelho)" class="mb-2">
                    <v-toolbar-title>{{ ENTREGAS_modal_titulo }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn dark icon @click="fechaModalProdutosPendentes">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-card-text>
                    <v-data-table :headers="ENTREGAS_lista_produtos_pendentes_header" :items="ENTREGAS_lista_produtos_pendentes">
                        <template v-slot:item.id_produto="{ item }">
                            <a :href="`fornecedores-produtos.php?id=${item.id_produto}`">
                                {{ item.id_produto }}
                            </a>
                        </template>

                        <template v-slot:item.produto_foto="{ item }">
                            <v-img class="m-auto" height="5rem" width="5rem" :aspect-ratio="16/9" :src="item.produto_foto ?? 'images/img-placeholder.png'" />
                        </template>

                        <template v-slot:item.id_transacao="{ item }">
                            <a :href="`transacao-detalhe.php?id=${item.id_transacao}`">
                                {{ item.id_transacao }}
                            </a>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Modal relatório -->
        <v-dialog persistent transition="dialog-bottom-transition" width="auto" v-model="ENTREGAS_modal_relatorio">
            <v-card width="auto">
                <div id="relatorio-geral-imprimivel">
                    <v-toolbar flat class="mb-2">
                        <v-toolbar-title>
                            <h5 class="font-weight-bold">Relatório de entregas</h5>
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon @click="fechaModalRelatorioEntregas">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-toolbar>
                    <v-card-text>
                        <v-row class="justify-content-around">
                            <p>
                                <b>Data: </b> {{ ENTREGAS_relatorio_data }}
                            </p>
                            <p>
                                <b>Quantidade de entregas: </b> {{ ENTREGAS_lista_relatorios?.length || ENTREGAS_totalComEntregas }}
                            </p>
                        </v-row>
                        <br />
                        <div
                            class="mb-10"
                            style='page-break-after: always'
                            v-if="ENTREGAS_entregas_frete_custeado.length > 0"
                        >
                            <h4 class="text-center">Entregas com frete custeado</h4>
                            <hr size="100%">
                            <v-data-table disable-filtering disable-pagination disable-sort hide-default-footer item-key="identificador" :headers="ENTREGAS_lista_entregas_frete_custeado" :items="ENTREGAS_entregas_frete_custeado">
                                <template v-slot:item.id="{ item }">
                                    <b>{{ item.id_entrega }}</b>
                                </template>
                                <template v-slot:item.volumes="{ item }">
                                    <b>{{ item.volumes}}</b>
                                </template>
                                <template v-slot:item.tipo_entrega="{ item }">
                                    {{ informacoesTipoEntrega(item.tipo_entrega).texto }}
                                </template>
                                <template v-slot:item.valor_custo_frete="{ item }">
                                    R$ {{ item.valor_custo_frete }}
                                </template>
                                <template v-slot:body.append>
                                    <tr>
                                        <td class="text-start">
                                            <b>Total<b>
                                        </td>
                                        <td class="text-start text-bold">Destinos:
                                            {{ENTREGAS_entregas_frete_custeado.length}}
                                        </td>
                                        <td class="text-end">
                                            <b>Volumes: </b>
                                        </td>
                                        <td class="text-start">
                                            <b>{{ ENTREGAS_total_volumes_custeados }}</b>
                                        </td>
                                        <td colspan="3"></td>
                                        <td>
                                            <b>{{ ENTREGAS_valor_total_frete }}</b>
                                        </td>
                                    </tr>
                                </template>
                            </v-data-table>
                        </div>
                        <div
                            style='page-break-after: always'
                            v-if="ENTREGAS_entregas_frete_nao_custeado.length > 0"
                        >
                            <h4 class="text-center">Entregas com frete não custeado</h4>
                            <hr size="100%">
                            <v-data-table
                                disable-filtering
                                disable-pagination
                                disable-sort
                                hide-default-footer
                                item-key="identificador"
                                :headers="ENTREGAS_relatorio_headers"
                                :items="ENTREGAS_entregas_frete_nao_custeado"
                                :items-per-page="-1"
                            >
                                <template v-slot:item.id="{ item }">
                                    <b>{{ item.id_entrega }}</b>
                                </template>
                                <template v-slot:item.volumes="{ item }">
                                    <b>{{ item.volumes }}</b>
                                </template>
                                <template v-slot:item.tipo_entrega="{ item }">
                                    {{ informacoesTipoEntrega(item.tipo_entrega).texto }}
                                </template>
                                <template v-slot:body.append>
                                    <tr>
                                        <td class="text-start">
                                            <b>Total<b>
                                        </td>
                                        <td class="text-start text-bold">Destinos:
                                            {{ ENTREGAS_entregas_frete_nao_custeado.length }}
                                        </td>
                                        <td class="text-end">
                                            <b>Volumes: </b>
                                        </td>
                                        <td class="text-start">
                                            <b>{{ ENTREGAS_total_volumes_nao_custeados }}</b>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </template>
                            </v-data-table>
                        </div>
                    </v-card-text>
                </div>
                <v-card-actions class="justify-content-between">
                    <v-btn
                        :disabled="desabilitarBotaoRelatorioEntregadores"
                        :loading="ENTREGAS_carregando_relatorio_entregadores"
                        @click="buscaRelatorioEntregadores"
                    >
                        Relatório Entregadores
                        &ensp;
                        <v-icon>{{ informacoesTipoEntrega('PM').icone }}</v-icon>
                    </v-btn>
                    <v-btn
                        :loading="COLETA_carregando_relatorio"
                        @click="buscaRelatorioColeta"
                    >
                        Relatório Coletas
                        &ensp;
                        <v-icon>{{ informacoesTipoEntrega('COLETA').icone }}</v-icon>
                    </v-btn>
                    <div>
                        <v-btn
                            color="error"
                            :disabled="ENTREGAS_carregando_relatorio_entregadores"
                            @click="fechaModalRelatorioEntregas"
                        >
                            Fechar
                        </v-btn>
                        <v-btn
                            :dark="!ENTREGAS_carregando_relatorio_entregadores"
                            :disabled="ENTREGAS_carregando_relatorio_entregadores"
                            @click="() => imprimirRelatorio('geral')"
                        >
                            Imprimir <v-icon>mdi-printer</v-icon>
                        </v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal relatório detalhado de entregas -->
        <v-dialog
            persistent
            min-width="60rem"
            transition="dialog-bottom-transition"
            width="auto"
            v-model="ENTREGAS_dialog_relatorio_entregadores"
        >
            <v-card min-width="60rem" width="auto">
                <div id="relatorio-detalhado-imprimivel">
                    <div class="p-4" v-for="relatorioEntregador in ENTREGAS_relatorio_entregadores">
                        <div class="bg-dark d-flex justify-content-around p-2">
                            <h5 class="m-0" >Entregador: {{ relatorioEntregador.entregador }}</h5>
                            <h5 class="m-0" >ID entrega: {{ relatorioEntregador.id_entrega }}</h5>
                            <h5 class="m-0" >Raio: {{ relatorioEntregador.apelido_raio }}</h5>
                        </div>
                        <v-data-table
                            disable-diltering
                            disable-pagination
                            disable-sort
                            hide-default-footer
                            :headers="ENTREGAS_relatorio_entregadores_headers"
                            :items="relatorioEntregador.detalhes_entrega"
                        >
                            <template v-slot:item.tem_troca="{ item }">
                                <div class="d-flex justify-content-center">
                                    <v-checkbox
                                        disabled
                                        v-model="item.tem_troca"
                                    ></v-checkbox>
                                </div>
                            </template>
                        </v-data-table>
                        <br />
                        <hr />
                    </div>
                </div>
                <v-card-actions class="justify-content-end">
                    <v-btn color="error" @click="ENTREGAS_dialog_relatorio_entregadores = false">Voltar</v-btn>
                    <v-btn dark @click="() => imprimirRelatorio('detalhado')">
                        Imprimir <v-icon>mdi-printer</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal para exibir destinos agrupados -->
        <v-dialog v-model="ENTREGAS_dialog_destinos_agrupados" transition="dialog-bottom-transition" persistent max-width="35rem">
            <v-card :loading="ENTREGAS_loading_acompanhar_grupo" :disabled="ENTREGAS_disabled_acompanhar_grupo">
                <v-card-title class="text-h5 grey lighten-2 d-flex justify-content-between">
                    <span>Acompanhamento em grupo</span>
                    <v-btn small icon @click="ENTREGAS_limparAcompanharDestinos">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-card-text class="mt-5">
                    Esse destino pertence à um grupo e possui outros destinos que podem ser acompanhados juntos.
                </v-card-text>

                <div class="listaPontos">
                    <v-list-item v-for="(item, index) in ENTREGAS_grupos_destinos" :key="index">
                        <v-list-item-content>
                            <v-list-item-title class="title">
                                ({{ item.id_colaborador_tipo_frete }}) {{ item.nome }}
                            </v-list-item-title>
                            <v-list>
                                <v-list-item v-for="(destino, destinoIndex) in item.destinos" :key="destinoIndex">
                                    <v-list-item-action>
                                        <v-checkbox
                                            :input-value="!!ENTREGAS_grupos_destinos_acompanhar.find(dest => dest.identificador === destino.identificador)"
                                            @change="ENTREGAS_adicionarDestinoParaAcompanhar(destino)"
                                        ></v-checkbox>
                                    </v-list-item-action>
                                    <v-list-item-content>
                                        <v-list-item-title>
                                            {{ destino.apelido }} <span v-if="destino.apelido">|</span> {{ destino.cidade }}
                                        </v-list-item-title>
                                    </v-list-item-content>
                                </v-list-item>
                            </v-list>
                        </v-list-item-content>
                    </v-list-item>
                </div>

                <v-divider></v-divider>

                <v-card-actions class="d-flex flex-column">
                    <v-btn color="primary" text @click="ENTREGAS_acompanharDestinoEmGrupo(ENTREGAS_grupos_destinos_acompanhar)" :disabled="ENTREGAS_grupos_destinos_acompanhar.length === 0 || ENTREGAS_loading_acompanhar_grupo">
                        Acompanhar Destinos
                    </v-btn>
                    <v-btn
                        text
                        :disabled="ENTREGAS_loading_acompanhar_grupo"
                        color="primary"
                        @click="ENTREGAS_acompanharDestino(ENTREGAS_destino_origem)"
                    >
                        Acompanhar somente {{ ENTREGAS_destino_origem?.nome}} ({{ ENTREGAS_destino_origem?.cidade }})
                    </v-btn>
                </v-card-actions>

            </v-card>
        </v-dialog>


        <!-- Modal para exibir os grupos do destino -->
        <v-dialog v-model="ENTREGAS_dialog_destinos_grupos" transition="dialog-bottom-transition" persistent max-width="35rem">
            <v-card :loading="ENTREGAS_loading_acompanhar_grupo" :disabled="ENTREGAS_disabled_acompanhar_grupo">
                <v-card-title class="text-h5 grey lighten-2 d-flex justify-content-between">
                    <span>O destino pertence à um grupo</span>
                    <v-btn small icon @click="ENTREGAS_dialog_destinos_grupos = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-card-text class="mt-5">
                    Esse destino pertence aos seguintes grupos. Escolha um grupo para exibir os destinos que podem ser acompanhados.
                </v-card-text>

                <div class="listaPontos">
                    <v-list-item
                        v-for="item in ENTREGAS_grupos"
                        v-bind:key="item.id_tipo_frete_grupos"
                        class="justify-center"
                        :disabled="ENTREGAS_loading_acompanhar_grupo"
                    >
                        <v-btn @click="ENTREGAS_listarDestinosDoGrupo(item)">{{ item.nome_grupo }}</v-btn>
                    </v-list-item>
                </div>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-btn
                        text
                        :disabled="ENTREGAS_loading_acompanhar_grupo"
                        color="primary"
                        @click="ENTREGAS_acompanharDestino(ENTREGAS_destino_origem)"
                    >
                        Acompanhar somente {{ ENTREGAS_destino_origem?.nome}} ({{ ENTREGAS_destino_origem?.cidade }})
                    </v-btn>
                </v-card-actions>

            </v-card>
        </v-dialog>

        <!-- Modal exibir lista de transações -->
        <v-dialog v-model="ENTREGAS_modal_transacoes.aberto" transition="dialog-bottom-transition" max-width="35rem">
            <v-card>
                <v-toolbar flat class="mb-2">
                    <v-toolbar-title>
                        <h5 class="font-weight-bold">Transações</h5>
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon @click="gerirModalTransacoes([])">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-card-text>
                    <div class="align-items-center d-flex flex-column">
                        <a target="_blank" :href="`transacao-detalhe.php?id=${transacao}`" :key="transacao" v-for="transacao in ENTREGAS_modal_transacoes.transacoes">{{ transacao }}</a>
                    </div>
                </v-card-text>
                <v-card-actions class="d-flex justify-content-center">
                    <v-btn color="error" text @click="gerirModalTransacoes([])">Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal para exibir mais detalhes do destino/entrega -->
        <v-dialog
            transition="dialog-bottom-transition"
            width="45rem"
            v-model="modal_detalhes_destino_entrega"
        >


            <v-card>
                <div class="d-flex flex-column">
                    <v-row class="m-0 justify-content-around">
                        <div>
                            <v-card-title>
                                Endereço
                            </v-card-title>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.razao_social)">mdi-content-copy
                                </v-icon>
                                <b>Nome:</b> {{ ENTREGAS_modalDetalhes.razao_social }}
                            </p>
                            <p v-if="ENTREGAS_modalDetalhes.cpf">
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.cpf)">mdi-content-copy
                                </v-icon>
                                <b>CPF:</b> {{ ENTREGAS_modalDetalhes.cpf }}
                            </p>
                            <p v-if="ENTREGAS_modalDetalhes.cnpj">
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.cnpj)">mdi-content-copy
                                </v-icon>
                                <b>CNPJ:</b> {{ ENTREGAS_modalDetalhes.cnpj }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.telefone)">mdi-content-copy
                                </v-icon>
                                <b>Telefone:</b> {{ ENTREGAS_modalDetalhes.telefone }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.logradouro)">mdi-content-copy
                                </v-icon>
                                <b>Endereço:</b> {{ ENTREGAS_modalDetalhes.logradouro }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.numero)">mdi-content-copy
                                </v-icon>
                                <b>Número:</b> {{ ENTREGAS_modalDetalhes.numero }}
                            </p>
                            <p v-if="ENTREGAS_modalDetalhes.complemento">
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.complemento)">mdi-content-copy
                                </v-icon>
                                <b>Complemento:</b> {{ ENTREGAS_modalDetalhes.complemento }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.bairro)">mdi-content-copy
                                </v-icon>
                                <b>Bairro:</b> {{ ENTREGAS_modalDetalhes.bairro }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes?.cidade)">mdi-content-copy
                                </v-icon>
                                <b>Cidade:</b> {{ ENTREGAS_modalDetalhes.cidade?.cidade }}
                            </p>
                            <p>
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes?.cidade.uf)">mdi-content-copy
                                </v-icon>
                                <b>Estado:</b> {{ ENTREGAS_modalDetalhes.cidade?.uf }}
                            </p>
                            <p v-if="ENTREGAS_modalDetalhes.cep">
                                <v-icon @click="copiarDados(ENTREGAS_modalDetalhes.cep)">mdi-content-copy
                                </v-icon>
                                <b>CEP:</b> {{ ENTREGAS_modalDetalhes.cep }}
                            </p>
                            <p>
                                <span id="whatsapp" @click="navegaParaWhatsApp(ENTREGAS_modalDetalhes.telefone)">
                                    <v-icon color="var(--cor-padrao-whatsapp)">mdi-whatsapp</v-icon>
                                    <b>Whatsapp:</b> {{ ENTREGAS_modalDetalhes.telefone }}
                                </span>
                            </p>
                            <textarea id="ENTREGAS_inputCopiar" style="height: 1px;"></textarea>
                        </div>
                        <div>
                            <v-card-title>
                                Informações financeiras
                            </v-card-title>
                            <p>
                                <b>Qtd Produtos Prontos pra Entrega:</b> {{ ENTREGAS_modalDetalhes.qtd_produtos_prontos }}
                            </p>
                            <p>
                                <b>Qtd Total de Produtos:</b> {{ ENTREGAS_modalDetalhes.qtd_total_produtos }}
                            </p>
                            <p>
                                <b>Total do Pedido:</b> {{ ENTREGAS_modalDetalhes.valor_pedido }}
                            </p>
                            <p v-if="ENTREGAS_modalDetalhes.qtd_total_produtos > 0">
                                <b>Total ÷ Produtos:</b> {{ ENTREGAS_modalDetalhes.valor_custo_produto }}
                            </p>
                            <p>
                                <b>Custo do Frete Ponto Coleta:</b> {{ ENTREGAS_modalDetalhes.custo_frete_ponto_coleta }}
                            </p>
                            <p>
                                <b>Custo do Frete Transportadora:</b> {{ ENTREGAS_modalDetalhes.custo_frete_transportadora }}
                            </p>
                            <p>
                                <b>Qtd Volumes:</b> {{ ENTREGAS_modalDetalhes.volumes}}
                            </p>
                            <v-card-title>
                                Outras Informações
                            </v-card-title>
                            <v-select :items="ENTREGAS_tipos_embalagens" :label="ENTREGAS_modalDetalhes.tipo_embalagem" item-text="item" item-value="value" @click="selecionaEntrega(ENTREGAS_modalDetalhes.id_colaborador)" @change="mudaTipoEmbalagem"></v-select>
                            <div v-if="ENTREGAS_modalDetalhes.id_entregas_anteriores?.length > 0">
                                <p><b>Entregas Anteriores:</b></p>
                                <a v-for="id_entrega in ENTREGAS_modalDetalhes?.id_entregas_anteriores" :href="`detalhes-entrega.php?id=${id_entrega}`" :key="id_entrega" target="_blank">
                                    {{ id_entrega }}
                                    <v-icon style="font-size: 1rem; color: gray;">mdi-link-variant</v-icon>
                                </a>
                            </div>
                        </div>
                    </v-row>
                    <v-row class="p-2 m-0 justify-content-around flex-column">
                        <v-textarea
                            name="colaboradores_observacoes"
                            label="Observações"
                            value=""
                            rows="3"
                            :value="ENTREGAS_modalDetalhes?.observacoes"
                            :loading="loading"
                            :disabled="loading"
                        ></v-textarea>
                        <v-btn small :disabled="loading" @click="ENTREGAS_salvarObservacaoColaborador">Salvar Observação</v-btn>
                    </v-row>

                </div>
                <v-divider></v-divider>
                <v-card-actions class="d-flex justify-content-center">
                    <v-btn color="error" text @click="fecharModalDetalhesDestinoEntrega">Fechar</v-btn>
                </v-card-actions>
            </v-card>

        </v-dialog>

        <v-snackbar :color="snackbar.cor" v-model="snackbar.mostra">
            {{ snackbar.texto }}
            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="snackbar.mostra = false">
                    Fechar
                </v-btn>
            </template>
        </v-snackbar>

    </v-app>
</div>

<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/marketplace.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script src="js/printThis.js"></script>
<script src="js/whatsapp.js"></script>
<script src="js/tools/removeAcentos.js"></script>
