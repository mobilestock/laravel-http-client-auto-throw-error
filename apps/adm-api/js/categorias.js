let categoriasVue = new Vue({
  el: "#categoriasVue",
  vuetify: new Vuetify(),
  data() {
    return {
      dialog: false,
      dialogCategoria: false,
      nextId: 1,
      open: ["public"],
      tree: [],
      open: [1, 2],
      search: null,
      listaCategorias: [],
      listaTags: [],
      dialog: false,
      dialogEditarCategoria: false,
      documentosProcessos: [],
      imageData: "",
      iconeName: "",
      editedItem: {},
      loadingCategorias: false,
      categoriaAtiva: {
        id: 0,
        nome: "",
        tags: [],
        id_categoria_pai: 0,
        fotoUpload: null,
      },
      snackbar: {
        mostrar: false,
        cor: "primary",
        texto: "",
      },
      textoTags: "",
      tabAtual: 0,
      tabs: ["Categorias", "Tags"],
      materiais: [],
      materiaisHeaders: [
        {
          text: "ID",
          align: "center",
          value: "id_tag",
        },
        {
          text: "Nome",
          align: "center",
          value: "nome",
        },
        {
          text: "",
          value: "deletar",
        },
      ],
      cores: [],
      tagAtiva: {
        id_tag: 0,
        nome: "",
        tipo: "",
      },
      abrirModalTags: false,
    };
  },

  methods: {
    goBack() {
      history.back();
    },

    previewImage: function (event) {
      var input = event.target;
      if (input.files && input.files[0]) {
        this.iconeName = input.files[0].nome;
        var reader = new FileReader();
        reader.onload = (e) => {
          this.imageData = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
      }
    },

    close() {
      this.dialog = false;
      this.dialogCategoria = false;
      setTimeout(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      }, 300);
      this.iconeName = [];
      this.categoriaAtiva.nome = "";
    },

    async salvaCategoria(id_categoria_pai = 0) {
      this.loadingCategorias = true;

      let form = new FormData();
      form.append("id", this.categoriaAtiva.id);
      form.append("nome", this.categoriaAtiva.nome);
      form.append("tags", JSON.stringify(this.categoriaAtiva.tags));
      form.append("id_categoria_pai", id_categoria_pai);
      form.append("foto", this.categoriaAtiva.fotoUpload);

      let json = await MobileStockApi("api_administracao/categorias", {
        method: "POST",
        body: form,
      }).then((r) => r.json());
      this.loadingCategorias = false;

      if (!json.status) {
        $.alert(json.message);
        return;
      }
      await this.buscaCategorias();
      this.snackbar = {
        mostrar: true,
        cor: "primary",
        texto: json.message,
      };
      this.dialogCategoria = false;
      this.categoriaAtiva.nome = "";
    },

    editCategory(item) {},

    addChildFolder() {
      if (!this.editedItem.children) {
        this.$set(this.editedItem, "children", []);
      }

      const nome = this.categoriaAtiva.nome;
      const id = this.nextId++;
      const idPai = this.editedItem.id;
      this.editedItem.children.push({
        id,
        nome,
        icone: this.imageData,
        tags: [],
        idPai,
      });
      this.dialog = false;
    },

    delFile(item, items) {
      // debugger;
      for (let i = 0; i < items.length; i++) {
        if (items[i].id == item.id) {
          return this.removeItem(i);
        } else {
          if (items[i].children) {
            if (items[i].idPai == null) {
              this.caminho = [];
            }
            this.caminho.push(i);
            this.delFile(item, items[i].children);
          }
          if (items.length == 1 + i) {
            this.caminho.pop();
          }
        }
      }
    },

    removeItem(oCaraQueVaiSerRemovido) {
      let comandoCompleto = "";
      if (this.caminho?.length > 0) {
        for (let i = 0; i < this.caminho.length; i++) {
          if (i == 0) {
            comandoCompleto = "this.listaCategorias[" + this.caminho[i] + "]";
          } else {
            comandoCompleto =
              comandoCompleto + ".children[" + this.caminho[i] + "]";
          }
        }
        comandoCompleto = `${comandoCompleto}.children.splice(${oCaraQueVaiSerRemovido}, 1)`;
      } else {
        comandoCompleto = `this.listaCategorias.splice(${oCaraQueVaiSerRemovido}, 1)`;
      }
      var comando = eval(comandoCompleto);
    },

    async buscaCategorias() {
      this.loadingCategorias = true;
      let json = await MobileStockApi(
        "api_administracao/categorias"
      ).then((r) => r.json());

      this.listaCategorias = json.data.categorias;
      this.listaTags = json.data.tags;
      this.loadingCategorias = false;
    },

    async salvaCategorias() {
      this.loadingCategorias = true;
      let json = await MobileStockApi("api_administracao/categorias", {
        method: "POST",
        body: JSON.stringify({
          lista_categorias: this.listaCategorias,
        }),
      }).then((r) => r.json());

      this.loadingCategorias = false;
      if (!json.status) {
        $.dialog(json.message);
        return;
      }

      $.dialog({ title: "sucesso!", content: json.message });
    },

    addCategoriaFilho(item) {
      this.dialogCategoria = true;
      this.categoriaAtiva.id_categoria_pai = parseInt(item.id);
    },

    buscaTags() {
      MobileStockApi("api_administracao/tags/tipos")
        .then((r) => r.json())
        .then((json) => {
          this.materiais = json.data.materiais;
          this.cores = json.data.cores;
        });
    },

    criarNovaLinha() {
      this.tagAtiva = {
        id_tag: 0,
        nome: "",
        tipo: "",
      };

      if (this.tabAtual === 0) this.dialogCategoria = true;
      else {
        this.abrirModalTags = true;
        this.tagAtiva.tipo = this.tabAtual === 1 ? "MA" : "CO";
      }
    },

    async salvaTag() {
      this.loadingCategorias = true;
      let json = await MobileStockApi("api_administracao/tags", {
        method: "POST",
        body: JSON.stringify(this.tagAtiva),
      }).then((r) => r.json());

      this.snackbar = {
        mostrar: true,
        cor: !json.status ? "error" : "primary",
        texto: json.message,
      };

      this.buscaTags();

      this.abrirModalTags = false;
      this.loadingCategorias = false;
    },

    async removeCategoria(item) {
      this.loadingCategorias = true;

      let json = await MobileStockApi(
        "api_administracao/categorias/" + item.id,
        {
          method: "DELETE",
        }
      ).then((r) => r.json());

      this.snackbar = {
        mostrar: true,
        cor: json.status ? "primary" : "error",
        texto: json.message,
      };
      this.buscaCategorias();
      this.loadingCategorias = false;
    },

    removeTag(item) {
      if (!confirm("Deseja realmente remover essa tag?")) return;

      this.loadingCategorias = true;
      MobileStockApi("api_administracao/tags/tipos/" + item.id_tag, {
        method: "DELETE",
      }).then((r) => {
        this.buscaTags();
        this.loadingCategorias = false;
      });
    },
  },

  computed: {
    fotoPreview() {
      return this.categoriaAtiva.fotoUpload
        ? URL.createObjectURL(this.categoriaAtiva.fotoUpload)
        : null;
    },
  },

  watch: {
    dialog(val) {
      val || this.close();
    },

    dialogCategoria(newV) {
      if (newV === false) {
        this.categoriaAtiva = {
          id: 0,
          nome: "",
          tags: [],
          fotoUpload: null,
          id_categoria_pai: 0,
        };
      }
    },
  },

  mounted() {
    this.buscaCategorias();
    this.buscaTags();
  },
});
