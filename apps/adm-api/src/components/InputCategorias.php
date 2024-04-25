<script src="js/recursiveFindMixin.js"></script>
<script type="text/x-template" id="input-categorias">
    <div>
        <v-menu v-model="menu" :close-on-content-click="false" bottom origin="center center" transition="slide-x-transition">
            <template v-slot:activator="{ on, attrs }">
                <v-select hide-details :rules="[() => value !== undefined && (!value || (value.length > 0 && value.length <= 2)) || 'Selecione as entrada']" multiple chips deletable-chips small-chips :value="value" :items="value" v-bind="attrs" v-on="on" :label="nome" :menu-props="{ maxHeight: 0 }">
                    <template #selection="{ item }">
                        <v-chip close @click:close="atualiza(value.filter(el => el !== item))">{{ recursiveFind(amostra, item)?.nome || item }}</v-chip>
                    </template>
                </v-select>
            </template>

            <div v-if="entrada[0] !== undefined">
                <v-card class="mx-auto">
                    <v-card-text>
                        <div v-if="entrada[0]['id_categoria_pai'] == null">
                            <!-- Input categorias na adição de produtos (seller) -->
                            <v-text-field autofocus ref="autocomplete" v-model="categoriaAutocomplete" :label="descricao" v-on:keyup.enter='onEnter' clearable></v-text-field>
                            <div style="max-height: 450px; overflow: auto" v-if="entrada[0]['children'] === null">
                                <v-treeview :id="nome" :value="value" @input="atualizaCategoria" :search="categoriaAutocomplete" indeterminate-icon="$checkboxOn" :items="entrada" open-on-click selected-color="primary" transition hoverable dense item-text="nome" selection-type="independent"  item-value="id" selectable></v-treeview>
                            </div>

                            <!-- Input categorias na tela de trocas -->
                            <div style="max-height: 450px; overflow: auto" v-else-if="value && entrada[0]['children'] !== null">
                                <v-treeview :value="value" @input="atualiza" :search="categoriaAutocomplete" indeterminate-icon="$checkboxOn" :items="entrada" open-on-click selected-color="primary" transition hoverable dense item-text="nome" selection-type="independent"  item-value="id" selectable></v-treeview>
                            </div>
                        </div>

                        <!-- Input tipos na adição de produtos (seller) -->
                        <div v-else>
                            <v-text-field autofocus ref="autocomplete" v-model="categoriaAutocomplete" :label="descricao" v-on:keyup.enter='onEnter' clearable></v-text-field>
                            <div style="max-height: 450px; overflow: auto" v-if="entrada[0]['id_categoria_pai']">
                                <v-treeview :id="nome" :value="value" @input="atualizaCategoria" :search="categoriaAutocomplete" indeterminate-icon="$checkboxOn" :items="entrada" open-on-click selected-color="primary" transition hoverable dense item-text="nome" selection-type="independent"  item-value="id" selectable></v-treeview>
                            </div>
                        </div>

                    </v-card-text>
                </v-card>
            </div>
        </v-menu>
    </div>
</script>

<script>
    Vue.component("input-categorias", {
        name: 'input-categorias',
        template: "#input-categorias",
        mixins: [recursiveFindMixin],
        props: {
            entrada: {
                required: true,
                type: Array,
            },
            amostra: {
                required: true,
                type: Array,
            },
            value: {
                required: true,
            },
            nome: {
                required: false,
                type: String,
            },
            descricao: {
                required: false,
                type: String,
            }
        },
        data() {
            return {
                menu: false,
                categoriaAutocomplete: '',
            }
        },
        methods: {
            onEnter: function() {
                document.querySelector('#' + this.$props.nome).querySelector('button').click()
            },
            atualizaCategoria(v) {
                let deveNotificar = false;
                if (v.length === 2) {
                    v.shift();
                    let deveNotificar = true;
                }
                if (v.length > 1) {
                    v = [(v.pop()), (v.pop())];
                    let deveNotificar = true;
                }
                this.$emit('input', v);
            },
            atualiza(v) {
                let deveNotificar = false;
                if (v.length === 3) {
                    v.shift();
                    let deveNotificar = true;
                }
                if (v.length > 2) {
                    v = [(v.pop()), (v.pop())];
                    let deveNotificar = true;
                }
                this.$emit('input', v);
            },
        },
    });
</script>