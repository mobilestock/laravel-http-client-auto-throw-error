<?php

use MobileStock\helper\Globals;

require_once __DIR__ . '/cabecalho.php';
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
figure {
  margin: 0;
}
.container-imagem-produto {
  min-height: 4rem;
  min-width: 4rem;
  max-height: 4rem;
  max-width: 4rem;
}
.container-imagem-cliente {
  height: 1.5rem;
  width: 1.5rem;
}
img {
  height: 100%;
  width: 100%;
  object-fit: cover;
}
textarea {
  width: 100%;
  background-color: #EEE;
  padding: 0.5rem;
  margin-bottom: 1rem;
}
</style>

<v-app id="solicitacoes-troca">
  <div class="container-md py-3">
    <section>
      <h1 class="text-center">Solicitações de Troca</h1>
      <div class="my-3 text-justify">
        <b>Você pode conferir as solicitações de troca por defeito das vendas do meulook.</b>
      </div>
    </section>
    <section>
      <input
        class="w-100 mb-3 p-2 rounded border"
        v-model="pesquisa"
        :disabled="carregando"
        placeholder="Buscar produto, cliente ou vendedor por ID ou nome"
      />
    </section>
    <section v-if="produtos?.length">
      <!-- Item da Lista -->
      <div class="card p-2" v-for="produto in produtos">
        <section class="d-flex">
          <figure class="container-imagem-produto">
            <img :src="produto.foto_produto" class="rounded">
          </figure>
          <div class="ml-1 overflow-hidden">
            <p class="m-0 text-nowrap text-capitalize">
              <span>
                <b>{{ produto.id_produto }}</b>
              </span>
              <span> - </span>
              <span>{{ produto.nome_comercial }}</span>
            </p>
            <span class="m-0">Tamanho: <b>{{ produto.nome_tamanho }}</b></span>
            <p v-if="usuarioInterno">Transação: <b>{{ produto.id_transacao }}</b></p>
            <span v-if="usuarioInterno && produto.ultimo_numero_coleta">Último número de coleta gerado: <b> {{ produto.ultimo_numero_coleta }} </b></span>
          </div>
        </section>
        <section class="row">
          <div class="col">
            <span>Cliente:</span>
            <div class="d-flex overflow-hidden">
              <figure class="container-imagem-cliente">
                <img :src="produto.foto_cliente" class="rounded-circle">
              </figure>
              <span class="ml-1 text-nowrap font-weight-bold">{{ produto.nome_cliente ?? produto.razao_social_cliente }}</span>
            </div>
            <a
              :href="produto.link_whatsapp_cliente"
              v-if="usuarioInterno"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ formatarTelefone(produto.telefone_cliente) }}
            </a>
            -
            <span v-if="usuarioInterno" @click="abreModalQrCode(false ,produto)">Qr Code Cliente</span>
          </div>
          <div v-if="usuarioInterno" class="col">
            <span>Vendedor:</span>
            <div class="d-flex overflow-hidden">
              <figure class="container-imagem-cliente">
                <img :src="produto.foto_vendedor" class="rounded-circle">
              </figure>
              <span class="ml-1 text-nowrap font-weight-bold">{{ produto.nome_vendedor ?? produto.razao_social_vendedor}}</span>
            </div>
            <a
              :href="produto.link_whatsapp_vendedor"
              v-if="usuarioInterno"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ formatarTelefone(produto.telefone_vendedor) }}
            </a>
            -
            <span @click="abreModalQrCode(true ,produto)">Qr Code Vendedor</span>
          </div>
        </section>
        <section class="row">
          <div class="col">
            <p>
              <span>Compra</span>
              <br>
              <span><b>{{ produto.data_pagamento }}</b></span>
            </p>
          </div>
          <div class="col">
            <p>
              <span>Retirada</span>
              <br>
              <span><b>{{ produto.data_retirada }}</b></span>
            </p>
          </div>
        </section>
        <section class="my-2 text-center">
          <b class="text-info">{{ produto.observacao }}</b>
        </section>
        <section>
          <v-btn @click="abrirModalTroca(produto)" block v-if="produto.situacao_solicitacao == 'TROCA_PENDENTE' || produto.situacao_solicitacao == 'PENDENTE_FOTO'">Responder Solicitação</v-btn>
          <v-btn @click="abrirModalDisputa(produto)" block v-if="usuarioInterno && produto.situacao_solicitacao == 'DISPUTA'">Resolver Disputa</v-btn>
          <v-btn
            @click="abrirModalTroca(produto)"
            block
            v-if="usuarioInterno && !['TROCA_PENDENTE','PENDENTE_FOTO','DISPUTA'].includes(produto.situacao_solicitacao)  "
          >
            Ver troca
          </v-btn>
        </section>
      </div>
    </section>
    <section class="text-center">
      <v-btn
        dark
        v-if="!carregando"
        @click="verMais()"
      >
        <v-icon>mdi-chevron-down</v-icon>
          Ver mais
        <v-icon>mdi-chevron-down</v-icon>
      </v-btn>
    </section>
    <section class="py-5 text-center" v-if="produtos?.length === 0 && !carregando">
      Não foram encontrados resultados
    </section>
    <section class="text-center mt-5" v-if="carregando">
      <b>Carregando ...</b>
    </section>
  </div>

  <!-- Modal troca -->
  <v-dialog
    v-model="mostrarModalTroca"
    hide-overlay
    fullscreen
    transition="dialog-bottom-transition"
    style="z-index: 2000"
  >
    <v-card>
      <v-toolbar color="error" dark>
        <v-toolbar-title>Responder Solicitação</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon dark @click="fecharModais()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <div>
        <section class="container-md my-3">
          <p class="text-justify">
          <b>Produto comprado:</b>
          <figure class="container-imagem-produto">
            <img :src="produtoSelecionado?.foto_produto" class="rounded">
          </figure>
            <b>Descrição do Defeito:</b> {{ produtoSelecionado?.descricao_defeito }}
          </p>
          <section>
            <b>Foto{{ produtoSelecionado?.foto2 ? 's' : ''}}:</b>
            <figure class="rounded" v-if="produtoSelecionado?.foto1">
              <img class="rounded" :src="produtoSelecionado?.foto1" >
            </figure>
            <figure class="mt-2" v-if="produtoSelecionado?.foto2">
              <img class="rounded" :src="produtoSelecionado?.foto2" >
            </figure>
            <figure class="mt-2"v-if="produtoSelecionado?.foto3">
              <img class="rounded" :src="produtoSelecionado?.foto3" >
            </figure>
          </section>
          <section v-if="produtoSelecionado?.foto4">
            <br>
            <p class="text-justify">Novas fotos enviadas pelo cliente:</p>
            <figure class="rounded">
              <img class="rounded" :src="produtoSelecionado?.foto4">
            </figure>
            <figure class="mt-2" v-if="produtoSelecionado?.foto5">
              <img class="rounded" :src="produtoSelecionado?.foto5">
            </figure>
            <figure class="rounded" v-if="produtoSelecionado?.foto6">
              <img class="rounded" :src="produtoSelecioando?.foto6">
            </figure>
          </section>
          <section class="mt-2 text-justify" v-if="produtoSelecionado?.situacao_solicitacao == 'TROCA_PENDENTE' || produtoSelecionado?.situacao_solicitacao == 'PENDENTE_FOTO'">
            <p>
              Essa é uma solicitação de troca para um de seus produtos. Responda de acordo com as condições abaixo:
            </p>
            <p class="text-warning" v-if="produtoSelecionado?.situacao_solicitacao != 'PENDENTE_FOTO'">
                <b>Atenção</b>
            </p>
            <ul v-if="produtoSelecionado?.situacao_solicitacao != 'PENDENTE_FOTO'">
              <li>Você pode solicitar novas fotos caso estas não apresentem os defeitos ou não estejam legíveis.</li>
              <li>
                <b>Será descontada a taxa da plataforma para produtos aprovados como defeitos<span class="text-danger">*</span></b>
              </li>
            </ul>
            <p class="text-success">
              <b>Aprovar:</b>
            </p>
            <ul>
              <li>Se estiver de acordo com a solicitação.</li>
            </ul>
            <p class="text-danger">
              <b>Recusar:</b>
            </p>
            <ul>
              <li>Se a(s) foto(s) não corresponder(em) ao produto.</li>
              <li>Se a descrição não condizer com a(s) foto(s) enviada(s).</li>
            </ul>
            <v-btn
              :disabled="mostrarDialogConfirmarTroca || dialogSolicitarNovasFotos"
              @click="abrirDialogConfirmarTroca()"
              block
              large
              color="success"
            >
              Aprovar
            </v-btn>
            <br>
            <v-btn
              :disabled="mostrarDialogConfirmarTroca || dialogSolicitarNovasFotos"
              @click="abrirDialogConfirmarTroca(false)"
              block
              text
              color="error"
              class="text-danger"
            >
              Recusar
            </v-btn>
            <br>
            <v-btn
              v-if="produtoSelecionado?.situacao_solicitacao != 'PENDENTE_FOTO'"
              :disabled="mostrarDialogConfirmarTroca || dialogSolicitarNovasFotos"
              @click="abreModalSolicitarNovasFotos"
              block
              large
              color="warning"
            >
              Solicitar novas fotos
            </v-btn>
          </section>
        </section>
      </div>
    </v-card>
    <!-- Dialog confirmar aprovar/reprovar troca -->
    <v-dialog
      :value="mostrarDialogConfirmarTroca"
      hide-overlay
      transition="dialog-bottom-transition"
      style="z-index: 2050"
      persistent
    >
      <v-card>
        <section class="container-md p-3">
          <p>
            Tem certeza que deseja
            <b :class="dialogConfirmarTrocaAprovar ? 'text-success' : 'text-danger'">{{ dialogConfirmarTrocaAprovar ? 'APROVAR' : 'RECUSAR' }}</b>
            essa troca?
          </p>
          <div v-if="!dialogConfirmarTrocaAprovar">
            <p class="text-secondary">Descreva o motivo de estar recusando a troca</p>
            <textarea v-model="motivoReprovacao" maxlength="300">
              {{preencheTextareaTexto}}
            </textarea>
          </div>
          <section class="d-flex justify-content-end">
          <v-btn
              @click="preencherTextArea"
              color="info"
              class="mr-1"
              :disabled="carregando"
              :loading="carregando"
              v-if="usuarioInterno && !dialogConfirmarTrocaAprovar"
            >
              Texto
            </v-btn>
            <v-btn
              @click="mostrarDialogConfirmarTroca = false"
              color="error"
              class="mr-1"
              :disabled="carregando"
              :loading="carregando"
            >
              Voltar
            </v-btn>
            <v-btn
              @click="dialogConfirmarTrocaAprovar ? aprovarSolicitacaoTroca() : recusarSolicitacaoTroca()"
              color="success"
              class="ml-1"
              :disabled="botaoConfirmarDesabilitado"
              :loading="carregando"
            >
              Confirmar
            </v-btn>
          </section>
        </section>
      </v-card>
    </v-dialog>
  </v-dialog>

  <!-- Modal Resolver Disputa -->
  <v-dialog
    :value="mostrarModalDisputa"
    hide-overlay
    fullscreen
    transition="dialog-bottom-transition"
    style="z-index: 2000"
  >
    <v-card>
      <v-toolbar color="error" dark>
        <v-toolbar-title>Resolver Disputa</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon dark @click="fecharModais()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <div>
        <section class="container-md my-3">
          <section>
            <p class="text-justify">
              <b>Produto Comprado:</b>
              <figure class="container-imagem-produto">
                <img :src="produtoSelecionado?.foto_produto" class="rounded">
              </figure>
              <b>Descrição do Defeito:</b> {{ produtoSelecionado?.descricao_defeito }}
              <br />
              <b>Motivo Reprovação:</b> {{ produtoSelecionado?.motivo_reprovacao }}
            </p>
          </section>
          <section class="row">
            <figure class="col" v-if="produtoSelecionado?.foto1">
              <img class="rounded" :src="produtoSelecionado?.foto1" >
            </figure>
            <figure class="col" v-if="produtoSelecionado?.foto2">
              <img class="rounded" :src="produtoSelecionado?.foto2" >
            </figure>
            <figure class="col"v-if="produtoSelecionado?.foto3">
              <img class="rounded" :src="produtoSelecionado?.foto3" >
            </figure>
          </section>
          <section class="mt-2 text-justify">
            <p>
              Para resolver a disputa, responda de acordo com as condições abaixo:
            </p>
            <p class="text-success">
              <b>Aprovar:</b>
            </p>
            <ul>
              <li>A disputa está a favor do <b>CLIENTE</b> e o mesmo poderá trocar seu produto.</li>
            </ul>
            <p class="text-danger">
              <b>Recusar:</b>
            </p>
            <ul>
              <li>A disputa está a favor do <b>FABRICANTE</b> e o mesmo não será obrigado a trocar o produto.</li>
            </ul>
          </section>
          <section class="row align-items-center mt-5">
            <v-btn
              class="col m-1"
              :disabled="mostrarDialogConfirmarDisputa"
              @click="abrirDialogConfirmarDisputa(false)"
              color="error"
            >
              Reprovar Disputa
            </v-btn>
            <v-btn
              class="col m-1"
              :disabled="mostrarDialogConfirmarDisputa"
              @click="abrirDialogConfirmarDisputa()"
              color="success"
            >
              Aprovar Disputa
            </v-btn>
          </section>
        </section>
      </div>
    </v-card>
    <!-- Dialog confirmar aprovar/reprovar disputa -->
    <v-dialog
      :value="mostrarDialogConfirmarDisputa"
      hide-overlay
      transition="dialog-bottom-transition"
      style="z-index: 2050"
      persistent
    >
      <v-card>
        <section class="container-md p-3">
          <p>
            Tem certeza que deseja
            <b :class="dialogConfirmarDisputaAprovar ? 'text-success' : 'text-danger'">{{ dialogConfirmarDisputaAprovar ? 'APROVAR' : 'REPROVAR' }}</b>
            essa disputa?
          </p>
          <div v-if="!dialogConfirmarDisputaAprovar">
            <p class="text-secondary">Descreva o motivo de estar reprovando essa disputa</p>
            <textarea v-model="motivoReprovacao" maxlength="300">
            {{preencheTextareaTexto}}
            </textarea>
          </div>
          <section class="d-flex justify-content-end">
          <v-btn
              @click="preencherTextArea"
              color="info"
              class="mr-1"
              :disabled="carregando"
              :loading="carregando"
              v-if="usuarioInterno && !dialogConfirmarDisputaAprovar"
            >
              Texto
            </v-btn>
            <v-btn
              @click="mostrarDialogConfirmarDisputa = false"
              color="error"
              class="mr-1"
              :disabled="carregando"
              :loading="carregando"
            >
              Voltar
            </v-btn>
            <v-btn
              @click="resolverDisputa()"
              color="success"
              class="ml-1"
              :disabled="botaoConfirmarDesabilitado"
              :loading="carregando"
            >
              Confirmar
            </v-btn>
          </section>
        </section>
      </v-card>
    </v-dialog>
  </v-dialog>

  <v-dialog
    v-model="dialogQrCode"
    width="250"
  >
    <v-card>
      <v-card-title>
        <span class="text-h5">Contato</span>
      </v-card-title>
      <v-card-text>
        <div style="display: flex; flex-direction: column; align-items: center;">
          <img :src="'<?= Globals::geraQRCODE('') ?>' + telefoneQrCode">
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>

  <!-- Dialog solicitar novas fotos -->
  <v-dialog
    v-model="dialogSolicitarNovasFotos"
    hide-overlay
    transition="dialog-bottom-transition"
    style="z-index: 2050"
    persistent
  >
    <v-card>
      <v-card-title>
        <span class="text-h5">Solicitar novas fotos</span>
      </v-card-title>
      <v-card-text>
        <section class="container-md p-3">
          <p class="text-secondary">Descreva o motivo de estar solicitando novas fotos.</p>
          <textarea v-model="motivoNovasFotos"></textarea>
        </section>
        <section class="d-flex justify-content-end">
          <v-btn
            @click="preencherTextAreaNovasFotos"
            color="info"
            class="mr-1"
            :disabled="carregando"
            :loading="carregando"
          >
            Texto
          </v-btn>
          <v-btn
              @click="dialogSolicitarNovasFotos = false"
              color="error"
              class="mr-1"
              :disabled="carregando"
              :loading="carregando"
            >
              Voltar
          </v-btn>
          <v-btn
            @click="enviarSolicitacaoNovasFotos()"
            color="success"
            class="ml-1"
            :disabled="botaoConfirmarDesabilitado"
            :loading="carregando"
          >
            Enviar
          </v-btn>
        </section>
      </v-card-text>
    </v-card>
  </v-dialog>

  <v-snackbar v-model="mostrarSnackBar">{{ mensagemSnackBar }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/whatsapp.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/solicitacoes-troca.js"></script>
