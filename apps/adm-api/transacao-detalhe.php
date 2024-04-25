<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<input type="hidden" id="id-transacao" value="<?= $_GET['id'] ?>" />

<div class="container-fluid d-none" id="app">
    <v-app>
        <v-main>
        <v-alert
            v-for="(item, index) in transacao?.fraudes_colaborador"
            v-bind:key="index"
            :type="item?.situacao === 'FR' ? 'error' : 'warning'"
            v-if="item?.situacao && item?.situacao !== 'LG'"
            v-cloak
        >
            Atenção! Esse pagador
            {{ item?.situacao === 'FR' ? `é uma fraude de ${item?.origem.toLowerCase()}.` : `está na lista de suspeitos para fraude de ${item?.origem.toLowerCase()}.` }}
            {{ item?.situacao === 'LT' ? `Foi liberado temporariamente da fraude de ${item?.origem} em: ${item?.data_atualizacao}` : '' }}
        </v-alert>
            <v-card elevation="0" color="grey lighten-4" rounded="lg" class="mb-2">
                <v-row no-gutters>
                    <v-col cols="9" class="py-2">
                        <v-card-title class="w-100 d-flex justify-content-between">
                            Transação {{ idTransacao }}
                            <v-btn color="primary" @click="historico.modal = true">Ver Acompanhamento</v-btn>
                        </v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col>
                                    <span class="d-flex" style="height: 1.25rem;">
                                        Cliente:
                                        &ensp;
                                        <a :href="'/extratos.php?id=' + transacao?.id_colaborador">{{ transacao?.cliente }}</a>
                                        &ensp;-&ensp;
                                        <a
                                            @click="modalQrCode = true"
                                        > {{ transacao?.telefone }}</a>
                                    </span>
                                    Endereço Escolhido: <b>{{ transacao?.endereco_transacao?.endereco }}
                                        {{ transacao?.endereco_transacao?.numero }} - {{ transacao?.endereco_transacao?.bairro }}
                                        ({{ transacao?.endereco_transacao?.cidade }} - {{ transacao?.endereco_transacao?.uf }})</b><br>
                                    Métodos disponíveis de pagamento: <b>{{ transacao?.metodos_pagamentos_disponiveis }}</b> <br>
                                    Data de criação: <b>{{ transacao?.data_criacao }}</b> <br>
                                    Data de atualização: <b>{{ transacao?.data_atualizacao }}</b> <br>
                                    URL Boleto: <a target="_blanc" :href="transacao?.url_boleto">Ver URL</a> <br>
                                    QR Pix: <a target="_blanc" :href="transacao?.qrcode_pix">Ver URL</a> <br>
                                </v-col>
                                <v-col>
                                    Valor Produtos: <b>{{ transacao?.valor_itens | dinheiro }}</b> <br>
                                    Valor Crédito: <b>{{ transacao?.valor_credito - transacao?.valor_credito_bloqueado | dinheiro }}</b> <br>
                                    Valor Bloqueado: <b>{{ transacao?.valor_credito_bloqueado | dinheiro }}</b> <br>
                                    Valor Acrescimo: <b>{{ transacao?.valor_acrescimo | dinheiro }}</b> <br>
                                    Valor Liquido: <b>{{ transacao?.valor_liquido | dinheiro }}</b> <br>
                                    Valor Total: <b>{{ transacao?.valor_total | dinheiro }}</b> <br>
                                    Valor Estornado: <b>{{ transacao?.valor_estornado | dinheiro }}</b> <br>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-col>
                    <v-col cols="3">
                        <v-sheet class="h-100 w-100" color="grey lighten-2" rounded="lg">
                            <v-alert v-if="transacao?.status === 'PA'" type="success">Pago</v-alert>
                            <v-alert v-else-if="transacao?.status === 'PE'" type="warning">Pendente</v-alert>
                            <v-alert v-else-if="transacao?.status === 'CA'" type="error">Cancelado</v-alert>
                            <v-alert v-else-if="transacao?.status === 'ES'" type="info" color="black">Estornado</v-alert>
                            <v-alert v-else-if="transacao?.status === 'PX'" type="info" color="grey darken-1">Parcialmente estornado</v-alert>
                            <v-alert v-else type="info" color="grey lighten-1">{{ transacao?.status }}</v-alert>
                            <v-card-text>
                                Transação: <b>{{ transacao?.cod_transacao }}</b><br>
                                Meio de Pagamento: <b>{{ transacao?.metodo_pagamento }} {{ transacao?.emissor_transacao }}</b><br>
                                Origem da transação: <b>{{ transacao?.origem_transacao }}</b>
                            </v-card-text>
                        </v-sheet>
                    </v-col>
                </v-row>
            </v-card>
            <v-row no-gutters>
                <v-col cols="2" class="pr-2">
                    <v-sheet class="py-2" rounded="lg" color="grey lighten-4">
                        <v-list color="transparent">

                            <v-list-item link @click="telaAtual = 'produtos'">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        <div>
                                            Produtos
                                            <v-badge :content="String(produtos.itens?.length)" inline overlap></v-badge>
                                        </div>
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>

                            <v-list-item link @click="telaAtual = 'lancamentos'">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        <div>
                                            Lançamentos
                                        </div>
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>

                            <v-list-item link @click="telaAtual = 'transferencias'">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        <div>
                                            Transferencias
                                        </div>
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>

                            <v-list-item link @click="telaAtual = 'trocas'">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        <div>
                                            Trocas
                                        </div>
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>

                            <v-list-item link @click="telaAtual = 'tentativas'">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        <div>
                                            Tentativas
                                        </div>
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>

                            <v-divider class="my-2"></v-divider>

                            <v-subheader class="w-100 d-flex justify-content-between">
                                Ações
                                <v-tooltip bottom>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-icon color="error" v-bind="attrs" v-on="on">mdi-alert</v-icon>
                                    </template>
                                    <span>Atenção!! Essas ações não poderão ser desfeitas após a confirmação</span>
                                </v-tooltip>
                            </v-subheader>

                            <v-dialog :value="['aberto', 'carregando'].includes(situacaoModalCancelaTransacao)" @input="val => situacaoModalCancelaTransacao = val ? 'aberto' : 'fechado'" max-width="600px" :persistent="situacaoModalCancelaTransacao === 'carregando'">
                                <template v-slot:activator="{ on, attrs }">
                                    <v-list-item link color="error" :disabled="carregando || ['CA', 'PX'].includes(transacao?.status)">
                                        <v-list-item-content v-on="on" v-bind="attrs">
                                            <v-list-item-title>
                                                Cancelar
                                            </v-list-item-title>
                                        </v-list-item-content>
                                    </v-list-item>
                                </template>
                                <v-card>
                                    <v-card-title>Atenção</v-card-title>
                                    <v-form ref="formularioCancelaTransacao" @submit.prevent="cancelaTransacao">
                                        <v-card-text>
                                            Deseja realmente cancelar essa transação? <br>
                                            <b>Essa alteração não poderá ser desfeita.</b>
                                            <v-select :disabled="situacaoModalCancelaTransacao === 'carregando'"
                                                      :rules="[r => !!r || 'Preencha um motivo']"
                                                      :items="[
                            {text: 'Fraude (Não volta valores para o cliente)', value: 'FRAUDE'},
                            {text: 'Cliente desistiu da compra e quer os valores de volta', value: 'CLIENTE_DESISTIU'}]"
                                                      v-model="motivoCancelamento"
                                                      label="Motivo do cancelamento"></v-select>
                                        </v-card-text>
                                        <v-card-actions>
                                            <div class="w-100 d-flex flex-row-reverse">
                                                <v-btn type="submit" :loading="situacaoModalCancelaTransacao === 'carregando'" plain color="error">Sim</v-btn>
                                                <v-btn type="button" :disabled="situacaoModalCancelaTransacao === 'carregando'" @click="situacaoModalCancelaTransacao = 'fechado'" plain>Não</v-btn>
                                            </div>
                                        </v-card-actions>
                                    </v-form>
                                </v-card>
                            </v-dialog>

                            <v-list-item link color="error" :disabled="carregando || transacao?.emissor_transacao === 'Interno'" @click="atualizaComApi">
                                <v-list-item-content>
                                    <v-list-item-title>
                                        Atualizar com API
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
                    </v-sheet>
                </v-col>
                <v-col cols="10">

                    <v-window vertical v-model="telaAtual" class="h-100">

                        <v-window-item value="produtos" class="h-100">
                            <div class="grey lighten-4 rounded-lg h-100" v-if="produtos.itens.length">
                                <div class="p-4 pb-0">
                                    <div class="align-items-center d-flex flex-column font-weight-bold">
                                        <h3>Valor total pago pelos itens</h3>
                                        <h3 class="text-success">{{ transacao?.valor_itens | dinheiro }}</h3>
                                    </div>
                                    <v-timeline dense>
                                        <v-timeline-item
                                            large
                                            right
                                            color="success"
                                            :icon="item.icone"
                                            :key="index"
                                            v-for="(item, index) in produtos.itens"
                                        >
                                            <v-card width="50%" :dark="item.usarTemaDark">
                                                <v-card-text>
                                                    <div v-if="item.tipo_item === 'FOTO_PRODUTO'">
                                                        <v-img
                                                            class="m-auto"
                                                            height="25rem"
                                                            width="25rem"
                                                            :src="item.foto"
                                                        ></v-img>
                                                        <hr />
                                                        <div class="d-flex flex-row justify-content-between">
                                                            <div class="d-flex flex-column align-items-center text-center">
                                                                <p class="m-0" v-if="item.id_entrega > 0">
                                                                    <b>ID Entrega:</b>
                                                                    <a target="_blank" :href="`detalhes-entrega.php?id=${item.id_entrega}`">
                                                                        {{ item.id_entrega }}
                                                                    </a>
                                                                </p>
                                                                <p class="m-0">
                                                                    <b>ID produto:</b>
                                                                    <a :href="`produtos-busca.php?id=${item.id_produto}`">
                                                                        {{ item.id_produto }}
                                                                    </a> - {{ item.tamanho }}
                                                                </p>
                                                                <p class="m-0">
                                                                    <b>Nome Produto:</b> {{ item.nome }}
                                                                </p>
                                                            </div>
                                                            <div class="align-center d-flex justify-center">
                                                                <v-btn small @click="exibirQrcodeProdutoFn(item)">
                                                                    <v-icon>mdi-qrcode</v-icon>
                                                                </v-btn>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else>
                                                        <ul>
                                                            <li>
                                                                <b>ID:</b> {{ item.id }}
                                                            </li>
                                                            <li>
                                                                <b>Tipo Item:</b> {{ item.tipo_item }}
                                                            </li>
                                                            <li>
                                                                <b>Comissionado:</b> <a :href="'/extratos.php?id=' + item?.id_comissionado" target="_blank">{{ item.comissionado }} ({{ item.id_comissionado }})</a>
                                                            </li>
                                                            <li>
                                                                <b>Valor Comissão</b> {{ item.valor_comissao | dinheiro }}
                                                            </li>
                                                            <li>
                                                                <span :class="corSituacaoPagamento(item.situacao_pagamento)">{{ item.situacao_pagamento.replace(/_/, ' ') }}</span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </v-card-text>
                                            </v-card>
                                        </v-timeline-item>
                                    </v-timeline>
                                </div>
                            </div>
                            <div  class="grey lighten-4 rounded-lg h-100" v-else>
                                <div class="align-items-center d-flex flex-column font-weight-bold">
                                    <h3>Pedido Cancelado</h3>
                                </div>
                            </div>
                        </v-window-item>
                        <v-window-item value="lancamentos" class="h-100">
                            <v-overlay absolute v-if="lancamentos.carregando">
                                <v-progress-circular indeterminate size="32"></v-progress-circular>
                            </v-overlay>
                            <v-data-table class="grey lighten-4 rounded-lg h-100"
                                          :item-class="item => item.tipo === 'pendente'
                                            ? 'yellow lighten-4'
                                            : ''"
                                          :loading="lancamentos.carregando"
                                          :items="lancamentos.itens"
                                          :headers="lancamentos.headers">

                                <template v-slot:item.colaborador="{ item }">
                                    {{ item.colaborador }} <small>({{ item.id_colaborador }})</small>
                                </template>

                                <template v-slot:item.credito="{ item }">
                                    <span class="text-success" v-if="item.valor >= 0 || item.faturamento_criado_pago === 'T'">{{ item.valor | dinheiro }}</span>
                                    <span v-else>{{ 0 | dinheiro }}</span>
                                </template>

                                <template v-slot:item.debito="{ item }">
                                    <span class="text-danger" v-if="item.valor < 0 || item.faturamento_criado_pago === 'T'">{{ item.valor | dinheiro }}</span>
                                    <span v-else>{{ 0 | dinheiro }}</span>
                                </template>

                            </v-data-table>
                        </v-window-item>
                        <v-window-item value="transferencias" class="h-100">
                            <v-overlay absolute v-if="transferencias.carregando">
                                <v-progress-circular indeterminate size="32"></v-progress-circular>
                            </v-overlay>
                            <v-data-table class="grey lighten-4 rounded-lg h-100" :items="transferencias.itens" :headers="transferencias.headers">

                                <template v-slot:item.id_transferencia="{ item }">
                                    <a target="_blank" :href="`lista-sellers-transferencias.php?id=${item.id_transferencia}`">{{ item.id_transferencia }}</a>
                                </template>

                                <template v-slot:item.valor="{ item }">
                                    {{ item.valor | dinheiro }}
                                </template>

                                <template v-slot:item.valor_pago="{ item }">
                                    {{ item.valor_pago | dinheiro }}
                                </template>

                            </v-data-table>
                        </v-window-item>
                        <v-window-item value="trocas" class="h-100">
                            <v-overlay absolute v-if="trocas.carregando">
                                <v-progress-circular indeterminate size="32"></v-progress-circular>
                            </v-overlay>
                            <v-data-table
                                    class="grey lighten-4 rounded-lg h-100"
                                    :items="trocas.itens"
                                    :headers="trocas.headers"
                                    :item-class="item => item.tipo_lancamento === 'PENDENTE'
                                        ? 'yellow lighten-4'
                                        : ''"
                            >
                                <template v-slot:item.transacao_origem="{ item }">
                                    <a target="_blank"
                                       :href="`transacao-detalhe.php?id=${item.transacao_origem}`">
                                        {{ item.transacao_origem }}
                                    </a>
                                </template>

                                <template v-slot:item.foto="{ item }">
                                    <img v-if="item.foto"
                                         :src="item.foto"
                                         :alt="`${item.id_produto} - ${item.nome_tamanho}`"
                                         class="w-100"
                                         style="max-width: 100px">
                                </template>

                                <template v-slot:item.valor="{ item }">
                                    {{ item.valor | dinheiro }}
                                </template>

                                <template v-slot:item.valor_pago="{ item }">
                                    {{ item.valor_pago | dinheiro }}
                                </template>

                                <template v-slot:item.situacao="{ item }">
                                    <span v-html="formataSituacaoTroca(item)"></span>
                                </template>

                                <template v-slot:item.detalhes_produto="{ item }">
                                    <v-btn :loading="trocas.carregando"
                                           @click="() => buscaDebitosDaTroca(item)"
                                           icon>
                                        <v-icon>mdi-menu</v-icon>
                                    </v-btn>
                                </template>
                            </v-data-table>
                        </v-window-item>
                        <v-window-item value="tentativas" class="h-100">
                            <v-overlay absolute v-if="tentativas.carregando">
                                <v-progress-circular indeterminate size="32"></v-progress-circular>
                            </v-overlay>
                            <v-data-table class="grey lighten-4 rounded-lg h-100" :items="tentativas.itens" :headers="tentativas.headers">
                                <template v-slot:item.json="{ item }">
                                    <v-btn @click="modalTentativas = true">Json</v-btn>
                                </template>
                            </v-data-table>
                        </v-window-item>
                    </v-window>
                </v-col>
            </v-row>

            <v-overlay :value="carregando">
                <v-progress-circular indeterminate size="64"></v-progress-circular>
            </v-overlay>

            <v-snackbar :color="snackbar.cor" v-model="snackbar.aberto">{{ snackbar.mensagem }}</v-snackbar>
        </v-main>

        <v-dialog
            transition="dialog-bottom-transition"
            v-model="historico.modal"
        >
            <v-card>
                <v-toolbar class="mb-2" elevation="0">
                    <v-toolbar-title>
                        Acompanhamento
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon @click="historico.modal = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-card-text>
                    <!-- Próximo a mexer favor mudar as situações para constantes -->
                    <v-data-table
                        class="grey lighten-4 rounded-lg h-100"
                        :items="historico.itens"
                        :headers="historico.headers"
                    >
                        <template v-slot:item.foto="{ item }">
                            <v-img
                                height="6.25rem"
                                width="6.25rem"
                                :aspect-ratio="16/9"
                                :src="item.foto"
                            />
                        </template>

                        <template v-slot:item.nome="{item}">
                            <span>{{ item.nome }}</span>
                            <b class="text-danger" v-if="!!item.negociacao_aceita">(PRODUTO SUBSTITUTO)</b>
                        </template>

                        <template v-slot:item.previsao="{ item }">
                            <div class="text-center" v-if="item.previsao">
                                <p>
                                    Entre {{ item.previsao.media_previsao_inicial }}
                                    e {{ item.previsao.media_previsao_final }}
                                </p>
                            </div>
                            <p v-else>Sem Previsão</p>
                        </template>
                    </v-data-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="secondary"
                        @click="historico.modal = false"
                    >Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal QrCode -->
        <v-dialog
            width="unset"
            v-model="modalQrCode"
        >
            <template v-if="modalQrCode">
                <v-expand-x-transition>
                    <v-card
                        class="mx-auto"
                        v-show="modalQrCode"
                    >
                        <v-toolbar dark color="error">
                            <v-toolbar-title>
                                <v-icon>mdi-qrcode</v-icon>
                                {{ transacao?.cliente }} - QRcode WhatsApp
                            </v-toolbar-title>
                        </v-toolbar>
                        <br />
                        <v-img :src="transacao?.qr_code"/>
                    </v-card>
                </v-expand-x-transition>
            </template>
        </v-dialog>

        <!-- Modal QRCode do Produto -->
        <v-dialog
            max-width="30em"
            v-model="exibirQrcodeProduto"
        >
            <v-card>
                <v-card-title>
                    <h6 class="text-center">({{ qrcodeProduto.id_produto}}) {{ qrcodeProduto?.nome }} [{{ qrcodeProduto?.tamanho }}]</h6>
                </v-card-title>
                <v-card-text>
                    <img class="img-fluid" :src="qrcodeProduto?.qrcode_produto" alt="QRCode do Produto">
                </v-card-text>
                <v-card-actions class="d-flex justify-content-between">
                    <v-btn
                        color="primary"
                        text
                        @click="imprimeEtiquetasSeparacaoCliente(qrcodeProduto.uuid_produto)"
                        :loading="loadingImprimeEtiquetas"
                        :disabled="loadingImprimeEtiquetas"
                    >
                        <v-icon>mdi-printer</v-icon>&nbsp;Imprimir
                    </v-btn>
                    <v-btn color="error" text @click="exibirQrcodeProduto = false">
                        <v-icon>mdi-close</v-icon>
                        Fechar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <template>
            <div class="text-center">
                <v-dialog
                v-model="modalTentativas"
                scrollable
                max-width="500px"
                >

                <v-card>
                    <v-card-title class="text-h5 grey lighten-2">
                    Tentativas Json
                    </v-card-title>

                    <v-card-text>
                        <pre>{{ tentativas.json }}</pre>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="primary"
                        text
                        @click="modalTentativas = false"
                    >
                        Ok
                    </v-btn>
                    </v-card-actions>
                </v-card>
                </v-dialog>
            </div>
        </template>

    <v-dialog max-width="700px" :value="trocas.itensModal !== null" @input="val => !val && (trocas.itensModal = null)">
        <v-card>
            <v-card-title>
                <h6 class="text-center">Extrato da troca</h6>
            </v-card-title>

            <v-card-text>
                <v-row class="pt-2">
                    <v-col class="text-center" cols="2" style="margin-left: 5rem">
                        <small>Valor</small>
                    </v-col>
                    <v-col class="text-center" cols="3">
                        <small>Data</small>
                    </v-col>
                    <v-col class="text-center">
                        <small>ID do lançamento</small>
                    </v-col>
                    <v-col class="text-center">
                        <small>ID da transação</small>
                    </v-col>
                </v-row>
                <v-divider class="m-0"></v-divider>
                <v-timeline dense>
                    <v-timeline-item v-for="(lancamento, key) in this.trocas.itensModal"
                                     :key="key"
                                     :color="lancamento.valor_pago > 0
                                        ? 'var(--cor-fundo-verde)'
                                        : 'var(--cor-fundo-vermelho)'"
                                     :icon="lancamento.valor_pago > 0 ? 'mdi-plus' : 'mdi-minus'">
                        <v-row class="pt-2">
                            <v-col>
                                {{ lancamento.valor_pago | dinheiro }}
                            </v-col>
                            <v-col cols="4">
                                {{ lancamento.data_emissao }}
                            </v-col>
                            <v-col>
                                {{ lancamento.id }}
                            </v-col>
                            <v-col>
                                <a :href="`transacao-detalhe.php?id=${lancamento.transacao_origem}`"
                                   target="_blank">
                                    {{ lancamento.transacao_origem }}
                                </a>
                            </v-col>
                        </v-row>
                    </v-timeline-item>
                </v-timeline>
            </v-card-text>
        </v-card>
    </v-dialog>

    </v-app>
</div>



<script src="js/MobileStockApi.js"></script>
<script src="https://cdn.jsdelivr.net/gh/mobilestock/wait-queue-as-promise@f4ef7736ecc7c0f4ab8ade6e6eaea841a142078a/dist/bundle.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/tools/formataMoeda.js"></script>
<script type="module" src="js/transacao-detalhe.js<?= $versao ?>"></script>
