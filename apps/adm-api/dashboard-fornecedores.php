<?php require_once __DIR__ . '/cabecalho.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
  .grade-numero {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0.05rem 0.1rem;
    font-size: 0.8rem;
    font-weight: bold;
  }
  .celula {
    min-width: 1.5rem;
    width: 100%;
    padding: 0.1rem 0.2rem;
    text-align: center;
  }
  .grade-numero .tamanho {
    background: black;
    color: white;
  }
  .grade-numero .quantidade {
    border: solid 1px black;
  }
  table tr:nth-child(2n+2) {
    background-color: #eee;
  }
  .objetivo-concluido {
    color: var(--primary);
    text-decoration-line: line-through;
  }
  .barra-progresso {
    margin-top: 1rem;
    border-radius: 50px;
    position: relative;
    overflow: hidden;
    background-color: var(--cor-fundo-botao-cinza-claro);
  }
  .barra-progresso .porcentagem {
    position: absolute;
    left: 50%;
    top: 50%;
    translate: -50% -50%;
    font-weight: bold;
  }
</style>

<v-app id="dashboard-fornecedores">
  <section class="p-3">
    <div>
      <h4 class="text-danger text-center">
        ATENÇÃO!! Será cobrada uma multa no valor de R$10,00 para cada produto enviado errado.
      </h4>
    </div>
    <div class="card border rounded border-primary">
      <div class="card-body">
        <div v-if="seller.reputacao !== 'MELHOR_FABRICANTE'">
          <div class="card-title w-100 text-center text-primary">
          <i class="fas fa-check-circle"></i>
          Melhores Fabricantes
          </div>
          <p class="card-text text-justify">Os produtos de melhores fabricantes se destacam no meulook!</p>
          <div>
            <label :class="cumpriuPeriodoEntrega && 'objetivo-concluido' + ' text-justify'">
              <i v-if="cumpriuPeriodoEntrega" class="fas fa-check text-primary"></i>
              <i v-else class="fas fa-square text-muted"></i>
              <small>Entrega em {{ requisitos.media_dias_envio_melhor_fabricante }} dias ou menos no Centro de Distribuição.
                <span v-if="!cumpriuPeriodoEntrega">
                  <b>(Média atual {{ seller.dias_despacho }} dias)</b>
                </span>
              </small>
            </label>
          </div>
          <div>
            <label :class="cumpriuCancelamentos && 'objetivo-concluido' + ' text-justify'">
              <i v-if="cumpriuCancelamentos" class="fas fa-check text-primary"></i>
              <i v-else class="fas fa-square text-muted"></i>
              <small>Cancela menos de {{ requisitos.taxa_cancelamento_melhor_fabricante }}% das vendas.
                <span v-if="!cumpriuCancelamentos">
                  <b>(Média atual {{ seller.taxa_cancelamento }}%)</b>
                </span>
              </small>
            </label>
          </div>
          <div>
            <label :class="cumpriuValorVenda && 'objetivo-concluido' + ' text-justify'">
              <i v-if="cumpriuValorVenda" class="fas fa-check text-primary"></i>
              <i v-else class="fas fa-square text-muted"></i>
              <small>
                Vendeu mais de {{ requisitos.valor_vendido_melhor_fabricante | formatarDinheiro }}
                em produtos no últimos {{ requisitos.dias_mensurar_vendas }} dias.
                <span v-if="!cumpriuValorVenda">
                  <b>(Valor atual: {{ seller.valor_vendido | formatarDinheiro }})</b>
                </span>
              </small>
            </label>
          </div>
          <div class="barra-progresso border border-primary">
            <div class="bg-primary text-primary" :style="`width: ${seller.porcentagem_barra}%;`">.</div>
            <small class="porcentagem">{{ seller.porcentagem_barra }}%</small>
          </div>
          <p class="mt-3 mb-0 text-muted text-center">
            <small>Atualizado Diariamente</small>
          </p>
        </div>
        <div v-else>
          <div class="card-title w-100 text-center text-primary mb-0">
          <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
          <p class="m-0">
            Você já é um dos Melhores Fabricantes.
            <br />
            Continue com o bom trabalho! 😃
          </p>
          </div>
        </div>
      </div>
    </div>
    <div v-if="seller.reputacao !== 'MELHOR_FABRICANTE'" :class="'card border rounded bg-' + corReputacaoSeller">
      <div class="py-1 text-center">
        <span v-if="seller.reputacao === 'INDEFINIDA'">Você ainda não possui reputação</span>
        <span v-cloak v-else>Sua reputação atual é: {{ seller.reputacao }}</span>
      </div>
    </div>
    <div v-if="seller.reputacao === 'MELHOR_FABRICANTE'">
      <v-btn @click="impulsionarProdutos" v-if="tempoParaImpulsionarProdutos <= 0" class="w-100 mb-5">
        Impulsionar produtos
        <span class="ml-1 material-icons">moving</span>
      </v-btn>
      <template v-else>
        <v-btn class="w-100 mb-5" disabled>
          Poderá impulsionar em {{ tempoParaImpulsionarProdutos }} dia(s)
        </v-btn>
      </template>
    </div>
    <div class="pb-1" v-else>
      <p class="text-justify">
        Somente <b class="text-primary">Melhores Fabricantes</b> podem impulsionar seus produtos.
      </p>
    </div>
    <div v-if="produtos && produtos.length == 0" class="text-center py-5">
      Produtos não encontrados
    </div>
    <template v-else>
      <ul class="pl-0 mt-3" style="list-style: none">
        <li class="row mb-3" v-for="produto in produtos">
          <figure class="col-auto p-0 rounded mr-1 overflow-hidden" style="width: 70px; height: 70px;">
            <img class="w-100 h-100 img-fluid" :src="produto.foto" alt="Foto" />
          </figure>
          <div class="col p-0">
            <p class="p-0 m-0 font-weight-bold text-capitalize position-relative overflow-hidden">
              {{ produto.id }}. <span style="white-space: nowrap" class="position-absolute">{{ produto.nome }}</span>
            </p>
            <div class="d-flex flex-wrap">
              <div class="grade-numero" v-for="numero in produto.grade">
                <span class="celula tamanho">{{ numero.nome_tamanho }}</span>
                <span :class="'celula quantidade text-' + corNumero(numero.saldo)" :style="numero.q < 0 && 'color:red;'">{{ numero.saldo }}</span>
              </div>
            </div>
          </div>
          <v-menu class="col-auto" :value="menuProdutoAberto === produto.id">
              <template v-slot:activator="{ on, attrs }">
                <span @click="abrirMenuProduto(produto.id)" class="material-icons mt-3 mr-3">menu</span>
              </template>
              <v-list>
                <v-list-item @click="abrirGradeProduto(produto.grade)">
                  <span class="material-icons">search</span>
                  <v-list-item-title>Detalhes</v-list-item-title>
                </v-list-item>
                <v-list-item class="text-primary" @click="abrirTela(produto)">
                  <span class="material-icons">add</span>
                <v-list-item-title>Repor</v-list-item-title>
                </v-list-item>
                <v-list-item class="text-danger" @click="abrirModalTirarDeLinha(produto.id)">
                  <span class="material-icons">remove_shopping_cart</span>
                  <v-list-item-title>Tirar de linha</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
        </li>
      </ul>
      <v-icon v-if="!ultimaPagina" v-intersect="onIntersect">mdi-dots-horizontal</v-icon>
    </template>
    <!-- Modal Grade -->
    <v-dialog :value="gradeDetalhada !== ''" @click:outside="gradeDetalhada = ''">
      <v-card class="bg-secondary">
        <table class="bg-white">
          <tr>
            <td style="font-size: 0.6rem;" class="col-1 border-bottom border-secondary">Grade</td>
            <td style="font-size: 0.6rem;" class="col-1 border-bottom border-secondary">Estoque</td>
            <td style="font-size: 0.6rem;" class="col-1 border-bottom border-secondary">Estoque Full</td>
            <td style="font-size: 0.6rem;" class="col-1 border-bottom border-secondary">Fila de Espera</td>
            <td style="font-size: 0.6rem;" class="col-1 border-bottom border-secondary">Reposição</td>
            <td style="font-size: 0.6rem;" class="col-1 font-weight-bold border-bottom border-secondary">Saldo</td>
          </tr>
          <tr v-for="numero in gradeDetalhada">
            <td style="font-size: 0.6rem;" class="col-1 font-weight-bold">{{ numero.nome_tamanho }}</td>
            <td style="font-size: 0.6rem;" class="col-1">{{ numero.estoque }}</td>
            <td style="font-size: 0.6rem;" class="col-1">{{ numero.estoque_externo }}</td>
            <td style="font-size: 0.6rem;" class="col-1">{{ numero.fila_espera }}</td>
            <td style="font-size: 0.6rem;" class="col-1">{{ numero.reposicao }}</td>
            <td style="font-size: 0.6rem;" :class="'col-1 font-weight-bold text-' + corNumero(numero.saldo)">{{ numero.saldo }}</td>
          </tr>
        </table>
      </v-card>
    </v-dialog>
    <!-- Modal Tirar de Linha -->
    <v-dialog :value="produtoTirarDeLinha !== ''" persistent>
      <v-card>
        <v-toolbar color="primary" dark>
          <v-toolbar-title>Deseja realmente tirar este produto de linha?</v-toolbar-title>
        </v-toolbar>
        <br />
        <v-card-text>
          <h5>
            Ao tirar um produto de linha, clientes não conseguirão mais fazer reservas e, se houver estoque externo, ele será zerado.
            Mas fique tranquilo, <b>o estoque fulfillment será vendido normalmente!</b>
          </h5>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            class="bg-success text-white"
            @click="tirarProdutoDeLinha(produtoTirarDeLinha)"
            :disabled="carregandoTirarProdutoLinha"
            :loading="carregandoTirarProdutoLinha"
            text
          >
            Sim
          </v-btn>
          <v-btn
            class="bg-danger text-white"
            @click="produtoTirarDeLinha = ''"
            :disabled="carregandoTirarProdutoLinha"
            :loading="carregandoTirarProdutoLinha"
            text
          >
            Não
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </section>

  <!-- Modal lista produtos cancelados -->
  <v-dialog
    persistent
    v-if="modalProdutosCancelados"
    v-model="modalProdutosCancelados"
  >
    <v-card min-height="50rem">
      <v-toolbar dark color="error">
        <v-toolbar-title>ALERTA!</v-toolbar-title>
      </v-toolbar>
      <div class="p-2">
        <p class="text-center">Você não entregou os seguintes produtos dentro do prazo</p>
        <p class="text-center font-weight-bold">Sua reputação foi afetada e seus produtos perderam relevância na plataforma!</p>
        <br />
        <v-data-table
          hide-default-footer
          :headers="headerProdutosCancelados"
          :items="listaProdutosCancelados"
          :items-per-page="-1"
        >
          <template v-slot:item.foto_produto="{ item }">
            <v-img
              class="mx-auto"
              height="5rem"
              width="5rem"
              :aspect-ratio="16/9"
              :src="item.foto_produto"
            />
          </template>

          <template v-slot:item.acao="{ item }">
            <v-btn
              color="primary"
              :loading="carregandoCancelados"
              @click="estouCiente(item)"
            >
              Estou ciente
              <v-icon>mdi-check</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </div>
    </v-card>
  </v-dialog>

  <v-snackbar v-model="mostrarSnackbar" :color="corSnackbar">{{ mensagemSnackBar }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/dashboard-fornecedores.js"></script>
