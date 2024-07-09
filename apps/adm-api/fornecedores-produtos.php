<?php
require_once 'cabecalho.php';

acessoUsuarioFornecedorEInterno();

require_once __DIR__ . '/src/components/InputCategorias.php';
?>
<v-app class="container-fluid fill-height" id="fornecedoresProdutosVUE">

  <input type="hidden" id="id_colaborador" value="<?= $_SESSION['id_cliente'] ?>">
  <input type="hidden" id="id_produto" value="<?= $_GET['id'] ?>">

  <v-data-iterator :items="items" disable-pagination :search="search" hide-default-footer :custom-sort="customSort" no-data-text="Nenhum produto encontrado.">

    <template v-slot:header>
      <v-card dark color="#dc3545" class="mb-1">
        <v-card-text>
          <v-row>

            <v-col cols="12" class="d-flex">
              <v-text-field v-model="link" id="input-codBarras" readonly label="Divulge seus produtos dentro do Mobile através deste link!" @click:append="copiaLink" outlined>

                <template v-slot:append>
                  <v-tooltip top>
                    <template v-slot:activator="{ on, attrs }">
                      <v-icon @click="copiaLink">far fa-copy</v-icon>
                    </template>
                    <span>Copiar</span>
                  </v-tooltip>
                </template>

              </v-text-field>
            </v-col>

            <v-col cols="12" sm="3" v-if="fornecedor.nivelAcesso >= 50">

              <v-autocomplete v-model="fornecedor.idFornecedor" :items="selectFornecedor" :search-input.sync="buscaFornecedor" cache-items hide-no-data hide-details label="Nome do Fornecedor" item-text="nome" item-value="id" outlined dense dark>

              </v-autocomplete>

            </v-col>


            <v-col cols="12" sm="3">
              <v-form @submit.prevent="getAllProdutosFornecedor">
                <v-text-field v-model="search" clearable flat solo-inverted hide-details prepend-inner-icon="mdi-magnify" label="Pesquisa" />
              </v-form>
            </v-col>

            <v-col cols="7" sm="2">
              <v-select v-model="sortBy" flat solo-inverted hide-details :items="keys" prepend-inner-icon="mdi-magnify" label="Ordenar por" item-text="text" item-value="value" />
            </v-col>

            <v-col cols="3" sm="1">
              <v-form>
                <v-checkbox @change="getAllProdutosFornecedor" :input-value="showProductsOff" v-model="showProductsOff" label="Fora de linha" style="margin-top:0; margin-bottom:0;"></v-checkbox>
              </v-form>
            </v-col>

            <v-col cols="5" sm="1" class="d-flex align-center">
              <v-btn-toggle v-model="sortDesc" mandatory rounded dense>
                <v-btn small depressed color="#dc3545" :value="false" dark>
                  <v-icon>mdi-arrow-up</v-icon>
                </v-btn>
                <v-btn small depressed color="#dc3545" :value="true" dark>
                  <v-icon>mdi-arrow-down</v-icon>
                </v-btn>
              </v-btn-toggle>
            </v-col>


            <v-col cols="12" sm="3" class="d-flex justify-end align-center">
              <v-btn color="warning" :loading="loading_cadastrar_produto" @click="modal = true">
                Cadastrar
              </v-btn>
            </v-col>

          </v-row>

        </v-card-text>

      </v-card>

    </template>



    <template v-slot:no-data>
      <div class="fill-height text-center" style="min-height: 300px;">
        <span>Nenhum produto encontrado</span>
      </div>
    </template>


    <template v-slot:default="props">

      <v-alert type="error" prominent class="mt-2" v-show="possuiIncompleto">

        <div class="d-flex flex-column">
          <span>Caro fornecedor, você possuí produtos com cadastro incompleto. Esses produtos podem ser identificados pela borda vermelha e pelo símbolo
            <v-icon right small dark class="align-baseline">
              fas fa-exclamation-circle
            </v-icon>.
          </span>
          <span>Por gentileza, regularize todos os seus produtos.</span>
        </div>

      </v-alert>

      <div v-if="loading" class="w-100 d-flex align-items-center justify-content-center">
        <v-progress-circular :size="100" indeterminate></v-progress-circular>
      </div>
      <v-row v-if="!loading">
        <v-col v-for="item in props.items" :key="item.id" cols="12" md="6" md="4" lg="3">
          <v-card raised :class="`fill-height ${item.bloqueado ? 'opacidade-baixa ' : ''} ${item.incompleto ? 'incompleto' : ''}`">

            <div>
              <v-btn
                left
                fab
                absolute
                small
                :disabled="produtoSendoDeletado !== null"
                :loading="produtoSendoDeletado === item.id"
                @click="deletaProduto(item.id)"
            >
                <v-icon dark color="error">
                  mdi-delete
                </v-icon>
              </v-btn>

              <v-btn fab absolute small @click="editarProduto(item)">
                <v-icon dark color="error" v-if="item.incompleto">
                  fas fa-exclamation-circle
                </v-icon>
                <v-icon dark color="error" v-else>
                  mdi-pencil
                </v-icon>
              </v-btn>
            </div>
            <v-img class="white--text align-end" height="250" :src="item.fotos.length > 0 ? item.fotos.reduce((total, item) => item.sequencia < total.sequencia ? item : total, item.fotos[0]).foto_preview : getImgPlaceholder()" gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.2)" contain>

            </v-img>
            <v-card-text>
              <v-list dense>
                <v-list-item-group v-model="listItem" color="error">
                  <v-list-item v-for="(chave, index) in filteredKeys" :id="item.id" :key="index" class="li-border-bottom">

                    <v-list-item-content style="flex-wrap: nowrap">
                      <v-list-item-title :style="chave.value == 'rating' ? 'flex: 0.5' : ''" :class="chave.value == 'rating' ? 'text-decoration-underline' : ''" class="text-caption">

                        <b>{{ chave.text | upperCase }}:</b>
                      </v-list-item-title>
                      <v-list-item-subtitle v-if="chave.value === 'cores'" class="text-center overflow-auto">
                        <v-chip class="m-1" v-for="(cor, key) in item[chave.value]" :key="key">{{ cor }}</v-chip>
                      </v-list-item-subtitle>
                      <v-list-item-subtitle v-else-if="chave.value === 'tipo_grade'" class="text-center">
                        {{ tipos_grades.find(tipo => tipo.id == item[chave.value])?.nome }}
                      </v-list-item-subtitle>
                      <v-list-item-subtitle v-else-if="chave.value === 'array_id_categoria_formatado'" class="text-center overflow-auto">
                        <v-chip class="m-1" v-for="(id, key) in item[chave.value]" :key="key">{{ recursiveFind(backupCategorias, id)?.nome || recursiveFind(backupTipos, id)?.nome }}</v-chip>
                      </v-list-item-subtitle>
                      <v-list-item-subtitle v-else-if="chave.value === 'array_id_tipo'" class="text-center overflow-auto">
                        <v-chip class="m-1" v-for="(id, key) in item[chave.value]" :key="key">{{ recursiveFind(backupTipos, id)?.nome || recursiveFind(backupCategorias, id)?.nome }}</v-chip>
                      </v-list-item-subtitle>
                      <v-list-item-subtitle v-else class="text-center text-caption" :style="chave.value == 'rating' ? 'flex: 1' : ''">
                        <v-rating :value="parseFloat(item[chave.value.toLowerCase()])" color="yellow darken-3" background-color="grey darken-1" empty-icon="$ratingFull" half-increments small readonly length="5" v-if="chave.value == 'rating'" dense>
                        </v-rating>
                        {{ item[chave.value.toLowerCase()] ? item[chave.value.toLowerCase()] : '-'}}
                      </v-list-item-subtitle>
                    </v-list-item-content>
                  </v-list-item>
                </v-list-item-group>
              </v-list>
              <div class="text-center">
                <v-btn @click="editarProduto(item)" v-if="item.incompleto" small color="error">Regularizar</v-btn>
                <div v-else>
                  <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn outlined text v-bind="attrs" v-on="on" @click="adicionarNovoProdutoAPartirDesse(item)">
                        Criar a partir desse
                        <v-icon class="ml-2">
                          mdi-expand-all
                        </v-icon>
                      </v-btn>
                    </template>
                    <span>Aqui você cria um novo produto usando as informações deste.</span>
                  </v-tooltip>
                </div>
              </div>
            </v-card-text>
            <v-spacer />
          </v-card>
        </v-col>
      </v-row>
    </template>

    <v-spacer></v-spacer>
    <template v-slot:footer>
      <div class="text-center pb-5">
        <v-container>
          <v-row class="align-items-center">
            <v-col cols="9">
              <v-pagination v-model="page" class="mr-4" :length="numberOfPages"></v-pagination>
            </v-col>
            <v-col :cols="3">
              <v-select label="Itens por página" :value="itemsPerPage" @input="updateItemsPerPage" :items="[5, 10, 15, 20, 'Tudo']"></v-select>
            </v-col>
          </v-row>
        </v-container>
      </div>
    </template>
  </v-data-iterator>

  <!-- MODAL CADASTRO/EDIÇÃO PRODUTOS -->
  <v-dialog
    fullscreen
    hide-overlay
    persistent
    scrollable
    style="z-index: 2000; margin: 0 !important"
    transition="dialog-bottom-transition"
    v-model="modal"
  >
    <v-card :loading="loadingSalvandoProduto">
      <v-card-title style="padding: 0;">
        <v-toolbar dark :loading="loadingSalvandoProduto">
          <v-toolbar-title>
            {{ formulario.id ? 'Editar Produto - ' + formulario.id : 'Cadastro de Produto'}}
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-toolbar-items>
            <v-btn dark icon @click="limpaModalProdutos" tile>
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>
      </v-card-title>
      <v-card-text style="padding-bottom: 0px;">
        <v-form id="formulario" enctype="multipart/form-data" ref="form" v-model="valid" lazy-validation>
          <v-row>
            <v-col cols="12" md="3">
              <v-card-title primary-title class="px-0">
                <b>Dados do Produto</b>
              </v-card-title>
              <v-row no-gutters>
                <v-col cols="12">
                  <!-- Referencia -->
                  <v-text-field
                    clearable
                    counter
                    persistent-hint
                    spellcheck
                    hint="Ex: Sapatilha Mule Bico Fino SQUARE"
                    label="Nome Comercial"
                    maxlength="100"
                    :disabled="loadingSalvandoProduto"
                    :rules="[rules.required, rules.counter]"
                    v-model="formulario.nome_comercial"
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <!-- Referencia -->
                  <v-text-field v-model="formulario.descricao" :disabled="loadingSalvandoProduto" :rules="[rules.required, rules.counter]" label="Referência" counter maxlength="15" clearable hint="Ex: A1000" persistent-hint @keyup="removeCaracteresEspeciaisReferencia(formulario.descricao)"></v-text-field>
                </v-col>
                <!-- Cores -->
                <v-col cols="12">
                  <v-autocomplete :loading="loadingSalvandoProduto" :disabled="loadingSalvandoProduto" v-model="formulario.cores" :rules="[(v) => v.length > 0 || 'Preencha as cores']" auto-select-first chips deletable-chips multiple small-chips hide-details label="Cor" :items="cores" item-value="nome" item-text="nome" :search-input.sync="valorCorDescrita" @input="valorCorDescrita = ''" clearable>
                </v-col>
                <!-- Categoria -->
                <v-col cols="12">
                  <v-autocomplete :loading="loadingSalvandoProduto" :disabled="loadingSalvandoProduto" v-model="formulario.array_id_categoria_formatado[0]" auto-select-first chips deletable-chips small-chips hide-details label="Categorias" :items="categorias" item-value="id" item-text="nome" @input="filtering" clearable hide-no-data open-on-clear :rules="[rules.required, rules.counter]"></v-autocomplete>
                </v-col>
                <!-- Tipo -->
                <v-col cols="12">
                  <v-autocomplete :loading="loadingSalvandoProduto" :disabled="loadingSalvandoProduto" v-model="formulario.array_id_tipo[0]" auto-select-first chips deletable-chips small-chips hide-details label="Tipos" :items="tipos" item-value="id" item-text="nome" @input="selectTypes" clearable hide-no-data open-on-clear :rules="[rules.required, rules.counter]"></v-autocomplete>
                </v-col>
                <v-col cols="12">
                  <v-row no-gutters>
                    <v-col cols="6" class="p-0">
                      <!-- Linha -->
                      <v-select hide-details :disabled="loadingSalvandoProduto" :loading="loadingSalvandoProduto" :items="linhas" v-model="formulario.id_linha" :rules="[() => formulario.id_linha > 0 || 'Selecione a linha']" label="Linha" item-text="nome" item-value="id" item-key="id"></v-select>
                    </v-col>
                    <v-col cols="6" class="p-0">
                      <!-- Sexo -->
                      <v-select hide-details :disabled="loadingSalvandoProduto" :loading="loadingSalvandoProduto" label="Sexo" item-value="valor" item-text="nome" :rules="[rules.required]" :items="[{nome: 'Masculino', valor: 'MA'},{nome: 'Feminino', valor: 'FE'},{nome: 'Unissex', valor: 'UN'}]" v-model="formulario.sexo"></v-select>
                    </v-col>
                  </v-row>
                </v-col>
                <v-col cols="12">
                  <v-switch :loading="loadingSalvandoProduto" v-model="formulario.fora_de_linha" inset>
                    <template v-slot:label>
                      Fora de linha (somente se não existir reposição)
                    </template>
                  </v-switch>
                </v-col>
                <v-col cols="12">
                  <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn
                        block
                        dark
                        style="text-decoration: none;"
                        @click="redirecionaTutorial()"
                        v-bind="attrs"
                        v-on="on"
                      >
                        <v-icon>mdi-youtube</v-icon>
                        &ensp;
                        Como Cadastrar o Produto
                      </v-btn>
                    </template>
                    <span>Cadastrar os produtos corretamente pode alavancar suas vendas</span>
                  </v-tooltip>
                </v-col>
              </v-row>
              <v-row no-gutters>
              </v-row>
            </v-col>
            <v-divider vertical></v-divider>
            <v-col cols="12" md="3">
              <v-card-title primary-title class="px-0">
                <b>Preços</b>
              </v-card-title>

              <v-text-field
                clearable
                persistent-hint
                hint="Digite o custo do seu produto."
                label="Custo"
                step="any"
                type="number"
                :rules="[rules.required, rules.valorMin]"
                @input="calculaValor()"
                v-model="formulario.valor_custo_produto"
            ></v-text-field>
              <p class="font-weight-light my-4 mb-0">
                Este será o preço em que o produto será vendido no Mobile Stock:
              </p>
              <v-col cols="12" class="mx-0">
                <p class="text--primary mb-0">
                  Valor venda +{{this.porcentagemMS.valor_ida}}%
                </p>
                <v-text-field :loading="loadingSalvandoProduto" v-model="formulario.valor_venda_ms" solo readonly></v-text-field>
              </v-col>

              <p class="font-weight-light my-4 mb-0">
                Este será o preço em que o produto será vendido no Meu Look:
              </p>
              <v-col cols="12" class="mx-0">
                <p class="text--primary mb-0">
                  Valor venda +{{this.porcentagemML.valor_ida}}%
                </p>
                <v-text-field
                  readonly
                  solo
                  :loading="loadingSalvandoProduto"
                  v-model="formulario.valor_venda_ml"
                ></v-text-field>
              </v-col>

            </v-col>
            <v-divider vertical></v-divider>
            <v-col cols="12" md="6">

              <v-row no-gutters>
                <v-card-title primary-title class="w-100 px-0 position-relative">
                  <b>Grade</b>

                  <v-btn slot="append-outer" icon x-small color="primary" @click="visualizarFoto(detalhesPalmilha)" class="pl-2">
                    <v-icon>mdi-open-in-new</v-icon>
                  </v-btn>

                  <v-spacer></v-spacer>

                  <v-col cols="5" class="position-absolute" style="right: 0">
                    <v-select hide-details item-value="id" item-text="nome" :items="tipos_grades" v-model="formulario.tipo_grade" :disabled="formulario.id !== undefined" label="Tipo grade"></v-select>
                  </v-col>
                </v-card-title>
                <v-row no-gutters>


                  <div v-for=" (item, index) in grades">
                    <v-col cols="6" sm="auto" :key="index" class="m-1 p-0">
                      <div class="w-100 d-flex align-items-center justify-content-center">
                        <div class="p-0 m-0" style="width: 180px;">
                          <div class="w-100 d-flex justify-content-between position-relative">
                            <span></span>
                            <v-text-field dense fill-height filled hide-details class="text-center" label="Numeração" type="number" :disabled="item.esta_desabilitado" :readonly="item.esta_desabilitado" :rules="[(v) => !!v || 'Preencha a númeração', v => v <= 56 || 'Tamanho máximo é 56']" v-model="item.nome_tamanho" v-if="formulario.tipo_grade == 1"></v-text-field>
                            <v-text-field dense fill-height filled hide-details readonly class="w-100 text-center text-white" :label="formulario.tipo_grade != 3 ? 'Tamanho' : 'Tamanhos'" :disabled="formulario.tipo_grade != 3 || item.esta_desabilitado" :readonly="formulario.tipo_grade != 3 || item.esta_desabilitado" :rules="[(v) => !!v || 'Preencha esse campo']" v-model="item.nome_tamanho" v-else></v-text-field>

                            <v-btn v-if="gradeEhEditavel && !item.esta_desabilitado" @click="grades.splice(grades.indexOf(grades.filter(el => el.sequencia == item.sequencia)[0]), 1)" small absolute icon>
                              <v-icon>
                                mdi-close
                              </v-icon>
                            </v-btn>
                          </div>
                        </div>
                        <div class="ml-2" v-if="(index + 1) === grades.length && gradeEhEditavel">
                          <v-btn icon @click="adicionaNovoTamanhoGradeAhPartirDeOutro(item)" ref="botaoAddNovaGrade">
                            <v-icon>mdi-plus</v-icon>
                          </v-btn>
                        </div>
                      </div>
                    </v-col>
                  </div>
                  <div class="ml-2" v-if="0 === grades.length && gradeEhEditavel">
                    <span class="text-danger">
                      Preencha pelo menos uma grade
                    </span>
                    <v-btn icon @click="adicionaNovoTamanhoGrade" ref="botaoAddNovaGrade">
                      <v-icon>mdi-plus</v-icon>
                    </v-btn>
                  </div>
                </v-row>
              </v-row>
              <small v-if="formulario.tipo_grade == 3">Exemplo de grade conjugada: 36/37</small>
              <br />
              <div class="select-embalagem-forma" v-if="gradeEhEditavel">
                <v-select
                  persistent-hint
                  hint="Indicará se o cliente deve comprar um número maior ou menor que o comum"
                  label="Forma do calçado"
                  :disabled="loadingSalvandoProduto"
                  :items="formas"
                  :loading="loadingSalvandoProduto"
                  v-model="formulario.forma"
                ></v-select>
                <v-select
                  persistent-hint
                  prepend-inner-icon="mdi-package-variant"
                  hint="Indicará a embalagem que o produto será entregue ao cliente"
                  label="Embalagem do produto"
                  :disabled="loadingSalvandoProduto"
                  :items="embalagens"
                  :loading="loadingSalvandoProduto"
                  v-model="formulario.embalagem"
                  :rules="[rules.required, rules.counter]"
                ></v-select>
              </div>
              <v-divider></v-divider>
              <v-row>
                <v-col cols="12">
                  <v-textarea auto-grow counter filled :loading="loadingSalvandoProduto" :disabled="loadingSalvandoProduto" v-model="formulario.outras_informacoes" persistent-hint label="Descrição do produto" :rows="fornecedor.nivelAcesso >= 50 ? 5 : 1" hint="*Campo não obrigatório"></v-textarea>
                </v-col>
                <v-col cols="12">
                  <h5 class="font-weight-bold">Nesse campo você pode falar com o cliente</h5>
                  <p>Coloque medidas como:</p>
                  <ul>
                    <li>Tamanho da palmilha</li>
                    <li>Medida do cinto</li>
                    <li>Tamanho da bolsa</li>
                    <li>Material do cabedal</li>
                    <li>Material do solado</li>
                    <li>Tamanho da forma (pequena, grande ou normal)</li>
                  </ul>

                  <b>ESSAS INFORMARÇÕES TE AJUDAM A VENDER MAIS</b>
                </v-col>
              </v-row>
              <v-divider></v-divider>
              <v-row>

                <v-card-title class="font-weight-bold">
                  Fotos
                  <v-subheader>
                    Se você já tem fotos legais do produto coloque-a aqui, vamos mostra-las para quem clicar no produto
                  </v-subheader>
                </v-card-title>
                <v-row no-gutters style="width: -webkit-fill-available;">

                  <v-col cols="6" md="3" class="p-2" v-for="(foto, i) in formulario.fotos" :key="i">
                    <v-card :loading="loadingRemovendoFoto == foto.sequencia" class="w-100 h-100">
                      <v-img class="white--text align-end" height="250" :src="foto.foto_preview ? foto.foto_preview : getImgPlaceholder()" gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.2)" contain>
                        <div style="position: absolute; top: 0;" class="d-flex justify-content-between align-items-center w-100">
                          <span>
                            <v-btn icon target="_blank" :href="foto.foto_preview">
                              <v-icon>
                                mdi-open-in-new
                              </v-icon>
                            </v-btn>
                          </span>
                          <v-btn v-if="fotoEhDeletavel(foto.id_usuario)" icon @click="deletaFotoProduto(foto)">
                            <v-icon>mdi-close</v-icon>
                          </v-btn>
                        </div>
                      </v-img>
                      <v-card-title>
                        <v-select
                            label="Tipo de foto"
                            :disabled="typeof foto.caminho === 'string' || fornecedor.nivelAcesso == 30"
                            :items="[{text: 'Calçada', value: 'LG'}, {text: 'Catálogo', value: 'MD'}]"
                            :readonly="typeof foto.caminho === 'string' || fornecedor.nivelAcesso == 30"
                            v-model="foto.tipo_foto"
                            @change="calculaListaFotos"
                        ></v-select>
                      </v-card-title>
                      <v-card-subtitle>Tamanho da foto: {{ {'SM': 'Pequena', 'MD': 'Média', 'LG': 'Grande'}[foto.tipo_foto] }}</v-card-subtitle>
                    </v-card>
                  </v-col>
                  <div class="d-flex align-items-center justify-content-center">
                    <v-btn icon @click="abreSeletorImagem">
                      <v-icon>
                        mdi-plus
                      </v-icon>
                    </v-btn>
                    <input ref="inputFotoAdd" @change="insereFotoProduto" class="d-none" type="file" id="foto-add" multiple />
                  </div>

                </v-row>
              </v-row>

              <v-divider></v-divider>

              <v-card-title class="font-weight-bold">
                  Vídeos
                  <v-subheader>
                    Coloque o link do vídeo do produto, ele será exibido na página do produto
                  </v-subheader>
              </v-card-title>
              <div v-for=" (item, index) in formulario.videos">
                <span class="d-flex align-items-center">
                    <v-card class="d-flex align-items-center w-100 mb-5">
                        <img :src="'http://img.youtube.com/vi/' + item.id_youtube + '/maxresdefault.jpg'" class="w-25 m-2">
                        <v-card-title>{{ item.titulo }}</v-card-title>
                    </v-card>
                    <v-btn icon @click="deletaVideoProduto(index)" class="mb-5">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </span>
              </div>
              <v-text-field
                    solo
                    placeholder="Ex: https://www.youtube.com/watch?v=..."
                    v-model="videoUrl"
                    :loading="loadingVideo"
              ></v-text-field>
              <div class="d-flex justify-content-center">
                <v-btn icon class="w-100 rounded" @click="adicionaVideo(videoUrl)">
                    <v-icon>
                        mdi-plus
                    </v-icon>
                </v-btn>
              </div>
            </v-col>
          </v-row>
          </v-row>

          <v-divider></v-divider>
          <v-row v-if="fornecedor.nivelAcesso >= 50">
            <v-card-title class="font-weight-bold">
              Configurações
            </v-card-title>
            <v-col cols="12">
              <v-switch :loading="loadingSalvandoProduto" v-model="formulario.bloqueado" inset :label="`Bloqueado`"></v-switch>

              <v-btn dark :color="formulario.permitido_reposicao ? 'error' : 'light-green darken-2'" @click="openModalPermissao = true">
                <template v-if="formulario.permitido_reposicao">
                  <v-icon>mdi-close-circle</v-icon> &ensp; Proibir reposição no Mobile
                </template>
                <template v-else >
                  <v-icon>mdi-checkbox-marked-circle</v-icon> &ensp; Permitir reposição no Mobile
                </template>
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
            color="var(--cor-secundaria-meulook)"
            :dark="valid"
            :disabled="!valid"
            :loading="loadingSalvandoProduto"
            @click="salvar"
        >Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- MODAL CONFIRMAR PERMISSÃO -->
  <v-dialog persistent v-model="openModalPermissao" width="300">
    <v-card>
      <v-toolbar dark dense color="error">
        <v-toolbar-title>Atenção</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn dark icon @click="openModalPermissao = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <h6>
        <v-card-text>
          Você tem certeza de que deseja
          <template v-if="formulario.permitido_reposicao">
              <b style="color: #689f38;"> proibir </b>
          </template>
          <template v-else>
              <b style="color: #7cb342;"> permitir </b>
          </template>
          a reposição deste produto no <b>Mobile Stock</b>?
        </v-card-text>
      </h6>
      <v-card-actions>
        <v-btn @click="atualizaPermissao()">Confirmar</v-btn>
        <v-btn dark @click="openModalPermissao = false">Cancelar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- MODAL FOTO -->
  <v-dialog v-model="modalFoto.open" max-width="500" @click:outside="limpaModalDetalhes" scrollable style="z-index: 4000">
    <v-card v-if="!modalAvaliacao">
      <v-toolbar dark color="error">
        <v-toolbar-title>{{ modalFoto.cardTitle }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn dark icon @click="limpaModalDetalhes">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>

      <v-card-text>
        <v-subheader v-for="mensagem in modalFoto.cardText">{{mensagem}}</v-subheader>
        <v-card outlined class="text-center">
          <img contain :src="modalFoto.cardPath ? modalFoto.cardPath : 'images/img-placeholder.png'" style="max-width: 400px;">
        </v-card>
      </v-card-text>
    </v-card>
    <v-card v-else>
      <v-toolbar dark color="error" class="mb-2">
        <v-toolbar-title>{{ modalFoto.cardTitle }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn dark icon @click="limpaModalDetalhes">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
    </v-card>
  </v-dialog>

  <v-dialog eager v-model="abrirModalAddFoto" transition="scroll-y-transition" class="dialogImagens" persistent>
    <v-toolbar>
      Cortar
      <v-tabs align-with-title color="var(--cor-secundaria-meulook)" v-model="fotoAtivaModalAddFoto">
        <v-tabs-slider></v-tabs-slider>

        <v-tab v-for="(fotoPendente, key) in formulario.listaFotosPendentes" :key="key" :title="fotoPendente.file.name" class="d-none">
          <small class="texto-limitado m-0 p-0">{{ fotoPendente.file.name }}</small>
          <v-btn @click="removeFotoRedimensionamento(key)" plain class="m-0 p-0" icon small>
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-tab>
      </v-tabs>
      <v-spacer></v-spacer>
    </v-toolbar>
    <v-card tile eager class="cardFoto">
      <v-window eager :value="fotoAtivaModalAddFoto" touchless>
        <v-window-item class="cropWindow" eager v-for="(fotoPendente, key) in formulario.listaFotosPendentes" :key="key">
          <v-row align="center" justify="center">
            <div class="cropper">
              <img :id="'image' + key" :src="fotoPendente.foto_preview" class="img-fluid"/>
            </div>
          </v-row>
        </v-window-item>
      </v-window>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
          dark
          large
          color="var(--cor-secundaria-meulook)"
          elevation="2"
          @click="croppImagemPendente()"
        >
          Salvar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
  <!-- OVERLAY -->
  <template>
    <div class="text-center">
      <v-overlay :value="overlay">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
      </v-overlay>
    </div>
  </template>
  <!-- Notificações -->
  <template>
    <v-snackbar
      multi-line
      style="z-index: 5200"
      timeout="3000"
      :color="snackbar.color"
      :top="snackbar.top"
      v-model="snackbar.open"
    >
      {{ snackbar.text }}

      <template v-slot:action="{ attrs }">
        <v-btn
          icon
          tile
          color="white"
          v-bind="attrs"
          v-if="snackbar.button === 'FECHA_AVISO'"
          @click="snackbar.open = false"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>

        <v-btn
          outlined
          color="white"
          v-bind="attrs"
          v-else-if="snackbar.button === 'LIMPA_ESTOQUE'"
          @click="salvarProduto()"
        >
          Eu aceito
        </v-btn>
      </template>
    </v-snackbar>
  </template>
</v-app>

<style lang="scss" scoped>
  .v-btn--absolute {
    right: 2%;
    top: 1%;
  }

  .li-border-bottom {
    border-bottom: solid;
    border-width: thin;
    border-color: rgba(0, 0, 0, 0.12);
  }

  #canvas_embalagem {
    flex: 1;
    border: 1px solid gray;
  }

  .incompleto {
    border: solid;
    padding: 2px;
    border-color: #FF1744 !important;

  }

  .superior {
    z-index: 600;
  }

  .opacidade-baixa {
    opacity: .5;
  }

  .texto-limitado {
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    max-width: 150px;
  }
  .cropper {
    display: block;
    max-width: 100%;
  }
  .dialogImagens {
    max-width: 90vw;
  }
  .cropWindow {
    padding: 2rem;
  }
  .v-dialog--active {
    max-height: 100% !important;
  }
  .select-embalagem-forma {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
  }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/fornecedores-produtos.js<?= $versao ?>"></script>
<script src="js/tools/removeAcentos.js"></script>
